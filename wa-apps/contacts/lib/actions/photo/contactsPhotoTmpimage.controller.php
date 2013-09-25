<?php

/** Contact photo upload, step one: upload and save a full-sized image
  * to allow user to crop later. */
class contactsPhotoTmpimageController extends waController
{
    public function execute()
    {
        $id = $this->getId();
        $file = waRequest::file('photo');

        if (!$file->uploaded()) {
            $this->sendResponse('error:No file uploaded.');
            return;
        }

        try {
            $img = $file->waImage();
        } catch(Exception $e) {
            // Nope... it's not an image.
            $this->sendResponse('error:File is not an image ('.$e->getMessage().').');
            return;
        }

        // save it to a temporary directory (well... less temporary than /tmp)
        // in .jpg format
        $temp_dir  = wa('contacts')->getTempPath('photo');
        $fname = uniqid($id.'_').'.jpg';
        $img->save($temp_dir.'/'.$fname, 90);

        $photoEditors = $this->getStorage()->read('photoEditors');
        if (!$photoEditors) {
            $photoEditors = array();
        }

        if (isset($photoEditors[$id]) && file_exists($photoEditors[$id])) {
            // If there was another photo editor session for this contact,
            // assume it closed and delete old temp file
            if (!unlink($photoEditors[$id])) {
                throw new waException('Unable to delete photo temp file: '+$photoEditors[$id]);
            }
        }
        $photoEditors[$id] = $temp_dir.'/'.$fname;

        // Save file name in session (race condition possible)
        $this->getStorage()->write('photoEditors', $photoEditors);

        // Return temporary file url to browser
        $temp_file_url = $this->getPreviewUrl($fname);
        $this->sendResponse($temp_file_url);
    }

    protected function sendResponse($string) {
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head></head><body>'.
            $string
        .'</body></html>';
    }

    protected function getId()
    {
        return (int)waRequest::request('id');
    }

    protected function getPreviewUrl($file)
    {
        return $this->getConfig()->getBackendUrl(true).'?app=contacts&action=data&temp=1&path=photo/'.$file;
    }
}

