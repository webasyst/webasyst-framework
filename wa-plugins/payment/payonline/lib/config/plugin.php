<?php
return array(
    'name'        => 'PayOnline',
    'description' => 'Оплата через процессинговый центр PayOnline',
    'icon'        => 'img/payonline16.png',
    'logo'        => 'img/payonline.png',
    'vendor'      => 'webasyst',
    'version'     => '1.2.1',
    'locale'      => array('ru_RU',),
    'type'        => waPayment::TYPE_ONLINE,
    'discount'    => true,
);
