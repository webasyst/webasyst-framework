<?php

class photosDialogCreateAlbumAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getRights('upload')) {
            throw new waRightsException(_w("You don't have sufficient access rights"));
        }
        $groups_model = new waGroupModel();
        $this->view->assign('groups', $groups_model->getNames());

        $photo_tag_model = new photosTagModel();
        $cloud = $photo_tag_model->getCloud('name');
        $this->view->assign('cloud', $cloud);
    }
}