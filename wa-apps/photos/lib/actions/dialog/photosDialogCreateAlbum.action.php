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
    }
}
