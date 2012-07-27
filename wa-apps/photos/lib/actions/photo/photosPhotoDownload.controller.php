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
            waFiles::readFile($path,basename($photo['name'].'.'.$photo['ext']), waRequest::get('attach') ? true : false);
        } else {
            throw new waException(_w("Photo not found"), 404);
        }
    }
}