<?php

abstract class webasystSettingsViewAction extends waViewAction
{
    public function __construct($params = null)
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waException('Access denied', 403);
        }

        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new webasystSettingsLayout());
        }
    }
}