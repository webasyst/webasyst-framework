<?php

class teamCalendarExternalModel extends waModel
{
    const INTEGRATION_LEVEL_SUBSCRIPTION = 'subscription';
    const INTEGRATION_LEVEL_SYNC = 'sync';
    const INTEGRATION_LEVEL_FULL = 'full';

    /**
     * @var string
     */
    protected $table = 'team_calendar_external';

    /**
     * @var array
     */
    protected static $cache = array();

    /**
     * @var teamCalendarExternalParamsModel
     */
    private $params_model;

    /**
     * @var teamEventExternalModel
     */
    private $events_model;

    public function add($data)
    {
        $data = (array) $data;
        if (empty($data['type'])) {
            return false;
        }

        if (empty($data['contact_id'])) {
            $data['contact_id'] = wa()->getUser()->getId();
        }

        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }

        if (!empty($data['calendar_id'])) {
            $calendar_id = (int) $data['calendar_id'];
            $data['calendar_id'] = $calendar_id;
        } else {
            $data['calendar_id'] = null;
        }

        if (empty($data['name'])) {
            $data['name'] = '';
        }

        return $this->insert($data);
    }

    public function update($id, $data)
    {
        $id = (int) $id;
        $data = (array) $data;
        if (empty($data) || $id <= 0) {
            return false;
        }
        $params = array();
        foreach ($data as $field => $value) {
            if (!$this->fieldExists($field)) {
                $params[$field] = $value;
                unset($data[$field]);
            }
        }
        $this->updateById($id, $data);
        if ($params) {
            $this->getParamsModel()->set($id, $params);
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @return array Calendar structure with all relations
     */
    public function getCalendar($id, $params = array())
    {
        if (!empty($params['reset_cache']) && !empty(self::$cache[$id])) {
            unset(self::$cache[$id]);
        }
        return $this->getCachedCalendar($id);
    }

    public function getLastSynchronized()
    {
        $calendar = $this->select('*')->order('synchronize_datetime')->where('synchronize_datetime IS NOT NULL')->limit(1)->fetchAssoc();
        if ($calendar) {
            $calendar = $this->workupCalendar($calendar);
        }
        return $calendar;
    }

    public function countByCalendarId($calendar_id)
    {
        return $this->countByField('calendar_id', $calendar_id);
    }

    /**
     * Delete external calendars with params,events_external and optional wa_contact_events
     * @param int|array[]int $id
     * @param bool $with_events delete wa_contact_events
     */
    public function delete($id, $with_events = true)
    {
        $ids = array_map('intval', (array) $id);
        $this->getParamsModel()->deleteAll($ids);
        $this->getEventsModel()->deleteByCalendarId($ids, $with_events);
        $this->deleteById($ids);
    }

    /**
     * @param array[]int|int $calendar_id
     */
    public function deleteByCalendarId($calendar_id)
    {
        $calendar_ids = array_map('intval', (array) $calendar_id);
        $ids = $this->select('id')->where('calendar_id IN(:calendar_id)', array(
            'calendar_id' => $calendar_ids
        ))->fetchAll(null, true);
        if ($ids) {
            $this->delete($ids);
        }
    }

    /**
     * @param array[]string|string $type
     * @param bool $with_wa_events
     */
    public function deleteByType($type, $with_wa_events = false)
    {
        $ids = $this->select('id')->where('type IN(:type)', array(
            'type' => $type
        ))->fetchAll(null, true);
        if ($ids) {
            $this->delete($ids, $with_wa_events);
        }
    }

    public function deleteAbandoned($timeout = null)
    {
        if ($timeout === null) {
            $timeout = 3600;   // 1 hour
        }
        $sql = "SELECT tce.id FROM `team_calendar_external` tce 
                  LEFT JOIN `wa_contact_calendars` wcc ON wcc.id = tce.calendar_id
                  WHERE wcc.id IS NULL AND tce.create_datetime < ?";
        $ids = $this->query($sql, date('Y-m-d H:i:s', time() - $timeout))->fetchAll(null, true);
        if ($ids) {
            $this->delete($ids, false);
        }
    }

    public function getMinStart($id)
    {
        $calendar = $this->getCalendar($id);
        $wcem = new teamWaContactEventsModel();
        return $wcem->getMinStartForCalendar($calendar['calendar_id']);
    }

    /**
     * @param int $contact_id
     * @return array
     */
    public function getCalendars($contact_id)
    {
        $calendars = $this->select('*')->where('contact_id = ?', $contact_id)->order('create_datetime')->fetchAll('id');
        return $this->workupCalendars($calendars);
    }

    public static function getIntegrationLevels($with_info = false)
    {
        $levels = array(

            // the lowest level
            self::INTEGRATION_LEVEL_SUBSCRIPTION =>
                array(
                    'name' => _w('Import only'),
                    'description' => _w('All entries available in an external calendar will be imported into your Webasyst account. No data will be exported from Webasyst to an external calendar.')
                ),

            self::INTEGRATION_LEVEL_SYNC =>
                array(
                    'name' => _w('Import + export of changes and deletions'),
                    'description' => _w('All entries available in an external calendar will be imported into your Webasyst account. From Webasyst to an external calendar will be exported only your changes and deletions; newly added entries will not be exported to an external calendar.')
                ),

            // the highest level
            self::INTEGRATION_LEVEL_FULL => array(
                'name' => _w('Full data synchronization'),
                'description' => _w('All entries available in an external calendar will be imported into your Webasyst account. From Webasyst to an external calendar will be exported newly added entries as well as your changes and deletions.')
            )

        );
        return $with_info ? $levels : array_keys($levels);
    }

    protected function getCachedCalendar($id)
    {
        if (empty(self::$cache[$id])) {
            $id = (int)$id;
            $calendar = $this->getById($id);
            if (!$calendar) {
                return false;
            }
            $calendar = $this->workupCalendar($calendar);
            self::$cache[$id] = $calendar;
        }
        return self::$cache[$id];
    }

    protected function workupCalendar($calendar)
    {
        $params = $this->getParamsModel()->get($calendar['id']);
        $calendar['params'] = $params;
        $level = $calendar['integration_level'];
        $levels = self::getIntegrationLevels(true);
        if (!isset($levels[$level])) {
            $level = self::INTEGRATION_LEVEL_SUBSCRIPTION;
        }
        $calendar['integration_level_name'] = $levels[$level]['name'];
        return $calendar;
    }

    protected function workupCalendars($calendars)
    {
        foreach ($calendars as &$calendar) {
            $calendar = $this->workupCalendar($calendar);
        }
        unset($calendar);
        return $calendars;
    }

    /**
     * @return teamCalendarExternalParamsModel
     */
    private function getParamsModel()
    {
        if (!$this->params_model) {
            $this->params_model = new teamCalendarExternalParamsModel();
        }
        return $this->params_model;
    }

    /**
     * @return teamEventExternalModel
     */
    private function getEventsModel()
    {
        if (!$this->events_model) {
            $this->events_model = new teamEventExternalModel();
        }
        return $this->events_model;
    }
}
