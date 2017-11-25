<?php
return array(
    'LMI_MERCHANT_ID' => array(
        'value'        => '',
        'title'        => 'Merchant ID',
        'description'  => 'Ваш ID продавца в системе WebMoney или PayMaster',
        'control_type' => 'input',
    ),
    'LMI_PAYEE_PURSE' => array(
        'value'        => '',
        'title'        => 'Номер кошелька',
        'description'  => 'На этот кошелек будет приниматься оплата. Формат: буква и 12 цифр (актуально только для WebMoney)',
        'control_type' => 'input',
    ),
    'secret_key'      => array(
        'value'        => '',
        'title'        => 'Секретный ключ',
        'description'  => 'Этот ключ добавляется к подписи и должен быть одинаковым с тем, что вы установили в кабинете продавца на сайте платежной системы',
        'control_type' => 'input',
        'requiered'    => true,
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
            'md5'    => 'MD5',
            'sha'    => 'SHA-1',
            'sha256' => 'SHA-256',
        ),
    ),
    'vatProducts'     => array(
        'value'            => 'no_vat',
        'title'            => 'Ставки НДС для продукта',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'vatProductsOptions'),
    ),
    'vatDelivery'     => array(
        'value'            => 'no_vat',
        'title'            => 'Ставки НДС для доставки',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Выберете ставку НДС для обложения доставки',
        'options_callback' => array($this, 'vatDeliveryOptions'),
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
