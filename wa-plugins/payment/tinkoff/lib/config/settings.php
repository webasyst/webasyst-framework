<?php

return array(
    'terminal_key' => array(
        'value'        => '',
        'title'        => /*_wp*/('Terminal ID'),
        'control_type' => waHtmlControl::INPUT,
        'description'  => 'Выдается Т-Кассой после подключения.',
    ),
    'terminal_password' => array(
        'value'        => '',
        'title'        => /*_wp*/('Пароль'),
        'control_type' => waHtmlControl::PASSWORD,
        'description'  => <<<HTML
<span class="js-tkassa-registration-link" style="background-color: #e3ffc8; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%; border-radius: 8px;">
Подключайтесь к Т-Кассе <b><a href="https://www.tbank.ru/kassa/?utm_source=partners_sme&utm_medium=prt.utl&utm_campaign=business.int_acquiring.5-3AKNBMR5&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32" target="_blank" style="color: #09f;">через Webasyst по этой ссылке</a> и получите ставку 2,7% с дальнейшим понижением</b>. Данные для заполнения Terminal ID и пароля будут выданы сразу после подключения.
</span>
HTML
        ,
    ),
    'currency_id' => array(
        'value'        => '',
        'title'        => /*_wp*/('Валюта платежей'),
        'description'  => /*_wp*/(''),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'RUB', 'value' => 'RUB'),
        ),
    ),
    'two_steps' => array(
        'value'        => false,
        'title'        => 'Оплата картами',
        'description'  => '',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            '0' => 'Одностадийная (списание средств автоматически)',
            '1' => 'Двухстадийная (с холдированием и ручным подтверждением платежей)',
        ),
    ),
    'testmode' => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => /*_wp*/('Используется платежный шлюз <em>https://rest-api-test.tinkoff.ru/rest/</em>.'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'check_data_tax' => array(
        'value'        => '',
        'title'        => /*_wp*/('Фискализация чеков'),
        'control_type' => waHtmlControl::CHECKBOX,
        'description'  => 'Если включено, то этот способ оплаты доступен только в следующих случаях:'
            .'<br>'
            .'— либо к позициям заказа и стоимости доставки не применяются налоги;'
            .'<br>'
            .'— либо размер налога составляет 0%, 5%, 7%, 10%, 20% или 22% и <em>включён</em> в стоимость позиций заказа и в стоимость доставки.'.

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
            ''                   => 'выберите',
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

    'finalization_receipt' => array(
        'value'        => '',
        'title'        => 'Закрывающий чек',
        'description'  => 'Если выше в настройке «Признак способа расчета в чеках» выбран <em>не полный расчет</em> (например, предоплата), то по закону может дополнительно потребоваться пробитие <em>закрывающего чека</em>. Выберите «Пробивать закрывающий чек», если хотите, чтобы за это отвечал данный плагин оплаты.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            ''    => 'Без закрывающего чека',
            '1' => 'Пробивать закрывающий чек',
        ),
    ),

    'payment_ffd' => array(
        'value'        => '1.05',
        'title'        => 'Версия ФФД',
        'description'  => 'Должна совпадать с версией в настройках ОФД.',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            '1.05' => '1.05',
            '1.2'  => '1.2'
        )
    ),

    'payment_language' => array(
        'value'        => 'ru',
        'title'        => 'Язык платежной формы',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'ru' => 'русский',
            'en' => 'английский'
        )
    ),
);
