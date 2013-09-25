<?php

class photosPublicgalleryPluginBackendPhotoListAction extends waViewAction
{
    public function execute()
    {
        $status = waRequest::get('status', 'waited', waRequest::TYPE_STRING_TRIM);
        $offset = waRequest::get('offset', 1, waRequest::TYPE_INT);

        $c = new photosCollection('', array('ignore_moderation' => true));
        $c->addWhere("p.moderation='" . ($status == 'waited' ? 'waited' : 'declined') . "'");

        $fields = "*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights";
        if ($this->getRequest()->isMobile()) {
            $fields = "*,thumb_mobile";
        }
        $photos = array_values(
                $c->getPhotos($fields, 
                        $offset, 
                        $this->getConfig()->getOption('photos_per_page')
                )
        );
        $photos = photosCollection::extendPhotos($photos);
        
        $loaded = count($photos) + $offset;
        $count = $c->count();
        $this->response['photos'] = $photos;
        $this->response['status'] = $status;
        $this->response['string'] = array(
            'loaded' => _w('%d photo','%d photos', $loaded),
            'of' => sprintf(_w('of %d'), $count),
            'chunk' => ($loaded < $count) ? _w('%d photo','%d photos', min($this->getConfig()->getOption('photos_per_page'),$count - $loaded)) : false,
        );
    }
}