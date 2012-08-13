<?php

class photosAlbumMoveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', null, waRequest::TYPE_INT);
        $parent_id = waRequest::post('parent_id', null, waRequest::TYPE_INT);
        $before_id = waRequest::post('before_id', 0, waRequest::TYPE_INT);

        $album_model = new photosAlbumModel();
        $album = $album_model->move($id, $before_id, $parent_id);
        $this->response['album'] = $album;
        if ($album['status'] == 1) {
            $this->response['frontend_link'] = photosFrontendAlbum::getLink($album);
        }

        // recalculate
        // TODO: optimaize
        $albums = $album_model->getDescendant($album['id']);
        $albums[] = $album;
        $counters = array();
        foreach ($albums as &$item) {
            if ($item['type'] == photosAlbumModel::TYPE_DYNAMIC) {
                $c = new photosCollection('album/'.$item['id']);
                $counters[$item['id']] = $c->count();
            }
        }
        unset($item);
        $this->response['counters'] = $counters;
    }
}