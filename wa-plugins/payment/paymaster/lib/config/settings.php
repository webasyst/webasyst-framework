<?php
return array(
    'LMI_MERCHANT_ID'   => array(
        'value'        => '',
        'title'        => 'Идентификатор продавца',
        'description'  => 'Для того, чтобы узнать Идентификатор продавца (ID Merchant) вам необходимо зайти в личный кабинет на сайте PayMaster',
        'control_type' => 'input',
    ),
    'secretPhrase'       => array(
        'value'        => 'aaaaaa',
        'title'        => 'Секретная фраза',
        'description'  => 'Это сочетание знаков должно быть одинаковым и совпадать с тем, что вы ввели в интерфейсе PayMaster',
        'control_type' => 'input',
    ),
    'signMethod'   => array(
        'value'        => 'sha256',
        'title'        => 'Метод шифрования',
        'description'  => 'Для формирования подписи, должно значение совпадать с тем, что выставлено в PayMaster',
        'control_type' => 'radiogroup',
        'options'      => array(
            'md5'    => 'md5',
            'sha1'   => 'sha1',
            'sha256' => 'sha256',
        ),
    ),
    'description'         => array(
        'value'        => 'Заказ на сайте №',
        'title'        => 'Описание платежа',
        'description'  => 'Как будет выглядеть платеж в системе PayMaster',
        'control_type' => 'input',
    ),
    'vatProducts' => array(
        'value'            => 'map',
        'title'            => 'Ставки НДС для продукта',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'vatProductsOptions'),
    ),
    'vatDelivery' => array(
        'value'            => 'map',
        'title'            => 'Ставки НДС для доставки',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Выберете ставку НДС для обложения доставки',
        'options_callback' => array($this, 'vatDeliveryOptions'),
    ),
    'testMode'   => array(
	    'value'        => '',
	    'title'        => 'Тестовый режим',
	    'description'  => 'Отметьте если хотите выполнять транзакции в тестовом режиме',
	    'control_type' => 'checkbox',
    ),
);
