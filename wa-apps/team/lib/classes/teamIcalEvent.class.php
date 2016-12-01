<?php

class teamIcalEvent
{
    protected $data;

    const DATE_TIME_UTC = 'Ymd\THis\Z';
    const DATE_YMD = 'Ymd';

    /**
     * teamIcalEvent constructor.
     * @param string $vevent
     */
    public function __construct($vevent = '')
    {
        foreach (explode("\n", $vevent) as $attribute) {
            $attribute = trim($attribute);
            if (!$attribute) {
                continue;
            }

            list($definition, $value) = $this->split(':', $attribute, 2);
            $definition = $this->split(';', $definition);
            $name = $this->shift($definition);
            $def_map = array();
            foreach ($definition as $def_prop) {
                $def_prop = trim($def_prop);
                if (!$def_prop) {
                    continue;
                }
                list($dp_name, $dp_val) = $this->split('=', $def_prop, 2);
                if ($dp_name) {
                    $def_map[$dp_name] = $dp_val;
                }
            }
            $this->data[$name] = array(
                'def' => $def_map,
                'val' => $value
            );
        }
    }

    public static function parseAppEvent($event)
    {
        $data = array(
            'BEGIN' => 'VEVENT'
        );
        foreach (array('uid', 'summary', 'description', 'location', 'sequence') as $field) {
            if (isset($event[$field])) {
                $data[strtoupper($field)] = $event[$field];
            }
        }
        if (isset($event['is_allday'])) {
            $is_allday = $event['is_allday'];
            if (isset($event['start'])) {
                $start_time = strtotime($event['start']);
                if ($is_allday) {
                    $data['DTSTART;VALUE=DATE'] = date(self::DATE_YMD, $start_time);
                } else {
                    $data['DTSTART;VALUE=DATE-TIME'] = self::getUtcDatetime($start_time);
                }
            }
            if (isset($event['end'])) {
                $end_time = strtotime($event['end']);
                if ($is_allday) {
                    $end_time = strtotime('+1 day', $end_time);
                    $data['DTEND;VALUE=DATE'] = date(self::DATE_YMD, $end_time);
                } else {
                    $data['DTEND;VALUE=DATE-TIME'] = self::getUtcDatetime($end_time);
                }
            }
        }
        $data['END'] = 'VEVENT';
        foreach ($data as $field => $value) {
            $data[$field] = $field . ':' . $value;
        }
        return join("\n", $data);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function toAppEvent()
    {
        $event = array(
            'uid' => $this->getUID(),
            'native_event_id' => $this->getUID(),
            'create_datetime' => $this->reformatDatetime($this->getCreated()),
            'update_datetime' => $this->reformatDatetime($this->getLastModified()),
            'summary' => $this->getSummary(),
            'description' => $this->getDescription(),
            'location' => $this->getLocation(),
            'sequence' => (int) $this->getSequence(),
            'is_status' => 0
        );

        foreach (array('DTSTART' => 'start', 'DTEND' => 'end') as $field => $event_field) {
            $value = $this->getField($field, false);
            $dt_type = ifset($value['def']['VALUE'], 'DATE-TIME');
            if ($dt_type === 'DATE-TIME') {
                $tz = ifset($value['def']['TZID']);
                $val = $this->reformatDatetime($value['val'], $tz);
            } else {
                $val = $this->reformatDate($value['val'], true);
            }
            $event[$event_field] = $val;
            if ($field === 'DTSTART') {
                $event['is_allday'] = $dt_type === 'DATE';
            }
        }

        if ($event['is_allday']) {
            $event['end'] = $this->reformatDate(strtotime('-1 day', strtotime($event['end'])));
        }

        return $event;
    }

    public function getUID()
    {
        return $this->getField('UID');
    }

    public function getCreated()
    {
        return $this->getField('CREATED');
    }

    public function getLastModified()
    {
        return $this->getField('LAST-MODIFIED');
    }

    public function getSummary()
    {
        return $this->getField('SUMMARY');
    }

    public function getDescription()
    {
        return $this->getField('DESCRIPTION');
    }

    public function getLocation()
    {
        return $this->getField('LOCATION');
    }

    public function getSequence()
    {
        return $this->getField('SEQUENCE');
    }

    public function getTransp()
    {
        return $this->getField('TRANSP');
    }

    private function reformatDatetime($datetime, $tz = null)
    {
        if ($tz !== null) {
            $dtz = waDateTime::getDefaultTimeZone();
            if ($tz != $dtz) {
                $date_time = new DateTime($datetime, new DateTimeZone($tz));
                $date_time->setTimezone(new DateTimeZone($dtz));
                return $date_time->format('Y-m-d H:i:s');
            }
        }
        return date('Y-m-d H:i:s', strtotime($datetime));
    }

    private function reformatDate($datetime, $force = false)
    {
        if ($force) {
            $time = strtotime($datetime);
        } else {
            $time = wa_is_int($datetime) ? $datetime : strtotime($datetime);
        }
        return date('Y-m-d', $time);
    }

    private static function getUtcDatetime($time)
    {
        $tz = waDateTime::getDefaultTimeZone();
        date_default_timezone_set('UTC');
        $datetime = date(self::DATE_TIME_UTC, $time);
        date_default_timezone_set($tz);
        return $datetime;
    }

    private function getField($field, $val = true)
    {
        $value = array();
        if (isset($this->data[$field])) {
            $value = (array) $this->data[$field];
        }
        if (!isset($value['val'])) {
            $value['val'] = '';
        }
        $value['val'] = (string) $value['val'];
        if ($val) {
            return $value['val'];
        }
        if (!isset($value['def'])) {
            $value['def'] = array();
        }
        $value['def'] = (array) $value['def'];
        return $value;
    }

    private function split($sep, $str, $parts_num = null)
    {
        if ($parts_num === null) {
            return explode($sep, $str);
        }
        $res = explode($sep, $str, $parts_num);
        for ($i = 0; $i < $parts_num; $i += 1) {
            $res[$i] = (string) ifset($res[$i], '');
        }
        return $res;
    }

    private function shift(&$ar)
    {
        return (string) array_shift($ar);
    }
}
