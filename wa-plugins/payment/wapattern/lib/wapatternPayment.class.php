<?php

/**
 *
 */
class wapatternPayment extends waPayment implements waIPayment
{
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $form = array(
            'hidden_field' => 'value',
        );
        $view = wa()->getView();

        $view->assign('data', $form);
        $view->assign('order', $order);
        $view->assign('auto_submit', $auto_submit);
        $view->assign('settings', $this->getSettings());
        $view->assign('form_url', $this->getEndpointUrl());
        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function getEndpointUrl()
    {
        return 'http://example.com';
    }
}
