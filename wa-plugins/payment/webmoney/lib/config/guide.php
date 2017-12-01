<?php
return array(
    array(
        'value'       => '%HTTPS_RELAY_URL%',
        'title'       => 'Result URL',
        'description' => 'URL(https), на который отправляется запрос «Уведомление об оплате».<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта Paymaster.</strong>',
    ),
    array(
        'value'       => '%HTTPS_RELAY_URL%',
        'title'       => 'Invoice Confirmation URL',
        'description' => 'URL(https), на который отправляется запрос «Проверка заказа».<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта Paymaster.</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?result=success',
        'title'       => 'Success URL',
        'description' => 'URL для кнопки "возврат в магазин" на странице, отображаемой покупателю после успешной оплаты.<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта Paymaster.</strong>',
    ),
    array(
        'value'       => '%RELAY_URL%?result=fail',
        'title'       => 'Failure URL',
        'description' => 'URL для кнопки "возврат в магазин" на странице, отображаемой покупателю в случае ошибки оплаты.<br>
<strong>Указанный в этом поле адрес скопируйте и сохраните в соответствующем поле внутри вашего аккаунта Paymaster.</strong>',
    ),
);
