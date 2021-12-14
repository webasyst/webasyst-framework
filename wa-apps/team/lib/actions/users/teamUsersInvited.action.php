<?php

class teamUsersInvitedAction extends teamContentViewAction
{
    public function execute()
    {
        if (!teamHelper::hasRights('add_users')) {
            throw new waRightsException(_w('Access denied'));
        }

        $invited = self::getInvited();
        $contacts = array();
        if ($invited) {
            $contacts = teamUser::getList('/id/'.join(',', array_keys($invited)), array(
                'order' => 'last_datetime DESC',
                'access_rights' => false,
            ));
            foreach ($contacts as &$c) {
                $c['update_datetime'] = $invited[$c['id']]['create_datetime'];
                $c['expires_in'] = self::timeLeft($invited[$c['id']]['expire_datetime']);
            }
            unset($c);
            teamUser::convertFieldToUtc($contacts);
        }
        $this->view->assign(array(
            'contacts' => $contacts,
        ));
    }

    public static function getInvited()
    {
        $atm = new waAppTokensModel();
        $cm = new waContactModel();
        $sql = "SELECT t.* FROM {$atm->getTableName()} t
            INNER JOIN {$cm->getTableName()} c ON c.id=t.contact_id
            WHERE t.app_id='team' AND t.type='user_invite' AND c.is_user = 0 AND t.expire_datetime > '"
            .date('Y-m-d H:i:s')."'
            ORDER BY t.expire_datetime DESC";
        return $atm->query($sql)->fetchAll('contact_id', true);
    }

    public static function timeLeft($date) {
        $expire_time = strtotime($date) - time();
        if($expire_time <= 0){
            return '';
        }

        //$days = ceil($expire_time/86400);
        $hours = ceil($expire_time/3600);
        $minutes = ceil($expire_time/60);

        $time_left = '';
        if($hours > 0) {
            $time_left = _w('%d hour', '%d hours', $hours);
        }else if($minutes > 0) {
            $time_left = _w('%d minute', '%d minutes', $minutes);
        }

        return $time_left;
    }
}
