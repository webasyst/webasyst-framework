<?php

class webasystProfileWaidUnbindController extends waJsonController
{
    public function execute()
    {
        if (!$this->isWebasystIDForced()) {
            $contact = $this->getContact();
            if ($contact && ($this->getUser()->isAdmin() || $contact->getId() == $this->getUserId())) {
                $this->unbind($contact);
            }
        }
    }

    protected function unbind(waContact $contact) {
        $api = new waWebasystIDApi();
        $api->deleteUser($contact->getId());

        // no matter of result of deletion on server, delete binding params from client (from current db)
        $contact->unbindWaid();
    }

    protected function getContact()
    {
        $id = $this->getRequest()->post('id');
        if (wa_is_int($id) && $id > 0) {
            $contact = new waContact($id);
            if ($contact->exists()) {
                return $contact;
            }
        }
        return null;
    }

    protected function isWebasystIDForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
    }
}
