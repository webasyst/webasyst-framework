<?php

/** Delete contact photo completely. */
class contactsPhotoDeleteController extends waJsonController
{
    public function execute()
    {
        $id = (int)waRequest::get('id');

        // Delete the old photos if they exist
        $oldDir = wa()->getDataPath("photo/$id", TRUE);
        if (file_exists($oldDir)) {
            waFiles::delete($oldDir);
        }

        // Update record in DB for this user
        $contact = new waContact($id);
        $contact['photo'] = 0;
        $contact->save();

        // Update recent history to reload thumbnail correctly
        $history = new contactsHistoryModel();
        $history->save('/contact/'.$id, null, null, '--');

        $this->response = array('done' => 1);
    }
}

// EOF