<?php

class webasystSettingsBalanceBuyController extends waJsonController
{
    public function execute()
    {
        $api = new webasystTransportServiceApi();
        $this->response = $api->getBalanceCreditUrl(waRequest::get('service', null, waRequest::TYPE_STRING_TRIM));
    }
}
