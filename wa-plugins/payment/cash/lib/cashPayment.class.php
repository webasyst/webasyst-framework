<?php
class cashPayment extends waPayment implements waIPayment
{
    public function payment($payment_form_data, $order_data, $transaction_type)
    {
        return '';
    }

    public function allowedCurrency()
    {
        return true;
    }
}
