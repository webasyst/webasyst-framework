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

    // List of news feeds in widget settings depends on locale
    protected function getSettingsConfig()
    {
        $feeds = array(
            'http://rss.nytimes.com/services/xml/rss/nyt/InternationalHome.xml'
                => 'New York Times',
            'http://feeds.washingtonpost.com/rss/world'
                => 'Washington Post',
            'http://www.theguardian.com/world/rss'
                => 'The Guardian',
        );

        if (wa()->getLocale() == 'ru_RU') {
            $feeds = array_merge(array(
                'https://news.yandex.ru/index.rss'
                    => 'Яндекс.Новости',
                'http://russian.rt.com/rss/'
                    => 'Russia Today (на русском)',
            ), $feeds);
        } else {
            $feeds = array_merge($feeds, array(
                'http://rt.com/rss/news/'
                    => 'Russia Today',
            ));
        }

        $feeds = array_merge($feeds, array(
            'custom'
                => 'RSS feed:',
        ));

        $result = parent::getSettingsConfig();
        $result['rss_feed']['options'] = $feeds;

        // Select first feed by default if nothing is selected
        if (empty($result['rss_feed']['value'])) {
            $result['rss_feed']['value'] = key($feeds);
        }

        return $result;
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

