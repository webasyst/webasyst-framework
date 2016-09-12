<?php
return array(
    array(
        'value'       => '%RELAY_URL%?transaction_result=result',
        'title'       => 'ResultURL',
        'description' => 'Адрес отправки оповещения о платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOKASSA.</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=success&app_id=%APP_ID%',
        'title'       => 'SuccessURL',
        'description' => 'Адрес страницы с уведомлением об успешно проведенном платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOKASSA.</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?transaction_result=failure&app_id=%APP_ID%',
        'title'       => 'FailURL',
        'description' => 'Адрес страницы с уведомлением о неуспешном платеже. <strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта ROBOKASSA.</strong>',
    ),
);
