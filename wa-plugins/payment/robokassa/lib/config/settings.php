<?php

return array(
    'merchant_login'    => array(
        'value'        => 'demo',
        'title'        => 'Логин',
        'description'  => 'Логин магазина в обменном пункте',
        'control_type' => waHtmlControl::INPUT,
    ),
    'merchant_pass1'    => array(
        'value'        => '',
        'title'        => 'Пароль №1',
        'description'  => 'Вводится в настройках аккаунта в ROBOXchange.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'merchant_pass2'    => array(
        'value'        => '',
        'title'        => 'Пароль №2',
        'description'  => 'Вводится в настройках аккаунта в ROBOXchange.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'locale'            => array(
        'value'        => '',
        'title'        => 'Язык интерфейса',
        'description'  => 'Выберите язык, на котором должна отображаться платежная страница на сайте ROBOXchange',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'ru' => 'русский',
            'en' => 'английский',
            ''   => '(не определен)',
        ),
    ),
    'gateway_currency'  => array(
        'value'        => '',
        'title'        => 'Валюта шлюза',
        'description'  => '',
        'control_type' => 'GatewayCurrency',
    ),
    'merchant_currency' => array(
        'value'        => 'RUB',
        'title'        => 'Валюта, указанная при регистрации магазина',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'testmode'          => array(
        'value'        => '1',
        'title'        => 'Тестовый режим',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
