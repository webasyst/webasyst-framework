<?php

class photosSearchPhotosAction extends waViewAction
{
    public function execute()
    {

        $query = trim(waRequest::post('q'), ' /');

        $hash = '/search/'.$query;
        $collection = new photosCollection($hash);
        if ($query == 'rate>0') {
            $collection->orderBy('p.rate DESC, p.id');
        }

        $this->template = 'templates/actions/photo/PhotoList.html';

        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights");
        $photos = photosCollection::extendPhotos($photos);
        $this->view->assign('big_size', $this->getConfig()->getSize('big'));
        $this->view->assign('frontend_link', $query == 'rate>0' ? photosCollection::getFrontendLink('favorites', false) : photosCollection::getFrontendLink($hash, false));
        $this->view->assign('photos', $photos);
        $this->view->assign('title', $query == 'rate>0' ? _w('Rated') : $collection->getTitle());
        $this->view->assign('total_count', $collection->count());
    }
}