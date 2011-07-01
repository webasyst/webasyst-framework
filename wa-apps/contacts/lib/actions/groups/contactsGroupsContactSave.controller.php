<?php

/** Save a set of groups a contact belongs to. */
class contactsGroupsContactSaveController extends waJsonController
{
    public function execute()
    {
        if (! ( $id = (int)waRequest::get('id'))) {
            throw new waException('Contact id not specified.');
        }

        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $groups = waRequest::post('groups', array(), 'array_int');
        $ugm = new waUserGroupsModel();
        $ugm->delete($id, array());
        if ($groups) {
            $ugm->add(array_map(wa_lambda('$gid', 'return array('.$id.', $gid);'), $groups));
        }
        $this->response = 'ok';
    }
}

// EOF