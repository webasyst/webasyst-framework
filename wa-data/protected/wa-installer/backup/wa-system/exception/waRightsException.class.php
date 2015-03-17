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
    public function __construct($message, $code=403)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        $wa_url = wa()->getRootUrl();
        $app_settings_model = new waAppSettingsModel();
        $account_name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
        $wa_header = wa_header();
        $t = "_ws";
        $html = "";
        if (!waRequest::isXMLHttpRequest()) {
            $html .= <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$t("Welcome")} &mdash; {$account_name}</title>
<link href="{$wa_url}wa-content/css/wa/wa-1.0.css" rel="stylesheet">
<!--[if IE 8]><link type="text/css" href="{$wa_url}wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="{$wa_url}wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->
<script src="{$wa_url}wa-content/js/jquery/jquery-1.7.1.min.js"></script>
</head>
<body>
{$wa_header}
<div id="wa-app" class="block double-padded">
HTML;
        } else {
            $response = new waResponse();
            $response->setStatus(403);
            $response->sendHeaders();
        }
        $html .= <<<HTML
  <h1>{$t("Error")} #403</h1>
  <div style="border:1px solid #EAEAEA;padding:10px; margin:10px 0">
  <p style="color:red; font-weight: bold">{$t("You have no permission to access this page.")}</p>

  <p>{$t("Please refer to your system administrator.")}</p>
  </div>
HTML;
        if (!waRequest::isXMLHttpRequest()) {
            $html .= "</div></body></html>";
        }
        return $html;
    }
}