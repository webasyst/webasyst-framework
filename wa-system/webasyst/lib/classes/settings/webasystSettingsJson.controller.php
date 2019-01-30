<?php

class webasystSettingsJsonController extends waJsonController
{
    public function __construct($params = null)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new waRightsException('Access denied');
        }
    }
}