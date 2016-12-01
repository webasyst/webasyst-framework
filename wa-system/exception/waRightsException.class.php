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
class waRightsException extends waException
{
    public function __construct($message=null, $code=403, $previous = null)
    {
        if ($message === null) {
            $message = _ws('Access denied');
        }
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        if (waSystemConfig::isDebug() || wa()->getEnv() !== 'backend') {
            return parent::__toString();
        }

        $t = "_ws";
        $content = <<<HTML
  <h1>{$t("Error")} #403</h1>
  <div style="border:1px solid #EAEAEA;padding:1.5em 1.5em 0 1.5em;margin:12px 0">
  <p style="color:red; font-weight: bold">{$t("You have no permission to access this page.")}</p>

  <p>{$t("Please refer to your system administrator.")}</p>
  </div>
HTML;

        if (waRequest::isXMLHttpRequest()) {
            // Modify HTTP response code when exception propagated all the way up the stack.
            $send_response_code = true;
            if(function_exists('debug_backtrace')) {
                $send_response_code = count(debug_backtrace()) <= 1;
            }
            if ($send_response_code) {
                $this->sendResponseCode();
            }
            return $content;
        } else {
            $app_settings_model = new waAppSettingsModel();
            $account_name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
            $wa_url = wa()->getRootUrl();
            $version = wa()->getVersion('webasyst');
            $wa_header = wa_header();

            $viewport = '';
            if (!waRequest::isMobile(false)) {
                $viewport = '<meta name="viewport" content="width=device-width, initial-scale=1" />'; //for handling iPad and tablet computer default view properly
            }

            return <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$t("Welcome")} &mdash; {$account_name}</title>
<link href="{$wa_url}wa-content/css/wa/wa-1.3.css?v{$version}" rel="stylesheet" type="text/css" >
<!--[if IE 9]><link type="text/css" href="{$wa_url}wa-content/css/wa/wa-1.0.ie9.css?v{$version}" rel="stylesheet"><![endif]-->
<!--[if IE 8]><link type="text/css" href="{$wa_url}wa-content/css/wa/wa-1.0.ie8.css?v{$version}" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="{$wa_url}wa-content/css/wa/wa-1.0.ie7.css?v{$version}" rel="stylesheet"><![endif]-->
<link type="text/css" rel="stylesheet" href="{$wa_url}wa-content/font/ruble/arial/fontface.css">
{$viewport}
<script src="{$wa_url}wa-content/js/jquery/jquery-1.11.1.min.js"></script>
</head>
<body>
{$wa_header}
<div id="wa-app" class="block double-padded">
    {$content}
</div></body></html>
HTML;
        }
    }
}