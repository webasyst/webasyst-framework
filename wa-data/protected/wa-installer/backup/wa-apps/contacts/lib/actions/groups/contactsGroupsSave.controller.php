<?php

/** Save data from groups editor form (members and group name) */
class contactsGroupsSaveController extends waJsonController
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Access denied'));
        }

        $group_model = new waGroupModel();

        // Create a group or retreive by id
        $id = waRequest::post('id');
        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        $icon = waRequest::post('icon', null, waRequest::TYPE_STRING_TRIM);

        $data = array();
        if ($name || $name === '0') {
            $data['name'] = $name;
        }
        if ($icon) {
            $data['icon'] = $icon;
        }
        
        if (!$id) {
            if (!isset($data['name'])) {
                throw new waException('No group id and no name given.');
            }
            $id = $group_model->insert($data);
            $this->logAction('group_add', $id);
        } else {
            $group_model->updateById($id, $data);
        }

        $group = $group_model->getById($id);
        if (!$group) {
            throw new waException('No group with such id: '.$id);
        }
        $group = $group_model->getById($id);
        $group['name'] = htmlspecialchars($group['name']);
        $this->response['id'] = $id;
        $this->response['group'] = $group;
        
    }
}

// EOF