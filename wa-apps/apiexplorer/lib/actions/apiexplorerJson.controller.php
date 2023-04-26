<?php

class apiexplorerJsonController extends waJsonController
{

    protected $warnings = [];

    protected function preExecute()
    {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // suppress possible warnings
            if (0 === error_reporting()) {
                return false;
            }
            if (waSystemConfig::isDebug()) {
                $this->addWarning($errstr, ['code' => $errno, 'file' => $errfile, 'line' => $errline]);
            }
            waLog::dump(['warning' => $errstr,'code' => $errno, 'file' => $errfile, 'line' => $errline], 'error.log');
        });
    }

    public function addWarning($message, $data = array())
    {
        $this->warnings[] = [$message, $data];
    }

    public function display()
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $this->getResponse()->sendHeaders();
        $body = [];
        if ($this->errors) {
            $body = ['status' => 'fail', 'errors' => $this->errors];
        } else {
            $body = ['status' => 'ok', 'data' => $this->response];
        }
        if ($this->warnings) {
            $body[] = ['warnings' => $this->warnings];
        }
        echo waUtils::jsonEncode($body);
    }

}
