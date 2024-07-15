<?php

class webasystServicesIpWhiteListChangeConfirmController extends waJsonController
{
    public function execute()
    {
        $code = waRequest::post('code', '', waRequest::TYPE_STRING);
        $this->response = (new waServicesApi)->confirmIpWhiteListChange($code);
    }
}
