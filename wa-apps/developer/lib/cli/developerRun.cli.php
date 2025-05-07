<?php
class developerRunCli extends waCliController
{
    public function execute()
    {
        error_reporting(E_ALL | E_STRICT | E_NOTICE);
        ini_set('display_errors', 1);

        $code = waRequest::param(0);
        if ($code === 'file') {
            include(waRequest::param(1));
        } else {
            eval($code.';');
        }
    }
}
