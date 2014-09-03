<?php

class contactsGroupsAddAction extends waViewAction
{
    public function execute() 
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }
        
        $group_model = new waGroupModel();
        $groups = $group_model->getAll();
        $this->view->assign(array(
            'groups' => $groups
        ));
        
    }
}

// EOF