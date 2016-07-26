<?php
return array(
    #api
    'api_login'    => array(
        'value'        => '',
        'placeholder'  => '',
        'control_type' => waHtmlControl::INPUT,
        'title'        => 'Логин для API Почта России',
        'description'  => 'Чтобы получить логин, необходимо зарегистрироваться на сайте <a href="https://tracking.pochta.ru/" target="_blank">Почты Россиии<i class="icon16 new-window"></i></a>.<br/><br/>',
    ),
    'api_password' => array(
        'value'        => '',
        'placeholder'  => '',
        'control_type' => waHtmlControl::INPUT,
        'title'        => 'Пароль для API Почта России',
        'description'  => <<<HTML
Чтобы получить пароль, необходимо зарегистрироваться на сайте <a href="https://tracking.pochta.ru/" target="_blank">https://tracking.pochta.ru/</a>. <a href="https://tracking.pochta.ru/support/faq/how_to_get_access" target="_blank">Как получить доступ</a><br/><br/><br/>
HTML
        ,
    ),

    #parcel

    'region'           => array(
        'value'        => array(
            '22' => array('zone' => 3, 'avia_only' => false), /*Алтайский край*/
            '28' => array('zone' => 4, 'avia_only' => false), /*Амурская область*/
            '29' => array('zone' => 2, 'avia_only' => false), /*Архангельская область*/
            '30' => array('zone' => 2, 'avia_only' => false), /*Астраханская область*/
            '31' => array('zone' => 2, 'avia_only' => false), /*Белгородская область*/
            '32' => array('zone' => 1, 'avia_only' => false), /*Брянская область*/
            '33' => array('zone' => 1, 'avia_only' => false), /*Владимирская область*/
            '34' => array('zone' => 2, 'avia_only' => false), /*Волгоградская область*/
            '35' => array('zone' => 1, 'avia_only' => false), /*Вологодская область*/
            '36' => array('zone' => 1, 'avia_only' => false), /*Воронежская область*/
            '79' => array('zone' => 5, 'avia_only' => false), /*Еврейская автономная область*/
            '75' => array('zone' => 4, 'avia_only' => false), /*Забайкальский край*/
            '37' => array('zone' => 1, 'avia_only' => false), /*Ивановская область*/
            '38' => array('zone' => 4, 'avia_only' => false), /*Иркутская область*/
            '07' => array('zone' => 2, 'avia_only' => false), /*Кабардино-Балкарская республика*/
            '39' => array('zone' => 2, 'avia_only' => false), /*Калининградская область*/
            '40' => array('zone' => 1, 'avia_only' => false), /*Калужская область*/
            '41' => array('zone' => 5, 'avia_only' => false), /*Камчатский край*/
            '09' => array('zone' => 2, 'avia_only' => false), /*Карачаево-Черкесская республика*/
            '42' => array('zone' => 3, 'avia_only' => false), /*Кемеровская область*/
            '43' => array('zone' => 2, 'avia_only' => false), /*Кировская область*/
            '44' => array('zone' => 1, 'avia_only' => false), /*Костромская область*/
            '23' => array('zone' => 2, 'avia_only' => false), /*Краснодарский край*/
            '24' => array('zone' => 3, 'avia_only' => false), /*Красноярский край*/
            '91' => array('zone' => 3, 'avia_only' => false), /*Крым республика*/
            '45' => array('zone' => 3, 'avia_only' => false), /*Курганская область*/
            '46' => array('zone' => 1, 'avia_only' => false), /*Курская область*/
            '47' => array('zone' => 2, 'avia_only' => false), /*Ленинградская область*/
            '48' => array('zone' => 1, 'avia_only' => false), /*Липецкая область*/
            '49' => array('zone' => 5, 'avia_only' => true), /*Магаданская область*/
            '77' => array('zone' => 1, 'avia_only' => false), /*Москва*/
            '50' => array('zone' => 1, 'avia_only' => false), /*Московская область*/
            '51' => array('zone' => 2, 'avia_only' => false), /*Мурманская область*/
            '83' => array('zone' => 3, 'avia_only' => true), /*Ненецкий автономный округ*/
            '52' => array('zone' => 1, 'avia_only' => false), /*Нижегородская область*/
            '53' => array('zone' => 2, 'avia_only' => false), /*Новгородская область*/
            '54' => array('zone' => 3, 'avia_only' => false), /*Новосибирская область*/
            '55' => array('zone' => 3, 'avia_only' => false), /*Омская область*/
            '56' => array('zone' => 2, 'avia_only' => false), /*Оренбургская область*/
            '57' => array('zone' => 1, 'avia_only' => false), /*Орловская область*/
            '58' => array('zone' => 2, 'avia_only' => false), /*Пензенская область*/
            '59' => array('zone' => 2, 'avia_only' => false), /*Пермский край*/
            '25' => array('zone' => 5, 'avia_only' => false), /*Приморский край*/
            '60' => array('zone' => 2, 'avia_only' => false), /*Псковская область*/
            '01' => array('zone' => 2, 'avia_only' => false), /*Республика Адыгея*/
            '04' => array('zone' => 3, 'avia_only' => false), /*Республика Алтай*/
            '02' => array('zone' => 2, 'avia_only' => false), /*Республика Башкортостан*/
            '03' => array('zone' => 4, 'avia_only' => false), /*Республика Бурятия*/
            '05' => array('zone' => 3, 'avia_only' => false), /*Республика Дагестан*/
            '06' => array('zone' => 2, 'avia_only' => false), /*Республика Ингушетия*/
            '08' => array('zone' => 2, 'avia_only' => false), /*Республика Калмыкия*/
            '10' => array('zone' => 2, 'avia_only' => false), /*Республика Карелия*/
            '11' => array('zone' => 2, 'avia_only' => false), /*Республика Коми*/
            '12' => array('zone' => 2, 'avia_only' => false), /*Республика Марий Эл*/
            '13' => array('zone' => 2, 'avia_only' => false), /*Республика Мордовия*/
            '14' => array('zone' => 4, 'avia_only' => false), /*Республика Саха (Якутия)*/
            '15' => array('zone' => 2, 'avia_only' => false), /*Республика Северная Осетия-Алания*/
            '16' => array('zone' => 2, 'avia_only' => false), /*Республика Татарстан*/
            '17' => array('zone' => 3, 'avia_only' => false), /*Республика Тыва*/
            '19' => array('zone' => 3, 'avia_only' => false), /*Республика Хакасия*/
            '61' => array('zone' => 2, 'avia_only' => false), /*Ростовская область*/
            '62' => array('zone' => 1, 'avia_only' => false), /*Рязанская область*/
            '63' => array('zone' => 2, 'avia_only' => false), /*Самарская область*/
            '78' => array('zone' => 2, 'avia_only' => false), /*Санкт-Петербург*/
            '64' => array('zone' => 2, 'avia_only' => false), /*Саратовская область*/
            '65' => array('zone' => 5, 'avia_only' => false), /*Сахалинская область*/
            '66' => array('zone' => 2, 'avia_only' => false), /*Свердловская область*/
            '92' => array('zone' => 3, 'avia_only' => false), /*Севастополь*/
            '67' => array('zone' => 1, 'avia_only' => false), /*Смоленская область*/
            '26' => array('zone' => 2, 'avia_only' => false), /*Ставропольский край*/
            '68' => array('zone' => 1, 'avia_only' => false), /*Тамбовская область*/
            '69' => array('zone' => 1, 'avia_only' => false), /*Тверская область*/
            '70' => array('zone' => 3, 'avia_only' => false), /*Томская область*/
            '71' => array('zone' => 1, 'avia_only' => false), /*Тульская область*/
            '72' => array('zone' => 3, 'avia_only' => false), /*Тюменская область*/
            '18' => array('zone' => 2, 'avia_only' => false), /*Удмуртская республика*/
            '73' => array('zone' => 2, 'avia_only' => false), /*Ульяновская область*/
            '27' => array('zone' => 5, 'avia_only' => false), /*Хабаровский край*/
            '86' => array('zone' => 3, 'avia_only' => false), /*Ханты-Мансийский автономный округ - Югра*/
            '74' => array('zone' => 2, 'avia_only' => false), /*Челябинская область*/
            '20' => array('zone' => 2, 'avia_only' => false), /*Чеченская республика*/
            '21' => array('zone' => 2, 'avia_only' => false), /*Чувашская республика*/
            '87' => array('zone' => 5, 'avia_only' => true), /*Чукотский автономный округ*/
            '89' => array('zone' => 3, 'avia_only' => false), /*Ямало-Ненецкий автономный округ*/
            '76' => array('zone' => 1, 'avia_only' => false), /*Ярославская область*/
        ),
        'title'        => 'Регионы',
        'control_type' => 'RegionRatesControl',
    ),
    'exclude_cities'   => array(
        'value'        => '',
        'title'        => 'Не доставлять в города',
        'description'  => 'Названия городов через запятую (например, город магазина)',
        'control_type' => waHtmlControl::INPUT,
    ),
    'halfkilocost'     => array(
        'value'        => array(1 => 150.00, 2 => 185.0, 3 => 193.00, 4 => 233.00, 5 => 261.00,),
        'title'        => 'Стоимость отправки посылки весом до 0.5 килограмм (включительно)',
        'description'  => '',
        'control_type' => 'WeightCosts',
    ),
    'overhalfkilocost' => array(
        'value'        => array(1 => 16.00, 2 => 19.00, 3 => 26.00, 4 => 39.00, 5 => 44.00,),
        'title'        => 'Стоимость отправки каждых дополнительных 0.5 килограмм',
        'description'  => '',
        'control_type' => 'WeightCosts',
    ),

    'air'     => array(
        'value'        => '429.00',
        'title'        => 'Надбавка за отправление «Авиа» (руб.)',
        'description'  => 'Укажите стоимость в рублях',
        'control_type' => waHtmlControl::INPUT,
    ),
    'caution' => array(
        'value'        => '',
        'title'        => 'Все посылки отправляются с отметкой «Осторожно»',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'max_weight' => array(
        'value'        => '20',
        'title'        => 'Максимальный вес отправления',
        'description'  => 'Укажите вес в килограммах',
        'control_type' => waHtmlControl::INPUT,
    ),

    'complex_calculation_weight' => array(
        'value'        => '10',
        'title'        => 'Вес усложненной тарификации',
        'description'  => 'Укажите вес в килограммах, начиная с которого к стоимости доставки посылки прибавляется 30% (согласно правилам усложненной тарификации Почты России)',
        'control_type' => waHtmlControl::INPUT,
    ),

    'commission' => array(
        'value'        => '4',
        'title'        => 'Плата за сумму объявленной ценности посылки (%)',
        'description'  => 'Укажите размер комиссии в процентах. Например, укажите 4, если с каждого рубля взимается 4 копейки.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'extra_charge' => array(
        'value'        => 0,
        'title'        => 'Надбавка фиксированная (руб.)',
        'description'  => 'Указанная сумма будет добавлена к общей рассчитанной стоимости доставки.',
        'control_type' => waHtmlControl::INPUT,
        'description'  => '<br/><br/><br/><br/>',
    ),

    #bookpost

    'bookpost' => array(
        'value'        => 'none',
        'title'        => 'Отправлять бандероли',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'none'     => 'Не отправлять',
            'simple'   => 'Простые',
            'ordered'  => 'Заказные',
            'declared' => 'С объявленной ценностью',
        ),
        'description'  => '«Если вы включили выше отправку бандеролей, то все заказы стоимостью менее 10 000 руб. и весом менее максимального (не может превышать 2 кг) будут отправляться бандеролями, а не посылками.',
    ),

    'bookpost_max_weight' => array(
        'value'        => '1.9',
        'title'        => '«Максимальный вес заказа для отправки бандеролью (кг)',
        'description'  => 'Укажите вес в килограммах',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_simple_cost' => array(
        'value'        => '43.66',
        'title'        => 'Стоимость отправки бандероли весом 0,1 кг',
        'description'  => '',
        'class'        => 'russianpost_bookpost russianpost_simple',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_weight_simple_cost' => array(
        'value'        => '2.95',
        'title'        => 'Стоимость отправки каждых 0,02 кг',
        'description'  => '<br/><br/><br/><br/>',
        'class'        => 'russianpost_bookpost russianpost_simple',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_ordered_cost' => array(
        'value'        => '62.54',
        'title'        => 'Стоимость отправки бандероли весом 0,1 кг',
        'description'  => '',
        'class'        => 'russianpost_bookpost russianpost_ordered',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_weight_ordered_cost' => array(
        'value'        => '2.95',
        'title'        => 'Стоимость отправки каждых 0,02 кг',
        'description'  => '<br/><br/><br/><br/>',
        'class'        => 'russianpost_bookpost russianpost_ordered',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_weight_declared_cost' => array(
        'value'        => array(1 => 82.60, 2 => 88.50, 3 => 94.40, 4 => 100.30, 5 => 118.00,),
        'title'        => 'Стоимость отправки каждых 0.5 килограмм бандероли с объявленной ценностью',
        'description'  => '',
        'class'        => 'russianpost_bookpost russianpost_declared',
        'control_type' => 'WeightCosts',
    ),

    'bookpost_declared_commission' => array(
        'value'        => '4',
        'title'        => 'Плата за сумму объявленной ценности бандероли (%)',
        'description'  => 'Укажите размер комиссии в процентах. Например, укажите 4, если с каждого рубля взимается 4 копейки.',
        'class'        => 'russianpost_bookpost russianpost_declared',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_air'       => array(
        'value'        => '134.00',
        'title'        => 'Надбавка за отправление «Авиа» для бандероли(руб.)',
        'description'  => 'Укажите стоимость в рублях<br/><br/><br/><br/>',
        'class'        => 'russianpost_bookpost russianpost_declared',
        'control_type' => waHtmlControl::INPUT,
    ),

    #dleivery date
    'delivery_date_show' => array(
        'value'        => true,
        'title'        => 'Показывать приблизительные сроки доставки',
        'description'  => <<<HTML
<script type="text/javascript">
(function () {
    "use strict";
    var russianpost = {
        form: null,
        delivery_date_show: null,
        bookpost_type: null,
        bookpost_fields: null,

        bind: function () {
            var delivery_date = $(':input[name$="\[delivery_date_show\]"]:first');


            this.form = delivery_date.parents('form:first');

            var bookpost_type = this.form.find(':input[name$="\[bookpost\]"]');

            var self = this;

            delivery_date.unbind('change').bind('change', function (event) {
                if (self.delivery_date_show == null) {
                    self.delivery_date_show = self.form.find('.russianpost_delivery_date_show').parents('div.field');
                }
                self.toggle(self.delivery_date_show, event, this.checked)
            }).trigger('change');

            bookpost_type.unbind('change').bind('change', function (event) {
                self.changeBookpost(event, this)
            }).trigger('change');
        },

        changeBookpost: function (event, element) {
            if (element.checked) {
                var slow = event.originalEvent;
                if (this.bookpost_fields == null) {
                    this.bookpost_fields = this.form.find('.russianpost_bookpost');
                }
                this.hide(this.bookpost_fields.filter(':not(.russianpost_' + element.value + ')').parents('div.field'), slow);
                this.show(this.bookpost_fields.filter('.russianpost_' + element.value).parents('div.field'), slow);
            }
        },

        toggle: function (item, event, show) {
            if (show === null) {
                show = !item.is(':visible');
            }
            if (show) {
                this.show(item, event.originalEvent)
            } else {
                this.hide(item, event.originalEvent)
            }
        },
        show: function (item, slow) {
            if (slow) {
                item.slideDown();
            } else {
                item.show();
            }

        },
        hide: function (item, slow) {
            if (slow) {
                item.slideUp();
            } else {
                item.hide();
            }
        }
    };

    russianpost.bind();

})();
</script>
HTML
        ,
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'delivery_date_min' => array(
        'value'        => 7,
        'title'        => 'Приблизительный минимальный срок доставки',
        'description'  => 'Укажите число дней',
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'russianpost_delivery_date_show',
    ),

    'delivery_date_max' => array(
        'value'        => 14,
        'title'        => 'Приблизительный максимальный срок доставки',
        'description'  => 'Укажите число дней<br/><br/><br/><br/>',
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'russianpost_delivery_date_show',
    ),

    #sender
    'company_name'      => array(
        'value'        => '',
        'title'        => 'Получатель наложенного платежа (магазин)',
        'description'  => 'Для юридического лица — полное или краткое наименование; для гражданина — ФИО полностью.',
        'control_type' => 'text',
    ),

    'company_name2' => array(
        'value'        => '',
        'title'        => 'Получатель наложенного платежа (магазин)',
        'description'  => '(вторая строка)',
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
    'phone'               => array(
        'value'        => '',
        'title'        => 'Телефон отправителя (магазина)',
        'description'  => '',
        'placeholder'  => '+7(123)123-45-67',
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

    'document' => array(
        'value'        => '',
        'title'        => 'Документ (магазина)',
        'description'  => 'Наименование документа получателя наложенного платежа.',
        'control_type' => 'text',
    ),

    'document_series' => array(
        'value'        => '',
        'title'        => 'Серия документа (магазина)',
        'description'  => 'Серия документа получателя наложенного платежа.',
        'control_type' => 'text',
    ),

    'document_number' => array(
        'value'        => '',
        'title'        => 'Номер документа (магазина)',
        'description'  => 'Номер документа получателя наложенного платежа.',
        'control_type' => 'text',
    ),

    'document_issued_day' => array(
        'value'        => '',
        'title'        => 'Дата выдачи документа, число (магазина)',
        'description'  => 'Дата выдачи документа получателя наложенного платежа (число).',
        'control_type' => 'text',
    ),

    'document_issued_month' => array(
        'value'        => '',
        'title'        => 'Дата выдачи документа, месяц (магазина)',
        'description'  => 'Дата выдачи документа получателя наложенного платежа (месяц, две цифры).',
        'control_type' => 'text',
    ),

    'document_issued_year' => array(
        'value'        => '',
        'title'        => 'Дата выдачи документа, год (магазина)',
        'description'  => 'Дата выдачи документа получателя наложенного платежа (год, две последние цифры).',
        'control_type' => 'text',
    ),

    'document_issued' => array(
        'value'        => '',
        'title'        => 'Кем выдан документ (магазина)',
        'description'  => 'Название организации, выдавшей документ',
        'control_type' => 'text',
    ),

    'color' => array(
        'value'        => '1',
        'title'        => 'Печатать желтую полосу (форма ф. 113эн)',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
