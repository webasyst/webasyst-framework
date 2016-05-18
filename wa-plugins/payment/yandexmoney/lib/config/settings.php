<?php
return array(
    'integration_type' => array(
        'value'        => 'kassa',
        'title'        => 'Вариант подключения',
        'description'  => 'Выберите подходящий инструмент приема платежей',
        'control_type' => 'radiogroup',
        'options'      => array(
            'kassa'    => 'Яндекс.Касса',
            'personal' => 'Кнопка для приема платежей',
            'mpos'     => 'Мобильный терминал (mPOS)',
        ),
    ),
    'account'          => array(
        'value'        => '',
        'title'        => 'Номер счета',
        'description'  => 'Номер Яндекс.Кошелька.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-personal',
    ),
    'ShopID'           => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'scid'             => array(
        'value'        => '',
        'title'        => 'Номер витрины',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'shopPassword'     => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
        'data'         => array('integration-type' => 'kassa',),
    ),
    'payment_mode'     => array(
        'value'            => 'PC',
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentModeOptions'),
        'title'            => 'Способ оплаты',
        'description'      => 'Настройки выбора способа оплаты.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::RADIOGROUP,
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'paymentType'      => array(
        'value'            => array('PC' => true,),
        'title'            => 'Варианты для способа оплаты «на выбор покупателя»',
        'description'      => 'Настройки доступных способов оплаты для выбора покупателям.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('yandexmoneyPayment', 'settingsPaymentOptions'),
        'class'            => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
    ),
    'TESTMODE'         => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'control_type' => 'checkbox',
        'class'        => 'js-yandexmoney-integration-type js-yandexmoney-kassa',
        'description'  => 'Используется для оплаты в демо-рублях.'.<<<HTML
<script type="text/javascript">
(function () {
    "use strict";
    var yandexmarket = {
        fields: null,
        guide: null,
        form: null,
        payment_mode: null,

        init: function () {
            this.fields = this.form.find(".js-yandexmoney-integration-type");
            this.guide = this.form.find(":input[readonly=readonly]:first").parents("div.field-group");
        },
        changeIntegrationType: function (event, element) {
            if (element.attr("checked")) {
                this.init(element);
                var fast = event.originalEvent ? false : true;
                var selected = element.val();
                var queue = {
                    'show': [],
                    'hide': []
                };
                switch (selected) {
                    case 'mpos':
                        this.payment_mode.filter('[value="MP"]').attr('checked', 'checked');

                        queue.show.push(this.guide);
                        this.fields.filter('.js-yandexmoney-kassa:not([name*="\[paymentType\]"])').each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });

                        this.payment_mode.filter(':not([value="MP"])').each(function () {
                            queue.hide.push($(this).parents("div.value:first"));
                        });

                        this.fields.filter('.js-yandexmoney-personal, .js-yandexmoney-kassa[name*="\[paymentType\]"]').each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });


                        break;
                    case 'kassa':
                        queue.show.push(this.guide);
                        this.payment_mode.filter(':not([value="MP"])').each(function () {
                            queue.show.push($(this).parents("div.value:first"));
                        });

                        this.fields.filter(".js-yandexmoney-kassa").each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });

                        //hide
                        this.fields.filter(".js-yandexmoney-personal").each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });

                        break;
                    case 'personal':
                        //show
                        this.fields.filter(".js-yandexmoney-personal").each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });
                        //hide
                        queue.hide.push(this.guide);
                        this.fields.filter(".js-yandexmoney-kassa").each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });
                        break;
                }

                this.show(queue.show, fast);
                this.hide(queue.hide, fast);
                this.payment_mode.trigger('change');
            }
        },
        changePaymentMode: function (event, element) {
            if (element.attr("checked")) {
                var fast = event.originalEvent ? false : true;
                var field = this.form.find(':input[name*="\[paymentType\]"]:first').parents('div.field:first');
                if (element.val() == 'customer') {
                    this.show([field], fast);
                } else {
                    this.hide([field], fast);
                }
            }
        },
        show: function (elements, fast) {
            for (var i = 0; i < elements.length; i++) {
                if (elements[i]) {
                    if (fast) {
                        elements[i].show();
                    } else {
                        elements[i].slideDown();
                    }
                }
            }

        },
        hide: function (elements, show, fast) {
            for (var i = 0; i < elements.length; i++) {
                if (elements[i]) {
                    if (fast) {
                        elements[i].hide();
                    } else {
                        elements[i].slideUp();
                    }
                }
            }
        },
        bind: function () {
            this.form = $(':input[name$="\[integration_type\]"]').parents('form:first');
            this.payment_mode = this.form.find(':input[name$="\[payment_mode\]"]');

            var self = this;
            $(':input[name$="\[integration_type\]"]').unbind('change').bind('change', function (event) {
                self.changeIntegrationType(event, $(this));
            }).trigger('change');


            this.payment_mode.unbind('change').bind('change', function (event) {
                self.changePaymentMode(event, $(this));
            }).trigger('change');
        }
    };

    yandexmarket.bind();

})();
</script>
HTML
        ,
    ),
);
