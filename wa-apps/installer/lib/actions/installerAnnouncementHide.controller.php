<?php

class installerAnnouncementHideController extends waController
{
    public function execute()
    {
        $key = waRequest::post('key');
        $wcsm = new waContactSettingsModel();
        $wcsm->replace(array(
            'contact_id' => wa()->getUser()->getId(),
            'app_id'     => 'installer',
            'name'       => $key,
            'value'      => 1,
        ));
        echo 'ok';
    }
}
