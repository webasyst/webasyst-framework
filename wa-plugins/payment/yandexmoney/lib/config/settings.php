<?php
return array(
    'integration_type'              => array(
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
    'account'                       => array(
        'value'        => '',
        'title'        => 'Номер счета',
        'description'  => 'Номер Яндекс.Кошелька.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-personal',
    ),
    'ShopID'                        => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'scid'                          => array(
        'value'        => '',
        'title'        => 'Номер витрины',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'shopPassword'                  => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => <<<HTML
<span class="js-yandexmoney-registration-link" style="background-color: #FFE6E6; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%;">
Подключаясь к платежной системе <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank">через Webasyst или указав промокод&nbsp;<strong>Webasyst</strong></a>, вы получаете премиум-тариф со ставками от&nbsp;2,8% на 3&nbsp;месяца.
</span>
<span class="js-yandexmoney-registration-link" style="font-weight: normal; font-size: 14px;color: black;">
Чтобы получить идентификатор, номер витрины и пароль, <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank">отправьте заявку на подключение</a>.
</span>
<br><br>
HTML
        ,
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
        'data'         => array('integration-type' => 'kassa',),
    ),
    'payment_mode'                  => array(
        'value'            => 'PC',
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentModeOptions'),
        'title'            => 'Способ оплаты',
        'description'      => 'Настройки выбора способа оплаты.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::RADIOGROUP,
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'paymentType'                   => array(
        'value'            => array('PC' => true,),
        'title'            => 'Варианты для способа оплаты «на выбор покупателя»',
        'description'      => 'Настройки доступных способов оплаты для выбора покупателям.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'receipt'                       => array(
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
    'payment_subject_type_product'  => array(
        'value'            => 'commodity',
        'title'            => 'Признак предмета расчета для товаров в чеках',
        'description'      => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentSubjectTypeOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'payment_subject_type_service'  => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчета для услуг в чеках',
        'description'      => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentSubjectTypeOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'payment_subject_type_shipping' => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчета для доставки в чеках',
        'description'      => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentSubjectTypeOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),

    'payment_method_type' => array(
        'value'        => 'full_prepayment',
        'title'        => 'Признак способа расчета в чеках',
        'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'full_prepayment'    => 'полная предоплата',
            'partial_prepayment' => 'частичная предоплата',
            'advance'            => 'аванс',
            'full_payment'       => 'полный расчет',
            'partial_payment'    => 'частичный расчет и кредит',
            'credit'             => 'кредит',
            'credit_payment'     => 'выплата по кредиту',
        ),
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),

    'taxes'             => array(
        'value'            => 'map',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18% (20% с 1 января 2019). В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
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
