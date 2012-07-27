<?php

class photosPluginsController extends waViewController
{
    public function execute()
    {
        if (waRequest::get('id')) {
            $this->executeAction(new photosPluginsSettingsAction());
        } else {
            $this->executeAction(new photosPluginsAction());
        }

    }
}