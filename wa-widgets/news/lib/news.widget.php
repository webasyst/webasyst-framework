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

        $data = $this->getRssData($rss_url);

        $this->display(array(
            'rss_url'      => $rss_url,
            'channel'      => array(
                'name' => ifset($data['channel']['name']),
                'link' => ifset($data['channel']['link']),
            ),
            'items'        => ifset($data['items'], array()),
            'widget_id'    => $this->id,
            'widget_url'   => $this->getStaticUrl(),
            'uniqid'       => 'n'.uniqid(true),
        ), $this->getTemplatePath('Default.html'));
    }

    // List of news feeds in widget settings depends on locale
    protected function getSettingsConfig()
    {
        $feeds = array(
            'http://rss.nytimes.com/services/xml/rss/nyt/InternationalHome.xml' => 'New York Times',
            'http://feeds.washingtonpost.com/rss/world'                         => 'Washington Post',
            'http://www.theguardian.com/world/rss'                              => 'The Guardian',
        );

        if (wa()->getLocale() == 'ru_RU') {
            $feeds = array_merge(array(
                'https://news.yandex.ru/index.rss' => 'Яндекс.Новости',
                'http://russian.rt.com/rss/'       => 'Russia Today (на русском)',
                'https://meduza.io/rss/all'        => 'Meduza.io',
            ), $feeds);
        } else {
            $feeds = array_merge($feeds, array(
                'http://rt.com/rss/news/' => 'Russia Today',
            ));
        }

        $feeds = array_merge($feeds, array(
            'custom' => 'RSS feed:',
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
            'field_name'  => $field_name,
            'field_value' => $field_params['value'],
            'uniqid'      => 'n'.uniqid(true),
        ), self::$instance->getTemplatePath('CustomRssControl.html'), true);
    }

    public function getRssData($rss_url)
    {
        if (!$rss_url) {
            return array();
        }

        $xml = @simplexml_load_file($rss_url);
        if ($xml) {
            $data = array(
                'channel' => array(
                    'name' => $xml->channel->title,
                    'link' => $xml->channel->link,
                ),
                'items'   => array(),
            );
            foreach ($xml->xpath('//item') as $item) {
                $data['items'][] = array(
                    'title' => $item->title,
                    'link'  => $item->link,
                    'date'  => $item->pubDate,
                );
            }
            return $data;
        }
    }
}

