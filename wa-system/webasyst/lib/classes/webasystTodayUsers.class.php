<?php

/**
 * Class webasystTodayUsers
 *
 * Class for getting users that has something going on today (birthdays or some status in calendar)
 */
class webasystTodayUsers
{
    private static $static_cache = [];

    /**
     * @return array $users
     *      array   $users[<user_id>]
     *      mixed   $users[<user_id>][*] with fields: id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48
     *      string  $users[<user_id>]['formatted_name'] - formatted name
     * @throws waException
     */
    public function getBirthdayUsers()
    {
        $today = $this->getToday();
        $parts = explode('-', $today);
        $month = intval($parts[1]);
        $year = intval($parts[2]);

        return $this->getUsers([
            'hash' => "search/birth_month={$month}&birth_day={$year}",
        ]);
    }

    /**
     * @return array $users
     *      array   $users[<user_id>]
     *      mixed   $users[<user_id>][*] with fields: id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48
     *      string  $users[<user_id>]['formatted_name'] - formatted name
     *
     *      array $users[<user_id>]['statuses'] - statuses, id => status structure
     *      mixed $users[<user_id>]['statuses'][<id>][*] - all fields from wa_contact_events table
     *      array $users[<user_id>]['statuses'][<id>]['calendar']
     *      mixed $users[<user_id>]['statuses'][<id>]['calendar'][*] - all fields from wa_contact_calendars table
     *
     *
     * @throws waDbException
     * @throws waException
     */
    public function getUserStatuses()
    {
        $query = $this->getStatusesQuery();
        $statuses = $this->fetchStatuses($query);
        $contact_ids = array_keys($statuses);

        $contacts = $this->getUsers([
            'hash' => 'id/' . join(',', $contact_ids)
        ]);

        foreach ($contacts as $contact_id => &$contact) {
            $contact['statuses'] = $statuses[$contact_id];
        }
        unset($contact);

        return $contacts;
    }


    /**
     * @param array $params
     *      $params['hash']
     *      $params['fields'] - default is all names fields + photo_url_48
     * @return array
     * @throws waException
     */
    protected function getUsers(array $params = [])
    {
        $hash = ifset($params['hash']);
        $fields = ifset($params['fields'], 'id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48');
        $options = (array)ifset($params['options']);
        $options['photo_url_2x'] = true;

        $collection = new waContactsCollection($hash, $options);
        $collection->addWhere('is_user=1');
        $contacts = $collection->getContacts($fields, 0, 500);

        foreach ($contacts as &$contact) {
            $contact['formatted_name'] = waContactNameField::formatName($contact);
        }
        unset($contact);

        return $contacts;
    }

    /**
     * @return waDbResultSelect
     * @throws waDbException
     * @throws waException
     */
    protected function getStatusesQuery()
    {
        $today = $this->getToday();

        $select = ['wce.*'];
        foreach ($this->getCalendarsModel()->getMetadata() as $field => $_) {
            $select[] = "wcc.{$field} AS calendar_{$field}";
        }
        $select = join(',', $select);

        $sql = "SELECT {$select} 
                FROM `wa_contact_events` wce
                    JOIN `wa_contact_calendars` wcc on wce.calendar_id = wcc.id
                WHERE wce.is_status = 1 AND DATE(wce.start) <= :today AND DATE(wce.end) >= :today";

        return $this->getEventsModel()->query($sql, [
            'today' => $today
        ]);
    }

    /**
     * @param waDbResultSelect $query
     * @return array contact_id => event_id => { event.* + event['calendar'] }
     */
    protected function fetchStatuses($query)
    {
        $result = [];
        foreach ($query as $row) {
            $event_id = $row['id'];
            $contact_id = $row['contact_id'];
            list($event, $calendar) = $this->splitMapByKeyPrefix('calendar_', $row);
            $result[$contact_id][$event_id] = array_merge($event, [
                'calendar' => $calendar
            ]);
        }
        return $result;
    }

    private function splitMapByKeyPrefix($prefix, $map)
    {
        $result = [
            [],     // key had not prefix
            []      // key had prefix
        ];

        $prefix_len = strlen($prefix);
        foreach ($map as $key => $value) {
            if (substr($key, 0, $prefix_len) === $prefix) {
                $key = substr($key, $prefix_len);
                $result[1][$key] = $value;
            } else {
                $result[0][$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return waContactEventsModel
     */
    private function getEventsModel()
    {
        return $this->getObjectFromCache(__METHOD__, waContactEventsModel::class);
    }

    /**
     * @return waContactCalendarsModel
     */
    private function getCalendarsModel()
    {
        return $this->getObjectFromCache(__METHOD__, waContactCalendarsModel::class);
    }

    /**
     * @return waContactModel
     */
    private function getContactModel()
    {
        return $this->getObjectFromCache(__METHOD__, waContactModel::class);
    }

    private function getObjectFromCache($key, $class)
    {
        if (!isset(self::$static_cache[$key])) {
            self::$static_cache[$key] = new $class();
        }
        return self::$static_cache[$key];
    }

    private function getToday()
    {
        return date('Y-m-d');
    }
}
