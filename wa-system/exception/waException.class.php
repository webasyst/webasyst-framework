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
    const CONTEXT_RADIUS  = 5;

    private function getFileContext()
    {
        $file = $this->getFile();
        $line_number = $this->getLine();
        $context = array();
        $i = 0;
        foreach(file($file) as $line) {
            $i++;
            if($i >= $line_number - self::CONTEXT_RADIUS && $i <= $line_number + self::CONTEXT_RADIUS) {
                if ($i == $line_number) {
                    $context[] = ' >>'. $i ."\t". $line;
                } else {
                    $context[] = '   '. $i ."\t". $line;
                }
            }
            if($i > $line_number + self::CONTEXT_RADIUS) break;
        }
        return "\n". implode("", $context);
    }

    public function __toString()
    {
        $message = nl2br($this->getMessage());
        if (wa()->getApp()) {
            $app = wa()->getAppInfo();
        } else {
            $app = array();
        }
        if (!waSystem::getInstance()->getConfig()->isDebug()) {
            $file = $code = $this->getCode();
            if (!$code || !file_exists(dirname(__FILE__).'/data/'.$code.'.php')) {
                $file = 'error';
            }
            include(dirname(__FILE__).'/data/'.$file.'.php');
            exit;
        }

        if (waSystem::getInstance()->getEnv() == 'cli') {
            return date("Y-m-d H:i:s")." php ".implode(" ", waRequest::server('argv'))."\n".
            "Error with code {$this->getCode()} in '{$this->getFile()}' around line {$this->getLine()}:{$this->getFileContext()}\n".
            $this->getTraceAsString()."\n";
        }

        $result = <<<HTML
<div style="width:99%; position:relative">
<h2 id='Title'>{$message}</h2>
<div id="Context" style="display: block;"><h3>Error with code {$this->getCode()} in '{$this->getFile()}' around line {$this->getLine()}:</h3><pre>{$this->getFileContext()}</pre></div>
<div id="Trace"><h2>Call stack</h2><pre>{$this->getTraceAsString()}</pre></div>
<div id="Request"><h2>Request</h2><pre>
HTML;
        $result .= var_export($_REQUEST, true);
        $result .= "</pre></div></div>
<div><h2>Params</h2><pre>";
        $result .= var_export(waSystem::getInstance()->getRequest()->param(), true);
        $result .= "</pre></div></div>";
        return $result;
    }
}

