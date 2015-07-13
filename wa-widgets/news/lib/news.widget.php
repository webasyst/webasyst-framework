<?php

class newsWidget extends waWidget
{
    protected static $instance = null;

    public function __construct($info)
    {
        parent::__construct($info);
        self::$instance = $this;
    }

    public function defaultAction()
    {
        $settings = $this->getSettings();
        $rss_url = ifempty($settings['rss_feed']);
        if ($rss_url == 'custom') {
            $rss_url = ifempty($settings['custom_rss_feed']);
        }
        $this->display(array(
            'rss_url' => $rss_url,
            'uniqid' => 'n'.uniqid(true),
        ), $this->getTemplatePath('Default.html'));
    }

    // Callback for custom settings control, see settings.php
    public static function getCustomRssControl($field_name, $field_params)
    {
        self::$instance || wa('webasyst')->getWidget('news');

        return self::$instance->display(array(
            'field_name' => $field_name,
            'field_value' => $field_params['value'],
            'uniqid' => 'n'.uniqid(true),
        ), self::$instance->getTemplatePath('CustomRssControl.html'), true);
    }
}

