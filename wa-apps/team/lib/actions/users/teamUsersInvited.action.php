<?php

class teamUsersInvitedAction extends teamContentViewAction
{
    public function execute()
    {
        $invited = self::getInvited();
        $contacts = array();
        if ($invited) {
            $contacts = teamUser::getList('/id/'.join(',', array_keys($invited)), array(
                'order' => 'last_datetime DESC',
                'access_rights' => false,
            ));
            foreach ($contacts as &$c) {
                $c['update_datetime'] = $invited[$c['id']]['create_datetime'];
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
            .date('Y-m-d H:i:s')."'";
        return $atm->query($sql)->fetchAll('contact_id', true);
    }
}
