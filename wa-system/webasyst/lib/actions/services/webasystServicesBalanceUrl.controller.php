<?php

class webasystServicesBalanceUrlController extends waJsonController
{
    public function execute()
    {
        $result = (new waServicesApi)->getBalanceCreditUrl();
        if (waRequest::isXMLHttpRequest()) {
            $this->response = $result;
        } elseif (ifset($result, 'response', 'url', false)) {
            wa()->getResponse()->redirect($result['response']['url']);
        } else {
            throw new waException('Balance credit url not found');
        }
    }
}
