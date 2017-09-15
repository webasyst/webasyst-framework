<?php

return array(
    'shop_id' => array(
        'value'        => '',
        'title'        => 'Номер сайта продавца',
        'description'  => 'Ваш номер аккаунта в RBKmoney, на который будут зачислены платежи.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'shop_account' => array(
        'value'        => '',
        'title'        => 'Номер кошелька',
        'description'  => 'Ваш номер кошелька магазина в RBKmoney.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret_key' => array(
        'value'       => '',
        'title'       => 'Секретный ключ',
        'description' => 'Ваш секретный ключ в RBKmoney, известный только вам. Необходим для проверки ответа от платежной системы.',
        'control_type' => waHtmlControl::INPUT,
    ),
);