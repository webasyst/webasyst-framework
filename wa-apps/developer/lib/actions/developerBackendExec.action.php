<?php

/**
 * Get PHP and smarty code from POST, execute and echo output to browser.
 * Sounds crazy, isn't it?
 */
class developerBackendExecAction extends waViewAction
{
    public function execute()
    {
        // Access control
        $message = '';
        if (!wa()->getUser()->getRights('webasyst', 'backend')) {
            $message = _w('This application is available for Webasyst admin users only.');
        }
        if(!waSystemConfig::isDebug()) {
            $message = _w('This application works only when Debug mode is enabled in the Installer app.');
        }
        if ($message) {
            throw new waRightsException($message);
        }

        // !!! TODO: only allow access from localhost?

        eval(waRequest::post('code')); // Welcome to the dark side!
    }

    protected function isCached()
    {
        return false;
    }

    protected function getTemplate()
    {
        $tmpl = trim(waRequest::post('tmpl'));
        return 'string:</pre>'.($tmpl ? '<h2>[`Smarty`]</h2>'.$tmpl : '');
    }
}

