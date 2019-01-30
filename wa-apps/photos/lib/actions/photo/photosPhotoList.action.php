<?php

class photosPhotoListAction extends waViewAction
{
    /**
     * @var photosCollection
     */
    private $collection;

    public function execute()
    {
        $app_id = waRequest::request('app_id');
        if ($app_id && wa()->appExists($app_id) && wa()->getUser()->getRights($app_id, 'backend')) {
            $hash = 'app/'.$app_id;
        } else {
            $hash = '';
        }
        $this->collection = new photosCollection($hash);

        $config = $this->getConfig();

        $count = $config->getOption('photos_per_page');
        $photos = $this->getPhotos(0, $count);
        $photos = photosCollection::extendPhotos($photos);
        $this->view->assign('photos', $photos);

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
        $params = array('action' => 'list');
        $this->view->assign('backend_photos_toolbar', wa()->event('backend_photos_toolbar', $params));

        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        $this->view->assign('frontend_link', $hash ? '' : photosCollection::getFrontendLink($hash));
        $this->view->assign('title', $this->collection->getTitle());
        $this->view->assign('total_count', $this->collection->count());
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('sort_method', 'upload_datetime');
        $this->view->assign('hash', $hash);
    }

    public function getTemplate()
    {
        $template = parent::getTemplate();
        if ($this->getRequest()->isMobile()) {
            $template = str_replace('actions', 'actions-mobile', $template);
        }
        return $template;
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