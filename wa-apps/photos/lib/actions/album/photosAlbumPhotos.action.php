<?php

class photosAlbumPhotosAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException(_w('Unknown album'));
        }

        $album_model = new photosAlbumModel();
        $album = $album_model->getById($id);
        if (!$album) {
            throw new waException(_w('Unknown album'));
        }

        // check rights
        $album_rights_model = new photosAlbumRightsModel();
        if (!$album_rights_model->checkRights($album)) {
            throw new waRightsException(_w("You don't have sufficient access rights"));
        }
        $album['edit_rights'] = $album_rights_model->checkRights($album, true);

        $hash = '/album/'.$id;
        $frontend_link = photosCollection::getFrontendLink($hash);
        $collection = new photosCollection($hash);

        $count = $this->getConfig()->getOption('photos_per_page');
        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
        $photos = photosCollection::extendPhotos($photos);

        $album_photos_model = new photosAlbumPhotosModel();

        $album['count'] = $collection->count();
        if ($album['type'] == photosAlbumModel::TYPE_DYNAMIC) {
            $album['conditions'] = photosCollection::parseConditions($album['conditions']);
        }
        $album['count_new'] = 0;


        $this->template = 'templates/actions/photo/PhotoList.html';
        $this->view->assign('album', $album);
        $this->view->assign('frontend_link', $frontend_link);
        $this->view->assign('photos', $photos);
        $this->view->assign('title', $collection->getTitle());
        $this->view->assign('hash', $hash);
        $this->view->assign('big_size', $this->getConfig()->getSize('big'));
    }
}