<?php

return array (
    'name' => 'Символ рубля',
    'img' => 'img/rublesign.png',
    'version' => '1.0.0',
    'vendor' => 'webasyst',
    'site_settings' => true,
    'handlers' => array (
        '*' => array(
            array(
                'event_app_id' => 'webasyst',
                'event' => 'backend_header',
                'class' => 'siteRublesignPlugin',
                'method' => 'backendHeader',
            ),
            array(
                'event_app_id' => 'shop',
                'event' => 'frontend_head',
                'class' => 'siteRublesignPlugin',
                'method' => 'frontendHead',
            ),
            array(
                'event_app_id' => 'site',
                'event' => 'frontend_page',
                'class' => 'siteRublesignPlugin',
                'method' => 'frontendPage',
            ),
        ),
    ),
);