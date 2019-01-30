<?php

class teamIcsCalendarParser
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var int
     */
    protected $len;

    public function __construct($file, $options = array())
    {
        $this->content = (string) @file_get_contents((string) $file);
        $this->content = trim($this->content);
        $this->len = strlen($this->content);
    }

    public function getField($field)
    {
        if ($this->len <= 0) {
            return '';
        }
        $start = strpos($this->content, $field . ':');
        if ($start === false) {
            return '';
        }
        $end = strpos($this->content, "\n", $start);
        if ($end === false) {
            return '';
        }
        if ($end <= $start) {
            return '';
        }
        $substr = trim(substr($this->content, $start, $end - $start));
        $parts = explode(':', $substr);
        $value = trim(ifset($parts[1], ''));
        return strlen($value) > 0 ? $value : '';
    }

    public function hasBeginVCalendarStatement()
    {
        return strpos($this->content, 'BEGIN:VCALENDAR') !== false;
    }

    public function hasEndVCalendarStatement()
    {
        return strpos($this->content, 'END:VCALENDAR') !== false;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getEvents($filter = array())
    {
        $start_ts = ifset($filter['start_ts']);

        $events = array();

        $pos = 0;
        while ($pos < $this->len) {
            $begin = strpos($this->content, 'BEGIN:VEVENT', $pos);
            if ($begin === false) {
                break;
            }
            $end = strpos($this->content, 'END:VEVENT', $begin);
            if ($end === false || $end <= $begin) {
                break;
            }
            $end += 10; // length of 'END:VEVENT'
            $vevent = substr($this->content, $begin, $end - $begin);
            $pos = $end;

            $ical_event = new teamIcalEvent($vevent);
            $event = $ical_event->toAppEvent();
            $checksum = md5($vevent);
            $event['checksum'] = $checksum;
            if ($start_ts === null || strtotime($event['start']) >= $start_ts) {
                $events[] = $event;
            }
        }

        return $events;
    }

    public function __destruct()
    {
        $this->content = '';
    }
}
