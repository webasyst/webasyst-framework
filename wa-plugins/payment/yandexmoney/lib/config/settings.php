<?php
return array(
    'ShopID'       => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
    ),
    'scid'         => array(
        'value'        => '',
        'title'        => 'Номер витрины',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
    ),

    'shopPassword' => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => waHtmlControl::PASSWORD,
    ),

    'TESTMODE'     => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => 'Используется для оплаты в демо-рублях.',
        'control_type' => 'checkbox',
    ),
);
