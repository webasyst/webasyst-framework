<?php

class teamEventExternalModel extends waModel
{
    protected $table = 'team_event_external';

    /**
     * @var teamEventExternalParamsModel
     */
    protected $params_model;

    /**
     * @var teamWaContactEventsModel
     */
    protected $wa_event_model;

    /**
     * @var teamCalendarExternalModel
     */
    protected $calendar_model;

    protected $const_fields = array('id', 'event_id', 'calendar_id', 'calendar_external_id', 'native_event_id');

    /**
     * @param $calendar_external_id
     * @param $event
     * @return int|bool
     */
    public function add($calendar_external_id, $event)
    {
        if (empty($event['native_event_id'])) {
            return false;
        }

        if (isset($event['id'])) {
            unset($event['id']);
        }

        $cem = new teamCalendarExternalModel();
        $calendar = $cem->getCalendar($calendar_external_id);
        $event['contact_id'] = $calendar['contact_id'];
        $event['calendar_id'] = $calendar['calendar_id'];

        $params = array();
        if (array_key_exists('params', $event)) {
            $params = (array) $event['params'];
            unset($event['params']);
        }
        foreach ($event as $field => $value) {
            if (!$this->getWaEventModel()->fieldExists($field) && !$this->fieldExists($field)) {
                $params[$field] = $value;
                unset($event[$field]);
            }
        }

        $event_id = $this->getWaEventModel()->insertEvent($event);
        if (!wa_is_int($event_id) || $event_id <= 0) {
            return false;
        }

        $id = $this->insert(array(
            'event_id' => $event_id,
            'calendar_external_id' => $calendar_external_id,
            'native_event_id' => $event['native_event_id'],
        ));

        if ($params) {
            $this->getParamsModel()->set($id, $params);
        }

        return $id;
    }

    public function getByCalendarAndNativeId($calendar_and_native_id)
    {
        $calendar_and_native_id = array_values((array) $calendar_and_native_id);
        if (count($calendar_and_native_id) < 2) {
            return false;
        }
        return $this->getEvent(array(
            'calendar_external_id' => $calendar_and_native_id[0],
            'native_event_id' => $calendar_and_native_id[1]
        ));
    }

    public function getByEventIdAndCalendar($event_id_and_calendar)
    {
        $event_id_and_calendar = array_values((array) $event_id_and_calendar);
        if (count($event_id_and_calendar) < 2) {
            return false;
        }
        return $this->getEvent(array(
            'event_id' => $event_id_and_calendar[0],
            'calendar_external_id' => $event_id_and_calendar[1]
        ));
    }

    /**
     * @param int|array[]int $id
     * @return array
     */
    public function get($id)
    {
        if (is_array($id)) {
            return $this->getEvent(array(
                'id' => $id,
            ), true, false);
        } else {
            return $this->getEvent(array(
                'id' => $id
            ));
        }
    }

    /**
     * @param $wa_event_id
     * @param bool $with_wa
     * @return array|bool|mixed
     */
    public function getByWaEventId($wa_event_id, $with_wa = false)
    {
        return $this->getEvent(array(
            'event_id' => $wa_event_id
        ), true, false, $with_wa);
    }

    public function countByWaEventId($wa_event_id)
    {
        return $this->countByField('event_id', $wa_event_id);
    }

    public function update($event)
    {
        $original_event = null;

        $field = array();

        $id = (int) (isset($event['id']) ? $event['id'] : 0);
        $event_id = (int) (isset($event['event_id']) ? $event['event_id'] : 0);
        $calendar_external_id = (int) ($event['calendar_external_id'] ? $event['calendar_external_id'] : 0);
        $native_event_id = (string) ($event['native_event_id'] ? $event['native_event_id'] : '');

        if ($id > 0) {
            $field['id'] = $id;
        } elseif ($event_id > 0) {
            $field['event_id'] = $event_id;
        } elseif ($calendar_external_id > 0 && strlen($native_event_id) > 0) {
            $field += array('calendar_external_id' => $calendar_external_id, 'native_event_id' => $native_event_id);
        }

        $original_event = $this->getEvent($field, false);
        if (!$original_event) {
            return false;
        }


        $params = array();
        if (array_key_exists('params', $event)) {
            $params = (array) $event['params'];
            unset($event['params']);
        }

        $update = array();

        $const_fields_map = array_fill_keys($this->const_fields, true);
        foreach ($event as $field => $value) {
            if (!$this->getWaEventModel()->fieldExists($field) && !$this->fieldExists($field)) {
                $params[$field] = $value;
                continue;
            }
            if ($this->getWaEventModel()->fieldExists($field) && !isset($const_fields_map[$field]) && $original_event[$field] != $value) {
                $update[$field] = $value;
            }
        }

        if ($update) {
            $this->getWaEventModel()->updateById($original_event['event_id'], $update);
        }

        if ($params) {
            $this->getParamsModel()->set($original_event['id'], $params);
        }

        return true;
    }

    /**
     * @param int|array[]int $calendar_external_id
     * @param bool $delete_wa_events
     */
    public function deleteByCalendarId($calendar_external_id, $delete_wa_events = true)
    {
        $calendar_external_ids = (array) $calendar_external_id;
        if (!$calendar_external_ids) {
            return;
        }
        $sql = $this->buildDeleteSql(
            $delete_wa_events,
            'WHERE tee.calendar_external_id IN(:calendar_id)'
        );
        $this->exec($sql, array('calendar_id' => $calendar_external_ids));
    }

    public function getEventsByCalendarId($calendar_external_id)
    {
        $sql = "SELECT wce.*, tee.native_event_id 
                  FROM `wa_contact_events` wce  
                    JOIN `team_event_external` tee ON wce.id = tee.event_id
                  WHERE tee.calendar_external_id = :calendar_id";
        return $this->query($sql, array('calendar_id' => $calendar_external_id))->fetchAll();
    }

    /**
     * @param int $calendar_external_id
     * @param string|array[]string $native_event_id
     * @param bool $delete_wa_events
     */
    public function deleteByCalendarAndNativeEventId($calendar_external_id, $native_event_id, $delete_wa_events = true)
    {
        $native_event_ids = (array) $native_event_id;
        if (!$native_event_ids || !$calendar_external_id) {
            return;
        }

        $sql = $this->buildDeleteSql(
            $delete_wa_events,
            'WHERE tee.calendar_external_id = :calendar_id AND tee.native_event_id IN(:native_event_ids)'
        );
        $this->exec($sql, array('calendar_id' => $calendar_external_id, 'native_event_ids' => $native_event_ids));
    }

    public function delete($id, $delete_wa_events = true)
    {
        $ids = (array) $id;
        if (!$ids) {
            return;
        }

        $sql = $this->buildDeleteSql(
            $delete_wa_events,
            'WHERE tee.id = :ids'
        );
        $this->exec($sql, array('ids' => $ids));
    }

    /**
     * @param bool $delete_wa_events
     * @param string $where
     * @return string
     */
    private function buildDeleteSql($delete_wa_events, $where)
    {
        if ($delete_wa_events) {
            $sql = "DELETE wce, tee, teep
                        FROM `wa_contact_events` wce  
                        JOIN `team_event_external` tee ON wce.id = tee.event_id
                        LEFT JOIN `team_event_external_params` teep ON tee.id = teep.event_external_id";
        } else {
            $sql = "DELETE tee, teep
                        FROM `team_event_external` tee   
                        LEFT JOIN `team_event_external_params` teep ON tee.id = teep.event_external_id";
        }
        return $sql . ' ' . $where;
    }

    public function getNativeEventIdsByCalendarAndParam($calendar_external_id, $name, $value)
    {
        $values = (array) $value;
        if (!$values || !$name || !$calendar_external_id) {
            return array();
        }

        $sql = "SELECT DISTINCT tee.native_event_id  
                  FROM `team_event_external` tee
                  JOIN `wa_contact_events` wce ON wce.id = tee.event_id
                  JOIN `team_event_external_params` teep ON tee.id = teep.event_external_id AND teep.`name` = :name
                WHERE tee.calendar_external_id = :calendar_external_id AND teep.`value` IN (:values)";

        return $this->query($sql, array(
            'calendar_external_id' => $calendar_external_id,
            'name' => $name,
            'values' => $values
        ))->fetchAll(null, true);
    }


    public function getMinStartForCalendar($calendar_external_id)
    {
        $calendar = $this->getCalendarModel()->getCalendar($calendar_external_id);
        return $this->getWaEventModel()->getMinStartForCalendar($calendar['calenar_id']);
    }

    public function count($calendar_external_id)
    {
        return $this->countByField('calendar_external_id', $calendar_external_id);
    }

    protected function getEvent($field, $with_params = true, $single = true, $with_wa = true)
    {
        $where = $this->getWhereByField($field, 'tee');

        if ($with_wa) {
            $sql = "
              SELECT * 
              FROM `wa_contact_events` wce
              JOIN `team_event_external` tee ON wce.id = tee.event_id
              WHERE {$where}
            ";
        } else {
            $sql = "
              SELECT * 
              FROM `team_event_external` tee
              WHERE {$where}
            ";
        }

        $events = $this->query($sql)->fetchAll('id');
        if ($with_params) {
            $event_params = $this->getParamsModel()->get(array_keys($events));
            foreach ($events as $event_id => &$event) {
                $event['params'] = ifset($event_params[$event_id], array());
            }
            unset($event);
        }

        if (!$events) {
            return $single ? false : array();
        }

        if ($single) {
            return reset($events);
        }

        return $events;
    }

    /**
     * @return teamEventExternalParamsModel
     */
    private function getParamsModel()
    {
        if (!$this->params_model) {
            $this->params_model = new teamEventExternalParamsModel();
        }
        return $this->params_model;
    }

    /**
     * @return teamWaContactEventsModel
     */
    private function getWaEventModel()
    {
        if (!$this->wa_event_model) {
            $this->wa_event_model = new teamWaContactEventsModel();
        }
        return $this->wa_event_model;
    }

    /**
     * @return teamCalendarExternalModel
     */
    private function getCalendarModel()
    {
        if (!$this->calendar_model) {
            $this->calendar_model = new teamCalendarExternalModel();
        }
        return $this->calendar_model;
    }
}
