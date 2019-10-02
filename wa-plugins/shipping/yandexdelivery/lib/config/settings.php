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
            //    'always'  => 'Всегда.',
            'desktop' => 'кроме мобильных устройств',
            'none'    => 'никогда',
        ),
    ),
    'method_keys'  => array(
        'value'        => '',
        'title'        => 'method_keys',
        'description'  => 'Правила заполнения этих настроек описаны в <a href="https://www.shop-script.ru/help/18657/shop-script-yandex-delivery/" target="_blank">инструкции</a>. <i class="icon16 new-window"></i>',
        'control_type' => waHtmlControl::TEXTAREA,
        'rows'         => 60,
    ),
    'client_id'    => array(
        'value'        => '',
        'title'        => 'client_id',
        'control_type' => waHtmlControl::INPUT,
    ),
    'sender_id'    => array(
        'value'        => '',
        'title'        => 'sender_id',
        'control_type' => waHtmlControl::INPUT,
        'description'  => '&nbsp;',
    ),
    'warehouse_id' => array(
        'value'        => '',
        'title'        => 'warehouse_id',
        'control_type' => waHtmlControl::INPUT,
        'description'  => '&nbsp;',
    ),
    'city'         => array(
        'value'        => 'Москва',
        'title'        => 'Город магазина',
        'description'  => '&nbsp;',
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
        'description'  => '&nbsp;',
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
        'title'        => 'Комиссия за прием денежных средств',
        'description'  => 'Учитывать в стоимости доставки. Эта настройка не используется при пошаговом оформлении заказа.',
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
        'description'  => 'Выберите основной способ передачи заказов в «Яндекс.Доставку». Учитывается с любым значением настройки «Передавать заказы напрямую в службы доставки».<br>
Для отдельных заказов этот основной способ можно изменить вручную в кабинете «Яндекс.Доставки».',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array(
                'value'       => 'withdraw',
                'title'       => 'Забор с вашего склада',
                'description' => '',
            ),
            array(
                'value'       => 'import',
                'title'       => 'Самопривоз',
                'description' => '',
            ),
        ),
    ),
    'yd_warehouse'  => array(
        'value'        => true,
        'title'        => 'Передавать заказы через единый склад',
        'control_type' => waHtmlControl::CHECKBOX,
        'description'  => 'Если выключено, то единый склад «Яндекс.Доставки» не используется, и заказы поступают напрямую на склады служб доставки.<br>
Если включено, то заказы передаются на единый склад «Яндекс.Доставки» и оттуда потом распределяются по службам доставки.',
    ),
    'taxes'         => array(
        'value'            => 'skip',
        'title'            => 'Передача ставок НДС',
        'control_type'     => waHtmlControl::SELECT,
        'description'      => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 10% или 18%. В настройках налогов в приложении выберите, чтобы НДС был включен в цену товара.<br>
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
            'demo' => 'Демо данные',
        ),
    ),
);
