<?php
/**
 * Delete contacts after confirmation dialog (see UsersPrepareDelete)
 */
class teamUsersDeleteController extends waJsonController
{
    public function execute()
    {
        $ids_string = waRequest::post('id', '', 'string');
        $contact_ids = explode(',', $ids_string);
        $delete_forever_mode = !!waRequest::request('delete_forever');

        // Make sure user have seen the PrepareDelete confirmation
        if ($ids_string != wa()->getStorage()->get('team_allowed_deletion_ids')) {
            $this->errors = "Something's wrong with confirmation dialog.";
            return;
        }

        // Never ever try to delete self
        if (in_array($this->getUser()->getId(), $contact_ids)) {
            throw new waRightsException('Access denied: attempt to delete own account.');
        }

        // Are there users among $contact_ids?
        $um = new waUserModel();
        $user_ids = array_keys($um->getByField(array(
            'id' => $contact_ids,
            'is_user' => 1
        ), 'id'));

        // deletion of users is only allowed to superadmins
        if ($user_ids && !wa()->getUser()->isAdmin()) {
            throw new waRightsException('Access denied: only superadmin is allowed to delete users.');
        }

        // Revoke user access before deletion
        foreach ($user_ids as $user_id) {
            waUser::revokeUser($user_id);
            if (!$delete_forever_mode) {
                $this->logAction("revoke_backend_access", null, $user_id);
            }
        }

        // Unbind user from Webasyst ID (do it only if only one user is deleting)
        if (count($user_ids) === 1) {
            $this->revokeWebasystIdUserConnection($user_ids[0]);
        }

        $contact_model = new waContactModel();
        if ($delete_forever_mode) {
            $cnt = count($contact_ids);
            if ($cnt > 30) {
                $log_params = $cnt;
            } else {
                // contact names
                $log_params = $contact_model->getName($contact_ids);
            }

            // Bye bye...
            $contact_model->delete($contact_ids); // also throws a contacts.delete event

            $this->response['deleted'] = $cnt;
            $this->response['message'] = sprintf(_w("%d contact has been deleted", "%d contacts have been deleted", $this->response['deleted']), $this->response['deleted']);
            $this->logAction('contact_delete', $log_params);
        } else {
            $contact_model->updateById($contact_ids, [
                'is_staff' => 0,
                'is_user' => 0,
            ]);
            $this->response['deleted'] = count($contact_ids);
            $this->response['message'] = sprintf(_w("revoked access from %d contact", "revoked access from %d contacts", $this->response['deleted']), $this->response['deleted']);
        }
    }

    protected function revokeWebasystIdUserConnection($user_id)
    {
        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return;
        }

        $cwm = new waContactWaidModel();
        $data = $cwm->get($user_id);
        if (empty($data)) {
            return;
        }

        $api = new waWebasystIDApi();
        $api->deleteUser($user_id);
    }
}
