<?php

class teamGroupUserRemoveController extends waJsonController
{
    public function execute()
    {
        $group_id = waRequest::post('group_id', null, waRequest::TYPE_INT);
        $data = teamGroup::checkUserGroup(
            $group_id,
            waRequest::post('user_id', null, waRequest::TYPE_INT)
        );
        if (!teamHelper::hasRights('manage_group.'.$group_id)) {
            throw new waRightsException();
        }
        $ugm = new waUserGroupsModel();
        $ugm->delete($data['contact_id'], $data['group_id']);

        $this->logAction('user_group_remove', $data['group_id'], $data['contact_id']);
    }
}
