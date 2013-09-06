<?php

class developerBackendAction extends developerAction
{
    public function execute()
    {
        $message = '';
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            $message = _w('Coding sandbox is available for Webasyst admin users only.');
        }
        if(!waSystemConfig::isDebug()) {
            $message = _w('Coding sandbox works only if Debug mode is enabled in the Installer app.');
        }

        // !!! only allow access from localhost?

        if ($message) {
            $this->setTemplate('string:<div class="tripple-padded block"><h2 style="color:red">{$message|escape}</h2></div>');
            $this->view->assign('message', $message);
        }
    }
}
