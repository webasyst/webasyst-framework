<?php

class photosPhotoSearchMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $hash = $this->get('hash');
        $collection = new photosCollection($hash);

        $offset = waRequest::get('offset', 0, 'int');
        if ($offset < 0) {
            throw new waAPIException('invalid_param', 'Param offset must be greater than or equal to zero');
        }
        $limit = waRequest::get('limit', 100, 'int');
        if ($limit < 0) {
            throw new waAPIException('invalid_param', 'Param limit must be greater than or equal to zero');
        }
        if ($limit > 1000) {
            throw new waAPIException('invalid_param', 'Param limit must be less or equal 1000');
        }
        
        $photos = $collection->getPhotos('*,thumb', $offset, $limit);
        foreach ($photos as &$p) {
            if (isset($p['thumb']['url'])) {
                $p['image_url'] = $p['thumb']['url'];
                unset($p['thumb']);
            }
        }
        unset($p);
        
        $this->response['count'] = $collection->count();
        $this->response['offset'] = $offset;
        $this->response['limit'] = $limit;
        $this->response['photos'] = array_values($photos);
    }
}