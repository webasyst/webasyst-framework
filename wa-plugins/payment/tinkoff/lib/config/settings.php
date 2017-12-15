<?php

return array(
    'terminal_key' => array(
        'value'        => '',
        'title'        => /*_wp*/('TerminalKey'),
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
        'description'  => /*_wp*/('Вариант обработки платежей, выбранный при заключении договора с банком Тинькофф'),
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
    'atolonline_on' => array(
        'value'        => '',
        'title'        => /*_wp*/('Интеграция с «АТОЛ Онлайн»'),
        'control_type' => waHtmlControl::CHECKBOX,
        'description'  => 'Если включена интеграция, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 18% и <em>включен</em> в стоимость элементов заказа и стоимость доставки',
    ),
    'atolonline_sno' => array(
        'title' => /*_wp*/('Система налогообложения'),
        'control_type' => waHtmlControl::CUSTOM .' '.'tinkoffPayment::getAtolonlineSnoBlockHtml'
    ),
);
