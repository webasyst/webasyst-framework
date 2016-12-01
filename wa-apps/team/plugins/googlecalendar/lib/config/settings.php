<?php

return array(
    'top_block' => array(
        'control_type' => waHtmlControl::CUSTOM .' '.'teamGooglecalendarPlugin::getTopBlockHtml'
    ),
    'client_id' => array(
        'value' => '',
        'required' => true,
        'placeholder' => /*_wp*/('Client ID'),
        'title' => /*_wp*/('Client ID'),
        'description' => /*_wp*/('Issued by Google for your Web application'),
        'control_type' => waHtmlControl::INPUT
    ),
    'client_secret' => array(
        'value' => '',
        'required' => true,
        'placeholder' => /*_wp*/('Client secret'),
        'title' => /*_wp*/('Client secret'),
        'description' => /*_wp*/('Issued by Google for your Web application'),
        'control_type' => waHtmlControl::PASSWORD
    ),
    'bottom_block' => array(
        'control_type' => waHtmlControl::CUSTOM .' '.'teamGooglecalendarPlugin::getBottomBlockHtml'
    )
);