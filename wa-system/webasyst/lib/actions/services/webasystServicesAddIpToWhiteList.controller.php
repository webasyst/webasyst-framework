<?php

class webasystServicesAddIpToWhiteListController extends waJsonController
{
    public function execute()
    {
        $ip = waRequest::post('ip', '', waRequest::TYPE_STRING_TRIM);
        $this->response = (new waServicesApi)->addIpToWhiteList($ip);
    }
}