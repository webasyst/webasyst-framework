<?php

return array(
    'terminal_key' => array(
        'value'        => '',
        'title'        => /*_wp*/('Terminal ID'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'terminal_password' => array(
        'value'        => '',
        'title'        => /*_wp*/('Пароль'),
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'currency_id' => array(
        'value'        => '',
        'title'        => /*_wp*/('Валюта'),
        'description'  => /*_wp*/('Валюта, в которой будут выполняться платежи'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'RUB', 'value' => 'RUB'),
        ),
    ),
    'two_steps' => array(
        'value'        => false,
        'title'        => 'Схема подключения',
        'description'  => /*_wp*/('Вариант обработки платежей, выбранный при заключении договора с банком Тинькофф.<br>Двухстадийную схему подключения можно использовать только с поддерживаемым приложением, например, Shop-Script версии не ниже 8.6.'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            '0' => 'Одностадийная',
            '1' => 'Двухстадийная',
        ),
    ),
    'testmode' => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => /*_wp*/('Только для тестирования по старой схеме через платежный шлюз <em>https://rest-api-test.tinkoff.ru/rest/</em>'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'check_data_tax' => array(
        'value'        => '',
        'title'        => /*_wp*/('Передавать данные для формирования чека'),
        'control_type' => waHtmlControl::CHECKBOX,
        'description'  => 'Если включена интеграция с онлайн кассами, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 20% и <em>включен</em> в стоимость элементов заказа и стоимость доставки'.

            <<<HTML
<script type="text/javascript">
(function () {
    $(':input[name$="\[check_data_tax\]"]').unbind('change').bind('change', function (event) {
        var show = this.checked;
        var fast = !event.originalEvent;
        var name = [
            'taxation',
            'payment_object_type_product',
            'payment_object_type_service',
            'payment_object_type_shipping',
            'payment_method_type'
        ];
        var selector = [];
        for (var i = 0; i < name.length; i++) {
            selector.push(':input[name$="\[' + name[i] + '\]"]');
        }
        selector = selector.join(', ');
        $(this).parents('form').find(selector).each(function () {
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
    ),

    'taxation' => array(
        'value'        => '',
        'title'        => 'Система налогообложения',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            ''                   => 'выберите значение',
            'osn'                => 'общая СН',
            'usn_income'         => 'упрощенная СН (доходы)',
            'usn_income_outcome' => 'упрощенная СН (доходы минус расходы)',
            'envd'               => 'единый налог на вмененный доход',
            'esn'                => 'единый сельскохозяйственный налог',
            'patent'             => 'патентная СН',
        )
    ),

    'payment_object_type_product'  => array(
        'value'            => 'commodity',
        'title'            => 'Признак предмета расчета для товаров в чеках',
        'description'      => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options' => array(
            'commodity'             => 'товар',
            'excise'                => 'подакцизный товар',
            'job'                   => 'работа',
            'service'               => 'услуга',
            'gambling_bet'          => 'ставка в азартной игре',
            'gambling_prize'        => 'выигрыш в азартной игре',
            'lottery'               => 'лотерейный билет',
            'lottery_prize'         => 'выигрыш в лотерею',
            'intellectual_activity' => 'результаты интеллектуальной деятельности',
            'payment'               => 'платеж',
            'agent_commission'      => 'агентское вознаграждение',
            'composite'             => 'несколько вариантов',
            'another'               => 'другое',
        ),
    ),
    'payment_object_type_service' => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчета для услуг в чеках',
        'description'      => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options' => array(
            'commodity'             => 'товар',
            'excise'                => 'подакцизный товар',
            'job'                   => 'работа',
            'service'               => 'услуга',
            'gambling_bet'          => 'ставка в азартной игре',
            'gambling_prize'        => 'выигрыш в азартной игре',
            'lottery'               => 'лотерейный билет',
            'lottery_prize'         => 'выигрыш в лотерею',
            'intellectual_activity' => 'результаты интеллектуальной деятельности',
            'payment'               => 'платеж',
            'agent_commission'      => 'агентское вознаграждение',
            'composite'             => 'несколько вариантов',
            'another'               => 'другое',
        ),
    ),
    'payment_object_type_shipping' => array(
        'value'            => 'service',
        'title'            => 'Признак предмета расчета для доставки в чеках',
        'description'      => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options' => array(
            'commodity'             => 'товар',
            'excise'                => 'подакцизный товар',
            'job'                   => 'работа',
            'service'               => 'услуга',
            'gambling_bet'          => 'ставка в азартной игре',
            'gambling_prize'        => 'выигрыш в азартной игре',
            'lottery'               => 'лотерейный билет',
            'lottery_prize'         => 'выигрыш в лотерею',
            'intellectual_activity' => 'результаты интеллектуальной деятельности',
            'payment'               => 'платеж',
            'agent_commission'      => 'агентское вознаграждение',
            'composite'             => 'несколько вариантов',
            'another'               => 'другое',
        ),
    ),

    'payment_method_type'=>array(
        'value'            => 'full_prepayment',
        'title'            => 'Признак способа расчета в чеках',
        'description'      => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options' => array(
            'full_prepayment'    => 'полная предоплата',
            'prepayment'         => 'предоплата',
            'advance'            => 'аванс',
            'full_payment'       => 'полный расчет',
            'partial_payment'    => 'частичный расчет и кредит',
            'credit'             => 'кредит',
            'credit_payment'     => 'выплата по кредиту',
        ),
    ),

    'payment_language' => array(
        'value'        => 'ru',
        'title'        => 'Язык платежной формы',
        'description'  => 'Выберите язык платежной формы для своих клиентов',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'ru' => 'русский',
            'en' => 'английский'
        )
    ),
);
