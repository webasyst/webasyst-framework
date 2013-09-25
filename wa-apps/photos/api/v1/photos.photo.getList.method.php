<?php

class photosPhotoGetListMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $_GET['hash'] = '';
        $method = new photosPhotoSearchMethod();
        $this->response = $method->getResponse(true);
    }
}