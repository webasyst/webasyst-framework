<?php
return array(
    'merchant'      => array(
        'value'        => '',
        'title'        => 'Merchant_ID',
        'description'  => 'Ваш ID в системе ASSIST',
        'control_type' => waHtmlControl::INPUT,
    ),
    'authorization' => array(
        'value'        => false,
        'title'        => 'Режим предварительной авторизации',
        'description'  => 'В этом режиме сумма заказа только блокируется на карте покупателя.<br>Списывать средства, подтверждая транзакцию, в этом случае необходимо вручную.',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'locale'        => array(
        'value'        => 'RU',
        'title'        => 'Язык интерфейса',
        'description'  => 'Выберите язык, на котором должна отображаться платежная страница на сайте ASSIST.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('value' => '', 'title' => '(не определен)', ),
            array('value' => 'RU', 'title' => 'русский', ),
            array('value' => 'EN', 'title' => 'английский', ),
        ),
    ),
    'sandbox'       => array(
        'value'        => false,
        'title'        => 'Тестовый режим',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'version'       => array(
        'value'        => 'new',
        'title'        => 'Версия подключения к платежной системе',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'старая (http://www.assist.ru/files/manual_new.pdf)', 'value' => 'old'),
            array('title' => 'новая (http://www.assist.ru/files/TechNEW.doc)', 'value' => 'new'),
            array('title' => 'для Беларуси', 'value' => 'belarus'),
        ),
    ),
    'gate'          => array(
        'value'        => '',
        'title'        => 'Домен',
        'description'  => 'Введите число из домена подключения (<i>https://payments<u><strong>123</strong></u>.paysecure.ru</i> или '
            .'<i>https://payments<u><strong>123</strong></u>.paysec.by</i>).<br>Используется только для новой версии подключения и версии подключения для Беларуси.',
        'control_type' => waHtmlControl::INPUT,
    ),
);
