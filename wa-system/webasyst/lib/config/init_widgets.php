<?php
/**
 * This configures initial dashboard widgets for new users.
 *
 * Custom config may be placed here:
 *   * wa-config/apps/webasyst/init_widgets.php
 * When it exists, it will be used instead of this file.
 *
 * First locale specified will be used if actual user's locale
 * is not found in this config.
 */
return array(
    'en_US' => array(
        array(
            //'app_id' => 'webasyst',
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'http://rss.nytimes.com/services/xml/rss/nyt/InternationalHome.xml',
            ),
        ),
        array(
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'http://feeds.washingtonpost.com/rss/world',
            ),
        ),
        // Block with 3 clock widgets
        array(
            array(
                'widget' => 'clock',
                'size' => '2x1',
                'params' => array(
                    'source' => 'local',
                    'type' => 'round',
                ),
            ),
            array(
                'widget' => 'clock',
                'size' => '1x1',
                'params' => array(
                    'source' => '-4',
                    'town' => 'New York',
                    'type' => 'round',
                ),
            ),
            array(
                'widget' => 'clock',
                'size' => '1x1',
                'params' => array(
                    'source' => '1',
                    'town' => 'London',
                    'type' => 'round',
                ),
            ),
        ),
        array(
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'custom',
                'custom_rss_feed' => 'http://feeds.feedburner.com/webasystcom',
            ),
        ),
        array(
            'widget' => 'weather',
            'size' => '2x2',
            'params' => array(
                'city' => 'New York',
                'unit' => 'F',
            ),
        ),
        array(
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'http://www.theguardian.com/world/rss',
            ),
        ),
    ),
    'ru_RU' => array(
        array(
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'https://news.yandex.ru/index.rss',
            ),
        ),
        array(
            'widget' => 'currencyquotes',
            'size' => '2x2',
        ),
        // Block with 3 clock widgets
        array(
            array(
                'widget' => 'clock',
                'size' => '2x1',
                'params' => array(
                    'source' => 'local',
                    'type' => 'round',
                ),
            ),
            array(
                'widget' => 'clock',
                'size' => '1x1',
                'params' => array(
                    'source' => '3',
                    'town' => 'Москва',
                    'type' => 'round',
                ),
            ),
            array(
                'widget' => 'clock',
                'size' => '1x1',
                'params' => array(
                    'source' => '8',
                    'town' => 'Гонконг',
                    'type' => 'round',
                ),
            ),
        ),
        array(
            'widget' => 'weather',
            'size' => '2x2',
            'params' => array(
                'city' => 'Москва',
                'unit' => 'C',
            ),
        ),
        array(
            'widget' => 'traffic',
            'size' => '2x2',
            'params' => array(
                'city' => 'Москва',
            ),
        ),
        array(
            'widget' => 'news',
            'size' => '2x2',
            'params' => array(
                'rss_feed' => 'custom',
                'custom_rss_feed' => 'http://feeds.feedburner.com/webasystcom/ru',
            ),
        ),
    ),
);
