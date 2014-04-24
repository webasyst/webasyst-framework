<?php

class photosFrontendCollectionViewAction extends photosFrontendViewAction
{
    /**
     * @var photosPhotoModel
     */
    protected $photo_model;

    /**
     * @var photosAlbumModel
     */
    protected $album_model;

    /**
     * @var string
     */
    protected $hash = '';

    /**
     * @var int offset
     */
    protected $offset;

    /**
     * @var count of photos per page
     */
    protected $count;

    protected function init()
    {
        $lazy = waRequest::get('lazy');

        $this->photo_model = new photosPhotoModel();
        $this->photos_per_page = $this->getConfig()->getOption('photos_per_page');

        $page = 1;

        if (!is_null($lazy)) { // lazy loading ajax request or just get request
            $this->offset = max(0, waRequest::get('offset', 0, waRequest::TYPE_INT));
        } else {
            // TODO: in album and other type of collections ?
            $page = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
            $this->offset = ($page - 1) * $this->photos_per_page;
        }

        $this->view->assign('page', $page);
        $this->album_model = new photosAlbumModel();
    }

    public function finite()
    {
        $collection = new photosCollection($this->hash);

        $photos = $collection->getPhotos("*,thumb,frontend_link,tags", $this->offset, $this->photos_per_page);
        $photos = photosCollection::extendPhotos($photos);
        if ($this->hash) {
            $title = $collection->getTitle();
            if (!$title) {
                $this->getResponse()->setTitle(waRequest::param('title') ? waRequest::param('title') : wa()->accountName());
            } else {
                $this->getResponse()->setTitle($title);
            }
            
            $this->view->assign('title', photosPhoto::escape($title));
            
        } else {
            $this->getResponse()->setTitle(waRequest::param('title') ? waRequest::param('title') : wa()->accountName());
            $this->getResponse()->setMeta('keywords', waRequest::param('meta_keywords'));
            $this->getResponse()->setMeta('description', waRequest::param('meta_description'));
            $this->view->assign('title', '');
        }
        
        $total_count = $collection->count();

        $this->view->assign('photos_per_page', $this->photos_per_page);
        $this->view->assign('pages_count', floor($total_count / $this->photos_per_page) + 1);
        $this->view->assign('total_photos_count', $total_count);
        $this->view->assign('offset', $this->offset);
        $this->view->assign('photos', $photos);

        $is_xhr = waRequest::isXMLHttpRequest();
        $this->view->assign('is_xhr', $is_xhr);
        if ($is_xhr) {
            $this->view->assign('frontend_collection', array());
        } else {
            /**
             * @event frontend_collection
             * @return array[string][string]string $return[%plugin_id%]['name'] Extra name info
             * @return array[string][string]string $return[%plugin_id%]['content'] Extra album description and etc
             * @return array[string][string]string $return[%plugin_id%]['footer'] Footer section
             * @return array[string][string]string $return[%plugin_id%]['sidebar'] Footer section
             * @return array[string][string]string $return[%plugin_id%]['footer'] Footer section
             */
            $this->view->assign('frontend_collection', wa()->event('frontend_collection'));
        }

        $this->view->assign('lazy_load', !is_null(waRequest::get('lazy')));

        $v = wa()->getVersion();
        $this->getResponse()->addJs('js/lazy.load.js?v='.$v, true);
        $this->getResponse()->addJs('js/frontend.photos.js?v='.$v, true);
    }
}