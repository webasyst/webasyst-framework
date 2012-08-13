<?php

class photosDialogConfirmDeleteAlbumAction extends photosDialogConfirmViewAction
{
    public function __construct($params=null) {
        parent::__construct($params);
        $this->type = 'delete-album';
    }

    public function execute()
    {
        $album_id = waRequest::get('id', null, waRequest::TYPE_INT);
        if (!$album_id) {
            throw new waException(_w('Unknown album'));
        }
        $album_model = new photosAlbumModel();
        $album = $album_model->getById($album_id);
        $this->view->assign('album', $album);

        $collection = new photosCollection('/album/'.$album_id);
        $this->view->assign('photos_count', $collection->count());

    }
}