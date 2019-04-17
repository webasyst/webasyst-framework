<?php

class photosDialogCreateAlbumAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getRights('upload')) {
            throw new waRightsException(_w("You don't have sufficient access rights"));
        }

        $parent_id = waRequest::get('parent_id', 0, waRequest::TYPE_INT);
        $parent = null;
        if ($parent_id) {
            $album_model = new photosAlbumModel();
            $parent = $album_model->getById($parent_id);
        }
        $this->view->assign('parent', $parent);

        $groups_model = new waGroupModel();
        $this->view->assign('groups', $groups_model->getNames());

        $photo_tag_model = new photosTagModel();
        $cloud = $photo_tag_model->getCloud('name');
        $this->view->assign('cloud', $cloud);

        $this->view->assign('full_base_url', photosFrontendAlbum::getLink());

        /**
         * Extend album settings
         * Add extra html to album settings dialog
         * @event backend_album_settings
         * @params array[string]string $params['id'] Album id, if > 0 than edit existing album, otherwise new create new album
         * @return array[string][string]string $return[%plugin_id%]['top'] Insert html to the top of dialog (just after title)
         * @return array[string][string]string $return[%plugin_id%]['bottom'] Insert html to the bottom of dialog (right before buttons)
         */
        $params = array('id' => 0);
        $this->view->assign('backend_album_settings', wa()->event('backend_album_settings', $params));
    }
}
