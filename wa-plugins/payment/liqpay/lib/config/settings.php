<?php
return array(
    'merchant_id'    => array(
        'value'        => '',
        'title'        => 'ID продавца',
        'description'  => 'Идентификатор продавца, выданный платежной системой',
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret_key'     => array(
        'value'        => '',
        'title'        => 'Подпись',
        'description'  => 'Скопируйте значение «Signature for other operations» из вашего аккаунта в платежной системе',
        'control_type' => waHtmlControl::PASSWORD,
    ),

    'gateway'        => array(
        'value'        => 'card, liqpay, delayed',
        'title'        => 'Способ оплаты',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'card,liqpay,delayed' => 'на выбор покупателя',
            'card'                => 'банковской картой',
            'liqpay'              => 'из аккаунта LiqPay',
            'delayed'             => 'наличными',
        ),
    ),

    'order_prefix'   => array(
        'value'        => '',
        'title'        => 'Префикс номера счета',
        'description'  => 'Допускается использовать цифры и латинские буквы',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bugfix'         => array(
        'value'        => true,
        'title'        => 'Добавлять случайное число к номеру счета',
        'description'  => 'Позволяет обеспечить абсолютную уникальность Order ID платежной системы LiqPay при повторных оплатах счета',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'customer_phone' => array(
        'value'        => 'phone',
        'title'        => 'Номер телефона покупателя',
        'description'  => 'Выберите поле в форме регистрации покупателя, предназначенное для ввода номера телефона',
        'control_type' => waHtmlControl::CONTACTFIELD,
    ),
);
