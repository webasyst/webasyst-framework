<?php

$_im_field = waContactFields::get('im');
if ($_im_field instanceof waContactStringField) {
    $_ext_variants = $_im_field->getParameter('ext');
    if (is_array($_ext_variants)) {
        $_ext_variants = array_merge([
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'skype' => 'Skype',
            'viber' => 'Viber',
            'facebook' => 'Facebook Messenger',
            'discord' => 'Discord',
            'slack' => 'Slack',
            'wechat' => 'WeChat',
            'signal' => 'Signal',
        ], $_ext_variants);
        $_im_field->setParameter('ext', $_ext_variants);
        try {
            waContactFields::updateField($_im_field);
        } catch (waException $e) {
            waLog::log($e->getMessage());
        }
    }
}
