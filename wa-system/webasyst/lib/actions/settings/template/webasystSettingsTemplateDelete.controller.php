<?php

class webasystSettingsTemplateDeleteController extends webasystSettingsJsonController
{
    public function execute()
    {
        $id = (int)wa()->getRequest()->post('id');
        $channel = waVerificationChannel::factory($id);
        $channel->delete();
    }
}