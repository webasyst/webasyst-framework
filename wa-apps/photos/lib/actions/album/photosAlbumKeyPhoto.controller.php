<?php
class photosAlbumKeyPhotoController extends waJsonController
{
    public function execute()
    {
        $album_id = waRequest::post('album_id', 0, 'int');
        $photo_id = waRequest::post('photo_id', 0, 'int');
        if (!$album_id || !$photo_id) {
            throw new waException('Bad parameters', 404);
        }

        $album_rights_model = new photosAlbumRightsModel();
        if (!$album_rights_model->checkRights($album_id, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }

        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        if (!$photo) {
            $this->errors[] = _w('Photo not found');
            return;
        }

        $album_model = new photosAlbumModel();
        $album_model->updateById($album_id, array(
            'key_photo_id' => $photo_id,
        ));

        photosPhoto::generateThumbs($photo, array('192x192'));
    }
}
