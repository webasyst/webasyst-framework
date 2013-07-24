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
 */
class invoicephysPayment extends waPayment implements waIPayment, waIPaymentCapture
{
    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $view = wa()->getView();
        if (ifempty($payment_form_data['printform'])) {
            $wa_transaction_data = $this->formalizeData($order_data);
            $wa_transaction_data['printform'] = $this->id;
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_PRINTFORM, $wa_transaction_data));
        }
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function capture($transaction_raw_data)
    {
        //TODO store transaction data
        ;
    }

    public function getPrintForms(waOrder $order = null)
    {
        $forms = array();
        $forms[$this->id] = array(
            'name'        => 'Квитанция',
            'description' => 'Квитанция на оплату в банке (РФ)',
        );
        return $forms;
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param array $order_data
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id == $this->id) {
            $view = wa()->getView();
            $view->assign('settings', $this->getSettings(), true);
            $view->assign('order', $order);
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
}
