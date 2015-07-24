<?php
return array(
    'company_name'        => array(
        'value'        => '',
        'title'        => 'Название организации',
        'description'  => 'Счета будут выставляться от имени организации с указанными здесь реквизитами',
        'control_type' => waHtmlControl::INPUT,
    ),
    'company_address'     => array(
        'value'        => '',
        'title'        => 'Адрес',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'company_phone'       => array(
        'value'        => '',
        'title'        => 'Телефон',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
        'sort_order'   => 1,
    ),
    'bank_account_number' => array(
        'value'        => '',
        'title'        => 'Расчетный счет',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
        'sort_order'   => 1,
    ),
    'inn'                 => array(
        'value'        => '',
        'title'        => 'ИНН',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
        'sort_order'   => 1,
    ),
    'kpp'                 => array(
        'value'        => '',
        'title'        => 'КПП',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'bank_name'           => array(
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
    'cust_company'        => array(
        'value'        => 'company',
        'title'        => 'Компания покупателя',
        'description'  => 'Название компании в форме ввода контактной информации при оформлении заказа (поля формы настраиваются в разделе «Настройки — Оформление заказов — Информация о контакте»). Если такого поля в форме вашего магазина нет, то клиент должен будет ввести название компании уже после оформления заказа',
        'control_type' => waHtmlControl::CONTACTFIELD,
    ),
    'cust_inn'            => array(
        'value'        => 'inn',
        'title'        => 'ИНН покупателя',
        'description'  => 'ИНН в форме ввода контактной информации при оформлении заказа (поля формы настраиваются в разделе «Настройки — Оформление заказов — Информация о контакте»). Если такого поля в форме вашего магазина нет, то клиент должен будет ввести ИНН уже после оформления заказа.',
        'control_type' => waHtmlControl::CONTACTFIELD,
    ),
    'emailprintform'  => array(
        'value'        => true,
        'title'        => 'Отправлять счет покупателю по email',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
