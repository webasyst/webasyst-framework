<?php

class webasystServicesDeleteIpFromWhiteListController extends waJsonController
{
    public function execute()
    {
        $ip = waRequest::post('ip', '', waRequest::TYPE_STRING_TRIM);
        $this->response = (new waServicesApi)->deleteIpFromWhiteList($ip);
    }
}
