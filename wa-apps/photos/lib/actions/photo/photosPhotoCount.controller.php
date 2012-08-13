<?php

class photosPhotoCountController extends waJsonController
{
    public function execute()
    {
        $photo_model = new photosPhotoModel();

        $config = $this->getConfig();
        $last_activity_datetime = $config->getLastLoginTime(false);

        $this->response['count'] = $photo_model->countAll();
        $this->response['rated_count'] = $photo_model->countRated();
    }
}