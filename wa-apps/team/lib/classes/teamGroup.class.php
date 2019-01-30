<?php

class teamGroup
{
    public static function getAppsInfo($group)
    {
        $apps = wa()->getApps();
        $id = -$group['id'];
        $crm = new waContactRightsModel();
        if (!$crm->get($id, 'webasyst', 'backend')) {
            $group['is_admin'] = false;
            foreach ($apps as $a) {
                $group['rights'] = min($crm->get($id, $a['id'], 'backend'), 2);
                $group['access'][$a['id']] = $group['rights'];
            }
        } else {
            $group['is_admin'] = true;
            $group['access'] = 2;
        }
        $group['uri'] = wa()->getUrl() . 'group/' . $group['id'] . '/';

        return $group;
    }

    public static function checkUserGroup($group_id, $user_id)
    {
        $cm = new waContactModel();
        $gm = new waGroupModel();
        $user = $cm->getById($user_id);
        $group = $gm->getById($group_id);
        if (!$user_id || !$user || !$group_id || !$group) {
            throw new waException('Group not found');
        }
        if (!$user['is_user']) {
            throw new waRightsException();
        }
        return array(
            'contact_id' => $user_id,
            'group_id' => $group_id,
        );
    }
}
