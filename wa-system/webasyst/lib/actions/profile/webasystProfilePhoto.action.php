<?php
/**
 * Dialog to crop image to use as contact photo.
 */
class webasystProfilePhotoAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::request('id', wa()->getUser()->getId(), 'int');
        $ui = waRequest::request('id', '1.3', 'int');
        $contact = new waContact($id);

        // If there is a photo for this contact, show it to crop
        $oldPreview = $oldImage = null;
        $filename = wa()->getDataPath(waContact::getPhotoDir($id)."{$contact['photo']}.original.jpg", TRUE, 'contacts');
        if (file_exists($filename)) {
            $oldPreview = $contact->getPhoto();
            $oldImage = $contact->getPhoto('original');
        }

        if($ui === '1.3') {
            $this->setTemplate('templates/actions-legacy/profile/ProfilePhoto.html');
        }else{
            $this->setTemplate('templates/actions/profile/ProfilePhoto.html');
        }

        $this->view->assign(array(
            'oldPreview' => $oldPreview,
            'oldImage' => $oldImage,
            'contact' => $contact,
        ));
    }
}
