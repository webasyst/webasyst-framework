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
 */
class invoicejurPayment extends waPayment implements waIPayment, waIPaymentCapture
{
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * (non-PHPdoc)
     * @see waIPayment::payment()
     * @param $order_data waOrder
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if (!empty($payment_form_data)) {
            $wa_transaction_data = $this->formalizeData($order_data);
            $wa_transaction_data['printform'] = $this->id;
            $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_PRINTFORM, $wa_transaction_data);
            wa()->getResponse()->redirect($url);
        }
        return wa()->getView()->fetch($this->path.'/templates/payment.html');
    }

    public function capture($transaction_raw_data)
    {
        /*TODO*/
    }

    public function getPrintForms(waOrder $order = null)
    {
        $forms = array();
        $forms[$this->id] = array(
            'name'        => 'Счет',
            'description' => 'Счет на оплату для юридического лица (РФ)',
        );
        return $forms;
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param waOrder $order
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id == $this->id) {
            $view = wa()->getView();
            $view->assign('settings', $this->getSettings(), true);

            $company = ($this->cust_company ? $this->cust_company : 'company');
            $inn = ($this->cust_inn ? $this->cust_inn : 'inn');
            $params = $order['params'];

            $company = array(
                'company' => ifset($params['payment_params_'.$company], $order->contact_id ? $order->getContactField($company) : ''),
                'inn'     => ifset($params['payment_params_'.$inn], $order->contact_id ? $order->getContactField($inn) : ''),
            );

            $view->assign('order', $order);
            $view->assign('company', $company);
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

}
