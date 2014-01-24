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
    'payment_mode' => array(
        'value'        => '',
        'options'      => array(
            ''         => 'по умолчанию',
            'customer' => 'на выбор покупателя',
            'PC'       => 'платеж со счета в Яндекс.Деньгах.',
            'AC'       => 'платеж с банковской карты.',
            'GP'       => 'платеж по коду через терминал.',
            'MC'       => 'оплата со счета мобильного телефона.',
        ),
        'title'        => 'Способы оплаты',
        'description'  => 'Настройки выбора способа оплаты.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type' => waHtmlControl::RADIOGROUP,
    ),
    'paymentType'  => array(
        'value'        => array('PC' => true,),
        'options'      => yandexmoneyPayment::settingsPaymentOptions(),
        'title'        => 'Варианты для способа оплаты «на выбор покупателя»',
        'description'  => 'Настройки доступных способов оплаты для выбора покупателям.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type' => waHtmlControl::GROUPBOX,
    ),

    'TESTMODE'     => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => 'Используется для оплаты в демо-рублях.',
        'control_type' => 'checkbox',
    ),
);
