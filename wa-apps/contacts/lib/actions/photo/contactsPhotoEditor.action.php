<?php

/** Contact photo upload and crop dialog. */
class contactsPhotoEditorAction extends waViewAction
{
    public function execute()
    {
        $id = (int)waRequest::get('id');
        $contact = new waContact($id);

        // Show an uploaded image to crop?
        if (waRequest::get('uploaded')) {
            // Is there an uploaded file in session?
            $photoEditors = $this->getStorage()->read('photoEditors');
            if (isset($photoEditors[$id]) && file_exists($photoEditors[$id])) {
                $url = $this->getConfig()->getBackendUrl(true).'?app=contacts&action=data&temp=1&path=photo/'.basename($photoEditors[$id]);
                $this->view->assign('oldPreview', $url);
                $this->view->assign('oldImage', $url);
            }
        } else {
            // Is there a photo for this contact?
            $filename = wa()->getDataPath("photo/$id/{$contact['photo']}.original.jpg", TRUE);
            if (file_exists($filename)) {
                $this->view->assign('oldPreview', $contact->getPhoto());
                $this->view->assign('oldImage', $contact->getPhoto('original'));
                $this->view->assign('orig', 1);
            }
        }


        $this->view->assign('contactId', $id);
        $this->view->assign('logged_user_id', wa()->getUser()->getId());
        $this->view->assign('contact', $contact);
    }
}

// EOF
