<?php

class photosTagPhotosAction extends waViewAction
{
    public function execute()
    {

        $tag_name = waRequest::get('tag');
        $tag_name = urldecode($tag_name);
        $tag_model = new photosTagModel();
        $tag = $tag_model->getByName($tag_name);

        $title = _w('Tag not found');
        $photos = array();

        $config = $this->getConfig();
        
        if ($tag) {
            $hash = '/tag/'.$tag_name;
            $collection = new photosCollection($hash);
            $count = $config->getOption('photos_per_page');
            $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
            $photos = photosCollection::extendPhotos($photos);
            $title = $collection->getTitle();

            $this->view->assign('frontend_link', photosCollection::getFrontendLink($hash));
            $this->view->assign('total_count', $collection->count());
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
        $params = array('action' => 'tag');
        $this->view->assign('backend_photos_toolbar', wa()->event('backend_photos_toolbar', $params));

        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        $this->view->assign('title', $title);
        $this->view->assign('photos', $photos);
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('sort_method', 'upload_datetime');
        
        $this->template = 'templates/actions/photo/PhotoList.html';
    }
}