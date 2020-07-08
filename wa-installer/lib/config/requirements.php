<?php
/**
 *
 * @author Webasyst LLC
 * @package wa-installer
 *
 */
return array(
    'server'                        => array(),
    'php'                           => array(
        'strict'  => true,
        'version' => '>=5.6.25',
    ),
    'php.curl'                      => array(
        'description' => 'Get updates information from update servers',
        'strict'      => false,
    ),
    'php.ctype'                      => array(
        'description' => 'String type function',
        'strict'      => true,
    ),
    'php.json'                      => array(
        'description' => 'JavaScript data format support',
        'strict'      => true,
    ),
    'phpini.allow_url_fopen'        => array(
        'description' => 'Get updates information from update servers',
        'strict'      => false,
        'value'       => 1,
    ),
    'php.mbstring'                  => array(
        'strict' => true,
        'value'  => 1,
    ),
    'phpini.mbstring.func_overload' => array(
        'description' => 'Smarty properly work',
        'strict'      => true,
        'value'       => '<2',
    ),
    'php.dom'                       => array(
        'strict' => true,
        'value'  => 1,
    ),
    'php.simplexml'                 => array(
        'strict' => true,
        'value'  => 1,
    ),
    'rights'                        => array(
        'subject'     => array(
            '.',
            'wa-installer',
            'install.php',
            'index.php',
            'api.php',
            'wa-log',
            'wa-data/protected',
            'wa-apps',
            'wa-content',
            'wa-cache',
        ),
        'description' => 'Check folder rights for install&amp;update',
        'strict'      => true,
    ),
    'server.mod_rewrite'            => array(
        'description' => 'Use friendly URLs',
        'strict'      => false,
        'config'      => 'mod_rewrite',
    ),
    'md5'                           => array(
        'subject'     => '*.tar.gz|*.php',
        'description' => 'Check archives and files checksum',
        'strict'      => false,
        'silent'      => true,
        'allow_skip'  => true,
    ),
);
