<?php

class photosPhotoListAction extends waViewAction
{
    public function execute()
    {
        $collection = new photosCollection('');

        $count = $this->getConfig()->getOption('photos_per_page');
        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
        $photos = photosCollection::extendPhotos($photos);
        $this->view->assign('photos', $photos);

        $this->view->assign('frontend_link', photosCollection::getFrontendLink(''));
        $this->view->assign('title', $collection->getTitle());
        $this->view->assign('total_count', $collection->count());
        $this->view->assign('big_size', $this->getConfig()->getSize('big'));
    }
}