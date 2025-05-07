<?php

class developerBackendAction extends developerAction
{
    public function execute()
    {
        $message = '';
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            $message = _w('Coding sandbox is available for Webasyst admin users only.');
        }
        if (!defined('DEVELOPER_APP_IN_NONDEBUG') && !waSystemConfig::isDebug()) {
            $message = _w('Coding sandbox works only if developer mode is enabled in Settings app.');
        }

        // !!! only allow access from localhost?

        if ($message) {
            $this->setTemplate('string:<div class="triple-padded block"><h2 style="color:red">{$message|escape}</h2></div>');
            $this->view->assign('message', $message);
        }

        // Browsers don't like it when JS is sent over POST.
        // This disables internal browser's XSS filtering.
        wa()->getResponse()->addHeader('X-XSS-Protection', 0)->sendHeaders();
    }
}
