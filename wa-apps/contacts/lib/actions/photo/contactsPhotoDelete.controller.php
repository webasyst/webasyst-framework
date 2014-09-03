<?php

/** Delete contact photo completely. */
class contactsPhotoDeleteController extends waJsonController
{
    public function execute()
    {
        $id = $this->getId();

        // Delete the old photos if they exist
        $oldDir = wa()->getDataPath(waContact::getPhotoDir($id), TRUE);
        if (file_exists($oldDir)) {
            waFiles::delete($oldDir);
        }

        // Update record in DB for this user
        $contact = new waContact($id);
        $contact['photo'] = 0;
        $contact->save();

        // Update recent history to reload thumbnail correctly (if not called from personal account)
        if (wa()->getUser()->get('is_user')) {
            $history = new contactsHistoryModel();
            $history->save('/contact/'.$id, null, null, '--');
        }

        $this->response = array('done' => 1);
    }

    protected function getId()
    {
        return (int)waRequest::get('id');
    }
}

// EOF