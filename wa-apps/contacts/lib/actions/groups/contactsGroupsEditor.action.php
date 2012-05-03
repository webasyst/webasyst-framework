<?php

/** Group editor: edit name and a set of group members. */
class contactsGroupsEditorAction extends waViewAction
{
    public function execute() {
        // only allowed to global admin
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException('Access denied.');
        }

        $collection = new contactsCollection('users/all');

        $group = null;
        $memberIds = array();
        if ( ( $id = waRequest::get('id'))) {
            $group_model = new waGroupModel();
            $group = $group_model->getById($id);
        }

        if ($group) {
            $user_groups_model = new waUserGroupsModel();
            $memberIds = $user_groups_model->getContactIds($id);
        }

        $users = $collection->getContacts('id,name'); // array(id => array(id=>...,name=>...))
        $members = array();
        foreach($memberIds as $mid) {
            if (isset($users[$mid])) {
                $members[$mid] = $users[$mid];
                unset($users[$mid]);
            }
        }

        usort($members, array($this, '_cmp'));
        usort($users, array($this, '_cmp'));

        $this->view->assign('group', $group);
        $this->view->assign('notIncluded', $users);
        $this->view->assign('members', $members);
    }

    function _cmp($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }
}

// EOF