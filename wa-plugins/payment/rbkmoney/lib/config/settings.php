<?php

return array(
    'shop_id' => array(
        'value'        => '',
        'title'        => 'Номер сайта продавца',
        'description'  => 'Ваш номер аккаунта в платежной системе RBK Money, на который будут зачислены платежи.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'shop_account' => array(
        'value'        => '',
        'title'        => 'Номер кошелька',
        'description'  => 'Ваш номер кошелька магазина в системе RBK Money.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret_key' => array(
        'value'       => '',
        'title'       => 'Секретный ключ',
        'description' => 'Ваш секретный ключ в системе RBK Money, известный только вам. Необходим для проверки ответа от платежной системы RBK Money.',
        'control_type' => waHtmlControl::INPUT,
    ),
);