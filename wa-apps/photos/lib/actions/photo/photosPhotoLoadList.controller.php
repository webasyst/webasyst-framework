<?php

class photosPhotoLoadListController extends waJsonController
{
    public function execute()
    {
        $count = $this->getConfig()->getOption('photos_per_page');
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $hash = waRequest::post('hash', '', waRequest::TYPE_STRING_TRIM);
        $offset = waRequest::post('offset', 1, waRequest::TYPE_INT);
        $direction = waRequest::post('direction', 1, waRequest::TYPE_INT);

        $this->collection = new photosCollection($hash);
        if (strstr($hash, 'rate>0') !== false) {
            $this->collection->orderBy('p.rate DESC, p.id');
        }

        if ($id) {
            $photo_model = new photosPhotoModel();
            $photo = $photo_model->getById($id);
            $offset = $this->collection->getPhotoOffset($photo);
            if ($direction > 0) {
                $offset +=1;
            } else {
                $offset -= $count;
                if ($offset < 0) {
                    $count += $offset;
                    $offset = 0;
                }
            }
        }

        $photos = array_values($this->getPhotos($offset, $count));
        $photos = photosCollection::extendPhotos($photos);
        $loaded = count($photos)+$offset;
        $count = $this->collection->count();
        $this->response['photos'] = $photos;
        $this->response['hash'] = $hash;
        $this->response['string'] = array(
            'loaded' => _w('%d photo','%d photos', $loaded),
            'of' => sprintf(_w('of %d'), $count),
            'chunk' => ($loaded < $count) ? _w('%d photo','%d photos',min($this->getConfig()->getOption('photos_per_page'),$count - $loaded)) : false,
        );
    }

    public function getPhotos($offset, $limit)
    {
        $fields = "*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights";
        if ($this->getRequest()->isMobile()) {
            $fields = "*,thumb_mobile";
        }
        return $this->collection->getPhotos($fields, $offset, $limit);
    }
}