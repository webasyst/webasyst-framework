<?php

class siteFilemanagerHideAlertFilesController extends waJsonController
{
    public function execute()
    {
        wa()->getUser()->setSettings('site', 'hide_alert_files', 1);
    }
}
