<?php
return array(
    'name'            => 'ЮKassa (бывшая Яндекс.Касса)',
    'description'     => 'Приём платежей через сервис «ЮKassa» (<a href="https://yookassa.ru/">yookassa.ru</a>).',
    'icon'            => 'img/yandexkassa16.png',
    'logo'            => 'img/yandexkassa.png',
    'version'         => '1.2.4',
    'vendor'          => 'webasyst',
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
);
