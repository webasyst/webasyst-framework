<?php

class photosBackendController extends waViewController
{
    public function execute()
    {
        $this->setLayout(!$this->getRequest()->isMobile() ? new photosDefaultLayout() : new photosMobileLayout());
    }
}