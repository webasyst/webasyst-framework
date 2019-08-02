<?php

return array(
    'api_login'       => array(
        'value'        => '',
        'title'        => 'API login',
        'description'  => 'Email-адрес пользователя, созданного с опцией «Автоматический доступ». Используется для выполнения автоматических возвратов и требует соответствующего уровня прав доступа.',
        'control_type' => 'input',
    ),
    'api_password'    => array(
        'value'        => '',
        'title'        => 'API password',
        'description'  => 'Пароль пользователя',
        'control_type' => 'input',
    ),
    'api_debug'       => array(
        'value'        => 0,
        'control_type' => 'hidden',
    ),
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
        'description'  => 'Секретный ключ'.
            <<<HTML
<span class="js-webmoney-registration-link" style="background-color: #FFE6E6; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%;">
Подключаясь к платежной системе <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=1474e584a17b158f24e83c8c47ec500373" target="_blank">через Webasyst</a>, вы получаете <b>специальные ставки ниже стандартных</b>:
при обороте до 0,8 млн руб. — <b>2,7%</b> вместо 2,95%;
при обороте 0,8–2 млн руб. — <b>2,4%</b> вместо 2,6%;
при обороте больше 2 млн руб. — <b>2,3%</b> вместо 2,5%.
Вы также получаете преимущество по коммерческим условиям сотрудничества.
</span>
<span class="js-webmoney-registration-link" style="font-weight: normal; font-size: 14px;color: black;">
Чтобы получить идентификатор и пароль, <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=1474e584a17b158f24e83c8c47ec500373" target="_blank">отправьте заявку на подключение</a>.
</span>
<br><br>
HTML
        ,
        'control_type' => 'input',
    ),
    'protocol'        => array(
        'value'            => webmoneyPayment::PROTOCOL_WEBMONEY,
        'title'            => 'Протокол подключения',
        'description'      => '',
        'control_type'     => 'select',
        'options_callback' => array($this, 'settingsProtocolOptions'),

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

    'receipt'                       => array(
        'value'        => false,
        'title'        => 'Фискализировать чеки через онлайн-кассу',
        'description'  => <<<HTML
Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:
<br>
— к элементам заказа и стоимости доставки не применяются налоги
<br>
— налог составляет 0%, 10% либо 20% и <em>включен</em> в стоимость элементов заказа и стоимость доставки
<script type="text/javascript">
    (function () {
        var form = $(':input[name$="\[LMI_MERCHANT_ID\]"]').parents('form:first');
        var registered = true;

        var toggle = function (input, event, selector, show) {
            if (show === undefined) {
                show = input.checked;
            }
            form.find(selector).each(function () {
                if (show) {
                    $(this).parents('div.field').show(400);
                } else {
                    $(this).parents('div.field').hide(event.originalEvent ? 400 : 0);
                }
            });
        };

        form.find(':input[name$="\[LMI_MERCHANT_ID\]"], :input[name$="\[secret_key\]"]').each(function () {
            /** @this HTMLInputElement */
            if (('' + this.value).length === 0) {
                registered = false;
            }
        });

        if (registered) {
            form.find('.js-webmoney-registration-link').hide();
        }


        form.find(':input[name$="\[receipt\]"]').unbind('change').bind('change', function (event) {
            var name = [
                'taxes',
                'payment_subject_type_product',
                'payment_subject_type_service',
                'payment_subject_type_shipping',
                'payment_method_type',
                'payment_agent_type'
            ];
            var selector = [];
            for (var i = 0; i < name.length; i++) {
                selector.push(':input[name$="\[' + name[i] + '\]"]');
            }
            toggle(this, event, selector.join(', '));
        }).trigger('change');

        form.find(':input[name$="\[TESTMODE\]"]').unbind('change').bind('change', function (event) {
            toggle(this, event, ':input[name$="\[LMI_SIM_MODE\]"]');
        }).trigger('change');
    })();
</script>
HTML
        ,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'payment_subject_type_product'  => array(
        'value'        => 1,
        'title'        => 'Признак предмета расчета для товаров в чеках',
        'description'  => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            1  => 'Товар',
            2  => 'Подакцизный товар',
            3  => 'Работа',
            4  => 'Услуга',
            5  => 'Ставка азартной игры',
            6  => 'Выигрыш азартной игры',
            7  => 'Лотерейный билет',
            8  => 'Выигрыш лотереи',
            9  => 'Предоставление РИД',
            10 => 'Платеж',
            11 => 'Агентское вознаграждение',
            12 => 'Составной предмет расчета',
            13 => 'Иной предмет расчета',
            14 => 'Имущественное право',
            15 => 'Внереализационный доход',
            16 => 'Страховые взносы',
            17 => 'Торговый сбор',
            18 => 'Курортный сбор',
            19 => 'Залог',
        ),
    ),
    'payment_subject_type_service'  => array(
        'value'        => 4,
        'title'        => 'Признак предмета расчета для услуг в чеках',
        'description'  => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            1  => 'Товар',
            2  => 'Подакцизный товар',
            3  => 'Работа',
            4  => 'Услуга',
            5  => 'Ставка азартной игры',
            6  => 'Выигрыш азартной игры',
            7  => 'Лотерейный билет',
            8  => 'Выигрыш лотереи',
            9  => 'Предоставление РИД',
            10 => 'Платеж',
            11 => 'Агентское вознаграждение',
            12 => 'Составной предмет расчета',
            13 => 'Иной предмет расчета',
            14 => 'Имущественное право',
            15 => 'Внереализационный доход',
            16 => 'Страховые взносы',
            17 => 'Торговый сбор',
            18 => 'Курортный сбор',
            19 => 'Залог',
        ),
    ),
    'payment_subject_type_shipping' => array(
        'value'        => 4,
        'title'        => 'Признак предмета расчета для доставки в чеках',
        'description'  => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            1  => 'Товар',
            2  => 'Подакцизный товар',
            3  => 'Работа',
            4  => 'Услуга',
            5  => 'Ставка азартной игры',
            6  => 'Выигрыш азартной игры',
            7  => 'Лотерейный билет',
            8  => 'Выигрыш лотереи',
            9  => 'Предоставление РИД',
            10 => 'Платеж',
            11 => 'Агентское вознаграждение',
            12 => 'Составной предмет расчета',
            13 => 'Иной предмет расчета',
            14 => 'Имущественное право',
            15 => 'Внереализационный доход',
            16 => 'Страховые взносы',
            17 => 'Торговый сбор',
            18 => 'Курортный сбор',
            19 => 'Залог',
        ),
    ),

    'payment_method_type' => array(
        'value'        => 1,
        'title'        => 'Признак способа расчета в чеках',
        'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            1 => 'Полная предварительная оплата до момента передачи предмета расчета',
            2 => 'Частичная предварительная оплата до момента передачи предмета расчета',
            3 => 'Аванс',
            4 => 'Полная оплата, в том числе с учетом аванса (предварительной оплаты) в момент передачи предмета расчета',
            5 => 'Частичная оплата предмета расчета в момент его передачи с последующей оплатой в кредит',
            6 => 'Передача предмета расчета без его оплаты в момент его передачи с последующей оплатой в кредит',
            7 => 'Оплата предмета расчета после его передачи с оплатой в кредит (оплата кредита)',

        ),
    ),

    'taxes' => array(
        'value'            => 'map',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 20%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'taxesOptions'),
    ),

    'payment_agent_type' => array(
        'value'        => 42,
        'title'        => 'Признак агента по предмету расчета',
        'description'  => 'Выберите «Не передавать», только если чек оформляет продавец, а не лицо, которое принимает средства, например, платежный агент или комиссионер.',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            42 => 'Не передавать',
            0  => 'Оказание услуг покупателю (клиенту) пользователем, являющимся банковским платежным агентом банковским платежным агентом',
            1  => 'Оказание услуг покупателю (клиенту) пользователем, являющимся банковским платежным агентом банковским платежным субагентом',
            2  => 'Оказание услуг покупателю (клиенту) пользователем, являющимся платежным агентом',
            3  => 'Оказание услуг покупателю (клиенту) пользователем, являющимся платежным субагентом',
            4  => 'Осуществление расчета с покупателем (клиентом) пользователем, являющимся поверенным',
            5  => 'Осуществление расчета с покупателем (клиентом) пользователем, являющимся комиссионером',
            6  => 'Осуществление расчета с покупателем (клиентом) пользователем, являющимся агентом и не являющимся банковским платежным агентом (субагентом), платежным агентом (субагентом), поверенным, комиссионером',
        ),
    ),
);
