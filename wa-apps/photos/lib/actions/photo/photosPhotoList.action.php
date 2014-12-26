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