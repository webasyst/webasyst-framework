<?php

class photosAlbumPhotosAction extends waViewAction
{
    /**
     * @throws waException
     * @throws waRightsException
     */
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

        $child_albums = $album_model->getChildren($album['id']);
        $album_model->keyPhotos($child_albums);

        $hash = '/album/'.$id;
        $frontend_link = photosCollection::getFrontendLink($hash);
        $collection = new photosCollection($hash);

        $config = $this->getConfig();

        $count = $config->getOption('photos_per_page');
        $photos = $collection->getPhotos("*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights", 0, $count);
        $photos = photosCollection::extendPhotos($photos);

        $album_photos_model = new photosAlbumPhotosModel();

        $album['count'] = $collection->count();
        if ($album['type'] == photosAlbumModel::TYPE_DYNAMIC) {
            $album['conditions'] = photosCollection::parseConditions($album['conditions']);
        }
        $album['count_new'] = 0;

        $sort_method = 'sort';
        if ($album['type'] == photosAlbumModel::TYPE_DYNAMIC) {
            $params_model = new photosAlbumParamsModel();
            $params = $params_model->get($album['id']);
            if ($params && isset($params['order']) && $params['order'] == 'rate') {
                $sort_method = 'rate';
            } else {
                $sort_method = 'upload_datetime';
            }
        }

        $this->template = 'templates/actions/photo/PhotoList.html';

        /**
         * Extend photo list toolbar in photo-list-page
         * Add extra item to toolbar and add extra toolbar-menu(s)
         * @event backend_photos_toolbar
         * @params array[string]string $params['action'] What action is working now
         * @return array[string][string]string $return[%plugin_id%]['top'] Insert own menu in top of toolbar
         * @return array[string][string]string $return[%plugin_id%]['share_menu'] Extra item for share_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['organize_menu'] Extra item for organize_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['save_menu'] Extra item for save_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['selector_menu'] Extra item for selector_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['hint_menu'] Extra item for hint_menu in photo list toolbar
         * @return array[string][string]string $return[%plugin_id%]['bottom'] Insert own menu in bottom on toolbar
         */
        $params = array('action' => 'album');
        $this->view->assign('backend_photos_toolbar', wa()->event('backend_photos_toolbar', $params));

        $this->view->assign('sidebar_width', $config->getSidebarWidth());
        $this->view->assign('album', $album);
        $this->view->assign('child_albums', $child_albums);
        $this->view->assign('frontend_link', $frontend_link);
        $this->view->assign('photos', $photos);
        $this->view->assign('title', $collection->getTitle());
        $this->view->assign('hash', $hash);
        $this->view->assign('big_size', $config->getSize('big'));
        $this->view->assign('sort_method', $sort_method);
    }
}