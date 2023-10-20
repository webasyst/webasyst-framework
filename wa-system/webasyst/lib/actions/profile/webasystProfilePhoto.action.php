<?php
/**
 * Dialog to crop image to use as contact photo.
 */
class webasystProfilePhotoAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::request('id', wa()->getUser()->getId(), 'int');
        $ui = waRequest::request('ui', null, 'string');
        $contact = new waContact($id);

        // If there is a photo for this contact, show it to crop
        $oldPreview = $oldImage = null;
        $filename = wa()->getDataPath(waContact::getPhotoDir($id)."{$contact['photo']}.original.jpg", TRUE, 'contacts');
        if (file_exists($filename)) {
            $oldPreview = $contact->getPhoto();
            $oldImage = $contact->getPhoto('original');
        }

        if (!empty($ui) && $ui != wa()->whichUI()) {
            waRequest::SetParam('force_ui_version', $ui);
        }

        $this->view->assign(array(
            'oldPreview' => $oldPreview,
            'oldImage' => $oldImage,
            'contact' => $contact,
        ));
    }
}
