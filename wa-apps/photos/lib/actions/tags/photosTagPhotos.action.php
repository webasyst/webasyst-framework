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

        if ($tag) {
            $hash = '/tag/'.$tag_name;
            $collection = new photosCollection($hash);
            $count = $this->getConfig()->getOption('photos_per_page');
            $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
            $photos = photosCollection::extendPhotos($photos);
            $title = $collection->getTitle();

            $this->view->assign('frontend_link', photosCollection::getFrontendLink($hash));
            $this->view->assign('total_count', $collection->count());
        }

        $this->view->assign('title', $title);
        $this->view->assign('photos', $photos);
        $this->template = 'templates/actions/photo/PhotoList.html';
        $this->view->assign('big_size', $this->getConfig()->getSize('big'));
    }
}