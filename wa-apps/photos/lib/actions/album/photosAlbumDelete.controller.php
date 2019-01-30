<?php

class photosAlbumDeleteController extends waJsonController
{
    public function execute()
    {
        $album_id = waRequest::post('album_id', null, waRequest::TYPE_INT);

        $album_rights_model = new photosAlbumRightsModel();
        if (!$album_rights_model->checkRights($album_id, true)) {
            throw new waException(_w("You don't have sufficient access rights"));
        }

        $album_model = new photosAlbumModel();

        $album_model->delete($album_id);

        /**
         * Extend delete process
         * @event album_delete
         */
        wa()->event('album_delete', $album_id);

        $this->log('album_delete', 1);

    }
}