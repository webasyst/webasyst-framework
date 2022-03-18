<?php

/**
 * @property-read string $bankname
 * @property-read string $bank_account_number
 * @property-read string $bank_kor_number
 * @property-read string $bik
 * @property-read string $companyname
 * @property-read string $description
 * @property-read string $inn
 * @property-read string $kpp
 * @property-read string $second_name
 * @property-read string $bank_name
 * @property-read string $emailprintform
 */
class invoicephysPayment extends waPayment implements waIPayment, waIPaymentCapture
{
    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if (!empty($payment_form_data['printform'])) {
            $wa_transaction_data = $this->formalizeData($order_data);
            $wa_transaction_data['printform'] = $this->id;
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_PRINTFORM, $wa_transaction_data));
        }
        $view = wa()->getView();
        $view->assign('order_id', $order_data['id']);
        $view->assign('merchant_id', $order_data['merchant_id']);
        $view->assign('app_payment', ifset($payment_form_data['app_payment']));
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function capture($transaction_raw_data)
    {
        //TODO store transaction data
    }

    public function getPrintForms(waOrder $order = null)
    {
        $forms = array();
        $forms[$this->id] = array(
            'name'           => 'Квитанция',
            'description'    => 'Квитанция на оплату в банке (РФ)',
            'emailprintform' => $this->emailprintform,
        );
        return $forms;
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param waOrder $order
     * @param array $params
     * @throws waException
     * @return string HTML
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id == $this->id) {
            $view = wa()->getView();

            $view->assign('order', $order);
            $view->assign('settings', $this->getSettings(), true);

            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException('print form not found');
        }
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['order_id'] = $transaction_raw_data['order_id'];
        $transaction_data['amount'] = $transaction_raw_data['amount'];
        $transaction_data['currency_id'] = $transaction_raw_data['currency_id'];
        $transaction_data['native_id'] = '';
        return $transaction_data;
    }

    public function printFormAction($params)
    {
        $order = new waOrder($params);
        return $this->displayPrintForm($params['plugin'], $order);
    }
}
