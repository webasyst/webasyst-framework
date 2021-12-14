<?php

class teamUsersAction extends teamContentViewAction
{
    protected static $offset = 0;
    protected static $limit = 50;

    public function execute()
    {
        $sort = $this->getSort();
        $contacts = teamUser::getList('users', array(
            'order' => $sort,
            'convert_to_utc' => 'update_datetime',
            'additional_fields' => array(
                'update_datetime' => 'c.create_datetime',
            ),
            'fields' => teamUser::getFields(),
        ));

        // Redirect on first login
        if (wa()->getUser()->isAdmin('webasyst') && count($contacts) < 2) {
            $asm = new waAppSettingsModel();
            if (!$asm->getByField(array('app_id' => wa()->getApp(), 'name' => 'first_login'))) {
                $asm = new waAppSettingsModel();
                $asm->insert(array('app_id' => wa()->getApp(), 'name' => 'first_login', 'value' => date('Y-m-d H:i:s')));
                $this->redirect(wa()->getConfig()->getBackendUrl(true).wa()->getApp().'/welcome/');
            }
        }

        $users_state = $this->usersState($contacts);

        $this->view->assign(array(
            'contacts' => $contacts,
            'sort'     => $sort,
            'online' => $users_state['online'],
            'offline' => $users_state['offline'],
        ));
    }

    protected function getSort()
    {
        $sort = waRequest::request(
            'sort',
            wa()->getUser()->getSettings(wa()->getApp(), 'sort', 'last_seen'),
            waRequest::TYPE_STRING_TRIM
        );
        if (waRequest::request('sort')) {
            $csm = new waContactSettingsModel();
            $csm->set(wa()->getUser()->getId(), wa()->getApp(), 'sort', $sort);
        }
        return $sort;
    }

    protected function usersState($contacts) {
        $online =  $offline = [];
        foreach ($contacts as $c) {
            if ($c['_online_status'] === 'online' || $c['_online_status'] === 'idle') {
                $online[$c['id']] = $c;
            } else {
                if (!empty($c['last_datetime'])) {
                    $c['last_datetime_formatted'] = self::formatLastSeenDate($c['last_datetime']);
                }
                $offline[$c['id']] = $c;
            }
        }
        return [
            'online' => $online,
            'offline' => $offline
        ];
    }

    protected static function formatLastSeenDate($time) {
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
