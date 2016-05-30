<?php
return array(
    'region'          => array(
        'title'        => 'Домашний регион магазина',
        'description'  => 'Выберите регион (область, край, округ, республику) пункта отправления, из которого осуществляется доставка.',
        'control_type' => waHtmlControl::SELECT,
    ),
    'city'            => array(
        'title'        => 'Город магазина',
        'description'  => 'Введите название города пункта отправления. ВАЖНО: Убедитесь, что указанное вами название города присутствует в списке городов, доставка между которыми может быть автоматически рассчитана с помощью API службы «EMS Почта России»: <a href="http://emspost.ru/api/rest/?method=ems.get.locations&type=cities&plain=true" target="_blank">http://emspost.ru/api/rest/?method=ems.get.locations&type=cities&plain=true</a> (регистр при вводе названия города не имеет значения).',
        'control_type' => waHtmlControl::INPUT,
    ),
    'home_delivery'   => array(
        'value'        => true,
        'title'        => 'Доставлять ЕМС в городе магазина',
        'description'  => 'Выключите эту опцию, если не хотите предлагать доставку ЕМС в регионе, из которого вы отправляете заказы.',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'surcharge'       => array(
        'value'        => 1,
        'title'        => 'Надбавка (%)',
        'description'  => 'Указанный процент от общей стоимости отправления будет прибавлен к стоимости доставки.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'fixed_surcharge' => array(
        'value'        => 1,
        'title'        => 'Надбавка фиксированная (руб.)',
        'description'  => 'Указанная сумма будет добавлена к общей рассчитанной стоимости доставки.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'company_name'    => array(
        'value'        => '',
        'title'        => 'Получатель наложенного платежа (магазин)',
        'description'  => 'Для юридического лица — полное или краткое наименование; для гражданина — ФИО полностью.',
        'control_type' => 'text',
    ),

    'address1'            => array(
        'value'        => '',
        'title'        => 'Адрес получателя наложенного платежа (магазина), строка 1',
        'description'  => 'Почтовый адрес получателя наложенного платежа.',
        'control_type' => 'text',
    ),
    'address2'            => array(
        'value'        => '',
        'title'        => 'Адрес получателя наложенного платежа (магазина), строка 2',
        'description'  => 'Заполните, если адрес не помещается в одну строку.',
        'control_type' => 'text',
    ),
    'zip'                 => array(
        'value'        => '',
        'title'        => 'Индекс получателя наложенного платежа (магазина)',
        'description'  => 'Индекс должен состоять ровно из 6 цифр.',
        'control_type' => 'text',
    ),
    'inn'                 => array(
        'value'        => '',
        'title'        => 'ИНН получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц. 10 цифр.',
        'control_type' => 'text',
    ),
    'bank_kor_number'     => array(
        'value'        => '',
        'title'        => 'Кор. счет получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц. 20 цифр.',
        'control_type' => 'text',
    ),
    'bank_name'           => array(
        'value'        => '',
        'title'        => 'Наименование банка получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц.',
        'control_type' => 'text',
    ),
    'bank_account_number' => array(
        'value'        => '',
        'title'        => 'Расчетный счет получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц. 20 цифр.',
        'control_type' => 'text',
    ),
    'bik'                 => array(
        'value'        => '',
        'title'        => 'БИК получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц. 9 цифр.',
        'control_type' => 'text',
    ),
);
