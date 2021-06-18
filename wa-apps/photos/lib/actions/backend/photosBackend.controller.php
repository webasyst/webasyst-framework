<?php

class photosBackendController extends waViewController
{
    public function execute()
    {
        if ($this->getRequest()->isMobile() && wa()->whichUI('photos') === '1.3') {
            $layout = new photosMobileLayout();
        } else {
            $layout = new photosDefaultLayout();
        }
        $this->setLayout($layout);
    }
}
