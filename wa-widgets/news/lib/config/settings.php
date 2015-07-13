<?php

return array(
    'rss_feed' => array(
        'value'        => '',
        'title'        => 'News feed',
        'description'  => '',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'https://news.yandex.ru/index.rss'
                => 'Яндекс.Новости',
            'http://rss.nytimes.com/services/xml/rss/nyt/InternationalHome.xml'
                => 'New York Times',
            'http://feeds.washingtonpost.com/rss/world'
                => 'Washington Post',
            'http://www.theguardian.com/world/rss'
                => 'The Guardian',
            'http://rt.com/rss/news/'
                => 'Russia Today',
            'http://russian.rt.com/rss/'
                => 'Russia Today (на русском)',
            'custom'
                => 'RSS feed:',
        ),
    ),

    'custom_rss_feed' => array(
        'title' => '',
        'value' => '',
        'control_type' => waHtmlControl::CUSTOM.' '.'newsWidget::getCustomRssControl',
    ),
);