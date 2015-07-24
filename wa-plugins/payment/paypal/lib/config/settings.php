<?php
/**
 * Массив, описывающий настройки плагина в формате
 * array(
 * '%setting_key%'=>array(
 * 'value'=>'',//значение по умолчанию
 * 'title'=>'Setting title',
 * 'description'=>'Setting description',
 * 'control_type'=>waHtmlControl::INPUT, //тип элемента ввода значений настроек
 * ),
 * )
 * Подробнее TODO ссылка на описание настроек плагинов.
 */

return array(
    'email'    => array(
        'value'        => '',
        # конструкция вида /*_wp*/('Merchant email') используется для сборщика локализации — он такие строки добавляет
        # в файл локализации, но фактически функция перевода строки не вызывается при подключении этого файла
        'title'        => /*_wp*/('Merchant email'),
        'description'  => /*_wp*/('Your PayPal account email address'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'currency' => array(
        'value'            => array('USD' => 1),
        'title'            => /*_wp*/('Transaction currency'),
        'description'      => /*_wp*/('Must be acceptable at merchant settings'),
        'control_type'     => waHtmlControl::GROUPBOX,
        'options_callback' => array('paypalPayment', 'settingCurrencySelect'),
    ),
    'sandbox'  => array(
        'value'        => '',
        'title'        => /*_wp*/('Sandbox'),
        'description'  => /*_wp*/('Enable for test mode'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
