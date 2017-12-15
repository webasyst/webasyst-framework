<?php

return array(
    'merchant_login'      => array(
        'value'        => 'demo',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Указан в «Технических настройках» в аккаунте ROBOKASSA.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'merchant_pass1'      => array(
        'value'        => '',
        'title'        => 'Пароль №1',
        'description'  => 'Вводится в «Технических настройках» в аккаунте ROBOKASSA.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'merchant_pass2'      => array(
        'value'        => '',
        'title'        => 'Пароль №2',
        'description'  => 'Вводится в «Технических настройках» в аккаунте ROBOKASSA.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'testmode'            => array(
        'value'        => '1',
        'title'        => 'Тестовый режим',
        'description'  => <<<HTML
<script type="text/javascript">
(function () {
    $(':input[name$="\[testmode\]"]').unbind('change').bind('change', function (event) {
        var show = this.checked;
        var fast = !event.originalEvent;
        $(this).parents('form').find(':input[name*="_test_"]').each(function () {
            if (show) {
                $(this).parents('div.field').show(400);
            } else {
                if (fast) {
                    $(this).parents('div.field').hide();
                } else {
                    $(this).parents('div.field').hide(400);
                }
            }
        })
    }).trigger('change');
})();
</script>
HTML
        ,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'merchant_test_pass1' => array(
        'value'        => '',
        'title'        => 'Тестовый пароль №1',
        'description'  => 'Вводится в «Технических настройках» в аккаунте ROBOKASSA.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'merchant_test_pass2' => array(
        'value'        => '',
        'title'        => 'Тестовый пароль №2',
        'description'  => 'Вводится в «Технических настройках» в аккаунте ROBOKASSA.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'hash'                => array(
        'value'        => 'md5',
        'title'        => 'Алгоритм расчета хеша',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'md5'    => 'MD5',
            'sha1'   => 'SHA1',
            'sha256' => 'SHA256',
        ),
    ),
    'locale'              => array(
        'value'        => '',
        'title'        => 'Язык интерфейса',
        'description'  => 'Выберите язык, на котором должна отображаться платежная страница на сайте ROBOKASSA.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'ru' => 'русский',
            'en' => 'английский',
            ''   => '(не определен)',
        ),
    ),
    'gateway_currency'    => array(
        'value'        => '',
        'title'        => 'Способ оплаты',
        'description'  => '',
        'control_type' => 'GatewayCurrency',
    ),
    'merchant_currency'   => array(
        'value'        => 'RUB',
        'title'        => 'Валюта, указанная при регистрации магазина',
        'description'  => 'Допустимо указать несколько кодов валют через запятую.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'lifetime'            => array(
        'value'        => '0',
        'title'        => 'Время жизни счета',
        'description'  => 'Укажите срок оплаты счета в часах. Если поле пустое, то без ограничений.',
        'class'        => 'small',
        'control_type' => 'input',
    ),
    'commission'          => array(
        'value'        => false,
        'title'        => 'Комиссия',
        'description'  => 'Заплатить комиссию за покупателя.<br/>
<i class="icon16 exclamation"></i>Расчет комиссии возможен, только если выбран конкретный способ оплаты.',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'receipt'             => array(
        'value'        => false,
        'title'        => 'Фискализировать чеки',
        'description'  => 'Для  пользователей Robokassa, выбравших для себя Облачное или Кассовое решение.<br>
Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:
<br>
— к элементам заказа и стоимости доставки не применяются налоги
<br>
— налог составляет 0%, 10% либо 18% и <em>включен</em> в стоимость элементов заказа и стоимость доставки'.

'
<script type="text/javascript">
(function () {
    $(\':input[name$="\[receipt\]"]\').unbind(\'change\').bind(\'change\', function (event) {
        var show = this.checked;
        var fast = !event.originalEvent;
        $(this).parents(\'form\').find(\':input[name$="\[sno\]"]\').each(function () {
            if (show) {
                $(this).parents(\'div.field\').show(400);
            } else {
                if (fast) {
                    $(this).parents(\'div.field\').hide();
                } else {
                    $(this).parents(\'div.field\').hide(400);
                }
            }
        })
    }).trigger(\'change\');
})();
</script>
',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'sno'                 => array(
        'value'        => '',
        'title'        => 'Система налогообложения',
        'description'  => 'Заполняется, если у организации имеется только один тип налогообложения.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            ''                   => '',
            'osn'                => 'общая СН',
            'usn_income'         => 'упрощенная СН (доходы)',
            'usn_income_outcome' => 'упрощенная СН (доходы минус расходы)',
            'envd'               => 'единый налог на вмененный доход',
            'esn'                => 'единый сельскохозяйственный налог',
            'patent'             => 'патентная СН',
        ),
    ),
);
