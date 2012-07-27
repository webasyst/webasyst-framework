<?php

class photosDialogEmbedPhotoAction extends waViewAction
{
    public function execute()
    {
        $photo_id = waRequest::get('photo_id', null, waRequest::TYPE_INT);
        $size = waRequest::get('size', null, waRequest::TYPE_STRING);

        $album = null;
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        if (!$photo) {
            throw new waException(_w("Unknown photo"));
        }
        $photo['frontend_link'] = photosFrontendPhoto::getLink($photo, $album);

        $sizes = $this->getConfig()->getSizes();

        $contexts = array();
        foreach ($sizes as $sz) {
            $contexts[$sz]['html'] = photosPhoto::getEmbedImgHtml($photo, $sz);
            $contexts[$sz]['url']  = photosPhoto::getPhotoUrl($photo, $sz, true);
        }

        if (!$size || !isset($contexts[$size])) {
            $size = $sizes[0];
        }

        $this->view->assign('photo', $photo);
        $this->view->assign('sizes', $sizes);
        $this->view->assign('size',  $size);
        $this->view->assign('contexts', $contexts);
    }
}