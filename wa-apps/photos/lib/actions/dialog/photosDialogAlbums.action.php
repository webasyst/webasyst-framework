<?php

class photosDialogAlbumsAction extends waViewAction
{
    public function execute()
    {
        if ($id = waRequest::get('id',waRequest::TYPE_INT)){
            $photo_model = new photosPhotoModel();
            $photo = $photo_model->getById($id);
            $album_photos_model = new photosAlbumPhotosModel();
            $photo_albums = $album_photos_model->getByPhoto($id);
        } else {
            $photo = null;
            $photo_albums = array();
        }
        $this->view->assign('photo_albums', $photo_albums);

        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums(false, photosAlbumModel::TYPE_STATIC, $this->getRights('edit') ? false : true, false);
        $this->view->assign('albums', $albums);
        $this->view->assign('photo', $photo);
    }
}