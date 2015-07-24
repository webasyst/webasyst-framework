<?php

return array(
    'rss_feed' => array(
        'value'        => '',
        'title'        => /*_w*/('News feed'),
        'description'  => '',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
           /* see newsWidget::getSettingsConfig() */
        ),
    ),

    'custom_rss_feed' => array(
        'title' => '',
        'value' => '',
        'control_type' => waHtmlControl::CUSTOM.' '.'newsWidget::getCustomRssControl',
    ),
);