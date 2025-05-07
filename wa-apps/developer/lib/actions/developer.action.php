<?php

abstract class developerAction extends waViewAction
{
    protected function checkRights()
    {
        if (!$this->getUser()->getRights('webasyst', 'backend')) {
            throw new waRightsException(_w('Coding sandbox is available for Webasyst admin users only.'));
        }
        if (!defined('DEVELOPER_APP_IN_NONDEBUG') && !waSystemConfig::isDebug()) {
            throw new waException(_w('This application works only when developer mode is enabled in Settings app.'));
        }
    }

    public function display($clear_assign = true)
    {
        $this->checkRights();
        $this->setLayout(new developerBackendLayout());
        return parent::display($clear_assign);
    }
}
