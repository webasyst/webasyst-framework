<?php
return array(
    'name'                => 'ЮKassa',
    'description'         => 'Банковские карты, СБП, SberPay, T-Pay, кошелек ЮMoney',
    'icon'                => 'img/yookassa16.svg',
    'logo'                => 'img/yookassa.svg',
    'version'             => '1.3.1',
    'vendor'              => 'webasyst',
    'type'                => waPayment::TYPE_ONLINE,
    'partial_refund'      => true,
    'partial_capture'     => true,
    'fractional_quantity' => true,
    'stock_units'         => false,
);
