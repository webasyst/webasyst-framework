<?php
return array(
    'userName'                 => array(
        'value'        => '',
        'title'        => 'Логин магазина',
        'description'  => 'Выдаётся при подключении к платёжному шлюзу, для тестового и рабочего режима используются разные учётные данные.',
        'control_type' => waHtmlControl::INPUT,
        'class'        => '',
    ),
    'password'                 => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => 'Выдаётся при подключении к платёжному шлюзу, для тестового и рабочего режима используются разные учётные данные.',
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => ''
    ),
    'currency_id'              => array(
        'value'        => '',
        'title'        => 'Валюта',
        'description'  => 'Валюта, в которой будут выполняться платежи.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('title' => 'RUB', 'value' => 'RUB'),
        ),
    ),
    'TESTMODE'                 => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Использует тестовый шлюз 3dsec.sberbank.ru и отключает автоматическое перенаправление покупателя на страницу оплаты.<br>
Для выполнения тестового платежа нужно перейти на сайт «Сбербанка» с помощью кнопки.'
    ),
    'sessionTimeoutSecs'       => array(
        'value'        => '24',
        'title'        => 'Продолжительность жизни заказа в часах',
        'description'  => 'Заказ можно будет оплатить только в течение указанного времени.',
        'control_type' => waHtmlControl::INPUT,
        'class'        => '',
        'placeholder'  => '24',
    ),
    'two_step'                 => array(
        'value'        => '',
        'title'        => 'Двухстадийная оплата',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Включите, только если ваш договор предусматривает необходимость ручного потверждения платежей.'
    ),
    'cancel'                   => array(
        'value'        => '',
        'title'        => 'Отмена платежей',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Отмена платежей для двухстадийной оплаты должна поддерживаться приложением.<br>
Пример поддерживаемого приложения: CRM.'
    ),
    'fiscalization'            => array(
        'value'        => '',
        'title'        => 'Фискализация платежей',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => /*_wp*/
            ('Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:')
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 10% либо 20% и <em>включён</em> в стоимость элементов заказа и стоимость доставки',
    ),
    'payment_method'           => array(
        'value'        => '1',
        'title'        => 'Признак способа расчёта в чеках',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '1' => 'полная предоплата',
            '2' => 'частичная предоплата',
            '3' => 'аванс',
            '4' => 'полный расчёт',
            '5' => 'частичный расчёт и кредит',
            '6' => 'кредит',
            '7' => 'выплата по кредиту',
        ),
    ),
    'payment_subject_product'  => array(
        'value'            => '1',
        'title'            => 'Признак предмета расчёта для товаров в чеках',
        'description'      => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('sbPayment', 'settingsPaymentSubjectOptions'),
    ),
    'payment_subject_service'  => array(
        'value'            => '4',
        'title'            => 'Признак предмета расчёта для услуг в чеках',
        'description'      => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('sbPayment', 'settingsPaymentSubjectOptions'),
    ),
    'payment_subject_shipping' => array(
        'value'            => '4',
        'title'            => 'Признак предмета расчёта для доставки в чеках',
        'description'      => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('sbPayment', 'settingsPaymentSubjectOptions'),
    ),
    'tax_system'               => array(
        'value'        => '',
        'title'        => 'Система налогообложения',
        'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '0' => 'Общая',
            '1' => 'Упрощённая, доход',
            '2' => 'Упрощённая, доход минус расход',
            '3' => 'Единый налог на вменённый доход',
            '4' => 'Единый сельскохозяйственный налог',
            '5' => 'Патентная система налогообложения',
        ),
    ),
    'credit'                   => array(
        'value'        => '',
        'title'        => 'Кредит',
        'control_type' => 'checkbox',
        'class'        => '',
        'description'  => 'Включение режима «Кредит» выключает обычный режим оплаты.'
    ),
    'credit_type'              => array(
        'value'        => '',
        'title'        => 'Вид кредита',
        'control_type' => waHtmlControl::SELECT,
        'class'        => '',
        'description'  => 'Выберите подходящий вид кредита, если включён режим «Кредит».',
        'options'      => array(
            'INSTALLMENT' => 'кредит без переплаты',
            'CREDIT'      => 'кредит',
        ),
    ),
);
