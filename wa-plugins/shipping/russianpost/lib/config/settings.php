<?php
return array(
    #api
    'api_login'    => array(
        'value'        => '',
        'placeholder'  => '',
        'control_type' => waHtmlControl::INPUT,
        'title'        => 'Логин для API «Почты России»',
    ),
    'api_password' => array(
        'value'        => '',
        'placeholder'  => '',
        'control_type' => waHtmlControl::INPUT,
        'title'        => 'Пароль для API «Почты России»',
        'description'  => <<<HTML
Чтобы получить логин и пароль, необходимо зарегистрироваться на сайте «<a href="https://tracking.pochta.ru/" target="_blank">Почты России</a>». <a href="https://tracking.pochta.ru/support/faq/how_to_get_access" target="_blank">Как получить доступ</a>.<br/><br/><br/>
HTML
        ,
    ),
    'required_address_fields' => array(
        'title'        => 'Обязательные поля адреса',
        'control_type' => waHtmlControl::GROUPBOX,
        'options'      => array(
            array(
                'value' => 'zip',
                'title' => 'Почтовый индекс',
            ),
            array(
                'value' => 'street',
                'title' => 'Улица, дом, квартира',
            ),
        ),
        'description'  => 'Выберите поля адреса, которые должны быть обязательны для заполнения.<br><br>',
    ),

    #parcel

    'region'           => array(
        'value'        => array(
            '22' => array('zone' => 3), /*Алтайский край*/
            '28' => array('zone' => 4), /*Амурская область*/
            '29' => array('zone' => 2), /*Архангельская область*/
            '30' => array('zone' => 2), /*Астраханская область*/
            '31' => array('zone' => 2), /*Белгородская область*/
            '32' => array('zone' => 1), /*Брянская область*/
            '33' => array('zone' => 1), /*Владимирская область*/
            '34' => array('zone' => 2), /*Волгоградская область*/
            '35' => array('zone' => 1), /*Вологодская область*/
            '36' => array('zone' => 1), /*Воронежская область*/
            '79' => array('zone' => 5), /*Еврейская автономная область*/
            '75' => array('zone' => 4), /*Забайкальский край*/
            '37' => array('zone' => 1), /*Ивановская область*/
            '38' => array('zone' => 4), /*Иркутская область*/
            '07' => array('zone' => 2), /*Кабардино-Балкарская республика*/
            '39' => array('zone' => 2), /*Калининградская область*/
            '40' => array('zone' => 1), /*Калужская область*/
            '41' => array('zone' => 5), /*Камчатский край*/
            '09' => array('zone' => 2), /*Карачаево-Черкесская республика*/
            '42' => array('zone' => 3), /*Кемеровская область*/
            '43' => array('zone' => 2), /*Кировская область*/
            '44' => array('zone' => 1), /*Костромская область*/
            '23' => array('zone' => 2), /*Краснодарский край*/
            '24' => array('zone' => 3), /*Красноярский край*/
            '91' => array('zone' => 3), /*Крым республика*/
            '45' => array('zone' => 3), /*Курганская область*/
            '46' => array('zone' => 1), /*Курская область*/
            '47' => array('zone' => 2), /*Ленинградская область*/
            '48' => array('zone' => 1), /*Липецкая область*/
            '49' => array('zone' => 5), /*Магаданская область*/
            '77' => array('zone' => 1), /*Москва*/
            '50' => array('zone' => 1), /*Московская область*/
            '51' => array('zone' => 2), /*Мурманская область*/
            '83' => array('zone' => 3), /*Ненецкий автономный округ*/
            '52' => array('zone' => 1), /*Нижегородская область*/
            '53' => array('zone' => 2), /*Новгородская область*/
            '54' => array('zone' => 3), /*Новосибирская область*/
            '55' => array('zone' => 3), /*Омская область*/
            '56' => array('zone' => 2), /*Оренбургская область*/
            '57' => array('zone' => 1), /*Орловская область*/
            '58' => array('zone' => 2), /*Пензенская область*/
            '59' => array('zone' => 2), /*Пермский край*/
            '25' => array('zone' => 5), /*Приморский край*/
            '60' => array('zone' => 2), /*Псковская область*/
            '01' => array('zone' => 2), /*Республика Адыгея*/
            '04' => array('zone' => 3), /*Республика Алтай*/
            '02' => array('zone' => 2), /*Республика Башкортостан*/
            '03' => array('zone' => 4), /*Республика Бурятия*/
            '05' => array('zone' => 3), /*Республика Дагестан*/
            '06' => array('zone' => 2), /*Республика Ингушетия*/
            '08' => array('zone' => 2), /*Республика Калмыкия*/
            '10' => array('zone' => 2), /*Республика Карелия*/
            '11' => array('zone' => 2), /*Республика Коми*/
            '12' => array('zone' => 2), /*Республика Марий Эл*/
            '13' => array('zone' => 2), /*Республика Мордовия*/
            '14' => array('zone' => 4), /*Республика Саха (Якутия)*/
            '15' => array('zone' => 2), /*Республика Северная Осетия-Алания*/
            '16' => array('zone' => 2), /*Республика Татарстан*/
            '17' => array('zone' => 3), /*Республика Тыва*/
            '19' => array('zone' => 3), /*Республика Хакасия*/
            '61' => array('zone' => 2), /*Ростовская область*/
            '62' => array('zone' => 1), /*Рязанская область*/
            '63' => array('zone' => 2), /*Самарская область*/
            '78' => array('zone' => 2), /*Санкт-Петербург*/
            '64' => array('zone' => 2), /*Саратовская область*/
            '65' => array('zone' => 5), /*Сахалинская область*/
            '66' => array('zone' => 2), /*Свердловская область*/
            '92' => array('zone' => 3), /*Севастополь*/
            '67' => array('zone' => 1), /*Смоленская область*/
            '26' => array('zone' => 2), /*Ставропольский край*/
            '68' => array('zone' => 1), /*Тамбовская область*/
            '69' => array('zone' => 1), /*Тверская область*/
            '70' => array('zone' => 3), /*Томская область*/
            '71' => array('zone' => 1), /*Тульская область*/
            '72' => array('zone' => 3), /*Тюменская область*/
            '18' => array('zone' => 2), /*Удмуртская республика*/
            '73' => array('zone' => 2), /*Ульяновская область*/
            '27' => array('zone' => 5), /*Хабаровский край*/
            '86' => array('zone' => 3), /*Ханты-Мансийский автономный округ - Югра*/
            '74' => array('zone' => 2), /*Челябинская область*/
            '20' => array('zone' => 2), /*Чеченская республика*/
            '21' => array('zone' => 2), /*Чувашская республика*/
            '87' => array('zone' => 5), /*Чукотский автономный округ*/
            '89' => array('zone' => 3), /*Ямало-Ненецкий автономный округ*/
            '76' => array('zone' => 1), /*Ярославская область*/
        ),
        'title'        => 'Регионы',
        'control_type' => 'RegionRatesControl',
    ),
    'exclude_cities'   => array(
        'value'        => '',
        'title'        => 'Не доставлять в населенные пункты (например, город магазина)',
        'description'  => 'Перечислите названия населенных пунктов через запятую',
        'control_type' => waHtmlControl::INPUT,
    ),
    'halfkilocost'     => array(
        'value'        => array(1 => 173.00, 2 => 235.00, 3 => 244.00, 4 => 294.00, 5 => 330.00,),
        'title'        => 'Стоимость отправки посылки весом до 0,5 кг (включительно)',
        'description'  => '',
        'control_type' => 'WeightCosts',
    ),
    'overhalfkilocost' => array(
        'value'        => array(1 => 21.00, 2 => 24.00, 3 => 33.00, 4 => 48.00, 5 => 55.00,),
        'title'        => 'Стоимость отправки каждых дополнительных 0,5 кг',
        'description'  => '',
        'control_type' => 'WeightCosts',
    ),

    'caution'         => array(
        'value'        => '',
        'title'        => 'Все посылки отправляются с отметкой «Осторожно»',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'caution_percent' => array(
        'value'        => '30',
        'placeholder'  => '30',
        'title'        => 'Процент надбавки за отметку «Осторожно» (%)',
        'description'  => 'Отредактируйте стандартное значение, только если выше включена настройка «Все посылки отправляются с отметкой „Осторожно”».',
        'control_type' => waHtmlControl::INPUT,
    ),
    'max_weight'      => array(
        'value'        => '20',
        'title'        => 'Максимальный вес посылки',
        'description'  => 'Укажите максимальный вес в килограммах',
        'control_type' => waHtmlControl::INPUT,
    ),
    'max_volume'      => array(
        'value'        => '120',
        'title'        => 'Максимальный объем посылки',
        'description'  => 'Укажите сумму длины, ширины и высоты в сантиметрах. Если общий размер будет превышен, то будет добавлена надбавка за негабарит',
        'control_type' => waHtmlControl::INPUT,
    ),
    'max_side_length'      => array(
        'value'        => '60',
        'title'        => 'Максимальная длина стороны посылки',
        'description'  => 'Укажите максимальную длину стороны посылки в сантиметрах. Если любая из сторон посылки будет превышать указанное значение, то будет добавлена надбавка за негабарит',
        'control_type' => waHtmlControl::INPUT,
    ),

    'complex_calculation_weight'  => array(
        'value'        => '10',
        'title'        => 'Вес усложненной тарификации для посылки',
        'description'  => 'Укажите вес в килограммах, начиная с которого к стоимости доставки посылки должна прибавляться надбавка за негабарит согласно правилам усложненной тарификации «Почты России»',
        'control_type' => waHtmlControl::INPUT,
    ),
    'complex_calculation_percent' => array(
        'value'        => '40',
        'placeholder'  => '40',
        'title'        => 'Процент надбавки за негабарит посылки (%)',
        'description'  => 'Надбавка будет прибавлена к стоимости оплаты за весь вес посылки, если ее вес превышает значение веса усложненной тарификации.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'commission' => array(
        'value'        => '4',
        'title'        => 'Плата за сумму объявленной ценности посылки (%)',
        'description'  => 'Укажите размер комиссии в процентах. Например, укажите <em>4</em>, если с каждого рубля взимается 4 копейки.<br><br><br><br>',
        'control_type' => waHtmlControl::INPUT,
    ),

    'cod'              => array(
        'value'        => '',
        'title'        => 'Комиссия за почтовый перевод наложенного платежа',
        'description'  => <<<HTML
Рассчитанная комиссия «Почты России» за почтовый перевод будет показана для информации покупателю во время оформления заказа и пользователю бекенда в виде дополнительного поля на
странице просмотра заказа. Эта комиссия не включается в стоимость доставки, покупатель оплачивает ее дополнительно к сумме почтового перевода за заказ (наложенный платеж) при
получении посылки в отделении.
<script type="text/javascript">
(function () {
    var handler = $('.russianpost_cash_on_delivery_handler'),
        data = $('.russianpost_cash_on_delivery_data');
    showData(handler, data);

    handler.on('change', function () {
        showData(handler, data);
    });

     function showData (handler, data) {
        if (handler.is(':checked')) {
            data.parents('div.field').show();
        } else {
            data.parents('div.field').hide();
        }
    }
})();
</script>
HTML
        ,
        'control_type' => waHtmlControl::CHECKBOX,
        'class'        => 'russianpost_cash_on_delivery_handler',
    ),
    'cash_on_delivery' => array(
        'value'        => array(
            0 => array('rate' => 80, 'percent' => 5),
            1 => array('rate' => 90, 'percent' => 4),
            2 => array('rate' => 190, 'percent' => 2),
            3 => array('rate' => 290, 'percent' => 1.5),
        ),
        'title'        => 'Тарифы на доставку почтового перевода',
        'description'  => '',
        'control_type' => 'CashDelivery',
        'class'        => 'russianpost_cash_on_delivery_data'
    ),
    'difficult_charge' => array(
        'value'        => '50',
        'placeholder'  => '50',
        'title'        => 'Процент надбавки за доставку в труднодоступные отделения (%)',
        'description'  => 'Надбавка будет добавлена к стоимости оплаты за весь вес посылки или бандероли.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'extra_charge'     => array(
        'value'        => 0,
        'title'        => 'Надбавка фиксированная (руб.)',
        'description'  => 'Указанная сумма будет добавлена к общей рассчитанной стоимости доставки посылки или бандероли.<br><br><br><br>',
        'control_type' => waHtmlControl::INPUT,
    ),


    #parcel

    'parcel' => array(
        'value'        => 'always',
        'title'        => 'Отправлять посылки',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'always'    => 'Отправлять всегда (если включены еще и бандероли, то у покупателя будет выбор между посылками и бандеролями)',
            'otherwise' => 'Отправлять, если недоступны бандероли по весу или стоимости заказа ',
            'none'      => 'Не отправлять (только бандероли, если они включены и доступны по весу и стоимости заказа)',
        ),
        'description'  => 'Учитывайте настройку «Отправлять бандероли».',
    ),

    #bookpost

    'bookpost' => array(
        'value'        => 'none',
        'title'        => 'Отправлять бандероли',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'none'     => 'Не отправлять',
            'simple'   => 'Простые бандероли',
            'ordered'  => 'Заказные бандероли',
            'declared' => 'Бандероли с объявленной ценностью',
        ),
        'description'  => 'Если включена отправка бандеролей, то заказы стоимостью и весом менее максимального будут отправляться бандеролями или посылками на <strong>выбор покупателя</strong>. Если необходимо, то можно отключить отправку посылок (см. настройку «Отправка посылок»).',
    ),

    'bookpost_max_weight' => array(
        'value'        => '1.9',
        'title'        => 'Максимальный вес заказа для отправки бандеролью (кг)',
        'description'  => '2 кг — ограничение «Почты России» для бандеролей.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_max_price' => array(
        'value'        => 10000,
        'placeholder'  => 10000,
        'title'        => 'Максимальная стоимость заказа для отправки бандеролью (руб.)',
        'description'  => '10000 рублей — ограничение «Почты России» для бандеролей.',
        'control_type' => waHtmlControl::INPUT,
    ),

    'bookpost_simple_cost' => array(
        'value'        => '47.20',
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
        'value'        => '70.80',
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
        'value'        => array(1 => 94.40, 2 => 106.20, 3 => 118.00, 4 => 129.80, 5 => 147.50,),
        'title'        => 'Стоимость отправки каждых 0,5 кг бандероли с объявленной ценностью',
        'description'  => '',
        'class'        => 'russianpost_bookpost russianpost_declared',
        'control_type' => 'WeightCosts',
    ),

    'bookpost_declared_commission' => array(
        'value'        => '4',
        'title'        => 'Плата за сумму объявленной ценности бандероли (%)',
        'description'  => 'Укажите размер комиссии в процентах. Например, укажите <em>4</em>, если с каждого рубля взимается 4 копейки.',
        'class'        => 'russianpost_bookpost russianpost_declared',
        'control_type' => waHtmlControl::INPUT,
    ),

    #dleivery date
    'delivery_date_show'           => array(
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
        'description'  => 'Укажите количество дней',
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'russianpost_delivery_date_show',
    ),

    'delivery_date_max' => array(
        'value'        => 14,
        'title'        => 'Приблизительный максимальный срок доставки',
        'description'  => 'Укажите количество дней<br/><br/><br/><br/>',
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
        'description'  => 'Заполните, если имя (наименование) получателя не помещается в одну строку.',
        'control_type' => 'text',
    ),

    'address1'                => array(
        'value'        => '',
        'title'        => 'Адрес получателя наложенного платежа (магазина), строка 1',
        'description'  => 'Почтовый адрес получателя наложенного платежа.',
        'control_type' => 'text',
    ),
    'address2'                => array(
        'value'        => '',
        'title'        => 'Адрес получателя наложенного платежа (магазина), строка 2',
        'description'  => 'Заполните, если адрес не помещается в одну строку.',
        'control_type' => 'text',
    ),
    'zip'                     => array(
        'value'        => '',
        'title'        => 'Индекс получателя наложенного платежа (магазина)',
        'description'  => 'Индекс должен состоять только из 6 цифр.',
        'control_type' => 'text',
    ),
    'zip_distribution_center' => array(
        'value'        => '',
        'title'        => 'Индекс центра распределения переводов',
        'description'  => 'Заполните, если нужно указать отдельный индекс для бланка наложенного платежа',
        'control_type' => 'text',
    ),
    'phone'                   => array(
        'value'        => '',
        'title'        => 'Телефон отправителя (магазина)',
        'description'  => '',
        'placeholder'  => '+7(123)123-45-67',
        'control_type' => 'text',
    ),
    'inn'                     => array(
        'value'        => '',
        'title'        => 'ИНН получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц — 10 цифр.',
        'control_type' => 'text',
    ),
    'bank_kor_number'         => array(
        'value'        => '',
        'title'        => 'Корр. счет получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц — 20 цифр.',
        'control_type' => 'text',
    ),
    'bank_name'               => array(
        'value'        => '',
        'title'        => 'Наименование банка получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц.',
        'control_type' => 'text',
    ),
    'bank_account_number'     => array(
        'value'        => '',
        'title'        => 'Расчетный счет получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц — 20 цифр.',
        'control_type' => 'text',
    ),
    'bik'                     => array(
        'value'        => '',
        'title'        => 'БИК получателя наложенного платежа (магазина)',
        'description'  => 'Заполняется только для юридических лиц — 9 цифр.',
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
        'description'  => 'Дата выдачи документа получателя наложенного платежа (номер месяца в виде 2 цифр).',
        'control_type' => 'text',
    ),

    'document_issued_year' => array(
        'value'        => '',
        'title'        => 'Дата выдачи документа, год (магазина)',
        'description'  => 'Дата выдачи документа получателя наложенного платежа (год, 2 последние цифры).',
        'control_type' => 'text',
    ),

    'document_issued' => array(
        'value'        => '',
        'title'        => 'Кем выдан документ (магазина)',
        'description'  => 'Название организации, выдавшей документ.',
        'control_type' => 'text',
    ),

    'color' => array(
        'value'        => '1',
        'title'        => 'Печатать желтую полосу (форма ф. 113эн)',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
