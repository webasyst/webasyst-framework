<?php

$model = new waModel();

try {
    $model->query("SELECT `name` FROM `shop_plugin_settings` WHERE 0");

    $atolonline_on = "update shop_plugin_settings set `name` = 'check_data_tax' where `name` = 'atolonline_on'";
    $model->exec($atolonline_on);
    $atolonline_sno = "update shop_plugin_settings set `name` = 'taxation' where `name` = 'atolonline_sno'";
    $model->exec($atolonline_sno);
} catch (waException $exception) {

}
