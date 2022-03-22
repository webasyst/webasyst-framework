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
     * @return array
     *      array   $users[<user_id>]
     *      array   $users[<user_id>]['id']
     */
    protected function getBirthdayUsers()
    {
        return $this->getBirthdayUsersQuery()->fetchAll('id');
    }

    /**
     * @return array $calendars - calendar_id => contact_id => contact_info + status
     *
     *      array   $calendars[<id>]
     *      mixed   $calendars[<id>][<wa_contact_calendar.*>] - all fields from wa_contact_calendar
     *      int     $calendars[<id>]['total_count'] - total contacts count today has statuses
     *      array   $calendars[<id>]['contacts'][<contact_id>]['id'] - contact ID
     */
    protected function getCalendarContactStatuses()
    {
        $query = $this->getUsersWithMinCalendarSortsQuery();

        $result = $this->fetchUsersWithMinCalendarSortsData($query);

        $calendar_sorts = $result['calendar_sorts'];
        $contact_ids = $result['contact_ids'];
        $sort_contact_index = $result['index'];

        $query = $this->getCalendarsBySortsQuery($calendar_sorts);
        $result = $this->fetchCalendarsData($query);
        $calendars = $result['calendars'];
        $calendar_sort_index = $result['index'];

        $calendar_ids = array_keys($calendars);

        $query = $this->getStatusesQuery($contact_ids, $calendar_ids);
        $result = $this->fetchStatuses($query, $sort_contact_index, $calendar_sort_index);
        $calendar_statuses = $result['calendar_statuses'];

        foreach ($calendars as $id => &$calendar) {
            foreach ($calendar_statuses[$id] as $contact_id => $status_record) {
                $calendar['contacts'][$contact_id] = ['id' => $contact_id, 'status' => $status_record];
            }
        }
        unset($calendar);

        return $calendars;
    }

    /**
     * @return array $groups
     *      $groups[<id>]['id']
     *      $groups[<id>]['name']
     *      $groups[<id>]['color']
     *      $groups[<id>]['contacts'] - limited list of contacts (by default limit is 5)
     *      $groups[<id>]['contacts'][<contact_id>] - contact info, id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48,formatted_name
     *      $groups[<id>]['total_count'] - total count that in this group
     *      $groups[<id>]['rest_count']  - res count that in this group (total_count - count of listed contacts)
     * @throws waException
     */
    public function getGroups()
    {
        return $this->getFromCache(__METHOD__, [$this, 'loadGroups']);
    }

    /**
     * @return array $groups
     *      $groups[<id>]['id']
     *      $groups[<id>]['name']
     *      $groups[<id>]['color']
     *      $groups[<id>]['contacts'] - limited list of contacts (by default limit is 5)
     *      $groups[<id>]['contacts'][<contact_id>] - contact info
     *      $groups[<id>]['total_count'] - total count that in this group
     *      $groups[<id>]['rest_count']  - res count that in this group (total_count - count of listed contacts)
     * @throws waException
     */
    private function loadGroups()
    {
        $calendar_user_statuses = $this->getCalendarContactStatuses();

        $birthday_users = $this->getBirthdayUsers();
        $birthday_users_count = count($birthday_users);

        // populate "birthday with status group" - group where contact at once has birthday and has some status
        $birthday_with_status_groups = [];
        foreach ($calendar_user_statuses as $calendar_id => &$calendar) {
            foreach ($calendar['contacts'] as $contact_id => $contact) {
                $is_today_also_birthday = isset($birthday_users[$contact_id]);
                if (!$is_today_also_birthday) {
                    continue;
                }

                // move contact into "birthday with status group"
                $group_id = 'birthday-' . $calendar_id;
                if (!isset($birthday_with_status_groups[$group_id])) {
                    $birthday_with_status_groups[$group_id]['id'] = $group_id;
                    $birthday_with_status_groups[$group_id]['name'] = _ws('Birthday')." + ".$calendar['name'];
                    $birthday_with_status_groups[$group_id]['calendar']['id'] = $calendar['id'];
                    $birthday_with_status_groups[$group_id]['calendar']['name'] = $calendar['name'];
                    $birthday_with_status_groups[$group_id]['calendar']['icon'] = $calendar['icon'] ;
                    $birthday_with_status_groups[$group_id]['calendar']['font_color'] = $calendar['font_color'] ;
                    $birthday_with_status_groups[$group_id]['calendar']['bg_color'] = $calendar['bg_color'] ;
                    $birthday_with_status_groups[$group_id]['color'] = $calendar['bg_color'];
                    $birthday_with_status_groups[$group_id]['contacts'] = [];
                    $birthday_with_status_groups[$group_id]['total_count'] = 0;

                }
                $birthday_with_status_groups[$group_id]['contacts'][$contact_id] = $contact;
                $birthday_with_status_groups[$group_id]['total_count']++;

                // correspondingly remove from "birthday" group
                $birthday_users_count--;
                unset($birthday_users[$contact_id]);

                // and also correspondingly remove from calendar (status) group
                $calendar['total_count']--;
                unset($calendar['contacts'][$contact_id]);
            }
            unset($calendar);
        }

        $this->limitContacts($birthday_with_status_groups);
        $this->limitContacts($calendar_user_statuses);

        $birthday_users_group = [
            'id' => 'birthday',
            'name' => 'Birthday',
            'color' => '#dddddd',
            'contacts' => $birthday_users,
            'total_count' => $birthday_users_count,
        ];

        $birthday_users_groups = [
            'birthday' => $birthday_users_group
        ];

        $this->limitContacts($birthday_users_groups);

        $all_groups = array_merge($birthday_users_groups, $birthday_with_status_groups);
        $all_groups += $calendar_user_statuses; // merge into by plus because keys are numerics

        $contact_ids = [];

        // delete groups that end up with empty contact lists
        foreach ($all_groups as $group_id => &$group) {
            if (!$group['contacts']) {
                unset($all_groups[$group_id]);
            } else {
                $contact_ids = array_merge($contact_ids, array_keys($group['contacts']));
            }
        }
        unset($group);

        $contacts = $this->getContacts($contact_ids);

        foreach ($all_groups as $group_id => &$group) {
            foreach ($group['contacts'] as $contact_id => $_) {
                $contacts[$contact_id]['summary'] = ifset($_, 'status', 'summary', '');
                $group['contacts'][$contact_id] = $contacts[$contact_id];
            }
        }
        unset($group);

        return $all_groups;
    }

    protected function limitContacts(&$groups, $limit = 5)
    {
        // in 'contacts' list show max 5 contacts
        foreach ($groups as $group_id => &$group) {
            $group['contacts'] = array_slice($group['contacts'], 0, $limit, true);
            $group['rest_count'] = $group['total_count'] - count($group['contacts']);
        }
        unset($group);
    }

    /**
     * @param array $ids
     * @return array $contacts with fields: id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48,formatted_name
     * @throws waException
     */
    protected function getContacts(array $ids = [])
    {
        $fields = 'id,name,firstname,lastname,middlename,is_company,is_user,login,email,login,photo_url_48';

        $collection = new waContactsCollection('id/' . join(',', $ids), [
            'photo_url_2x' => true
        ]);
        $contacts = $collection->getContacts($fields, 0, 500);

        foreach ($contacts as &$contact) {
            $contact['formatted_name'] = waContactNameField::formatName($contact);
        }
        unset($contact);

        return $contacts;
    }

    protected function getCalendarsBySortsQuery(array $sorts)
    {
        $sorts = waUtils::toIntArray($sorts);
        foreach ($sorts as $index => $int) {
            if ($int < 0) {
                unset($sorts[$index]);
            }
        }
        if (!$sorts) {
            return null;
        }

        $today = $this->getToday();

        $sql = "
            SELECT wcc.id, wcc.name, wcc.sort, wcc.icon, wcc.font_color, wcc.bg_color, COUNT(*) AS total_count
            FROM `wa_contact_calendars` wcc
            JOIN `wa_contact_events` wce ON wcc.id = wce.calendar_id
            JOIN `wa_contact` wc ON wc.id = wce.contact_id
            WHERE wce.is_status = 1 AND DATE(wce.start) <= :today AND DATE(wce.end) >= :today AND wc.is_user = 1 AND wcc.sort IN(:sorts)
            GROUP BY wcc.id
        ";

        return $this->getCalendarsModel()->query($sql, [
            'today' => $today,
            'sorts' => $sorts
        ]);

    }

    /**
     * @param waDbResultSelect $query
     * @return array[] $result
     *      array[] $result['calendars'] - indexed by ID
     *      array $result['index'] - calendar_id => sort
     */
    protected function fetchCalendarsData($query)
    {
        $result = [
            'calendars' => [],
            'index' => [],  // calendar_id => $sort
        ];

        if (!$query) {
            return $result;
        }

        foreach ($query as $row) {
            $id = $row['id'];
            $sort = $row['sort'];
            if (!isset($result['index'][$id])) {
                $result['calendars'][$id] = $row;
                $result['index'][$id] = $sort;
            }
        }

        return $result;
    }

    /**
     * @param waDbResultSelect $query
     * @return array[] $result
     *      int[] $result['contact_ids']
     *      int[] $result['calendar_sorts']
     *      array $result['index'] sort => contact_id => true
     */
    protected function fetchUsersWithMinCalendarSortsData($query)
    {
        $result = [
            'contact_ids' => [],
            'calendar_sorts' => [],
            'index' => []   // sort => contact_id => true
        ];

        if (!$query) {
            return $result;
        }

        foreach ($query as $row) {
            $contact_id = intval($row['contact_id']);
            $sort = intval($row['sort']);
            $result['contact_ids'][] = $contact_id;
            $result['calendar_sorts'][] = $sort;
            $result['index'][$sort][$contact_id] = true;
        }

        $result['contact_ids'] = array_unique($result['contact_ids']);
        $result['calendar_sorts'] = array_unique($result['calendar_sorts']);

        return $result;
    }

    /**
     * @return waDbResultSelect
     */
    protected function getBirthdayUsersQuery()
    {
        $today = $this->getToday();
        $parts = explode('-', $today);
        $month = intval($parts[1]);
        $day = intval($parts[2]);

        $sql = "SELECT id FROM `wa_contact`
                WHERE is_user=1 AND birth_month = :month AND birth_day = :day";

        return $this->getContactModel()->query($sql, [
            'month' => $month,
            'day' => $day,
        ]);
    }

    /**
     * @return waDbResultSelect
     */
    protected function getUsersWithMinCalendarSortsQuery()
    {
        $today = $this->getToday();
        $sql = "
            SELECT wce.contact_id, MIN(wcc.sort) AS sort
            FROM `wa_contact_events` wce
                JOIN `wa_contact_calendars` wcc on wce.calendar_id = wcc.id
                JOIN `wa_contact` wc ON wce.contact_id = wc.id
            WHERE wce.is_status = 1 AND DATE(wce.start) <= :today AND DATE(wce.end) >= :today AND wc.is_user = 1
            GROUP BY wce.contact_id
            LIMIT 0, 500;
        ";

        return $this->getEventsModel()->query($sql, [
            'today' => $today
        ]);
    }

    protected function getStatusesQuery(array $contact_ids, array $calendar_ids)
    {
        $contact_ids = waUtils::dropNotPositive(waUtils::toIntArray($contact_ids));
        $calendar_ids = waUtils::dropNotPositive(waUtils::toIntArray($calendar_ids));
        if (!$contact_ids || !$calendar_ids) {
            return [];
        }

        $sql = "SELECT * FROM `wa_contact_events`
                WHERE is_status = 1 AND DATE(start) <= :today AND DATE(end) >= :today 
                        AND contact_id IN(:contact_ids) AND calendar_id IN(:calendar_ids)";

        $today = $this->getToday();
        return $this->getEventsModel()->query($sql, [
            'today' => $today,
            'contact_ids' => $contact_ids,
            'calendar_ids' => $calendar_ids
        ]);
    }

    /**
     * @param waDbResultSelect $query
     * @param array $sort_contact_index sort => contact_id => true
     * @param array $calendar_sort_index calendar_id => sort
     * @return array[] $result
     *      array $result['calendar_statuses'] = calendar_id => contact_id => event record
     *      array $result['contact_ids'] - ids of contacts (users)
     */
    protected function fetchStatuses($query, array $sort_contact_index, array $calendar_sort_index)
    {
        $result = [
            'calendar_statuses' => [],  // calendar_id => [*]
            'contact_ids' => []
        ];

        if (!$query) {
            return $result;
        }

        foreach ($query as $row) {
            $calendar_id = $row['calendar_id'];
            $contact_id = $row['contact_id'];
            $sort = $calendar_sort_index[$calendar_id];
            if (!isset($sort_contact_index[$sort][$contact_id])) {
                continue;
            }
            $result['calendar_statuses'][$calendar_id][$contact_id] = $row;
            $result['contact_ids'][] = intval($contact_id);
        }

        $result['contact_ids'] = array_unique($result['contact_ids']);

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

    private function getFromCache($key, $loader)
    {
        if (!isset(self::$static_cache[$key])) {
            self::$static_cache[$key] = $loader();
        }
        return self::$static_cache[$key];
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
