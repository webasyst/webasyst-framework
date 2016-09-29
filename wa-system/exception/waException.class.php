<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage exception
 */

class waException extends Exception
{
    const CONTEXT_RADIUS = 5;

    private function getFileContext()
    {
        $file = $this->getFile();
        $context = array();
        if ($file && is_readable($file)) {
            $line_number = $this->getLine();
            $i = 0;
            foreach (file($file) as $line) {
                $i++;
                if ($i >= $line_number - self::CONTEXT_RADIUS && $i <= $line_number + self::CONTEXT_RADIUS) {
                    if ($i == $line_number) {
                        $context[] = ' >>'.$i."\t".$line;
                    } else {
                        $context[] = '   '.$i."\t".$line;
                    }
                }
                if ($i > $line_number + self::CONTEXT_RADIUS) {
                    break;
                }
            }
        }
        return "\n".implode("", $context);
    }

    public static function dump()
    {
        $message = '';
        foreach (func_get_args() as $v) {
            $message .= ($message ? "\n" : '').wa_dump_helper($v);
        }
        throw new self($message, 500);
    }

    public function __toString()
    {
        try {
            $wa = wa();
            $additional_info = '';
        } catch (Exception $e) {
            $wa = null;
            $additional_info = $e->getMessage();
        }

        $message = nl2br(htmlspecialchars($this->getMessage(), ENT_NOQUOTES, 'utf-8'));

        // CLI-friendly error message
        if (($wa && $wa->getEnv() == 'cli') || (!$wa && php_sapi_name() == 'cli')) {
            $result = array();
            $result[] = date("Y-m-d H:i:s")." php ".join(" ", waRequest::server('argv'));
            $result[] = "Error: {$this->getMessage()}";
            $result[] = "with code {$this->getCode()} in '{$this->getFile()}' around line {$this->getLine()}:{$this->getFileContext()}";
            $result[] = "";
            $result[] = "Call stack:";
            $result[] = $this->getTraceAsString();
            if ($additional_info) {
                $result[] = "Error while initializing waSystem during error generation: ".$additional_info;
            }
            return join("\n", $result);
        }

        // Modify HTTP response code when exception propagated all the way up the stack.
        $send_response_code = true;
        if(function_exists('debug_backtrace')) {
            $send_response_code = count(debug_backtrace()) <= 1;
        }
        if ($send_response_code) {
            $this->sendResponseCode();
        }

        // Error message in non-debug mode uses a separate file as a template
        if (!waSystemConfig::isDebug() && $wa) {
            if ($wa && waSystem::getApp()) {
                try {
                    $app = $wa->getAppInfo();
                } catch (Exception $e) {
                    $app = array();
                }
                $backend_url = $wa->getConfig()->getBackendUrl(true);
            } else {
                $app = array();
            }
            $env = $wa->getEnv();
            $url = $wa->getRootUrl(false);
            $file = $code = $this->getCode();
            if (!$code || !file_exists(dirname(__FILE__).'/data/'.$code.'.php')) {
                $file = 'error';
            }
            $file_candidates = array(
                waConfig::get('wa_path_config').'/exception/'.$code.'.php',
                waConfig::get('wa_path_config').'/exception/error.php',
                dirname(__FILE__).'/data/'.$file.'.php',
            );

            foreach($file_candidates as $f) {
                if (file_exists($f)) {
                    ob_start();
                    include($f);
                    return ob_get_clean();
                }
            }
            return '';
        }

        // Error message in debug mode includes development info
        $request = htmlentities(var_export($_REQUEST, true), ENT_NOQUOTES, 'utf-8');
        $params = htmlentities(var_export(waRequest::param(), true), ENT_NOQUOTES, 'utf-8');
        $context = htmlentities($this->getFileContext(), ENT_NOQUOTES, 'utf-8');
        $trace = htmlentities($this->getTraceAsString(), ENT_NOQUOTES, 'utf-8');
        $additional_info = htmlentities($additional_info, ENT_NOQUOTES, 'utf-8');
        $result = <<<HTML
<div style="width:99%; position:relative; text-align: left;">
    <h2 id='Title'>{$message}</h2>
    <div id="Context" style="display: block;">
        <h3>Error with code {$this->getCode()} in '{$this->getFile()}' around line {$this->getLine()}:</h3>
        <pre>{$context}</pre>
    </div>
    <div id="Trace">
        <h2>Call stack</h2>
        <pre>{$trace}</pre>
    </div>
    <div id="Request">
        <h2>Request</h2>
        <pre>{$request}</pre>
    </div>
</div>
    <div style="text-align: left;">
        <h2>Params</h2>
        <pre>{$params}</pre>
    </div>
HTML;

        if ($additional_info) {
            $result .= <<<HTML
    <div style="text-align: left;">
        <h2>Error while initializing waSystem during error generation</h2>
        <pre>{$additional_info}</pre>
    </div>
HTML;

        }
        return $result;
    }

    public function sendResponseCode()
    {
        $response = new waResponse();
        $response->setStatus(500);
        if (($this->code < 600) && ($this->code >= 400)) {
            $response->setStatus($this->code);
        }
        $response->sendHeaders();
    }
}
