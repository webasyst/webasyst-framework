<?php
return array(
    'name'        => 'Оплата по счету',
    'description' => 'Оплата безналичным расчетом для юридических лиц (РФ)',
    'icon'        => 'img/invoicejur16.png',
    'logo'        => 'img/invoicejur.png',
    'version'     => '1.0.8',
    'vendor'      => 'webasyst',
    'locale'      => array('ru_RU',),
    'type'        => waPayment::TYPE_MANUAL,
    'fractional_quantity' => true,
    'stock_units'         => true,
);