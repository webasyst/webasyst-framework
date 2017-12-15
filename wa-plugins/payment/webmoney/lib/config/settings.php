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
        'options_callback' => array($this, 'settingsProtocolOptions'),

    ),
    'hash_method'     => array(
        'value'        => 'md5',
        'title'        => 'Подпись',
        'description'  => 'Способ формирования контрольной подписи',
        'control_type' => 'select',
        'options'      => array(
            'md5' => 'MD5',
            'sha' => 'SHA-1',
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

    'receipt' => array(
        'value'            => false,
        'title'            => 'Фискализировать чеки через онлайн-кассу',
        'description'      => <<<HTML
Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:
<br>
— к элементам заказа и стоимости доставки не применяются налоги
<br>
— налог составляет 0%, 10% либо 18% и <em>включен</em> в стоимость элементов заказа и стоимость доставки
<script type="text/javascript">
    (function () {
        var toggle = function (input, event, selector, show) {
            if (show === undefined) {
                show = input.checked;
            }
            $(input).parents('form').find(selector).each(function () {
                if (show) {
                    $(this).parents('div.field').show(400);
                } else {
                    $(this).parents('div.field').hide(event.originalEvent ? 400 : 0);
                }
            });
        };
        $(':input[name$="\[receipt\]"]').unbind('change').bind('change', function (event) {
            toggle(this, event, ':input[name$="\[taxes\]"]');
        }).trigger('change');
        $(':input[name$="\[TESTMODE\]"]').unbind('change').bind('change', function (event) {
            toggle(this, event, ':input[name$="\[LMI_SIM_MODE\]"]');
        }).trigger('change');
    })();
</script>
HTML
,

        'control_type'     => waHtmlControl::CHECKBOX,
    ),

    'taxes'         => array(
        'value'        => 'map',
        'title'        => 'Передача ставок НДС',
        'control_type' => waHtmlControl::SELECT,
        'description'  => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback'=>array($this,'taxesOptions'),
    ),
);
