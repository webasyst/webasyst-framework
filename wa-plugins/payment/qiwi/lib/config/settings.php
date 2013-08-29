<?php
return array(
    'login'          => array(
        'value'        => '',
        'title'        => 'Идентификатор (логин)',
        'description'  => '',
        'control_type' => 'input',
    ),
    'password'       => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => 'password',
    ),
    'lifetime'       => array(
        'value'        => 24,
        'title'        => 'Время жизни счета',
        'description'  => 'Укажите срок оплаты счета в часах.',
        'control_type' => 'input',
    ),
    'alarm'          => array(
        'value'        => 0,
        'title'        => 'Уведомления',
        'description'  => 'Способ уведомления покупателя о состоянии счета в системе QIWI',
        'control_type' => 'select qiwiPayment::_getAlarmVariants',
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
    'TESTMODE'       => array(
        'value'        => false,
        'title'        => 'Обрабатывать запросы без пароля',
        'description'  => 'Используйте этот режим для обработки запросов, инициированных вручную из личного кабинета QIWI.',
        'control_type' => 'checkbox',
    ),
);
