<?php

class teamUsersOnlineAction extends teamContentViewAction
{
    public function execute()
    {
        $contacts = teamUser::getList('users', array(
            'order' => 'last_seen',
            'fields' => teamUser::getFields('default').',_online_status',
        ));

        $online = $offline = array();
        foreach ($contacts as $c) {
            if ($c['_online_status'] == 'online') {
                $online[$c['id']] = $c;
            } else {
                if (!empty($c['last_datetime'])) {
                    $c['last_datetime_formatted'] = self::formatLastSeenDate($c['last_datetime']);
                }
                $offline[$c['id']] = $c;
            }
        }

        $this->view->assign(array(
            'online' => $online,
            'offline' => $offline,
        ));
    }

    protected static function formatLastSeenDate($time)
    {
        $date_time = new DateTime($time);
        $date_time_today = new DateTime();
        $date_time_tomorrow = new DateTime('+1 day');
        $date_time_yesterday = new DateTime('-1 day');

        $timezone = wa()->getUser()->getTimezone();
        if ($timezone) {
            $date_timezone = new DateTimeZone($timezone);
            $date_time->setTimezone($date_timezone);
            $date_time_today->setTimezone($date_timezone);
            $date_time_tomorrow->setTimezone($date_timezone);
            $date_time_yesterday->setTimezone($date_timezone);
        }

        $day = $date_time->format('Y z');
        if ($day === $date_time_today->format('Y z')) {
            $result = mb_strtolower(_w('Today'));
        } else if ($day === $date_time_tomorrow->format('Y z')) {
            $result = mb_strtolower(_w('Tomorrow'));
        } else if ($day === $date_time_yesterday->format('Y z')) {
            $result = mb_strtolower(_w('Yesterday'));
        } else {
            $result = waDateTime::date(waDateTime::getFormat('humandate'), $time, $timezone);
        }

        return $result.' '.waDateTime::date(waDateTime::getFormat('time'), $time, $timezone);
    }
}
