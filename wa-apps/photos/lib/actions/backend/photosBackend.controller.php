<?php

class photosBackendController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new photosDefaultLayout());
    }
}