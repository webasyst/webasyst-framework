<?php

class photosPhotoEmbedSelectorAction extends waViewAction
{
    public function execute()
    {
        $collection = new photosCollection();

        $hash = '';

        // Specific album?
        if ( ( $id = waRequest::request('album_id', null, 'int'))) {
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
        }
        // Photos of specific app?
        else if ( ( $app_id = waRequest::request('app_id', '', 'string'))) {
            if (wa()->appExists($app_id) && wa()->getUser()->getRights($app_id, 'backend')) {
                $hash = 'app/'.$app_id;
            } else {
                throw new waRightsException(_w("You don't have sufficient access rights"));
            }
        }

        // Photos
        $collection = new photosCollection($hash);
        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, 100500);
        $photos = photosCollection::extendPhotos($photos);

        // Album tree
        $album_model = new photosAlbumModel();
        $albums = $album_model->getAlbums();
        $albums_tree = new photosViewTree($albums);

        $this->view->assign(array(
            'title' => $collection->getTitle(),
            'photos' => $photos,
            'albums_tree_html' => $albums_tree->display(),
            'app_albums' => photosDefaultLayout::getAppAlbums('blog'),
            'hash' => '#/'.trim($hash, '/#').'/',
        ));
    }
}

