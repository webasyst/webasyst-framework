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

        $count = $this->getConfig()->getOption('photos_per_page');
        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
        $photos = photosCollection::extendPhotos($photos);
        
        $frontend_link = $query == 'rate>0' ? photosCollection::getFrontendLink('favorites', false) : photosCollection::getFrontendLink($hash, false);
        /**
         * @event search_frontend_link
         * @param string $query
         * @return array of bool|string if false - default frontend_link isn't overridden, if string - override default frontend link
         */
        $res = wa()->event('search_frontend_link', $query);
        foreach ($res as $r) {
            if (is_string($r)) {
                $frontend_link = $r;
                break;
            }
        }
        
        $config = $this->getConfig();
        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('frontend_link', $frontend_link);
        $this->view->assign('photos', $photos);
        $this->view->assign('title', $query == 'rate>0' ? _w('Rated') : $collection->getTitle());
        $this->view->assign('total_count', $collection->count());
        $this->view->assign('sort_method', $query == 'rate>0' ? 'rate' : 'upload_datetime');
        $this->view->assign('hash', $hash);
    }
}