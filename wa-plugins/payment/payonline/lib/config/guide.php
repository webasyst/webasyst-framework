<?php
return array(
    array(
        'value'       => 'Standard',
        'title'       => 'Режим безопасности',
        'description' => 'Используйте это значение в настройках интеграции в личном кабинете PayOnline',
    ),
    array(
        'value'       => '%HTTP_RELAY_URL%',
        'title'       => 'Callback URL для успешных транзакций',
        'description' => 'Для автоматической обработки успешных транзакций должен быть включен параметр «Вызывать Callback для подтвержденных транзакций».',
    ),
    array(
        'value'       => '%HTTP_RELAY_URL%?transaction_result=failure',
        'title'       => 'Callback URL для отклоненных транзакций',
        'description' => 'Для автоматической обработки отклоненных транзакций должен быть включен параметр «Вызывать Callback для отклоненных транзакций».',
    ),
    array(
        'value' => 'UTF-8',
        'title' => 'Callback URL encoding',
    ),
    array(
        'value' => 'POST',
        'title' => 'Callback method',
    ),
);
