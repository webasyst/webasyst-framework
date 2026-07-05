<?php

class siteMapHideAlertMovingToSettingsController extends waJsonController
{
    public function execute()
    {
        if (waRequest::getMethod() === waRequest::METHOD_POST) {
            wa()->getUser()->setSettings('site', 'hide_alert_moving_to_settings', 1);
        } else {
            $this->errors = 'Method Not Allowed';
        }
    }
}
