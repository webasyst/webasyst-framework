<?php

class photosPhotoCountController extends waJsonController
{
    public function execute()
    {
        $photo_model = new photosPhotoModel();

        $config = $this->getConfig();
        $last_login_datetime = $config->getLastLoginTime();

        $this->response['count'] = $photo_model->countAll();
        $this->response['count_new'] = $photo_model->countAll($last_login_datetime);
        $this->response['rated_count'] = $photo_model->countRated();
    }
}