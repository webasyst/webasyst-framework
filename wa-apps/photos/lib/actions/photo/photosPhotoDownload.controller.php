<?php

class photosPhotoDownloadController extends waViewController
{
    public function execute()
    {
        $path = null;
        $photo_rights_model = new photosPhotoRightsModel();

        $photo_id = waRequest::get('photo_id', null, waRequest::TYPE_INT);
        if ($photo_rights_model->checkRights($photo_id, true)) {
            $photo_model = new photosPhotoModel();
            if ($photo = $photo_model->getById($photo_id)) {
                if (waRequest::get('original')) {
                    $path = photosPhoto::getOriginalPhotoPath($photo);
                } else {
                    $path = photosPhoto::getPhotoPath($photo);
                }
            }
        }

        if ($path) {
            if ($attach = waRequest::get('attach')?true:false) {
                $response = $this->getResponse();
                $response->addHeader('Expires', 'tomorrow');
                $response->addHeader('Cache-Control', (($photo['status'] == 1)?'public':'private').', max-age='.(86400*30));
            }
            waFiles::readFile($path, $attach?null:basename($photo['name'].'.'.$photo['ext']), true, !$attach);
        } else {
            throw new waException(_w("Photo not found"), 404);
        }
    }
}