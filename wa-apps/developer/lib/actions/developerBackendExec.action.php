<?php

/**
 * Get PHP and Smarty code from POST data, execute and print output to browser.
 */
class developerBackendExecAction extends waViewAction
{
    public function execute()
    {
        $error = $this->view->getVars('error');
        if ($error) {
            throw new waException($error);
        }

        // Welcome to the dark side!
        self::executeCode(waRequest::post('code'));
    }

    static private function executeCode(string $code)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
        ini_set('log_errors',  'Off');

        // Welcome to the dark side!
        eval($code);
    }

    protected function isCached(): bool
    {
        return false;
    }

    protected function getTemplate(): string
    {
        $tmpl = waRequest::post('tmpl', '', waRequest::TYPE_STRING_TRIM);
        return 'string:</pre>' . ($tmpl ? '<hr>' . PHP_EOL . '<h3>[`Smarty`]</h3>' . PHP_EOL . $tmpl : '');
    }
}
