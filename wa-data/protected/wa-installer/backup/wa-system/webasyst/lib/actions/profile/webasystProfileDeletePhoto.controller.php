<?php

/** Delete contact photo completely. */
class webasystProfileDeletePhotoController extends waJsonController
{
    public function execute()
    {
        $contact = wa()->getUser();
        $contact['photo'] = 0;
        $contact->save();

        $oldDir = wa()->getDataPath(waContact::getPhotoDir($contact->getId()), true, 'contacts', false);
        if (file_exists($oldDir)) {
            waFiles::delete($oldDir);
        }

        $this->response = array('done' => 1);
    }
}
