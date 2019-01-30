<?php

class teamGroupManageAction extends teamContentViewAction
{
    public function execute()
    {
        $group_id = waRequest::get('id', null, 'int');
        $group_id = waRequest::param('id', $group_id, 'int');
        if (!$group_id) {
            throw new waException('Group not found', 404);
        }
        if (!teamHelper::hasRights('manage_group.'.$group_id)) {
            throw new waRightsException();
        }
        $gm = new waGroupModel();
        $group = $gm->getById($group_id);
        if (!$group) {
            throw new waException('Group not found', 404);
        }

        // All users
        $all_users = teamUser::getList('users/all', array(
            'order' => 'from_user_settings',
            'fields' => 'minimal',
        ));

        // Belong to group
        $group_users = teamUser::getList('group/'.$group_id, array(
            'order' => 'from_user_settings',
            'fields' => 'minimal',
        ));

        // Do not belong to group
        $other_users = array_diff_key($all_users, $group_users);

        $this->view->assign(array(
            'group' => $group,
            'group_users' => $group_users,
            'other_users' => $other_users,
        ));
    }
}
