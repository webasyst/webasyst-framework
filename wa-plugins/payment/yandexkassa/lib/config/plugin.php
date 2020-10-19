<?php
return array(
    'name'            => 'Яндекс.Касса (новый протокол)',
    'description'     => 'Приём платежей через сервис «Яндекс.Касса» (<a href="https://kassa.yandex.ru/">kassa.yandex.ru</a>).',
    'icon'            => 'img/yandexkassa16.png',
    'logo'            => 'img/yandexkassa.png',
    'version'         => '1.2.0',
    'vendor'          => 'webasyst',
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
);
