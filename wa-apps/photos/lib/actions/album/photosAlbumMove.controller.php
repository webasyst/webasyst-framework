<?php

class photosAlbumMoveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', null, waRequest::TYPE_INT);
        $parent_id = waRequest::post('parent_id', null, waRequest::TYPE_INT);
        $before_id = waRequest::post('before_id', 0, waRequest::TYPE_INT);

        $album_model = new photosAlbumModel();
        $album_model->moveSort($id, $before_id, $parent_id);
    }
}