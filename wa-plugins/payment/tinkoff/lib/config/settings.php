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
        'description'  => /*_wp*/('Только для тестирования через платежный шлюз https://rest-api-test.tinkoff.ru/rest/ (при использовании <strong>старой</strong> схемы подключения).<br>
            При использовании <strong>новой</strong> схемы подключения для тестирования платежей используйте отдельную пару логин/пароль.'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'atolonline_on' => array(
        'value'        => '',
        'title'        => /*_wp*/('Интеграция с Атол-Онлайн'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'atolonline_sno' => array(
        'title' => /*_wp*/('СНО'),
        'control_type' => waHtmlControl::CUSTOM .' '.'tinkoffPayment::getAtolonlineSnoBlockHtml'
    ),
);
