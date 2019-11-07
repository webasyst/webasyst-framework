<?php
return array(
    'shop_id'                       => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдаётся оператором платёжной системы.',
        'control_type' => 'input',
    ),
    'shop_password'                 => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => <<<HTML
<span class="js-yandexkassa-registration-link" style="background-color: #FFE6E6; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%;">
Подключаясь к платёжной системе <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank">через Webasyst</a>, вы получаете <b>премиум-тариф со ставками от&nbsp;2,8% на 3&nbsp;месяца + купон на 7000&nbsp;рублей</b> на первую рекламную кампанию в «Яндекс.Директе» при оплате от 2500&nbsp;рублей.
</span>
<span class="js-yandexkassa-registration-link" style="font-weight: normal; font-size: 14px;color: black;">
Чтобы получить идентификатор, номер витрины и пароль, <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank">отправьте заявку на подключение</a>.
</span>
<br><br>
HTML
        ,
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'receipt'                       => array(
        'value'        => false,
        'title'        => 'Фискализировать чеки через «Яндекс.Кассу»',
        'description'  => 'Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 20% и <em>включён</em> в стоимость элементов заказа и стоимость доставки',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'payment_subject_type_product'  => array(
        'value'            => 'commodity',
        'title'            => 'Признак предмета расчёта для товаров в чеках',
        'description'      => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexkassaPayment', 'settingsPaymentSubjectTypeOptions'),
    ),
    'payment_subject_type_service'  => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчёта для услуг в чеках',
        'description'      => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexkassaPayment', 'settingsPaymentSubjectTypeOptions'),
    ),
    'payment_subject_type_shipping' => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчёта для доставки в чеках',
        'description'      => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('yandexkassaPayment', 'settingsPaymentSubjectTypeOptions'),
    ),

    'payment_method_type' => array(
        'value'        => 'full_prepayment',
        'title'        => 'Признак способа расчёта в чеках',
        'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'full_prepayment'    => 'полная предоплата',
            'partial_prepayment' => 'частичная предоплата',
            'advance'            => 'аванс',
            'full_payment'       => 'полный расчёт',
            'partial_payment'    => 'частичный расчёт и кредит',
            'credit'             => 'кредит',
            'credit_payment'     => 'выплата по кредиту',
        ),
    ),

    'taxes'             => array(
        'value'            => 'map',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 20%. В настройках налогов в приложении выберите, чтобы НДС был включён в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'taxesOptions'),
    ),
    'tax_system_code'   => array(
        'value'            => -1,
        'title'            => 'Несколько систем налогообложения',
        'description'      => 'Параметр <code>taxSystem</code>. Выберите нужное значение, только если вы используете несколько систем налогообложения.
В остальных случаях оставьте вариант «Не передавать».',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array($this, 'settingsTaxOptions'),
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

    'manual_capture' => array(
        'value'        => false,
        'title'        => 'Двухстадийная оплата',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
