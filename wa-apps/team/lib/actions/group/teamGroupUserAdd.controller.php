<?php

class teamGroupUserAddController extends waJsonController
{
    public function execute()
    {
        $data = teamGroup::checkUserGroup(
            waRequest::post('group_id', null, waRequest::TYPE_INT),
            waRequest::post('user_id', null, waRequest::TYPE_INT)
        );
        if (!teamHelper::hasRights('manage_group.'.$data['group_id'])) {
            throw new waRightsException();
        }
        $ugm = new waUserGroupsModel();
        $ug = $ugm->getByField($data);
        if (!$ug) {
            $ugm->add($data['contact_id'], $data['group_id']);
        }
        $this->logAction('user_group_add', $data['group_id'], $data['contact_id']);
    }
}
