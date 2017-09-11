<?php

class teamWaContactEventsModel extends waContactEventsModel
{
    private $const_fields_for_system_event = array(
        'uid', 'create_datetime'
    );

    private $const_fields_for_external_event = array(
        'uid', 'create_datetime', 'contact_id', 'calendar_id'
    );

    const SUMMARY_TYPE_TILL = 'till';
    const SUMMARY_TYPE_INTERVAL = 'interval';

    /**
     * @var teamEventExternalModel
     */
    private static $eem;

    /**
     * @var teamEventExternalParamsModel
     */
    private static $eepm;

    /**
     * @var teamCalendarExternalModel
     */
    private static $cem;

    /**
     * @var teamWaContactCalendarsModel
     */
    private static $cm;

    /**
     * @param int|array[]int|null $contact_id
     * @param array $filter
     * @return array
     */
    public function getStats($contact_id = null, $filter = array())
    {
        $contact_ids = array_map('intval', (array)$contact_id);

        $where = array();
        if ($contact_id !== null) {
            $where[] = "ce.contact_id IN (:contact_ids)";
        }

        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $where[] = 'ce.end >= :start_date AND ce.start <= :end_date';       // equal to NOT (ce.end < :start_date OR ce.start > :end_date)
        } elseif (empty($filter['end_date'])) {
            $where[] = 'ce.end >= :start_date';
        } else {
            return array();
        }

        $where[] = "ce.is_status = 1";
        $where = join(' AND ', $where);

        $group_by = array();
        if ($contact_id !== null) {
            $group_by[] = "ce.contact_id";
        }
        $group_by[] = "ce.calendar_id";
        $group_by = join(",", $group_by);

        $stats = array_fill_keys($contact_ids, array());

        $sql_start_date_time = 'IF(ce.start >= :start_date, ce.start, :start_date)';
        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $sql_end_date_time = 'IF(ce.end <= :end_date, ce.end, :end_date)';
        } elseif (!empty($filter['start_date'])) {
            $sql_end_date_time = 'ce.end';
        }

        $sql = "SELECT cc.*, ce.contact_id, 
                    COUNT(ce.id) AS events_count,
                    SUM(
                      IF(ce.is_allday = 1,
                          TIMESTAMPDIFF(HOUR, DATE({$sql_start_date_time}), DATE({$sql_end_date_time})) + 24,
                          TIMESTAMPDIFF(HOUR, {$sql_start_date_time}, {$sql_end_date_time})
                      )
                    ) AS duration_in_hours
                  FROM `wa_contact_events` ce
                  JOIN `wa_contact_calendars` cc ON ce.calendar_id = cc.id
                  WHERE {$where}
                  GROUP BY {$group_by}";

        $res = $this->query($sql, array('contact_ids' => $contact_ids) + $filter)->fetchAll();
        foreach ($res as $item) {
            $d = floor($item['duration_in_hours'] / 24);
            $h = $item['duration_in_hours'] % 24;
            $item['duration_in_days'] = $d;
            $item['duration'] = array(
                'days' => $d,
                'hours' => $h
            );
            $stats[$item['contact_id']] = ifset($stats[$item['contact_id']], array());
            $stats[$item['contact_id']][$item['id']] = $item;
        }

        if ($contact_id === null) {
            return $stats;
        }

        if (!is_array($contact_id)) {
            return ifset($stats[intval($contact_id)], array());
        }

        return $stats;
    }

    public function getMinStartForCalendar($calendar_id)
    {
        $res = $this->select('MIN(start)')->where('calendar_id = ?', $calendar_id)->fetchField();
        if (!$res) {
            return date('Y-m-d H:i:s');
        }
        return $res;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getEvent($id)
    {
        $id = (int)$id;
        $event = $this->getById($id);
        if (!$event) {
            return null;
        }
        $external_events = $this->getEventExternalModel()->getByWaEventId($id, false);
        $event['external_events'] = $external_events;
        $event['is_system'] = empty($external_events);
        return $event;
    }

    public function getEmptyRecord($filled = array())
    {
        $record = array_merge(
            $this->getEmptyRow(),
            array(
                'id' => null,
                'summary' => null,
                'description' => null,
                'location' => null,
                'is_allday' => 1,
                'is_status' => 0,
                'sequence' => null,
            ),
            (array)$filled
        );
        return $record;
    }

    public function addEvent(array $data)
    {
        $id = parent::insertEvent($data);
        if (!$id) {
            return $id;
        }
        $event = $this->getById($id);
        $calendar = $this->getCalendarExternalModel()->getByField(array(
            'contact_id' => $event['contact_id'],
            'calendar_id' => $event['calendar_id']
        ));
        if (!$calendar) {
            return;
        }
        $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar['id']);
        if (!$plugin) {
            return;
        }
        $integration_level = $plugin->getCalendar()->getIntegrationLevel();
        if (!in_array($integration_level, array(teamCalendarExternalModel::INTEGRATION_LEVEL_SYNC, teamCalendarExternalModel::INTEGRATION_LEVEL_FULL))) {
            return;
        }

        $result = $plugin->addEvent($event);
        if (!$result) {
            return;
        }

        $external_event = array(
            'event_id' => $event['id'],
            'calendar_external_id' => $calendar['id'],
            'native_event_id' => !empty($result['native_event_id']) ? $result['native_event_id'] : $event['uid'],
            'params' => array()
        );

        $em = $this->getEventExternalModel();
        $epm = $this->getEventExternalParamsModel();

        $result = (array) $result;
        $result['params'] = ifset($result['params'], array());
        foreach ($result['params'] as $param_name => $param_value) {
            $external_event['params'][$param_name] = $param_value;
        }

        $event_external_id = $em->insert($external_event);
        if (!$event_external_id) {
            return;
        }
        $epm->set($event_external_id, $result['params']);

        return $id;
    }

    /**
     * @param $id
     * @param $data
     */
    public function updateEvent($id, $data)
    {
        $external_events = ifset($data['external_events'], array());

        $is_system = $this->getEventExternalModel()->countByWaEventId($id) <= 0;
        if ($is_system) {
            $const_fields_map = array_fill_keys($this->const_fields_for_system_event, true);
        } else {
            $const_fields_map = array_fill_keys($this->const_fields_for_external_event, true);
        }

        $update = array();
        foreach ((array)$data as $field => $value) {
            if ($this->fieldExists($field) && $field !== 'id' && empty($const_fields_map[$field])) {
                $update[$field] = $value;
            }
        }

        if (!$update) {
            return;
        }

        $event = $this->getById($id);
        if (!$event) {
            return;
        }

        $event = array_merge($event, $update);
        $event['external_events'] = $external_events;

        // first try update external events, if failed (throw exception) we will not update event itself)
        $result = $this->updateExternalEvents($event);

        $this->updateById($id, $update);

        return array(
            'external_events_result' => $result
        );
    }

    public function deleteEvent($id)
    {
        $event = $this->getEvent($id);
        if (!$event) {
            return;
        }

        // first try delete external events, if failed (throw exception) we will not delete event itself
        $result = $this->deleteExternalEvents($event);

        $this->deleteById($event['id']);

        return array(
            'external_events_result' => $result
        );
    }

    /**
     * @param array[]int|int $calendar_id
     * @return int
     */
    public function countByCalendarId($calendar_id)
    {
        return $this->countByField('calendar_id', $calendar_id);
    }

    /**
     * @param array[]int|int $calendar_id
     * @return int
     */
    public function countExternalEventsByCalendarId($calendar_id)
    {
        $calendar_ids = array_map('intval', (array) $calendar_id);
        $sql = "SELECT COUNT(*) 
                FROM `team_event_external` tee 
                JOIN `wa_contact_events` wce ON wce.id = tee.event_id
                WHERE wce.calendar_id IN (:calendar_ids)";
        return $this->query($sql, array('calendar_ids' => $calendar_ids))->fetchField();
    }

    /**
     * @param array[]int|int $id
     */
    public function deleteEventsByCalendar($id)
    {
        $calendar_ids = array();
        foreach (array_map('intval', (array) $id) as $calendar_id) {
            if ($calendar_id > 0) {
                $calendar_ids[] = $calendar_id;
            }
        }
        if (!$calendar_ids) {
            return;
        }
        $where = $this->getWhereByField('calendar_id', $calendar_ids, 'wce');
        $this->deleteEventsByWhere($where);
    }

    protected function deleteEventsByWhere($where)
    {
        if (!$where) {
            return false;
        }

        // DELETE EXTERNAL EVENTS AND IT'S PARAMS
        $sql = "DELETE tee, teep 
                FROM `wa_contact_events` wce
                JOIN `team_event_external` tee ON wce.id = tee.event_id 
                LEFT JOIN `team_event_external_params` teep ON teep.event_external_id = tee.id
                WHERE {$where}";
        $this->exec($sql);

        // DELETE WA-EVENTS ITSELF
        $sql = "DELETE wce FROM `wa_contact_events` wce
                WHERE {$where}";
        $this->exec($sql);
    }

    protected function updateExternalEvents($event)
    {
        $cem = $this->getCalendarExternalModel();
        $eepm = $this->getEventExternalParamsModel();

        $external_events = (array)ifset($event['external_events']);
        unset($event['external_events']);

        $results = array();

        /**
         * @var teamCalendarExternalPlugin[] $calendar_external_plugins
         */
        $calendar_external_plugins = array();
        foreach ($external_events as $external_event) {
            $plugin = null;
            $calendar_external_id = (int)ifset($external_event['calendar_external_id']);
            if ($calendar_external_id > 0) {
                if (array_key_exists($calendar_external_id, $calendar_external_plugins)) {
                    $plugin = $calendar_external_plugins[$calendar_external_id];
                } else {
                    $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar_external_id);
                    $calendar_external_plugins[$calendar_external_id] = $plugin;
                }
                if ($plugin) {
                    $integration_level = $plugin->getCalendar()->getIntegrationLevel();
                    if (in_array($integration_level, array(teamCalendarExternalModel::INTEGRATION_LEVEL_SYNC, teamCalendarExternalModel::INTEGRATION_LEVEL_FULL))) {

                        $not_found = false;
                        try {
                            $result = $plugin->updateEvent($event + $external_event);
                        } catch (teamCalendarExternalEventNotFoundException $e) {
                            $results[$external_event['id']] = 'not_found';
                            $not_found = true;
                        }
                        if ($not_found) {
                            $this->getEventExternalModel()->delete($external_event['id'], false);
                        }
                        if (!$not_found && is_array($result)) {
                            $result['params'] = ifset($result['params'], array());

                            // if there are diffs in params, so safe differ params
                            $diff = array();
                            foreach ($external_event as $field => $value) {
                                if ($field === 'params' && is_array($value)) {
                                    foreach ($value as $param_name => $param_value) {
                                        if (array_key_exists($param_name, $result['params']) && $result['params'][$param_name] != $param_value) {
                                            $diff[$param_name] = $result['params'][$param_name];
                                        }
                                    }
                                }
                            }

                            if ($diff) {
                                $eepm->set($external_event['id'], $diff);
                            }
                        }
                    }
                }
            }
        }
        return $results;
    }

    protected function deleteExternalEvents($event)
    {
        $external_events = (array)ifset($event['external_events']);

        $result = array();

        /**
         * @var teamCalendarExternalPlugin[] $calendar_external_plugins
         */
        $calendar_external_plugins = array();
        foreach ($external_events as $external_event) {
            $plugin = null;
            $calendar_external_id = (int)ifset($external_event['calendar_external_id']);
            if ($calendar_external_id > 0) {
                if (array_key_exists($calendar_external_id, $calendar_external_plugins)) {
                    $plugin = $calendar_external_plugins[$calendar_external_id];
                } else {
                    $plugin = teamCalendarExternalPlugin::factoryByCalendar($calendar_external_id);
                    $calendar_external_plugins[$calendar_external_id] = $plugin;
                }
                if ($plugin) {
                    $integration_level = $plugin->getCalendar()->getIntegrationLevel();
                    if (in_array($integration_level, array(teamCalendarExternalModel::INTEGRATION_LEVEL_SYNC, teamCalendarExternalModel::INTEGRATION_LEVEL_FULL))) {
                        try {
                            $plugin->deleteEvent($external_event);
                        } catch (teamCalendarExternalEventNotFoundException $e) {
                            $result[$external_event['id']] = 'not_found';
                        }
                    }
                }
            }
            $this->getEventExternalModel()->delete($external_event['id']);
        }

        return $result;
    }

    public function moveEventDates($id, $date_diff_days)
    {
        parent::moveEventDates($id, $date_diff_days);
        $event = $this->getEvent($id);

        // change auto summary
        $summary_type = ifset($event['summary_type']);
        $summary = ifset($event['summary']);
        if ($summary_type === self::SUMMARY_TYPE_TILL) {
            $summary = self::formatTillSummary($event['end'], $event['calendar_id']);
        } elseif ($summary_type === self::SUMMARY_TYPE_INTERVAL) {
            $summary = self::formatIntervalSummary($event['start'], $event['end'], $event['calendar_id']);
        }
        if ($event['summary'] != $summary) {
            $this->updateById($event['id'], array(
                'summary' => $summary
            ));
            $event['summary'] = $summary;
        }

        $this->updateExternalEvents($event);

    }

    public static function formatTillSummary($end_date, $calendar_id)
    {
        $till_end_date_str = self::formatShortDate($end_date);
        $calendar_id = (int)$calendar_id;
        if ($calendar_id <= 0) {
            return '';
        }
        $calendar = self::getCalendarModel()->getById($calendar_id);
        if (!$calendar) {
            return '';
        }
        $status_str = !empty($calendar['default_status']) ? $calendar['default_status'] : mb_strtolower($calendar['name']);
        return str_replace(
            array(':status', ':till_end_date'),
            array($status_str, $till_end_date_str),
            _w(":status until :till_end_date")
        );
    }

    public static function formatIntervalSummary($start_date, $end_date, $calendar_id)
    {
        $from_start_date_str = self::formatShortDate($start_date);
        $until_end_date_str = self::formatShortDate($end_date);
        $calendar_id = (int)$calendar_id;
        if ($calendar_id <= 0) {
            return '';
        }
        $calendar = self::getCalendarModel()->getById($calendar_id);
        if (!$calendar) {
            return '';
        }
        $status_str = !empty($calendar['default_status']) ? $calendar['default_status'] : mb_strtolower($calendar['name']);
        return str_replace(
            array(':status', ':from_start_date', ':until_end_date'),
            array($status_str, $from_start_date_str, $until_end_date_str),
            _w(":status from :from_start_date until :until_end_date")
        );
    }

    protected static function formatShortDate($date)
    {
        return waDateTime::format('shortdate', strtotime($date), waDateTime::getDefaultTimeZone());
    }

    public static function workupExternalEvents($external_events, $reset_keys = true)
    {
        foreach ($external_events as &$event_external) {
            $calendar = array(
                'name' => '',
                'plugin' => array(
                    'name' => '',
                    'account_info_html' => '',
                    'icon' => ''
                )
            );
            $plugin = teamCalendarExternalPlugin::factoryByCalendar($event_external['calendar_external_id']);
            if ($plugin) {
                $calendar['name'] = $plugin->getCalendarName();
                $calendar['plugin'] = array(
                    'name' => $plugin->getName(),
                    'account_info_html' => $plugin->getAccountInfoHtml(array('action' => 'event')),
                    'icon' => $plugin->getIconUrl()
                );
            }
            $event_external['calendar'] = $calendar;
        }
        unset($event_external);

        return $reset_keys ? array_values($external_events) : $external_events;
    }

    protected function getEventExternalModel()
    {
        if (!self::$eem) {
            self::$eem = new teamEventExternalModel();
        }
        return self::$eem;
    }

    protected function getCalendarExternalModel()
    {
        if (!self::$cem) {
            self::$cem = new teamCalendarExternalModel();
        }
        return self::$cem;
    }

    protected static function getCalendarModel()
    {
        if (!self::$cm) {
            self::$cm = new teamWaContactCalendarsModel();
        }
        return self::$cm;
    }

    protected function getEventExternalParamsModel()
    {
        if (!self::$eepm) {
            self::$eepm = new teamEventExternalParamsModel();
        }
        return self::$eepm;
    }
}
