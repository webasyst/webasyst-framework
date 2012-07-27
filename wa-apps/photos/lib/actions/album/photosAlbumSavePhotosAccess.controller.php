<?php

class photosAlbumSavePhotosAccessController extends waJsonController
{
    public function execute()
    {
        $album_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $status = waRequest::post('status', 0, waRequest::TYPE_INT);
        $groups = waRequest::post('groups', array(), waRequest::TYPE_ARRAY_INT);
        $count = waRequest::post('count', 0, waRequest::TYPE_INT);
        $offset = waRequest::post('offset', 0, waRequest::TYPE_INT);

        $collection = new photosCollection('album/'.$album_id);
        $this->response['offset'] = $offset;
        $photos = $collection->getPhotos('*', $offset, $count, false);

        $photo_ids = array();
        foreach ($photos as $photo) {
            if ($photo['status'] == 1 && $status == 1) {
                continue;
            }
            if ($photo['stack_count'] > 0) {
                $photo_ids = array_merge($photo_ids, $photo_model->getIdsByParent($photo['id']));
            } else {
                $photo_ids[] = $photo['id'];
            }
        }

        $photo_rights_model = new photosPhotoRightsModel();
        $allowed_photo_ids = $photo_rights_model->filterAllowedPhotoIds($photo_ids, true);

        $photo_model = new photosPhotoModel();
        $photo_model->updateAccess($allowed_photo_ids, $status, $groups);
    }
}