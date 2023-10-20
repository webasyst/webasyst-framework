<?php

class webasystPushDataMethod extends waAPIMethod
{
    public function execute()
    {
        try {
            $push_adapter = wa()->getPush();
            if (!$push_adapter->isEnabled()) {
                $this->response['provider'] = 'none';
                return;    
            }
            $this->response['provider'] = $push_adapter->getId();
        } catch (waException $ex) {
            $this->response['provider'] = 'none';
        }
    }
}
