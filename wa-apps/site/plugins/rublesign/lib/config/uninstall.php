<?php

$app_settings = new waAppSettingsModel();
$is_foreign_config = $app_settings->get('site.rublesign', 'is_foreign_config', 0);
if (empty($is_foreign_config)) {
    $path = wa()->getConfig()->getPath('config', 'currency');
    waFiles::Delete($path, true);
}

$app_settings->del('site.rublesign', 'status');
$app_settings->del('site.rublesign', 'is_foreign_config');
$app_settings->del('site.rublesign', 'sign');
