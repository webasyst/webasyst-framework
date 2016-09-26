<?php
return array(
    'public_key' => array(
        'value'        => '',
        'title'        => 'Публичный ключ',
        'description'  => 'Идентификатор магазина. Получить ключ можно в настройках магазина вашего аккаунта в платежной системе.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key' => array(
        'value'        => '',
        'title'        => 'Приватный ключ',
        'description'  => ' Получить ключ можно в настройках магазина вашего аккаунта в платежной системе.',
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'sandbox'    => array(
        'value'        => true,
        'title'        => 'Тестовый режим',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
