<?php

class photosAlbumSearchMethod extends waAPIMethod
{
    public function execute()
    {
        $name = $this->get('name', true);
        $album_model = new photosAlbumModel();
        $this->response = $album_model->getByName($name);
        $this->response['_element'] = 'album';
    }
}