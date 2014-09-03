<?php

/** Show links to other applications in a dialog, usually before contact deletion. */
class contactsContactsLinksAction extends waViewAction
{
    public function execute() {
        $ids = waRequest::post('id', array(), 'array_int');
        if (!$ids) {
            $ids = (int)waRequest::get('id');
            if(!$ids) {
                throw new Exception('No ids specified.');
            }
            $ids = array($ids);
        }

        // do not try to delete self
        if (in_array($this->getUser()->getId(), $ids)) {
            die('<p>'._w('You can not delete yourself.').'</p><p>'._w('Please eliminate yourself from deletion list.').'</p>');
        }
        
        $crm = new contactsRightsModel();
        $ids = $crm->getAllowedContactsIds($ids);
        if (!$ids) {
            throw new waRightsException('Access denied');
        }

        $superadmin = wa()->getUser()->getRights('webasyst', 'backend');

        $result = wa()->event('links', $ids);

        $this->view->assign('apps', wa()->getApps());
        $links  = array();
        foreach ($result as $app_id => $app_links) {
            foreach ($app_links as $contact_id => $contact_links) {
                if ($contact_links) {
                    $links[$contact_id][$app_id] = $contact_links;
                }
            }
        }

        // Do not allow non-superadmin to remove users
        if (!$superadmin) {
            $um = new waUserModel();
            $users = array_keys($um->getByField(array(
                'id' => $ids,
                'is_user' => 1
            ), 'id'));

            foreach($users as $user_id) {
                if (!isset($links[$user_id]['contacts'])) {
                    $links[$user_id]['contacts'] = array();
                }
                $links[$user_id]['contacts'][] = array('user', 1);
            }
        }

        $contact_model = new waContactModel();

        $this->view->assign('ids', $superadmin ? $ids : array_diff($ids, array_keys($links)));
        $this->view->assign('contacts', $contact_model->getName(array_keys($links)));
        $this->view->assign('superadmin', $superadmin);
        $this->view->assign('all', count($ids));
        $this->view->assign('links', $links);
    }
}

// EOF