<?php

/**
 * @var waShipping $this
 */

return array(
    'map'          => array(
        'value'        => 'desktop',
        'title'        => 'Показывать карту выбора ПВЗ',
        'description'  => 'Когда нужно показывать клиенту карту для выбора пунктов выдачи заказов (ПВЗ).<br>
Если карта не показана, ПВЗ можно выбрать из простого списка.<br><br>',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'desktop' => 'кроме мобильных устройств',
            'none'    => 'никогда',
        ),
    ),
    'oauth'    => array(
        'value'        => '',
        'title'        => 'Токен авторизации',
        'control_type' => waHtmlControl::INPUT,
        'required'     => true,
        'description'  => '',
    ),
    'cabinetId'    => array(
        'value'        => '',
        'title'        => 'Идентификатор личного кабинета',
        'control_type' => waHtmlControl::INPUT,
        'required'     => true,
        'description'  => '',
    ),
    'senderId'    => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'control_type' => waHtmlControl::INPUT,
        'required'     => true,
        'description'  => '',
    ),
    'companyId'    => array(
        'value'        => '',
        'title'        => 'Номер кампании',
        'control_type' => waHtmlControl::INPUT,
        'required'     => true,
        'description'  => 'Введите число после дефиса из номера кампании. Например, число 10203040 из номера 11-10203040.',
    ),
    'warehouseId' => array(
        'value'        => '',
        'title'        => 'Идентификатор склада',
        'control_type' => waHtmlControl::INPUT,
        'required'     => true,
        'description'  => '',
    ),
    'city'         => array(
        'value'        => 'Москва',
        'title'        => 'Город магазина',
        'description'  => 'Введите название ближайшего крупного города',
        'control_type' => waHtmlControl::INPUT,
    ),
    'size'         => array(
        'value'        => array(
            'type'  => 'fixed',
            'table' => array(
                array(
                    'weight' => 1,
                    'height' => 10,
                    'width'  => 20,
                    'length' => 30,
                ),
            ),
        ),
        'title'        => 'Размеры отправления',
        'control_type' => 'SizeControl',
        'description'  => '',
    ),

    'insurance' => array(
        'value'        => 0,
        'placeholder'  => '100 + 12%',
        'title'        => 'Оценочная стоимость',
        'description'  => 'Укажите фиксированную стоимость в рублях или долю от суммы заказа в процентах, либо их сумму или разность.<br><br>Примеры:<br>'.
            '<code>0<br>'.
            '123.45<br>'.
            '12.23%<br>'.
            '123.45+12.23%<br>'.
            '123.45-12.23%</code><br><br>',
        'control_type' => waHtmlControl::INPUT,
    ),

    'cash_service' => array(
        'value'        => false,
        'title'        => 'Комиссия за приём денежных средств',
        'description'  => 'Учитывается в стоимости доставки. Эта настройка не используется при пошаговом оформлении заказа.',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'integration'   => array(
        'value'            => array(),
        'title'            => 'Настройки интеграции',
        'options_callback' => array($this, 'integrationOptions'),
        'control_type'     => waHtmlControl::GROUPBOX,
        'description'      => '<br>',
    ),
    'shipping_type' => array(
        'value'        => 'import',
        'title'        => 'Способ отгрузки',
        'description'  => 'Выберите основной способ передачи заказов в «Яндекс.Доставку».<br>
Для отдельных заказов этот основной способ можно изменить вручную в кабинете «Яндекс.Доставки».',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array(
                'value'       => 'WITHDRAW',
                'title'       => 'Курьером',
                'description' => '',
            ),
            array(
                'value'       => 'IMPORT',
                'title'       => 'Самостоятельно',
                'description' => '',
            ),
        ),
    ),
    'taxes'         => array(
        'value'            => 'skip',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 20%. В настройках налогов в приложении выберите, чтобы НДС был включён в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
        'options_callback' => array($this, 'taxesOptions'),
    ),
    'debug'         => array(
        'title'        => 'Отладка',
        'value'        => false,
        'control_type' => waHtmlControl::HIDDEN,
        'options'      => array(
            0      => '',
            'on'   => 'включена',
            'demo' => 'Демоданные',
        ),
    ),
);
