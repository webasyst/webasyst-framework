<?php
return array(
    'name'        => 'Оплата по квитанции',
    'description' => 'Оплата наличными по квитанции для физических лиц (РФ)',
    'icon'        => 'img/invoicephys16.png',
    'logo'        => 'img/invoicephys.png',
    'version'     => '1.0.1',
    'vendor'      => 'webasyst',
    'emailprintform' => true,
    'locale'      => array('ru_RU', ),
    'type'        => waPayment::TYPE_MANUAL,
);
