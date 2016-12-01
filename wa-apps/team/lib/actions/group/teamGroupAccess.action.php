<?php

class teamGroupAccessAction extends teamContentViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waRightsException();
        }
        $group_id = waRequest::param('id', waRequest::get('id', null, waRequest::TYPE_INT), waRequest::TYPE_INT);
        if (!$group_id) {
            throw new waException('Group not found');
        }
        $gm = new waGroupModel();
        $group = $gm->getById($group_id);
        if (!$group) {
            throw new waException('Group not found');
        }

        $contact_rights_model = new waContactRightsModel();
        $access = $contact_rights_model->getApps($group_id, 'backend', false, false) + array('webasyst' => 0);
        $apps = teamHelper::appsWithAccessRights($access);

        $this->view->assign(array(
            'access_types' => teamHelper::getAccessTypes(),
            'group' => $group,
            'apps' => $apps,
        ));
    }
}
