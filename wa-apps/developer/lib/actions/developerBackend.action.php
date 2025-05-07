<?php

class developerBackendAction extends developerAction
{
    public function execute()
    {
        if (!defined('DEVELOPER_APP_IN_NONDEBUG') && !waSystemConfig::isDebug()) {
            $this->view->assign('error', _w('Coding sandbox works only if Debug mode is enabled in the Installer app.'));
            $this->setTemplate('string:<h2 style="color: red">{$error|escape}</h2>');
        }

        // Browsers don't like it when JS is sent over POST. This disables internal browser's XSS filtering.
        $this->getResponse()
             ->addHeader('X-XSS-Protection', 0)
             ->sendHeaders();
    }
}
