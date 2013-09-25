<?php

class photosAlbumGetInfoMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $id = $this->get('id', true);
        $album_model = new photosAlbumModel();
        $album = $album_model->getById((int)$id);

        if ($album) {
            $this->response = $album;
        } else {
            throw new waAPIException('invalid_request', 'Album not found', 404);
        }
    }
}