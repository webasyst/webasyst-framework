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
    <li><code>payment.succeeded</code> — <i>платеж перешел в статус succeeded</i>
    <li><code>payment.waiting_for_capture</code> — <i>платеж перешел в статус waiting_for_capture</i>
    <li><code>payment.canceled</code> — <i>платеж перешел в статус canceled</i>
    <li><code>refund.succeeded</code> — <i>возврат перешел в статус succeeded</i>
</ul>',
        'control_type' => waHtmlControl::HELP,
    ),
);
