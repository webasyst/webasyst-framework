<?php

class photosPhotoRotateController extends waJsonController
{
    public function execute()
    {
        $id  = waRequest::post('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException("Can't rotate photo");
        }

        $direction = waRequest::post('direction', 'left', waRequest::TYPE_STRING_TRIM);
        
        $photo_model = new photosPhotoModel();
        $photo_model->rotate($id, $direction == 'right');
        
        $photo = $photo_model->getById($id);
        $photo['thumb'] = photosPhoto::getThumbInfo($photo, photosPhoto::getThumbPhotoSize());
        $photo['thumb_big'] = photosPhoto::getThumbInfo($photo, photosPhoto::getBigPhotoSize());
        $photo['thumb_middle'] = photosPhoto::getThumbInfo($photo, photosPhoto::getMiddlePhotoSize());
        $original_photo_path = photosPhoto::getOriginalPhotoPath($photo);
        if (wa('photos')->getConfig()->getOption('save_original') && file_exists($original_photo_path)) {
            $photo['original_exists'] = true;
        } else {
            $photo['original_exists'] = false;
        }
        
        $this->log('photo_edit', 1);
        
        $this->response['photo'] = $photo;
    }
}