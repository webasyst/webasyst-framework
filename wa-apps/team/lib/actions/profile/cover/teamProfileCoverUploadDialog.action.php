<?php

class teamProfileCoverUploadDialogAction extends waViewAction
{
    public function execute()
    {
        $user = $this->getProfileUser();
        $this->view->assign([
            'user' => $user,
            'can_edit' => teamUser::canEdit($user),
            'cover_thumbnails' => $this->getCoverThumbnails($user['id'])
        ]);
    }

    protected function getCoverThumbnails($id)
    {
        return (new waContactCoverList($id, [
            'size_aliases' => wa('team')->getConfig()->getProfileCoverSizeAliases()
        ]))->getThumbnails();
    }

    protected function getProfileUser()
    {
        return teamUser::getCurrentProfileContact();
    }
}
