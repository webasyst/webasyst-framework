<?php

class photosPhotoDeleteFromAlbumController extends waJsonController
{
    public function execute()
    {
        $album_id = waRequest::get('id', null, waRequest::TYPE_INT);

        // check rights
        $album_rights_model = new photosAlbumRightsModel();
        if (!$album_rights_model->checkRights($album_id, true)) {
            throw new waRightsException(_w("Access denied"));
        }
        
        $photo_id = waRequest::post('photo_id', null, waRequest::TYPE_ARRAY_INT);
        $album_photos_model = new photosAlbumPhotosModel();
        $album_photos_model->deletePhotos($album_id, $photo_id);
    }
}