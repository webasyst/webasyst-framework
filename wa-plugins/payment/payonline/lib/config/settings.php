<?php
return array(
    'payonline_id' => array(
        'value'        => '',
        'title'        => 'MerchantId',
        'description'  => 'Идентификатор, полученный при активации',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key'   => array(
        'value'        => '',
        'title'        => 'PrivateSecurityKey',
        'description'  => 'Ключ, полученный при активации',
        'control_type' => waHtmlControl::INPUT,
    ),
    'currency'     => array(
        'value'        => '',
        'title'        => 'Валюта заказа',
        'description'  => 'Валюта, в которой будет проводиться транзакция в процессинговом центре.',
        'control_type' => waHtmlControl::GROUPBOX,
        'options'      => array(
            array('title' => '(RUB) российский рубль', 'value' => 'RUB', 'description' => '',),
            array('title' => '(USD) доллар США', 'value' => 'USD', 'description' => '',),
            array('title' => '(EUR) евро', 'value' => 'EUR', 'description' => '',),
        ),
    ),

    'gateway'       => array(
        'value'        => 'select/',
        'title'        => 'Форма оплаты',
        'description'  => 'Выберите форму оплаты, которая должна открываться при переходе покупателя на платежную страницу PayOnline.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'форма для оплаты банковской картой', 'value' => ''),
            array('title' => 'форма выбора платежного инструмента', 'value' => 'select/'),
            array('title' => 'форма оплаты через QIWI', 'value' => 'select/qiwi/'),
            array('title' => 'форма оплаты через WebMoney', 'value' => 'select/webmoney/'),
            array('title' => 'форма оплаты через Яндекс.Деньги', 'value' => 'select/yandexmoney/'),
        ),
    ),
    'valid_until'   => array(
        'value'        => 0,
        'title'        => 'Срок оплаты',
        'description'  => 'Период времени, в течение которого необходимо оплатить заказ.<br>Введите 0, чтобы отменить ограничение.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'customer_lang' => array(
        'value'        => '',
        'title'        => 'Язык интерфейса',
        'description'  => 'Выберите язык, на котором должна отображаться платежная страница',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'автоматический выбор', 'value' => ''),
            array('title' => 'английский', 'value' => 'en'),
            array('title' => 'русский', 'value' => 'ru'),
        ),
    ),

    'receipt' => array(
        'value'        => false,
        'title'        => 'Фискализировать чеки',
        'description'  => 'Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 18% и <em>включен</em> в стоимость элементов заказа и стоимость доставки'.

            <<<HTML
<script type="text/javascript">
(function () {
    $(':input[name$="\[receipt\]"]').unbind('change').bind('change', function (event) {
        var show = this.checked;
        var fast = !event.originalEvent;
        var name = [
            'payment_subject_type_product',
            'payment_subject_type_service',
            'payment_subject_type_shipping',
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
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'payment_subject_type_product'  => array(
        'value'        => '1',
        'title'        => 'Признак предмета расчета для товаров в чеках',
        'description'  => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '1'  => 'товар',
            '2'  => 'подакцизный товар',
            '3'  => 'работа',
            '4'  => 'услуга',
            '5'  => 'ставка азартной игры',
            '6'  => 'выигрыш азартной игры',
            '7'  => 'лотерейный билет',
            '8'  => 'выигрыш лотереи',
            '9'  => 'предоставление РИД',
            '10' => 'платеж',
            '11' => 'агентское вознаграждение',
            '12' => 'составной предмет расчета',
            '13' => 'иной предмет расчета',
        ),
    ),
    'payment_subject_type_service'  => array(
        'value'        => '4',
        'title'        => 'Признак предмета расчета для услуг в чеках',
        'description'  => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '1'  => 'товар',
            '2'  => 'подакцизный товар',
            '3'  => 'работа',
            '4'  => 'услуга',
            '5'  => 'ставка азартной игры',
            '6'  => 'выигрыш азартной игры',
            '7'  => 'лотерейный билет',
            '8'  => 'выигрыш лотереи',
            '9'  => 'предоставление РИД',
            '10' => 'платеж',
            '11' => 'агентское вознаграждение',
            '12' => 'составной предмет расчета',
            '13' => 'иной предмет расчета',
        ),
    ),
    'payment_subject_type_shipping' => array(
        'value'        => '4',
        'title'        => 'Признак предмета расчета для доставки в чеках',
        'description'  => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '1'  => 'товар',
            '2'  => 'подакцизный товар',
            '3'  => 'работа',
            '4'  => 'услуга',
            '5'  => 'ставка азартной игры',
            '6'  => 'выигрыш азартной игры',
            '7'  => 'лотерейный билет',
            '8'  => 'выигрыш лотереи',
            '9'  => 'предоставление РИД',
            '10' => 'платеж',
            '11' => 'агентское вознаграждение',
            '12' => 'составной предмет расчета',
            '13' => 'иной предмет расчета',
        ),
    ),

    'payment_method_type' => array(
        'value'        => '1',
        'title'        => 'Признак способа расчета в чеках',
        'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '1' => 'предоплата 100%',
            '2' => 'частичная предоплата',
            '3' => 'аванс',
            '4' => 'полный расчет',
            '5' => 'частичный расчет и кредит',
            '6' => 'передача в кредит',
            '7' => 'оплата кредита',
        ),
    ),
);
