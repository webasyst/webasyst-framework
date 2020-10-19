<?php
return array(
    'name'            => 'Эквайринг Сбербанка',
    'description'     => 'Приём платежей через <a href="https://www.sberbank.ru/ru/legal/bankingservice/internet_acquiring">эквайринг «Сбербанка»</a> и фискализация через сервис <a href="https://online.atol.ru/">«АТОЛ Онлайн»</a>',
    'logo'            => 'img/sb.png',
    'icon'            => 'img/sb16.png',
    'version'         => '2.1.0',
    'vendor'          => 'webasyst',
    'locale'          => array('ru_RU',),
    'discount'        => false,
    'type'            => waPayment::TYPE_ONLINE,
    'partial_refund'  => true,
    'partial_capture' => true,
);
