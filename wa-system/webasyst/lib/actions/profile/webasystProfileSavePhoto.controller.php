<?php
/** Contact photo editor: accepts a file and a crop area. */
class webasystProfileSavePhotoController extends waJsonController
{
    public function execute()
    {
        $id = $this->getId();
        $this->response = array(
            'contact_id' => $id,
            'url' => '',
        );
        if (!$id) {
            return;
        }

        $contact = new waContact($id);
        $contact->getName(); // make sure contact exists

        $dir = waContact::getPhotoDir($id, true);
        $oldDir = wa()->getDataPath($dir, true, 'contacts');

        // Asked to delete old photo?
        if (waRequest::request('del')) {
            if (file_exists($oldDir)) {
                waFiles::delete($oldDir);
            }
            $contact['photo'] = 0;
            $contact->save();
            $this->response = array(
                'contact_id' => $id,
                'url' => $contact->getPhoto(),
            );
            return;
        }

        // Initialize all needed post vars as $vars in current namespace
        $orig = waRequest::post('orig', null, 'int');
        foreach(array('x1', 'y1', 'x2', 'y2', 'w', 'h', 'ww') as $var) {
            if (null === ( $$var = waRequest::post($var, null, 'int'))) { // $$ black magic...
                $this->response['error'] = 'wrong parameters';
                return;
            }
        }
        if (!$w || !$h || !$ww) {
            $this->response['error'] = 'wrong parameters';
            return;
        }

        // Path to file we need to crop
        $rand = mt_rand();
        $filename = wa()->getDataPath("{$dir}$rand.original.jpg", true, 'contacts');

        if (!$orig) {
            $file = waRequest::file('photo');
            if (!$file->uploaded()) {
                throw new waException('no file');
            }

            // Delete the old photos if they exist
            if (file_exists($oldDir)) {
                waFiles::delete($oldDir);
            }
            waFiles::create($oldDir);

            // Save the original image in jpeg for future use
            try {
                $img = $file->waImage()
                    ->save($filename);
            } catch (Exception $e) {
                $this->response['error'] = 'Unable to save new file '.$filename.' ('.pathinfo($filename, PATHINFO_EXTENSION).') as jpeg: '.$e->getMessage();
                return;
            }
            unset($img);
        } else {
            // cropping an old file. Move it temporarily to temp dir to delete all cached thumbnails
            $oldFile = wa()->getDataPath("{$dir}{$contact['photo']}.original.jpg", TRUE, 'contacts');
            $tempOldFile = wa()->getTempPath("{$id}/{$rand}.original.jpg", 'contacts');
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
        $croppedFilename = wa()->getDataPath("{$dir}{$rand}.jpg", TRUE, 'contacts');
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

        $this->response = array(
            'contact_id' => $id,
            'url' => $contact->getPhoto(),
        );
    }

    protected function getId()
    {
        return wa()->getUser()->getId();
    }
}
