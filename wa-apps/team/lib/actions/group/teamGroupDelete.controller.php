<?php
class teamGroupDeleteController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('webasyst')) { // !teamHelper::hasRights('edit_groups')
            throw new waRightsException();
        }
        $group_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $gm = new teamWaGroupModel();
        $group = $gm->getById($group_id);
        if (!$group_id || !$group) {
            throw new waException('Group not found');
        }
        $gm->deleteGroup($group_id);

        $this->logAction('group_delete', $group_id);
    }
}
