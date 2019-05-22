<?php

class teamCalendar
{
    protected static $calendars = null;

    public static function getHtml($user_id = null, $calendar_id = null, $start = null, $period = false)
    {
        $start = $start ? $start : date('Y-m-d H:i:s');

        list($period_start, $period_end) = self::getPeriod($start, $period);

        // ДАННЫЕ ДЛЯ ФИЛЬТРА ПОЛЬЗОВАТЕЛЕЙ
        $users = teamHelper::getUsers();
        /*
        // Выбранный пользователь
        if (!isset($users[$user_id])) {
            $user_id = null;
        }
        */

        // ДАННЫЕ ДЛЯ ФИЛЬТРА КАЛЕНДАРЕЙ
        $calendars = self::getCalendars();
        // Выбранный календарь
        if (!isset($calendars[$calendar_id])) {
            $calendar_id = null;
        }

        $data = self::getData($user_id, $calendar_id, $period_start, $period_end);

        return array(
            'calendars'            => $calendars,
            'users'                => $users,
            'selected_calendar_id' => $calendar_id,
            'selected_user_id'     => $user_id,
            'period_start'         => $start,
            'data'                 => $data,
        );
    }

    public static function getCalendars($all = true)
    {
        if (!self::$calendars) {
            $ccm = new waContactCalendarsModel();
            self::$calendars = $ccm->get();
        }
        if ($all) {
            $calendars = array(
                    'all' => array(
                        'id'         => null,
                        'name'       => _w('All calendars'),
                        'bg_color'   => null,
                        'font_color' => null,
                        'icon_class' => 'calendars',
                    )
                ) + self::$calendars;
            $calendars['birthday'] = array(
                'id'         => 'birthday',
                'name'       => _w('Birthday'),
                'bg_color'   => null,
                'font_color' => null,
                'icon_class' => 'cake'
            );
            return $calendars;
        } else {
            return self::$calendars;
        }
    }

    private static function mergeRows($row1, $row2)
    {
        foreach ($row1 as $i => $v) {
            if (!$v) {
                $row1[$i] = ifempty($row2[$i], array());
            }
        }
        return $row1;
    }

    private static function getPeriod($start, $period)
    {
        // extends the period
        $ts = strtotime($start);
        $offset = date('N', $ts) - 1; // first_day_of_first_week
        if ($offset) {
            $period_start = date('Y-m-d', strtotime("-$offset days", $ts));
        } else {
            $period_start = date('Y-m-d', $ts);
        }
        if (!$period) {
            $ts = strtotime(date('Y-m-t', $ts));
            $offset = 7 - date('N', $ts); // last_day_of_last_week
            if ($offset) {
                $period_end = date('Y-m-d', strtotime("+$offset days", $ts));
            } else {
                $period_end = date('Y-m-d', $ts);
            }
        } else {
            $period_end = date(
                'Y-m-d',
                strtotime('+'.teamConfig::CALENDAR_DAYS.' days', strtotime($period_start))
            );
        }
        return array($period_start, $period_end);
    }

    private static function getEvents($user_id, $calendar_id, $period_start, $period_end)
    {
        $cem = new waContactEventsModel();

        if ($calendar_id == 'birthday') {
            $events = array();
        } else {
            $start = date('Y-m-d 00:00:00', strtotime($period_start));
            $end = date('Y-m-d 23:59:59', strtotime($period_end));
            $events = $cem->getEventsByPeriod($start, $end, $calendar_id, $user_id);

            // Filter out events from contacts current user can not see.
            $contacts = array();
            foreach ($events as $e) {
                $contacts[$e['contact_id']] = array(
                    'id' => $e['contact_id'],
                );
            }
            teamUser::keepVisible($contacts);
            foreach ($events as $i => $e) {
                if (empty($contacts[$e['contact_id']])) {
                    unset($events[$i]);
                }
            }
            $events = array_values($events);
        }
        if (!$calendar_id || $calendar_id == 'birthday') {
            $users = teamHelper::getUsers();
            foreach ($users as $i => &$c) {
                if (empty($c['birth_day']) || empty($c['birth_month']) || ($user_id && $user_id != $c['id'])) {
                    continue;
                }
                $year = date('Y', strtotime($period_start));
                while ($year <= date('Y', strtotime($period_end))) {

                    $c['birthday'] = $year
                        .'-'.str_pad($c['birth_month'], 2, '0', STR_PAD_LEFT)
                        .'-'.str_pad($c['birth_day'], 2, '0', STR_PAD_LEFT);
                    $events[] = array(
                        'id'           => null,
                        'start'        => $c['birthday'],
                        'end'          => $c['birthday'],
                        'summary'      => 'Birthday',
                        'calendar_id'  => 'birthday',
                        'contact_id'   => $c['id'],
                        'is_allday'    => 1,
                        'birthday_str' => waDateTime::format('shortdate', strtotime($c['birthday']), waDateTime::getDefaultTimeZone())
                    );
                    $year = date('Y', strtotime('+1 year', strtotime($year.'-01-01')));
                }
            }
            unset($c);
            // usort($users, wa_lambda('$a,$b', 'return $a["birthday"] > $b["birthday"];'));
        }

        if (!$events) { // for empty calendar
            $events = array(
                array(
                    'id'    => null,
                    'start' => '1970-01-01',
                    'end'   => '1970-01-01',
                )
            );
        }
        return $events;
    }

    public static function countDays($start, $end)
    {
        if (!wa_is_int($start)) {
            $start = strtotime($start);
        }
        if (!wa_is_int($end)) {
            $end = strtotime($end);
        }

        $start = date('Y-m-d 00:00:00', $start);
        $end = date('Y-m-d 23:59:59', $end);

        return ceil((strtotime($end) - strtotime($start)) / (3600 * 24));
    }

    private static function getData($user_id, $calendar_id, $period_start, $period_end)
    {
        $events = self::getEvents($user_id, $calendar_id, $period_start, $period_end);

        $is_admin = wa()->getUser()->isAdmin('team');
        if (!$is_admin) {
            $tcm = new teamWaContactCalendarsModel();
            $calendars = $tcm->getCalendars($calendar_id);
        }

        $data = array();
        foreach ($events as $e) {
            $date = $period_start;
            $w = 0; // week of month
            while ($date < $period_end) {
                if (empty($e['is_allday'])) {
                    $start_day = waDateTime::format('Y-m-d', $e['start']);
                    $end_day = waDateTime::format('Y-m-d', $e['end']);
                } else {
                    $start_day = waDateTime::date('Y-m-d', $e['start']);
                    $end_day = waDateTime::date('Y-m-d', $e['end']);
                }
                $event_row = $days_data = array();
                $day = waDateTime::date('Y-m-d', $date);
                $not_empty_week = false;
                $line_index = array();
                for ($d = 1; $d <= 7; $d++) { // day of week
                    $event_row[$d] = array();
                    if ($start_day <= $day && $end_day >= $day) {
                        if (empty($event_row[$d - 1])) { // || $event_row[$d - 1]['id'] != $event_row[$d]['id']
                            $e['colspan'] = 1;

                            $can_edit = false;
                            if ($e['calendar_id'] !== 'birthday') {
                                if ($is_admin || !empty($calendars[$e['calendar_id']]['can_edit'])) {
                                    $can_edit = teamUser::canEdit($e['contact_id']);
                                }
                            }

                            $event_row[$d] = array_merge($e, array(
                                'is_status' => !empty($e['is_status']),
                                'day_count' => self::countDays($e['start'], $e['end']),
                                'can_edit'  => $can_edit,
                            ));
                        } else {
                            $event_row[$d] = $event_row[$d - 1];
                            $event_row[$d]['colspan']++;
                            $event_row[$d - 1]['colspan'] = 0;
                        }
                        $event_row[$d]['date'] = $day;
                        //$count[$day] = isset($count[$day]) ? $count[$day] + 1 : 1;
                        $not_empty_week = true;
                        if (!empty($data[$w]['events'])) {
                            foreach ($data[$w]['events'] as $idx => $row) {

                                if (!empty($row[$d])) {
                                    $line_index[$idx] = -1;
                                } elseif (empty($line_index[$idx]) || $line_index[$idx] != -1) {
                                    $line_index[$idx] = 1;
                                }
                            }
                        }
                    }
                    $days_data[$d] = array(
                        'date' => $day.' 00:00:00',
                    );
                    $day = date('Y-m-d', strtotime('+1 day', strtotime($day)));
                }
                foreach ($line_index as $idx => $val) {
                    if ($val < 0) {
                        unset($line_index[$idx]);
                    }
                }
                if ($not_empty_week) {
                    if (!$line_index) {
                        $data[$w]['events'][] = $event_row;
                    } else {
                        $idx = array_keys($line_index);
                        $i = array_shift($idx);
                        $data[$w]['events'][$i] = self::mergeRows($data[$w]['events'][$i], $event_row);
                        ksort($data[$w]['events'][$i]);
                    }
                }
                $data[$w]['days_data'] = $days_data;

                $date = date('Y-m-d', strtotime('+1 week', strtotime($date)));
                $w++;
            }
        }
        ksort($data);

        return $data;
    }
}
