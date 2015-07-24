<?php
return array(
    'integration_type' => array(
        'value'        => 'kassa',
        'title'        => 'Вариант подключения',
        'description'  => 'Выберете подходящий инструмент приема платежей
        <script type="text/javascript">
        $(\':input[name$="\\[integration_type\\]"]\').change(function(event){
            var $this = $(this);
            if($this.attr("checked")){
                var fast= event.originalEvent?false:true;
                var selected = $this.val();
                var complementary =(selected =="kassa")?"personal":"kassa";
                var $form = $this.parents("form:first");
                $form.find(".js-yandexmoney-"+this.value).each(function(){
                    if(fast){
                        $(this).parents("div.field").show();
                    }else {
                        $(this).parents("div.field").slideDown();
                    }
                });
                $form.find(".js-yandexmoney-"+complementary).each(function(){
                    if(fast){
                        $(this).parents("div.field").hide();
                    } else {
                        $(this).parents("div.field").slideUp();
                    }
                });

                var $callback = $form.find(":input[readonly=readonly]:first").parents("div.field-group");
                if(fast){
                    if(selected=="kassa"){
                        $callback.show();
                    } else {
                        $callback.hide();
                    }
                } else {
                    if(selected=="kassa"){
                        $callback.slideDown();
                    } else {
                        $callback.slideUp();
                    }
                }

            }

        }).change();
</script>
        ',
        'control_type' => 'radiogroup',
        'options'      => array(
            'kassa'    => 'Яндекс.Касса',
            'personal' => 'Кнопка для приема платежей',
        ),
    ),
    'account'          => array(
        'value'        => '',
        'title'        => 'Номер счета',
        'description'  => 'Номер Яндекс.Кошелька.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-personal',
    ),
    'ShopID'           => array(
        'value'        => '',
        'title'        => 'Идентификатор магазина',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-kassa',
    ),
    'scid'             => array(
        'value'        => '',
        'title'        => 'Номер витрины',
        'description'  => 'Выдается оператором платежной системы.',
        'control_type' => 'input',
        'class'        => 'js-yandexmoney-kassa',
    ),
    'shopPassword'     => array(
        'value'        => '',
        'title'        => 'Пароль',
        'description'  => '',
        'control_type' => waHtmlControl::PASSWORD,
        'class'        => 'js-yandexmoney-kassa',
    ),
    'payment_mode'     => array(
        'value'        => 'PC',
        'options'      => array(
            'PC'       => 'платеж со счета в Яндекс.Деньгах',
            'AC'       => 'платеж с банковской карты',
            'GP'       => 'платеж по коду через терминал',
            'MC'       => 'оплата со счета мобильного телефона',
            'customer' => 'на выбор покупателя',
            ''         => 'не задан (определяется Яндексом)',
        ),
        'title'        => 'Способ оплаты',
        'description'  => 'Настройки выбора способа оплаты.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type' => waHtmlControl::RADIOGROUP,
        'class'        => 'js-yandexmoney-kassa',
    ),
    'paymentType'      => array(
        'value'        => array('PC' => true,),
        'options'      => yandexmoneyPayment::settingsPaymentOptions(),
        'title'        => 'Варианты для способа оплаты «на выбор покупателя»',
        'description'  => 'Настройки доступных способов оплаты для выбора покупателям.<br/><strong>Доступны для подключения по протоколу версии 3.0</strong>',
        'control_type' => waHtmlControl::GROUPBOX,
        'class'        => 'js-yandexmoney-kassa',
    ),
    'TESTMODE'         => array(
        'value'        => '',
        'title'        => 'Тестовый режим',
        'description'  => 'Используется для оплаты в демо-рублях.',
        'control_type' => 'checkbox',
        'class'        => 'js-yandexmoney-kassa',
    ),
);
