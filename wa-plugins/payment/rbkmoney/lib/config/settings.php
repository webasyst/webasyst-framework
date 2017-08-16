<?php

return array(
    'shop_id' => array(
        'value'        => '',
        'title'        => 'Номер сайта продавца',
        'description'  => 'Ваш номер аккаунта в платежной системе RBK Money, на который будут зачислены платежи.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'shop_account' => array(
        'value'        => '',
        'title'        => 'Номер кошелька',
        'description'  => 'Ваш номер кошелька магазина в системе RBK Money.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret_key' => array(
        'value'       => '',
        'title'       => 'Секретный ключ',
        'description' => 'Ваш секретный ключ в системе RBK Money, известный только вам. Необходим для проверки ответа от платежной системы RBK Money.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'due_date' => array(
        'value' => '3',
        'title' => 'Количество дней ожидания платежа',
        'description' => 'Количество дней ожидания платежа. По истечении этого срока RBKMoney будет считать счет недействительным и не будет принимать оплату по нему',
        'control_type' => waHtmlControl::INPUT
    ),

    'payment_method' => array(
        'value' => 'all',
        'title' => 'Предпочитаемый метод оплаты',
        'description' => 'Это поле позволяет заранее определить метод оплаты для покупателя, сокращает на два шага процесс оплаты на сайте RBKMoney',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            'all' => 'На выбор покупателя',
            'rbkmoney' => 'Кошелек RBKMoney',
            'bankcard' => 'Банковская карта Visa/Mastercard',
            'terminals' => 'Платежные терминалы',
            'postrus' => 'Почта России', // Почтовый перевод
            'mobilestores' => 'Салоны связи',
            'transfers' => 'Системы денежных переводов',
            'ibank' => 'Интернет-банкинг',
            'sberbank' => 'Банковский платеж',
            'svyaznoy' => 'Связной',
            'euroset' => 'Евросеть',
            'contact' => 'Контакт',
//            'prepaidcard' => 'Предоплаченная карта RBKMoney', // Не работает
//            'exchangers' => 'Электронные платежные системы', // Не работает
//            'mts' => 'МТС', // Сейчас это то же самое, что и mobilestores
//            'uralsib' => 'Кассы Уралсиб', // Не работает, дублирует ibank
//            'handybank' => 'HandyBank', // Не работает, дублирует ibank
//            'ocean' => 'Банк Океан', // Не работает
//            'ibankuralsib' => 'Интернет-банк Уралсиб' Не работает, дублирует ibank
        )
    )
);