<?php
return array(
    'LMI_MERCHANT_ID' => array(
        'value'        => '',
        'title'        => 'Merchant ID',
        'description'  => 'Ваш ID продавца в системе WebMoney',
        'control_type' => 'input',
    ),
    'LMI_PAYEE_PURSE' => array(
        'value'        => '',
        'title'        => 'Номер кошелька',
        'description'  => 'На этот кошелек будет приниматься оплата. Формат: буква и 12 цифр.',
        'control_type' => 'input',
    ),
    'secret_key'      => array(
        'value'        => '',
        'title'        => 'Secret key',
        'description'  => 'Секретный ключ',
        'control_type' => 'input',
    ),
    'protocol'        => array(
        'value'            => webmoneyPayment::PROTOCOL_WEBMONEY,
        'title'            => 'Протокол подключения',
        'description'      => '',
        'control_type'     => 'select',
        'options_callback' => array('webmoneyPayment', '_getProtocols'),

    ),
    'hash_method'     => array(
        'value'        => 'md5',
        'title'        => 'Подпись',
        'description'  => 'Способ формирования контрольной подписи',
        'control_type' => 'select',
        'options'      => array(
            'md5' => 'MD5',
            'sha' => 'SHA-256',
        ),
    ),
    'TESTMODE'        => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => '',
        'control_type' => 'checkbox',
    ),
    'LMI_SIM_MODE'    => array(
        'value'        => '',
        'title'        => 'Sim mode',
        'description'  => 'Только для тестового режима: 0 — всегда успешный ответ от WebMoney о статусе оплаты, 1 — оплата не была произведена, 2 — успешный ответ с вероятностью 80%',
        'control_type' => 'input',
    ),
);
