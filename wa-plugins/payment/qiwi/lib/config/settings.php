<?php
return array(
    'protocol'       => array(
        'value'        => 'rest',
        'title'        => 'Способ подключения',
        'control_type' => 'select',
        'options'      => array(
            'rest' => 'Протокол Pull (REST)',
            'soap' => 'Протокол SOAP (устаревший)',
        ),
    ),
    'login'          => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'placeholder'  => '12345',
        'description'  => 'Идентификатор магазина (Shop ID)',
        'class'        => 'small',
        'control_type' => 'input',
    ),

    'api_login'      => array(
        'value'        => '',
        'title'        => 'API ID',
        'description'  => 'Аутентификационные данные для всех протоколов',
        'placeholder'  => '1234567',
        'class'        => 'small',
        'control_type' => 'input',

    ),

    'password'       => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => 'Пароль для выбранного протокола',
        'control_type' => 'password',
    ),

    'sign_password'  => array(
        'value'        => '',
        'placeholder'  => '',
        'title'        => 'Пароль оповещения',
        'description'  => 'Пароль оповещения для протокола Pull (REST)<br><strong>Рекомендуется включить подпись в настройках Pull (REST) протокола в личном кабинете QIWI</strong>',
        'control_type' => 'password',
    ),

    'prv_name'       => array(
        'value'        => '',
        'placeholder'  => '',
        'title'        => 'Продавец',
        'description'  => 'Название провайдера, которое будет показано клиенту (произвольная строка до 100 символов)',
        'control_type' => 'text',
    ),

    'lifetime'       => array(
        'value'        => 24,
        'title'        => 'Время жизни счета',
        'description'  => 'Укажите срок оплаты счета в часах.',
        'class'=>'small',
        'control_type' => 'input',
    ),

    'alarm'          => array(
        'value'        => 0,
        'title'        => 'Уведомления',
        'description'  => 'Способ уведомления покупателя о состоянии счета в системе QIWI',
        'control_type' => 'select',
        'options'      => array(
            0 => 'не уведомлять',
            1 => 'уведомление SMS-сообщением',
            2 => 'уведомление звонком',
        ),
    ),
    'prefix'         => array(
        'value'        => '',
        'title'        => 'Префикс счета',
        'description'  => 'Введите префикс номера счета в системе QIWI с использованием цифр и латинских букв.',
        'control_type' => 'input',
    ),
    'customer_phone' => array(
        'value'        => 'phone',
        'title'        => 'Телефон клиента',
        'description'  => 'Выберите поле вашей формы регистрации, предназначенное для ввода телефонного номера клиента.',
        'control_type' => 'contactfield',
    ),
    'test'           => array(
        'value'        => false,
        'title'        => 'Обрабатывать тестовые запросы',
        'description'  => 'Используйте этот режим для тестирования запросов, инициированных вручную из личного кабинета QIWI.',
        'control_type' => 'checkbox',
    ),
);
