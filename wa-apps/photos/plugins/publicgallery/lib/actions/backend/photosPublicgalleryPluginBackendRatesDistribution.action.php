<?php

class photosPublicgalleryPluginBackendRatesDistributionAction extends waViewAction
{
    public function execute()
    {
        $photo_id = waRequest::get('photo_id', null, waRequest::TYPE_INT);
        $photo_model = new photosPhotoModel();
        $photo = $photo_model->getById($photo_id);
        if (!$photo) {
            throw new waException(_w('Photo not found'), 404);
        }
        
        $vote_model = new photosPublicgalleryVoteModel();
        $this->view->assign(array(
            'photo_name' => $photo['name'],
            'distribution' => $vote_model->getDistribution($photo_id),
            'rate' => $photo['rate'],
            'votes_count' => $photo['votes_count'],
            'users' => $vote_model->getVotedUsers($photo_id)
        ));
    }
}