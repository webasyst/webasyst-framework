<?php
return array(
    'description'         => array(
        'value'        => 'Оплата заказа {$order.id}',
        'title'        => 'Назначение платежа',
        'description'  => 'В описании назначения платежа можно использовать переменную <strong>{$order.id}</strong> — она будет автоматически заменена на номер заказа',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'company_name'         => array(
        'value'        => '',
        'title'        => 'Название организации',
        'description'  => 'Квитанции будут выписываться от имени организации с указанными здесь реквизитами',
        'control_type' => waHtmlControl::INPUT,
    ),
    'bank_account_number' => array(
        'value'        => '',
        'title'        => 'Расчетный счет',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'inn'                 => array(
        'value'        => '',
        'title'        => 'ИНН',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'kpp'                 => array(
        'value'        => '',
        'title'        => 'КПП',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'bank_name'            => array(
        'value'        => '',
        'title'        => 'Наименование банка',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'bank_kor_number'     => array(
        'value'        => '',
        'title'        => 'Корреспондентский счет',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'bik'                 => array(
        'value'        => '',
        'title'        => 'БИК',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'emailprintform'  => array(
        'value'        => true,
        'title'        => 'Отправлять квитанцию покупателю по email',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
