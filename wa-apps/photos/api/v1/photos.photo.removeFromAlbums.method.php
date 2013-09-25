<?php

class photosPhotoRemoveFromAlbumsMethod extends waAPIMethod
{
    protected $method = 'POST';
    
    public function execute()
    {
        $photo_id = $this->post('id', true);
        if (!is_array($photo_id)) {
            if (strpos($photo_id, ',') !== false) {
                $photo_id = array_map('intval', explode(',', $photo_id));
            } else {
                $photo_id = array($photo_id);
            }
        }
        $album_id = waRequest::post('album_id', '');
        if (!$album_id) {
            $album_id = array();
        }
        if (!is_array($album_id)) {
            if (strpos($album_id, ',') !== false) {
                $album_id = explode(',', $album_id);
            } else {
                $album_id = array($album_id);
            }
        }
        $album_id = array_map('trim', $album_id);
        
        $album_photos_model = new photosAlbumPhotosModel();
        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
        if ($allowed_photo_id) {
            $album_photos_model->deletePhotos($album_id, $allowed_photo_id);
            $this->response = true;
        } else {
            throw new waAPIException('access_denied', 403);
        }
    }

}