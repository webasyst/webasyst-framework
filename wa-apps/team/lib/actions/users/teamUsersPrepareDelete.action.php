<?php
/** Show links to other applications in a dialog, usually before contact deletion. */
class teamUsersPrepareDeleteAction extends waViewAction
{
    protected function getIds()
    {
        $ids = waRequest::post('id', array(), 'array_int');
        if (!$ids) {
            $ids = waRequest::get('id', null, 'int');
            if (!$ids) {
                throw new waException('No ids specified.');
            }
            $ids = array($ids);
        }
        return $ids;
    }

    public function execute()
    {
        $ids = array();
        foreach ($this->getIds() as $id) {
            $ids[$id] = $id;
        }

        $is_superadmin = wa()->getUser()->getRights('webasyst', 'backend');

        $links  = array(); // contact_id => app_id => [role=>string, links_number=>int]
        $not_allowed = array(); // contact_id => string: reason why you can't delete a contact
        $allowed_ids = $ids;

        // do not try to delete self
        if (isset($allowed_ids[wa()->getUser()->getId()])) {
            $not_allowed[wa()->getUser()->getId()] = _w('You cannot delete yourself.');
            unset($allowed_ids[wa()->getUser()->getId()]);
        }

        // Do not allow non-superadmin to remove users
        if ($allowed_ids && !$is_superadmin) {
            $um = new waUserModel();
            $users = array_keys($um->getByField(array(
                'id' => $allowed_ids,
                'is_user' => 1,
            ), 'id'));

            foreach ($users as $user_id) {
                $not_allowed[$user_id] = _w('Only Administrator can delete backend users.');
                unset($allowed_ids[$user_id]);
            }
        }

        // Do not allow to delete contacts unless user has full access to Team app
        if ($allowed_ids && !wa()->getUser()->isAdmin('team')) {
            foreach ($allowed_ids as $contact_id) {
                $not_allowed[$contact_id] = _w('You need full access to Team app to be able to delete contacts.');
            }
            $allowed_ids = array();
        }

        // Count links to other applications
        if ($allowed_ids) {
            foreach (wa()->event(array('contacts', 'links'), $allowed_ids) as $app_id => $app_links) {
                foreach ($app_links as $contact_id => $contact_links) {
                    if ($contact_links) {
                        $links[$contact_id][$app_id] = $contact_links;
                        if (!$is_superadmin) {
                            unset($allowed_ids[$contact_id]);
                            //$not_allowed[$contact_id] = _w('Only Administrator can delete contacts that have links to other applications.');
                        }
                    }
                }
            }
        }

        // Prepare contacts for template
        $contacts = array();
        $contact_model = new waContactModel();
        $notable_contact_ids = array_keys($not_allowed + $links);
        if ($notable_contact_ids) {
            $contact_names = $contact_model->select('id,name,photo')->where('id IN (?)', array($notable_contact_ids))->fetchAll('id');
        } else {
            $contact_names = array();
        }
        foreach ($notable_contact_ids as $contact_id) {
            $c = ifset($contact_names[$contact_id]);
            $contacts[$contact_id] = array(
                'id' => $contact_id,
                'name' => ifset($c['name'], 'deleted contact_id='.$contact_id),
                'photo' => waContact::getPhotoUrl($contact_id, ifset($c['photo']), 20, 20, 'person'),
                'not_allowed_reason' => ifset($not_allowed[$contact_id]),
                'links' => ifset($links[$contact_id]),
            );
        }

        $allowed_ids = join(',', $allowed_ids);
        wa()->getStorage()->set('team_allowed_deletion_ids', $allowed_ids);

        $this->view->assign(array(
            'apps' => wa()->getApps(),
            'allowed_ids' => $allowed_ids,
            'is_superadmin' => $is_superadmin,
            'total_count_requested' => count($ids),
            'contacts' => $contacts,
        ));

    }
}
