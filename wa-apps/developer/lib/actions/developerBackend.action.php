<?php

class developerBackendAction extends developerAction
{
    public function execute()
    {
        $error = null;
        if (!$this->getUser()->getRights('webasyst', 'backend')) {
            $error = _w('Coding sandbox is available for Webasyst admin users only.');
        } elseif (!waSystemConfig::isDebug()) {
            $error = _w('Coding sandbox works only if Debug mode is enabled in the Installer app.');
        }

        if ($error) {
            $this->setTemplate('string:<h2 style="color: red">{$error|escape}</h2>');
            $this->view->assign('error', $error);
        }

        // Browsers don't like it when JS is sent over POST. This disables internal browser's XSS filtering.
        $this->getResponse()
             ->addHeader('X-XSS-Protection', 0)
             ->sendHeaders();
    }
}
