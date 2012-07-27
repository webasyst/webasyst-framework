<?php

class photosAlbumPhotoMoveController extends waJsonController
{
    public function execute()
    {
        $photo_id = waRequest::post('photo_id', array(), waRequest::TYPE_ARRAY_INT);
        $album_id = waRequest::post('album_id', null, waRequest::TYPE_INT);
        $before_id = waRequest::post('before_id', null, waRequest::TYPE_INT);

        if (!$photo_id || !$album_id) {
            throw new waException(_w("Can't move photo"));
        }

        $album_rights_model = new photosAlbumRightsModel();
        if (!$album_rights_model->checkRights($album_id, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }
        if ($photo_id && $album_id) {
            $album_photos_model = new photosAlbumPhotosModel();
            $album_photos_model->movePhoto($photo_id, $album_id, $before_id);
        }
    }
}