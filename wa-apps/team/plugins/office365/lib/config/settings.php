<?php

return array(
    'top_block' => array(
        'control_type' => waHtmlControl::CUSTOM .' '.'teamOffice365Plugin::getTopBlockHtml'
    ),
    'client_id' => array(
        'value' => '',
        'required' => true,
        'placeholder' => /*_wp*/('Application ID'),
        'title' => /*_wp*/('Application ID'),
        'description' => /*_wp*/('Issued by Microsoft for your Web application'),
        'control_type' => waHtmlControl::INPUT
    ),
    'client_secret' => array(
        'value' => '',
        'required' => true,
        'placeholder' => /*_wp*/('Application secret'),
        'title' => /*_wp*/('Application secret'),
        'description' => /*_wp*/('Issued by Microsoft for your Web application'),
        'control_type' => waHtmlControl::PASSWORD
    ),
    'bottom_block' => array(
        'control_type' => waHtmlControl::CUSTOM .' '.'teamOffice365Plugin::getBottomBlockHtml'
    )
);