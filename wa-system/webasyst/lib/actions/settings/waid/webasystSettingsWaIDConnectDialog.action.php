<?php

class webasystSettingsWaIDConnectDialogAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $this->view->assign([
            'connect_url' => wa()->getAppUrl('webasyst') . '?module=settings&action=waIDConnect'
        ]);
    }
}
