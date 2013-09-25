<?php

/** Contact photo editor, step two: user selected an area to crop. */
class contactsPhotoCropController extends waJsonController
{
    public function execute()
    {
        $this->response = array();

        // Initialize all needed post vars as $vars in current namespace
        foreach(array('x1', 'y1', 'x2', 'y2', 'w', 'h', 'ww', 'orig') as $var) {
            if (null === ( $$var = (int)waRequest::post($var))) { // $$ black magic...
                $this->response['error'] = 'wrong parameters';
                return;
            }
        }

        $id = $this->getId();
        $contact = new waContact($id);

        // Path to file we need to crop
        $rand = mt_rand();
        $filename = wa()->getDataPath("photo/$id/$rand.original.jpg", TRUE, 'contacts');

        $oldDir = wa()->getDataPath("photo/$id", TRUE, 'contacts');

        $no_old_photo = false;
        if (!$orig) {
            // Delete the old photos if they exist
            if (file_exists($oldDir)) {
                waFiles::delete($oldDir);
                $no_old_photo = true;
            }
            waFiles::create($oldDir);

            // Is there an uploaded file in session?
            $photoEditors = $this->getStorage()->read('photoEditors');

            if (!isset($photoEditors[$id]) || !file_exists($photoEditors[$id])) {
                $this->response['error'] = 'Photo editor session is not found or already expired.';
                return;
            }

            $newFile = $photoEditors[$id];

            // Save the original image in jpeg for future use
            try {
                $img = waImage::factory($newFile)
                    ->save($filename);
            } catch (Exception $e) {
                $this->response['error'] = 'Unable to save new file '.$filename.' ('.pathinfo($filename, PATHINFO_EXTENSION).') as jpeg: '.$e->getMessage();
                return;
            }

            // Remove uploaded file
            unset($photoEditors[$id]);
            $this->getStorage()->write('photoEditors', $photoEditors);
            unlink($newFile);
        } else {
            // cropping an old file. Move it temporarily to temp dir to delete all cached thumbnails
            $oldFile = wa()->getDataPath("photo/$id/{$contact['photo']}.original.jpg", TRUE, 'contacts');
            $tempOldFile = wa()->getTempPath("$id/$rand.original.jpg", 'contacts');
            waFiles::move($oldFile, $tempOldFile);

            // Delete thumbnails
            if (file_exists($oldDir)) {
                waFiles::delete($oldDir);
            }
            waFiles::create($oldDir);

            // return original image to its proper place
            waFiles::move($tempOldFile, $filename);
        }

        if (!file_exists($filename)) {
            $this->response['error'] = 'Image to crop not found (check directory access rights).';
            return;
        }

        // Crop and save selected area
        $croppedFilename = wa()->getDataPath("photo/$id/$rand.jpg", TRUE, 'contacts');
        try {
            $img = waImage::factory($filename);
            $scale = $img->width / $ww;
            $img
                ->crop(floor($w*$scale), floor($h*$scale), floor($x1*$scale), floor($y1*$scale))
                ->save($croppedFilename);
        } catch (Exception $e) {
            $this->response['error'] = 'Unable to crop an image: '.$e->getMessage();
            return;
        }

        // Update record in DB for this user
        $contact['photo'] = $rand;
        $contact->save();
        if ($no_old_photo) {
            $old_app = null;
            if (wa()->getApp() !== 'contacts') {
                $old_app = wa()->getApp();
                waSystem::setActive('contacts');
            }
            $this->log('photo_add', 1);
            if ($old_app) {
                waSystem::setActive($old_app);
            }
        }

        // Update recent history to reload thumbnail correctly (if not called from personal account)
        if (wa()->getUser()->get('is_user')) {
            $history = new contactsHistoryModel();
            $history->save('/contact/'.$id, null, null, '--');
        }

        $this->response = array('url' => $contact->getPhoto());
    }

    protected function getId()
    {
        return (int) waRequest::post('id');
    }
}

