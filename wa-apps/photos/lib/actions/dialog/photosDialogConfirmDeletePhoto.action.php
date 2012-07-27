<?php

class photosDialogConfirmDeletePhotoAction extends photosDialogConfirmDeleteAlbumAction
{
    public function __construct($params=null) {
        parent::__construct($params);
        $this->type = 'delete-photo';
    }

    public function execute()
    {
        $photo_id = waRequest::get('id', null, waRequest::TYPE_INT);
        if (!$photo_id) {
            throw new waException(_w('Unknown photo'));
        }

        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        $this->view->assign('photo_name', $photo['name']);
    }
}