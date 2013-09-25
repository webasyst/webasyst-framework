<?php

/**
 * Html view action for getting one photo
 * @see photosFrontendLoadPhotoController
 * @see photosFrontendLoadListAction
 */
class photosPublicgalleryPluginFrontendPhotoAction extends photosFrontendViewAction
{
    /**
     * @var photosPhotoModel
     */
    private $photo_model;

    /**
     * hash of collection
     * @var string
     */
    private $hash;

    /**
     * photo
     * @var array
     */
    private $photo;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setThemeTemplate('photo.html');
    }

    public function execute()
    {
        $url = waRequest::param('url');
        if (!$url) {
            throw new waException(_w('Page not found', 404));
        }
        $this->hash = 'publicgallery/myphotos';
        $this->photo_model = new photosPhotoModel();
        $this->photo = $this->photo_model->getByField('url', $url);
        if (!$this->photo) {
            throw new waException(_w('Page not found'), 404);
        }
        $this->photo = photosPhoto::escapeFields($this->photo);

        // delegate work to special render helper
        $render_helper = new photosPhotoRenderHelper($this->photo);
        $result = $render_helper->workUp(array(
            'hash' => $this->hash
        ));

        waRequest::setParam('title', $this->photo['name']);
        waRequest::setParam('nofollow', true);
        waRequest::setParam('disable_sidebar', true);

        // pull out work's up result
        $this->view->assign('photo',        $result['photo']);
        $this->view->assign('albums',       $result['blocks']['albums']);
        $this->view->assign('tags',         $result['blocks']['tags']);
        $this->view->assign('exif',         $result['blocks']['exif']);
        $this->view->assign('author',       $result['blocks']['author']);
        $this->view->assign('stack_nav',    $result['blocks']['stack_nav']);
        $this->view->assign('photo_stream', $result['blocks']['photo_stream']);

        /**
         * Add extra widgets to photo page
         * @event frontend_photo
         * @param string[array]mixed $photo photo data
         * @return array[string][string]string $return[%plugin_id%]['bottom'] In bottom, under photo - any widget
         * @return array[string][string]string $return[%plugin_id%]['sidebar']
         * @return array[string][string]string $return[%plugin_id%]['top_left']
         * @return array[string][string]string $return[%plugin_id%]['top_right']
         */
        $this->view->assign('frontend_photo', wa()->event('frontend_photo', $this->photo));

        $version = wa()->getVersion();
        $this->getResponse()->addJs('js/common.js?v='.$version, true);
        $this->getResponse()->addJs('js/photo.stream.slider.js?v='.$version, true);
        $this->getResponse()->addJs('js/frontend.photo.js?v='.$version, true);
    }
}