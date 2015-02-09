<?php

class mailerCampaignsRecipientsBlockContactsProSearchCountController extends waJsonController
{
    public function execute()
    {
        $hash = waRequest::post('hash');
        $count = 0;
        if($hash) {
            wa('contacts');
            $cc = new waContactsCollection($hash);
            $count = $cc->count();
        }
        else {
            $this->errors = "no hash";
        }
        if (!$count) {
            $this->errors = "no contacts";
        }
        else {
            $this->response = $count;
        }
    }
}

