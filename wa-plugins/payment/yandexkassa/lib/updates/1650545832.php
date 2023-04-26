<?php

if (wa()->appExists('shop')) {
    wa('shop');

    $plugin_model = new shopPluginModel();
    $plugin_ids = $plugin_model->select('id')->where('`type` = "payment" AND `plugin` = "yandexkassa"')->fetchAll('id');
    if ($plugin_ids) {
        $settings_model = new shopPluginSettingsModel();
        $fields = [
            'id' => array_keys($plugin_ids),
            'name' => 'payment_type',
            'value' => 'yandex_money'
        ];
        $settings_model->updateByField($fields, ['value' => 'yoo_money']);
    }
}