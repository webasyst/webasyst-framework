<?php

class teamProfileWaidConnectQrcodeDialogAction extends teamContentViewAction
{
    public function execute()
    {
        // Close session storage to allow parallel requests
        wa()->getStorage()->close();

        $this->view->assign([
        ]);
    }
}
