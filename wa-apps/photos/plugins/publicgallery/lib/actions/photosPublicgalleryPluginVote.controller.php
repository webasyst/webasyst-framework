<?php

class photosPublicgalleryPluginVoteController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getId()) {
            $this->errors[] = sprintf_wp("Please %ssign in%s to be able to vote for photos", '<a href="'.wa()->getRouteUrl('/login', null, true).'">', '</a>');
            return;
        }

        $plugin = wa()->getPlugin('publicgallery');

        $photo_id = waRequest::post('photo_id', null, waRequest::TYPE_ARRAY_INT);

        $allowed_photo_id = $this->filterAllowedPhotoIds($photo_id);
        if (!$allowed_photo_id) {
            return;
        }

        $vote_model = new photosPublicgalleryVoteModel();
        $photo_model = new photosPhotoModel();

        if (wa()->getEnv() == 'frontend' && !$plugin->getSettings('self_vote')) {
            $photo = $photo_model->getById($allowed_photo_id);
            if (!$photo) {
                $this->errors[] = _w("Photo not found");
                return;
            }
            $photo = reset($photo);
            if ($photo && $photo['contact_id'] == wa()->getUser()->getId()) {
                $this->errors[] = _wp("You may not vote for your own photos");
                return;
            }
        }

        $vote = (int) waRequest::post('rate', 0);
        if ($vote > 0) {
            $vote_model->vote($allowed_photo_id, $vote);
        } else {
            $vote_model->clearVote($allowed_photo_id);
        }

        $this->response['photos'] = $photo_model->
                select('id, rate, votes_count')->
                where("id IN (".implode(',', $photo_id).")")->
                fetchAll();
        foreach ($this->response['photos'] as &$p) {
            if ($p['votes_count']) {
                $p['votes_count_text'] = _wp('%d vote', '%d votes', $p['votes_count']);
            } else {
                $p['votes_count_text'] = '';
            }
        }
        unset($p);

        $this->response['count'] = $photo_model->countRated();

        if (count($photo_id) == 1) {
            $this->response['you_voted'] = (int) $vote_model->getByField(
                    array('photo_id' => $photo_id[0], 'contact_id' => wa()->getUser()->getId())
            );
        }

    }

    public function filterAllowedPhotoIds($photo_id)
    {
        if (!$photo_id) {
            return $photo_id;
        }
        if (wa()->getEnv() == 'backend') {
            if (wa()->getUser()->getRights('photos', 'edit')) {
                return $photo_id;
            }
            $photo_model = new photosPhotoModel();
            return array_keys($photo_model->select('id')->where(
                "rate > 0 AND id IN (".implode(',', $photo_id).")"
            )->fetchAll('id'));
        } else {
            return $photo_id;
        }
    }
}