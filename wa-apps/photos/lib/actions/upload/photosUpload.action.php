<?php

class photosUploadAction extends waViewAction
{
    public function execute()
    {
        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums(false, photosAlbumModel::TYPE_STATIC, $this->getRights('edit') ? false : true, false);
        $this->view->assign('albums', $albums);

        $group_model = new waGroupModel();
        $groups = $group_model->getNames();
        $this->view->assign('groups', $groups);
    }
}