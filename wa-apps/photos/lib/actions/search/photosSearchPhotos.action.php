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

        /**
         * Extend photo list toolbar in photo-list-page
         * Add extra item to toolbar and add extra toolbar-menu(s)
         * @event backend_photos_toolbar
         * @params array[string]string $params['action'] What action is working now
         * @return array[string][string]string $return[%plugin_id%]['top'] Insert own menu in top of toolbar
         * @return array[string][string]string $return[%plugin_id%]['share_menu'] Extra item for share_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['organize_menu'] Extra item for organize_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['save_menu'] Extra item for save_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['selector_menu'] Extra item for selector_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['hint_menu'] Extra item for hint_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['bottom'] Insert own menu in bottom on toolbar
         */
        $params = array('action' => 'search');
        $this->view->assign('backend_photos_toolbar', wa()->event('backend_photos_toolbar'), $params);
        
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