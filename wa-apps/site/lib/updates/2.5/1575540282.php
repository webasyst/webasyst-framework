<?php

/*
 * This will remove webasyst social network links from default theme settings
 * if hardcoded in wa-data/public/site/themes/default/theme.xml
 */

$theme = new waTheme('default', 'site');

// Don't do anything unless theme is copied to wa-data
if (!$theme->path_custom) {
    return;
}

// Make sure this meta-update is not run concurrently
$file_lock_path = $theme->path_custom.'/.htaccess';
if (!file_exists($file_lock_path)) {
    return;
}
$fd = fopen($file_lock_path, 'r');
if ($fd) {
    flock($fd, LOCK_EX);
}

$replacements = [
    'facebook' => [
        'new_value' => '#',
        'should_replace' => function($value) {
            return $value == 'https://www.facebook.com/Webasyst'
                || $value == 'https://www.facebook.com/Webasyst.RU';
        },
    ],
    'twitter' => [
        'new_value' => '#',
        'should_replace' => function($value) {
            return $value == 'https://twitter.com/webasyst'
                || $value == 'https://twitter.com/webasyst_ru';
        },
    ],
    'vk' => [
        'new_value' => '#',
        'should_replace' => function($value) {
            return $value == 'https://vk.com/webasyst_ru';
        },
    ],
    'instagram' => [
        'new_value' => '#',
        'should_replace' => function($value) {
            return $value == 'https://instagram.com/webasyst.ru';
        },
    ],
    'youtube' => [
        'new_value' => '#',
        'should_replace' => function($value) {
            return $value == 'https://www.youtube.com/user/webasyst';
        },
    ],
    'facebook_likebox_code' => [
        'new_value' => '',
        'should_replace' => function($value) {
            // also works for https://www.facebook.com/Webasyst.RU
            return false !== strpos($value, 'https://www.facebook.com/Webasyst');
        },
    ],
    'twitter_timeline_code' => [
        'new_value' => '',
        'should_replace' => function($value) {
            // also works for https://twitter.com/webasyst_ru
            return false !== strpos($value, 'https://twitter.com/webasyst');
        },
    ],
    'vk_widget_code' => [
        'new_value' => '',
        'should_replace' => function($value) {
            // 21415010 is internal id of vk group
            return false !== strpos($value, ', 21415010)');
        },
    ],
];

$theme = new waTheme('default', 'site', waTheme::CUSTOM);
$settings = $theme['settings'];

//$old_values = waUtils::getFieldValues($settings, 'value', true);

$settings_update = [];
foreach($replacements as $var => $data) {
    $old_value = ifset($settings, $var, 'value', null);
    $fn_should_replace = $data['should_replace'];
    if ($old_value && $fn_should_replace($old_value)) {
        $settings_update[$var] = ifset($data, 'new_value', '');
    }
}
if ($settings_update) {
    $theme['settings'] = $settings_update;
    /*wa_dump(
        $settings_update,
        array_diff_key($old_values, $settings_update)
            == array_diff_key(waUtils::getFieldValues($settings, 'value', true), $settings_update)
    );*/
    $theme->save();
}

if ($fd) {
    fclose($fd);
}
