<?php

abstract class webasystSettingsTemplateSaveController extends webasystSettingsJsonController
{

    /**
     * @var waVerificationChannel
     */
    protected $channel;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $channel_id = $this->getRequestId();
        $this->channel = waVerificationChannel::factory($channel_id);
    }

    protected function getRequestId()
    {
        return waRequest::get('id', null, waRequest::TYPE_INT);
    }
}