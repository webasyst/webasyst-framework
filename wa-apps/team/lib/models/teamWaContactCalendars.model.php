<?php

class teamWaContactCalendarsModel extends waContactCalendarsModel
{
    /**
     * @var teamWaContactEventsModel
     */
    private static $twcem;

    /**
     * @var waContactRightsModel
     */
    private static $wcrm;

    /**
     * @var teamCalendarExternalModel
     */
    private static $tcem;

    /**
     * Get calendar.
     * Has can_edit flag (true|false)
     *
     * @param int $calendar_id
     * @param int|null $contact_id
     * @return array
     */
    public function getCalendar($calendar_id, $contact_id = null)
    {
        $calendar_id = (int) $calendar_id;
        $calendars = $this->getCalendars($calendar_id, $contact_id);
        return $calendars[$calendar_id];
    }

    /**
     * Get calendars
     * Has can_edit flag (true|false)
     * @param int|array[]int|null $calendar_id
     * @param int|null $contact_id
     * @return array
     */
    public function getCalendars($calendar_id = null, $contact_id = null)
    {
        $app_id = 'team';

        if ($calendar_id !== null) {
            $calendar_ids = array_map('intval', (array) $calendar_id);
            $calendars = $this->select('*')->order('sort')->where('id IN(:ids)', array(
                'ids' => $calendar_ids
            ))->fetchAll('id');
        } else {
            $calendars = $this->get();
        }

        // get user
        $contact = $contact_id === null ? wa()->getUser() : new waUser($contact_id);

        // admin has always rights
        if ($contact->isAdmin($app_id)) {
            return $this->allCanEdit($calendars);
        }

        // init can edit right flags
        $calendars = $this->nothingCanEdit($calendars);

        // check is_limit flag
        foreach ($calendars as &$calendar) {
            if ($contact->getRights($app_id, 'edit_events_in_calendar.all')) {
                $calendar['can_edit'] = 1;
            } else {
                $calendar['can_edit'] = $calendar['is_limited'] <= 0;
            }
        }
        unset($calendar);

        foreach ($contact->getRights($app_id, 'edit_events_in_calendar.%') as $calendar_id => $can_edit) {
            if (isset($calendars[$calendar_id])) {
                $calendars[$calendar_id]['can_edit'] = $calendars[$calendar_id]['can_edit'] || $can_edit;
            }
        }
        return $calendars;
    }

    /**
     * @param array[]int|int $id
     */
    public function deleteCalendar($id)
    {
        // typecast
        $calendar_ids = array();
        foreach (array_map('intval', (array) $id) as $calendar_id) {
            if ($calendar_id > 0) {
                $calendar_ids[] = $calendar_id;
            }
        }
        if (!$calendar_ids) {
            return;
        }

        // delete events - take into account external events
        $this->getEventsModel()->deleteEventsByCalendar($calendar_ids);

        // delete rights
        $names = array();
        foreach ($calendar_ids as $calendar_id) {
            $names[] = 'edit_events_in_calendar.' . $calendar_id;
        }
        $this->getRightsModel()->deleteByField(array(
            'app_id' => 'team',
            'name' => $names
        ));

        // delete external calendars
        $this->getCalendarExternalModel()->deleteByCalendarId($calendar_ids);

        // delete calendar itself
        $this->deleteById($calendar_ids);
    }

    public function countEvents($calendar_id)
    {
        return $this->getEventsModel()->countByCalendarId($calendar_id);
    }

    public function countExternalEvents($calendar_id)
    {
        return $this->getEventsModel()->countExternalEventsByCalendarId($calendar_id);
    }

    public function countExternalCalendars($calendar_id)
    {
        return $this->getCalendarExternalModel()->countByCalendarId($calendar_id);
    }

    /**
     * @return teamWaContactEventsModel
     */
    protected function getEventsModel()
    {
        if (!self::$twcem) {
            self::$twcem = new teamWaContactEventsModel();
        }
        return self::$twcem;
    }

    /**
     * @return waContactRightsModel
     */
    protected function getRightsModel()
    {
        if (!self::$wcrm) {
            self::$wcrm = new waContactRightsModel();
        }
        return self::$wcrm;
    }

    /**
     * @return teamCalendarExternalModel
     */
    protected function getCalendarExternalModel()
    {
        if (!self::$tcem) {
            self::$tcem = new teamCalendarExternalModel();
        }
        return self::$tcem;
    }

    private function allCanEdit($calendars)
    {
        foreach ($calendars as &$calendar) {
            $calendar['can_edit'] = true;
        }
        return $calendars;
    }

    private function nothingCanEdit($calendars)
    {
        foreach ($calendars as &$calendar) {
            $calendar['can_edit'] = false;
        }
        return $calendars;
    }
}
