<?php

class photosPhotoResolutionController extends waJsonController
{
    // Resolution photo ids: return ids of photo taking in account photo in stack
    public function execute()
    {
        $album_id = waRequest::post('album_id', null, waRequest::TYPE_INT);
        if ($album_id) {
            $album_photos_model = new photosAlbumPhotosModel();
            $this->response['photo_id'] = array_keys($album_photos_model->getByField('album_id', $album_id, 'photo_id'));
        } else {
            $photo_model = new photosPhotoModel();
            $photo_id = waRequest::post('photo_id', null, waRequest::TYPE_ARRAY_INT);
            $this->response['photo_id'] = array_keys($photo_model->getPhotos($photo_id));
        }
    }
}