<?php

/**
 *
 * @link http://www.webasyst.com/developers/docs/features/localization/
 *
 */

require_once dirname(__FILE__).'/waGettext.class.php';
require_once dirname(__FILE__).'/waGettextParser.class.php';

// start script

if (count($argv) < 2) {
    $help = <<<HELP
Usage: php locale.php slug [params]
Slug examples:
    myapp
    someapp/plugins/myplugin
    someapp/themes/mytheme
    someapp/widgets/mywidget
    wa-widgets/myotherwidget
    wa-plugins/payment/myplugin
    wa-plugins/shipping/myplugin
    wa-plugins/sms/myplugin
Optional parameters:
    verify
    debug
HELP;

    die($help);
}

@mb_internal_encoding('UTF-8');
@ini_set('default_charset', 'utf-8');

@ini_set('register_globals', 'off');
// magic quotes
@ini_set("magic_quotes_runtime", 0);
if (version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()) {
    @set_magic_quotes_runtime(false);
}

$app_id = $argv[1];
$locale_id = trim($app_id, '/');
if ($app_id == 'webasyst') {
    $path = realpath(dirname(__FILE__)."/../../")."/wa-system/";
    $include = array(
        substr($path, 0, -1)
    );
} elseif (strpos($app_id, 'wa-widgets/') === 0) {
    $path = realpath(dirname(__FILE__)."/../../").'/';
    $locale_id = str_replace(array('wa-widgets/', '/'), array('widget_', '_'), $locale_id);
    $include = array(
        $path.$app_id,
    );
} elseif (strpos($app_id, 'wa-plugins/') === 0) {

    $path = realpath(dirname(__FILE__)."/../../").'/';
    $locale_id = str_replace(array('wa-plugins/', '/'), array('', '_'), $locale_id);
    $include = array(
        $path.$app_id,
    );
} elseif (strpos($app_id, '/themes/')) {

    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = str_replace('/themes/', '_', $locale_id);
    $include = array(
        $path.$app_id,
    );
} elseif (strpos($app_id, '/plugins/')) {

    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = str_replace('/plugins/', '_', $locale_id);
    $include = array(
        $path.$app_id."/templates",
        $path.$app_id."/js",
        $path.$app_id."/lib",
    );
} elseif (strpos($app_id, '/widgets/')) {
    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = str_replace('/widgets/', '_widget_', $locale_id);
    $include = array(
        $path.$app_id."/templates",
        $path.$app_id."/js",
        $path.$app_id."/lib",
    );
} else {
    $path = realpath(dirname(__FILE__)."/../../")."/wa-apps/";
    $locale_id = basename($locale_id);
    $include = array(
        $path.$app_id."/templates",
        $path.$app_id."/js",
        $path.$app_id."/lib",
        $path.$app_id."/themes/default",
    );
}

if (!file_exists($path)) {
    die("Application ".$app_id." does not exists\n");
}


$config = array(
    'project' => $app_id,
    'include' => ".+\\.(html|(?<!min\\.)js|php)",
    'path'    => $path.$app_id."/locale",
    'locales' => array()
);
$locales_path = realpath(dirname(__FILE__)."/../../")."/wa-config/locale.php";
if (file_exists($locales_path)) {
    $locales = include($locales_path);
} else {
    $locales = array('en_US', 'ru_RU',);
}

foreach ($locales as $l) {
    $config['locales'][$l] = $locale_id;
}

if (in_array('verify', $argv)) {
    $config['verify'] = true;
}

if (in_array('debug', $argv)) {
    $config['debug'] = true;
}
$parser = new waGettextParser($config);

$parser->exec($include);
