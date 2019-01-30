<?php

$asm = new waAppSettingsModel();

$asm->set('webasyst', 'map_adapter', $asm->get('webasyst', 'map_provider'));

if ($asm->get('webasyst', 'map_provider') === 'google') {
    $key = $asm->get('webasyst', 'google_map_key');
    if ($key) {
        $settings = $asm->get('webasyst', 'map_adapter_google');
        $settings = $settings ? json_decode($settings, true) : null;
        $settings = (array) $settings;
        $settings['key'] = $key;
        $asm->set('webasyst', 'map_adapter_google', json_encode($settings));
    }
}
$asm->del('webasyst', 'google_map_key');
$asm->del('webasyst', 'map_provider');
