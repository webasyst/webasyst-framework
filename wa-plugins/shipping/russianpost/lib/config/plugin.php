<?php
return array(
    'name'                  => 'Почта России',
    'description'           => 'Расчет стоимости доставки по алгоритму, опубликованному <a href="http://www.russianpost.ru/rp/servise/ru/home/postuslug/bookpostandparcel/parcelltariff" target="_blank">на сайте Почты России</a> для отправления посылок.',
    'icon'                  => 'img/RussianPost16.png',
    'logo'                  => 'img/RussianPost.png',
    'version'               => '1.6.0',
    'vendor'                => 'webasyst',
    'type'                  => waShipping::TYPE_POST,
    'external_tracking'     => true,
    'backend_custom_fields' => true,
    'services_by_type'      => true,
);
