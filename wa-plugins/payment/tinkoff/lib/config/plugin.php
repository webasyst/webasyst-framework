<?php
return array(
    'name'            => 'Т-Касса',
    'description'     => 'Интернет-эквайринг от «Т-Банка»: банковские карты, СБП, SberPay, T-Pay, MirPay',
    'icon'            => 'img/tinkoff.svg',
    'logo'            => 'img/tinkoff.png?v2',
    'vendor'          => 'webasyst',
    'version'         => '1.1.0',
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
    'fractional_quantity' => true,
    'stock_units'         => true,
);
