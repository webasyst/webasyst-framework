<?php
return array(
    'shop_id'         => array(
        'value'        => '',
        'title'        => 'ID магазина',
        'description'  => 'Идентификатор магазина, зарегистрированного в системе «INTERKASSA», на который был совершен платеж.<br>Пример: <em>64C18529-4B94-0B5D-7405-F2752F2B716C</em>.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key'      => array(
        'value'        => '',
        'title'        => 'Секретный ключ',
        'description'  => 'Секретный ключ — это строка символов, добавляемая к реквизитам платежа, которые отправляются продавцу вместе с оповещением о новом платеже. Используется для повышения надежности идентификации оповещения и не должна быть известна третьим лицам.',
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'paysystem_alias' => array(
        'value'        => '',
        'title'        => 'Способ оплаты',
        'description'  => 'Это поле позволяет заранее определить способ оплаты для покупателя.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(),
    ),
    'currency'        => array(
        'value'        => '',
        'title'        => 'Валюта оплаты',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
    ),
);
