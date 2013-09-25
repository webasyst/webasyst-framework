<?php

class photosPhotoGetInfoMethod extends waAPIMethod
{
    public function execute()
    {
        $id = $this->get('id', true);
        
        $collectoin = new photosCollection('id/'.$id);
        $data = $collectoin->getPhotos('*,thumb_big');

        if (!isset($data[$id])) {
            throw new waAPIException('invalid_param', 'Photo not found', 404);
        }
        
        $data = $data[$id];
        
        if (isset($data['thumb_big']['url'])) {
            $data['image_url'] = $data['thumb_big']['url'];
            unset($data['thumb_big']);
        }

        $this->response = $data;
    }
}