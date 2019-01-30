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
    protected $prev_exception = null;
    protected static $htmlspecialchars_mode = ENT_NOQUOTES;

    public function __construct($message='', $code=500, $previous = null)
    {
        if ($message instanceof Exception) {
            $previous = $message;
            $code = $previous->getCode();
            $message = $previous->getMessage();
        } else if ($code instanceof Exception) {
            $previous = $code;
            $code = $previous->getCode();
        }

        parent::__construct($message, $code);
        $this->prev_exception = $previous;

        if (defined('ENT_SUBSTITUTE')) {
            self::$htmlspecialchars_mode |= ENT_SUBSTITUTE;
        } else if (defined('ENT_IGNORE')) {
            self::$htmlspecialchars_mode |= ENT_IGNORE;
        }
    }

    public function getPrev()
    {
        return $this->prev_exception;
    }

    public function getFullTraceAsString()
    {
        $result = '## '.$this->getFile().'('.$this->getLine().")\n";
        $result .= $this->getTraceAsString();
        if ($this->prev_exception) {
            $wa = null;
            try {
                if (function_exists('wa')) {
                    $wa = wa();
                }
            } catch (Exception $e) { }
            $msg = $this->prev_exception->getMessage();
            if (($wa && $wa->getEnv() == 'cli') || (!$wa && php_sapi_name() == 'cli')) {
                $msg = nl2br(htmlspecialchars($msg, self::$htmlspecialchars_mode, 'utf-8'));
            }
            $result .= "\n\nNext ".get_class($this->prev_exception)." with message '".$msg."':\n";
            if ($this->prev_exception instanceof waException) {
                $result .= $this->prev_exception->getFullTraceAsString();
            } else {
                $result .= '## '.$this->prev_exception->getFile().'('.$this->prev_exception->getLine().")\n";
                $result .= $this->prev_exception->getTraceAsString();
            }
        }

        return $this->hideRootPath($result);
    }

    protected function hideRootPath($str)
    {
        $root_path = realpath(dirname(__FILE__).'/../..');
        $root_path = str_replace('\\', '/', $root_path);
        $root_path = preg_quote($root_path, '~');
        $str = str_replace('\\', '/', $str);
        return preg_replace("~(^|\s|'|\"){$root_path}/?~", '$1', $str);
    }

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

    protected function toStringCli($wa, $additional_info)
    {
        $result = array();
        if (waRequest::server('argv')) {
            $result[] = date("Y-m-d H:i:s")." php ".join(" ", waRequest::server('argv'));
        }
        $result[] = "Error: {$this->getMessage()}";
        $result[] = "with code {$this->getCode()} in '{$this->getFile()}' around line {$this->getLine()}";
        $result[] = "";
        $result[] = "Call stack:";
        $result[] = $this->getFullTraceAsString();
        if ($additional_info) {
            $result[] = "Error while initializing waSystem during error generation: ".$additional_info;
        }
        return join("\n", $result);
    }

    protected function toStringProduction($wa)
    {
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
        if ($wa) {
            $env = $wa->getEnv();
            $url = $wa->getRootUrl(false);
        } else {
            $env = 'frontend';
            $url = '/';
        }

        // Never show exception details in production
        $message = '';

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

    protected function toStringDebug($wa, $additional_info)
    {
        $_ = waRequest::param();
        $params = wa_dump_helper($_);
        $get = wa_dump_helper($_GET);
        $post = null;
        if ($_POST) {
            $_ = $_POST;
            if (isset($_['_csrf'])) {
                $_['_csrf'] = '[hidden]';
            }
            $post = wa_dump_helper($_);
        }

        $message = nl2br(htmlspecialchars($this->hideRootPath($this->getMessage()), self::$htmlspecialchars_mode, 'utf-8'));
        $message = ifempty($message, get_class($this));
        $trace = htmlspecialchars($this->getFullTraceAsString(), self::$htmlspecialchars_mode, 'utf-8');
        $additional_info = htmlspecialchars($additional_info, self::$htmlspecialchars_mode, 'utf-8');

        $context = false;//trim($this->getFileContext());
        if ($context) {
            $context = htmlspecialchars($context, self::$htmlspecialchars_mode, 'utf-8');
            $context = <<<HTML
                <div id="Context" style="display: block;">
                    <h3>{$this->hideRootPath($this->getFile())} around line {$this->getLine()}</h3>
                    <pre>{$context}</pre>
                </div>
HTML;
        }

        $result = <<<HTML
<div style="width:99%;position:relative;text-align:left;white-space:normal" class="wa-exception-debug-dump">
    <h2 id='Title'>{$message} <span class="hint">code {$this->getCode()}</span></h2>
    <div id="Trace">
        <pre>{$trace}</pre>
    </div>
    {$context}
    <div id="Request">
        <h2>GET</h2>
        <pre>{$get}</pre>
HTML;
        if ($post) {
            $result .= <<<HTML
        <h2>POST</h2>
        <pre>{$post}</pre>
HTML;
        }
        $result .= <<<HTML
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
        $result .= <<<HTML
</div>
HTML;
        return $result;
    }

    public function __toString()
    {
        $wa = null;
        $additional_info = '';
        try {
            if (function_exists('wa')) {
                $wa = wa();
            }
        } catch (Exception $e) {
            $additional_info = $e->getMessage();
        }

        // CLI-friendly error message
        if (($wa && $wa->getEnv() == 'cli') || (!$wa && php_sapi_name() == 'cli')) {
            return $this->toStringCli($wa, $additional_info);
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
        if (!defined('STACK_TRACE_IN_NONDEBUG') && !waSystemConfig::isDebug()) {
            if ($send_response_code) {
                waLog::log("Uncaught ".$this->toStringCli($wa, $additional_info));
            }
            return $this->toStringProduction($wa);
        }

        // Error message in debug mode includes development info
        return $this->toStringDebug($wa, $additional_info);
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
