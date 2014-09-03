<?php

class contactsGroupsDeleteFromController extends waJsonController
{
    public function execute() 
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }
        $contacts = $this->getRequest()->post('contacts', array(), 'array_int');
        $groups = $this->getRequest()->post('groups', array(), 'array_int');
        
        if (!$contacts || !$groups) {
            return;
        }
        
        $ugm = new waUserGroupsModel();
        $gm = new waGroupModel();
        foreach ($contacts as $id) {
            if ($groups) {
                $ugm->delete($id, $groups);
            }
        }
        
        $counters = array();
        foreach ($groups as $gid) {
            $cnt = $ugm->countByField(array('group_id' => $gid));
            $gm->updateCount($gid, $cnt);
            $counters[$gid] = $cnt; 
        }
        
        $contacts_count = count($contacts);
        $groups_count = count($groups);
        $this->response['message'] = sprintf(_w("%d user excluded", "%d users excluded", $contacts_count), $contacts_count);
        $this->response['message'] .= ' ';
        $this->response['message'] .= sprintf(_w("from %d group", "from %d groups", $groups_count), $groups_count);
        $this->response['counters'] = $counters;
    }
}

// EOF