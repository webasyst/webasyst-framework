<?php
return array(
    'userName'           => array(
        'value'        => '',
        'title'        => 'Логин магазина',
        'description'  => 'Выдаётся при подключении к платёжной системе',
        'control_type' => waHtmlControl::INPUT,
        'class'        => '',
    ),
    'password'           => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => 'Выдаётся при подключении к платёжной системе',
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => ''
    ),
    'currency_id'        => array(
        'value'        => '',
        'title'        => 'Валюта',
        'description'  => 'Валюта, в которой будут выполняться платежи',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'RUB', 'value' => 'RUB'),
        ),
    ),
    'tax_system'         => array(
        'value'        => '',
        'title'        => 'Система налогообложения',
        'description'  => 'Выберите для правильной фискализации платежей',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '0' => 'Общая',
            '1' => 'Упрощённая, доход',
            '2' => 'Упрощённая, доход минус расход',
            '3' => 'Единый налог на вменённый доход',
            '4' => 'Единый сельскохозяйственный налог',
            '5' => 'Патентная система налогообложения',
        ),
    ),
    'fiscalization'      => array(
        'value'        => '',
        'title'        => 'Фискализация платежей',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => ''
    ),
    'two_step'           => array(
        'value'        => '',
        'title'        => 'Двухстадийная оплата',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Включите, если ваш договор предусматривает необходимость ручного потверждения платежей.<br>В противном случае оставьте этот параметр отключенным.'
    ),
    'TESTMODE'           => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => ''
    ),
    'cancel'           => array(
        'value'        => '',
        'title'        => 'Отмена платежей',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Отмена платежей должна поддерживаться приложением.<br>Пример поддерживаемого приложения: CRM.'
    ),
    'sessionTimeoutSecs' => array(
        'value'        => '24',
        'title'        => 'Продолжительность жизни заказа в часах',
        'description'  => 'Если не заполнить, то будет использовано значение по умолчанию: 24 часа. По истечении указанного периода платеж по «устаревшему» заказу сформирует новый заказ в личном кабинете «Сбербанка».',
        'control_type' => waHtmlControl::INPUT,
        'class'        => '',
        'placeholder' => '24',
    ),
);
