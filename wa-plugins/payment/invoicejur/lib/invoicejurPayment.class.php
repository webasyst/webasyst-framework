<?php

/**
 * @property-read string $bank_account_number
 * @property-read string $bank_kor_number
 * @property-read string $bank_name
 * @property-read string $bik
 * @property-read string $company_address
 * @property-read string $company_name
 * @property-read string $company_phone
 * @property-read string $cust_company
 * @property-read string $cust_inn
 * @property-read string $inn
 * @property-read string $kpp
 * @property-read bool $emailprintform
 */
class invoicejurPayment extends waPayment implements waIPayment, waIPaymentCapture
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
            $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_PRINTFORM, $wa_transaction_data);
            wa()->getResponse()->redirect($url);
        }
        $payment_id = isset($payment_form_data['payment_id']) ? $payment_form_data['payment_id'] : null;

        $view = wa()->getView();
        $view->assign('order_id', $order_data['id']);
        $view->assign('merchant_id', $order_data['merchant_id']);
        $view->assign('app_payment', ifset($payment_form_data['app_payment']));
        $view->assign('payment_params', ifset($payment_form_data['payment_'.$payment_id]));
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function capture($transaction_raw_data)
    {
        /*TODO*/
    }

    public function getPrintForms(waOrder $order = null)
    {
        $forms = array();
        $forms[$this->id] = array(
            'name'           => 'Счет',
            'description'    => 'Счет на оплату для юридического лица (РФ)',
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
     * @return string
     * @throws waException
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id == $this->id) {
            $company = ($this->cust_company ? $this->cust_company : 'company');
            $inn = ($this->cust_inn ? $this->cust_inn : 'inn');
            $params = $order['params'];

            $company = array(
                'company' => ifset($params['payment_params_'.$company], $order->contact_id ? $order->getContactField($company) : ''),
                'inn'     => ifset($params['payment_params_'.$inn], $order->contact_id ? $order->getContactField($inn) : ''),
            );
            $view = wa()->getView();
            $view->assign('order', $order);
            $view->assign('settings', $this->getSettings(), true);
            $view->assign('company', $company, true);
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

    public function customFields(waOrder $order)
    {
        $company_field = ($this->cust_company ? $this->cust_company : 'company');
        $inn_field = ($this->cust_inn ? $this->cust_inn : 'inn');

        $result = array(
            'inn'     => array(
                'title'        => 'ИНН',
                'control_type' => waHtmlControl::INPUT,
            ),
            'company' => array(
                'title'        => _ws('Company'),
                'control_type' => waHtmlControl::INPUT,
            )
        );
        if ($company = $order->getContactField($company_field)) {
            $result['company']['value'] = $company;
        }
        if ($inn = $order->getContactField($inn_field)) {
            $result['inn']['value'] = $inn;
        }
        return $result;
    }

    public function printFormAction($params)
    {
        foreach ($params as $k=>$v) {
            if (strpos($k, 'payment_params_') === 0) {
                $params['params'][$k] = $v;
            }
        }
        if (!empty($params['contact_id'])) {
            $c = new waContact($params['contact_id']);
            $a = $c->get('address.billing');
            if (!empty($a[0])) {
                $params['billing_address'] = $a[0]['data'];
            }
            $a = $c->get('address.shipping');
            if (!empty($a[0])) {
                $params['shipping_address'] = $a[0]['data'];
            }
        }
        $order = new waOrder($params);
        return $this->displayPrintForm($params['plugin'], $order);
    }
}
