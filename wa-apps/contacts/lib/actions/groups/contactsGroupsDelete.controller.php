<?php

/** Delete a user group */
class contactsGroupsDeleteController extends waJsonController
{
    public function execute()
    {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Access denied'));
        }

        if (! ( $id = waRequest::post('id'))) {
            throw new waException('no id');
        }

        $group_model = new waGroupModel();
        $group_model->delete($id);
        $this->response['message'] = _w('Group has been deleted');
    }
}

// EOF