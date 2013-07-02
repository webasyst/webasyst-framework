<?php

/** Save data from groups editor form (members and group name) */
class contactsGroupsSaveController extends waJsonController
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $group_model = new waGroupModel();

        // Create a group or retreive by id
        $id = waRequest::post('id');
        $name = waRequest::post('name');

        if (!$id) {
            if (!$name && $name !== '0') {
                throw new waException('No group id and no name given.');
            }

            $id = $group_model->add($name);
            $this->log('group_add', 1);
        } else if ($name || $name === '0') {
            $group_model->updateById($id, array('name' => $name));
        }

        if (!$id) {
            throw new waException('Still no id here...'); // should not happen
        }

        $group = $group_model->getById($id);
        if (!$group) {
            throw new waException('No group with such id: '.$id);
        }

        $this->response['id'] = $id;

        $users = waRequest::post('users', array(), 'array_int');

        $type = waRequest::post('user_operation');
        $user_groups_model = new waUserGroupsModel();
        switch ($type) {
            case 'del':
                if ($users) {
                    $user_groups_model->delete($users, $id);
                }
                break;
            case 'set':
                $user_groups_model->emptyGroup($id);
                // breakthrough
            case 'add':
            default:
                if (!$users){
                    break;
                }
                $data = array();
                foreach ($users as $contact_id) {
                    $data[] = array($contact_id, $id);
                }
                $user_groups_model->add($data);

                if ($type == 'set') {
                    $group_model->updateCount($id, count($users));
                }

                break;
        }
    }
}

// EOF