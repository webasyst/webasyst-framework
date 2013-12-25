<?php
return array(
    'shop_id'    => array(
        'value'        => '',
        'title'        => 'ID магазина',
        'description'  => 'Идентификатор магазина, зарегистрированного в системе «INTERKASSA», через который был совершен платеж.<br>Пример: <em>68bf8b53973f2bba9ac9c153</em>.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key' => array(
        'value'        => '',
        'title'        => 'Секретный ключ',
        'description'  => 'Строка символов, добавляемая к реквизитам платежа, которые отправляются продавцу вместе с оповещением о новом платеже. Используется для повышения надежности идентификации оповещения и не должна быть известна третьим лицам!',
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'currency'   => array(
        'value'        => array('RUB' => 1,),
        'title'        => 'Валюта оплаты',
        'description'  => 'Доступные валюты оплаты (должны быть подключены в настройках на сайте платежной системы)',
        'control_type' => waHtmlControl::GROUPBOX.' interkassaPayment::availableCurrency',
    ),
    'test'       => array(
        'value'        => false,
        'title'        => 'Режим отладки',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'test_key'   => array(
        'value'        => '',
        'title'        => 'Тестовый ключ',
        'control_type' => waHtmlControl::PASSWORD,
    ),
);
