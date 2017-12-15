<?php
return array(
    'integration_type'  => array(
        'value'        => 'kassa',
        'title'        => 'Вариант подключения',
        'description'  => 'Выберите подходящий инструмент приема платежей',
        'control_type' => 'radiogroup',
        'options'      => array(
            'kassa'    => 'Яндекс.Касса',
            'personal' => 'Кнопка для приема платежей',
            'mpos'     => 'Мобильный терминал (mPOS)',
        ),
    ),
    'account'           => array(
        'value'        => '',
        'title'        => 'Номер счета',
        'description'  => 'Номер Яндекс.Кошелька.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-personal',
    ),
    'ShopID'            => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'scid'              => array(
        'value'        => '',
        'title'        => 'Номер витрины',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'shopPassword'      => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
        'data'         => array('integration-type' => 'kassa',),
    ),
    'payment_mode'      => array(
        'value'            => 'PC',
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentModeOptions'),
        'title'            => 'Способ оплаты',
        'description'      => 'Настройки выбора способа оплаты.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::RADIOGROUP,
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'paymentType'       => array(
        'value'            => array('PC' => true,),
        'title'            => 'Варианты для способа оплаты «на выбор покупателя»',
        'description'      => 'Настройки доступных способов оплаты для выбора покупателям.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'receipt'           => array(
        'value'        => false,
        'title'        => 'Фискализировать чеки через «Яндекс.Кассу»',
        'description'  => 'Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 18% и <em>включен</em> в стоимость элементов заказа и стоимость доставки',
        'control_type' => waHtmlControl::CHECKBOX,
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'taxes'             => array(
        'value'            => 'map',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'taxesOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'taxSystem'         => array(
        'value'            => -1,
        'title'            => 'Несколько систем налогообложения',
        'description'      => 'Параметр <code>taxSystem</code>. Выберите нужное значение, только если вы используете несколько систем налогообложения.
В остальных случаях оставьте вариант «Не передавать».',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array($this, 'settingsTaxOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'merchant_currency' => array(
        'value'            => 'RUB',
        'title'            => 'Валюта платежа',
        'description'      => 'Выберите валюту, отличную от российского рубля, чтобы принимать платежи в этой валюте.'
            .'<br>'
            .'Если выбрать российский рубль, то сумма заказа будет конвертирована в рубли.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array($this, 'settingsCurrencyOptions'),
    ),
    'TESTMODE'          => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'control_type' => 'checkbox',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
        'description'  => 'Используется для оплаты в демо-рублях.',
    ),
);
