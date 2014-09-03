<?php

/** Save a set of groups a contact belongs to. */
class contactsGroupsContactSaveController extends waJsonController
{
    public function execute()
    {
        $ids = waRequest::request('id', array(), 'array_int');
        if (!$ids) {
            throw new waException('Contact id not specified.');
        }

        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $groups = waRequest::post('groups', array(), 'array_int');
        
        $counters = array();
        $ugm = new waUserGroupsModel();
        if ($this->getRequest()->request('set')) {
            foreach ($ids as $id) {
                $ugm->delete($id, array());
            }
        }
        foreach ($ids as $id) {
            if ($groups) {
                $ugm->add(array_map(wa_lambda('$gid', 'return array('.$id.', $gid);'), $groups));
            }
        }
        $gm = new waGroupModel();
        foreach ($groups as $gid) {
            $cnt = $ugm->countByField(array('group_id' => $gid));
            $gm->updateCount($gid, $cnt);
            $counters[$gid] = $cnt; 
        }
        $this->response['counters'] = $counters;
        
        $this->response['message'] = _w("%d user has been added", "%d users have been added", count($ids));
        $this->response['message'] .= ' ';
        $this->response['message'] .= _w("to %d group", "to %d groups", count($groups));
    }
}

// EOF