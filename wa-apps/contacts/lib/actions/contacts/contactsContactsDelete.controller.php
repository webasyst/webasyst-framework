<?php
/** Delete a set of contacts. */
class contactsContactsDeleteController extends waJsonController
{
    public function execute()
    {
        $superadmin = $this->getUser()->getRights('webasyst', 'backend');
        $contacts = waRequest::post('id', array(), 'array_int');
        
        // do not try to delete self
        if (in_array($this->getUser()->getId(), $contacts)) {
            throw new waRightsException('Access denied: attempt to delete own account.');
        }
        

        $this->getRights();
        
        $crm = new contactsRightsModel();
        $contacts = $crm->getAllowedContactsIds($contacts);
        if (!$contacts) {
            throw new waRightsException('Access denied: no access to contacts ');
        }
        
        
        // Deletion of contacts with links to other applications is only allowed to superadmins
        if (!$superadmin && ( $links = wa()->event('links', $contacts))) {
            foreach ($links as $app_id => $l) {
                foreach ($l as $contact_id => $contact_links) {
                    if ($contact_links) {
                        throw new waRightsException('Access denied: only superadmin is allowed to delete contacts with links to other applications.');
                    }
                }
            }
        }

        // Are there users among $contacts?
        $um = new waUserModel();
        $users = array_keys($um->getByField(array(
            'id' => $contacts,
            'is_user' => 1
        ), 'id'));

        // deletion of users is only allowed to superadmins
        if (!$superadmin && $users) {
            throw new waRightsException('Access denied: only superadmin is allowed to delete users.');
        }

        // Revoke user access before deletion
        foreach($users as $user_id) {
            waUser::revokeUser($user_id);
        }

        $contact_model = new waContactModel();
        
        $cnt = count($contacts);
        if ($cnt > 30) {
            $log_params = $cnt;
        } else {
            // contact names
            $log_params = $contact_model->getName($contacts);
        }

        $history_model = new contactsHistoryModel();
        foreach ($contacts as $contact_id) {
            $history_model->deleteByField(array(
                'type' => 'add',
                'hash' => '/contact/' . $contact_id
            ));
        }
        
        // Bye bye...
        $contact_model->delete($contacts); // also throws a contacts.delete event
        
        $this->response['deleted'] = $cnt;
        $this->response['message'] = sprintf(_w("%d contact has been deleted", "%d contacts have been deleted", $this->response['deleted']), $this->response['deleted']);
        
        $this->logAction('contact_delete', $log_params);
    }
}