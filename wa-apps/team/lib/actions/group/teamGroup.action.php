<?php

class teamGroupAction extends teamUsersAction
{
    public function execute()
    {
        $group_id = waRequest::param('id', waRequest::get('id', null, waRequest::TYPE_INT), waRequest::TYPE_INT);
        if (!$group_id) {
            throw new waException('Group not found');
        }
        if (wa()->getUser()->getRights('team', 'manage_users_in_group.'.$group_id) < 0) {
            throw new waRightsException();
        }
        $gm = new teamWaGroupModel();
        $group = $gm->getGroup($group_id);
        if (!$group) {
            $this->setTemplate('templates/actions/404.html');
            $this->view->assign('error', _w('Group not found'));
        } else {
            $sort = $this->getSort();

            $tasm = new teamWaAppSettingsModel();
            $map_adapter = $tasm->getMapAdapter();

            $contacts = teamUser::getList('group/' . $group_id, array(
                'order' => $sort,
                'access_rights' => false,
                'convert_to_utc' => 'update_datetime',
                'additional_fields' => array(
                    'update_datetime' => 'cg.datetime',
                ),
            ));

            $this->view->assign(array(
                'group'            => $group,
                'contacts'         => $contacts,
                'can_manage_group' => teamHelper::hasRights('manage_group.' . $group_id),
                'sort'             => $sort,
                'map_adapter'     => $map_adapter,
            ));
        }
    }
}
