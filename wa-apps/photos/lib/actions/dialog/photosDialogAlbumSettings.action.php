<?php

class photosDialogAlbumSettingsAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);

        $album_model = new photosAlbumModel();
        $album = $album_model->getById($id);
        if (!$album) {
            throw new waException(_w("Unknown album"), 404);
        }

        $album_right_model = new photosAlbumRightsModel();
        if (!$album_right_model->checkRights($album, true)) {
            throw new waException(_w("You don't have sufficient access rights"), 403);
        }

        if ($album['type'] == photosAlbumModel::TYPE_DYNAMIC && $album['conditions']) {
            $album['conditions'] = photosCollection::parseConditions($album['conditions']);
        }
        
        if (!$album['conditions']) {
            $album['conditions'] = array();
        }

        $absolute_full_url = photosFrontendAlbum::getLink($album);
        if ($absolute_full_url) {
            $pos = strrpos($absolute_full_url, $album['url']);
            $full_base_url = $pos !== false ? rtrim(substr($absolute_full_url, 0, $pos),'/').'/' : '';
            $album['full_base_url'] = $full_base_url;
        }
        $this->view->assign('album', $album);
        if ($album['parent_id']) {
            $this->view->assign('parent', $album_model->getById($album['parent_id']));
        }

        $collection = new photosCollection('album/'.$id);
        $photos_count = $collection->count();
        $this->view->assign('photos_count', $photos_count);

        $album_params_model = new photosAlbumParamsModel();
        $this->view->assign('params', $album_params_model->get($id));

        $groups_model = new waGroupModel();
        $groups = $groups_model->getAll('id', true);
        $rights = $album_right_model->getByField('album_id', $id, 'group_id');

        $photo_tag_model = new photosTagModel();
        $cloud = $photo_tag_model->getCloud('name');
        if (!empty($album['conditions']['tag'][1])) {
            foreach ($album['conditions']['tag'][1] as $tag_name) {
                $cloud[$tag_name]['checked'] = true;
            }
        }
        $this->view->assign('rights', $rights);
        $this->view->assign('groups', $groups);
        $this->view->assign('cloud', $cloud);

        /**
         * Extend album settings
         * Add extra html to album settings dialog
         * @event backend_album_settings
         * @params array[string]string $params['id'] Album id, if > 0 than edit existing album, otherwise new create new album
         * @return array[string][string]string $return[%plugin_id%]['top'] Insert html to the top of dialog (just after title)
         * @return array[string][string]string $return[%plugin_id%]['bottom'] Insert html to the bottom of dialog (right before buttons)
         */
        $params = array('id' => $id);
        $this->view->assign('backend_album_settings', wa()->event('backend_album_settings', $params));
    }
}
