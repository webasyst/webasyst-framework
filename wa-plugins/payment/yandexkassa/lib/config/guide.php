<?php

return array(
    array(
        'value'       => '%HTTPS_RELAY_URL%',
        'title'       => 'HTTP-уведомления',
        'description' => 'URL для уведомлений.<br>
<strong>Скопируйте и сохраните этот адрес в личном кабинете на сайте «Яндекс.Кассы».</strong>',
    ),
    array(
        'value'        => '',
        'title'        => 'Входящие уведомления',
        'description'  => '<p>Отметьте эти события в личном кабинете на сайте «Яндекс.Кассы», чтобы автоматически получать актуальную информацию о состоянии платежей:<p>
<ul>
    <li><code>payment.succeeded</code> — платёж перешёл в статус <i>succeeded</i>
    <li><code>payment.waiting_for_capture</code> — платёж перешёл в статус <i>waiting_for_capture</i>
    <li><code>payment.canceled</code> — платёж перешёл в статус <i>canceled</i>
    <li><code>refund.succeeded</code> — возврат перешёл в статус <i>succeeded</i>
</ul>',
        'control_type' => waHtmlControl::HELP,
    ),
);
