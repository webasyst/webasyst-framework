<?php

$_im_field = waContactFields::get('im');
if ($_im_field instanceof waContactStringField) {
    $_ext_variants = $_im_field->getParameter('ext');
    if (is_array($_ext_variants)) {
        $_ext_variants = array_merge([
            'vk' => 'VK Messenger',
            'telegram' => 'Telegram',
            'max' => 'MAX',
            'whatsapp' => 'WhatsApp',
            'viber' => 'Viber',
            'facebook' => 'Facebook Messenger',
            'wechat' => 'WeChat',
            'qq' => 'QQ',
            'line' => 'Line',
            'signal' => 'Signal',
            'discord' => 'Discord',
            'slack' => 'Slack',
        ], $_ext_variants);
        $_ext_variants['vk'] = 'VK Messenger';
        $_im_field->setParameter('ext', $_ext_variants);
        try {
            waContactFields::updateField($_im_field);
        } catch (waException $e) {
            waLog::log($e->getMessage());
        }
    }
}
