<?php
return array(
    'name'            => 'Банк Тинькофф',
    'description'     => 'Оплата картами VISA, MasterCard и Maestro через интернет-эквайринг банка Тинькофф',
    'icon'            => 'img/tinkoff16.png',
    'logo'            => 'img/tinkoff.png',
    'vendor'          => 'webasyst',
    'version'         => '1.0.17',
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
);
