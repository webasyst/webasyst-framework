<?php
class teamGroupEditAction extends waViewAction
{
    public function execute()
    {
        $gm = new teamWaGroupModel();

        $group_id = waRequest::get('id', null, waRequest::TYPE_INT);
        if ($group_id) {

            if (!teamHelper::hasRights('manage_group.'.$group_id)) {
                throw new waRightsException();
            }
            $group = $gm->getGroup($group_id);
            if (!$group) {
                throw new waException('Group not found');
            }
        } else {

            if (!teamHelper::hasRights('add_groups')) {
                throw new waRightsException();
            }

            $group = $gm->getEmptyRecord(array('type' => $this->getType()));

        }

        $tasm = new teamWaAppSettingsModel();
        $this->view->assign(array(
            'group' => $group,
            'map_adapter' => $tasm->getMapAdapter()
        ));
    }

    public function getType()
    {
        return ((string) $this->getRequest()->get('type')) === 'location' ? 'location' : 'group';
    }
}
