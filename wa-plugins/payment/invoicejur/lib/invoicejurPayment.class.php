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
    public function payment($payment_form_data, $order_data, $transaction_type)
    {
        $pay = !empty($payment_form_data);
        $company = $this->cust_company ? $this->cust_company : 'company';
        $inn = $this->cust_inn ? $this->cust_inn : 'inn';

        $params = $order_data->params;

        $contact = $order_data->getContact();
        $contact_changed = false;

        if (empty($payment_form_data['company'])) {
            $payment_form_data['company'] = ifempty(ifset($params['billing_'.$company]), $order_data->getContactField($company));
        } elseif ($contact && ($contact->get($company) != $payment_form_data['company'])) {
            $contact->set($company, $payment_form_data['company'], true);
            $contact_changed = true;
        }

        if (empty($payment_form_data['inn'])) {
            $payment_form_data['inn'] = ifempty(ifset($params['billing_'.$inn]), $order_data->getContactField($inn));
        } elseif ($contact && ($contact->get($inn) != $payment_form_data['inn'])) {
            $contact->set($inn, $payment_form_data['inn'], true);
            $contact_changed = true;
        }

        if ($contact_changed) {
            $contact->save();
        }

        $pay = ifempty($payment_form_data['company']) && $pay;
        $pay = ifempty($payment_form_data['inn']) && $pay;

        $view = wa()->getView();
        $view->assign('printform', $pay);
        $view->assign('data', $payment_form_data, true);
        if ((true || $pay) && ifempty($payment_form_data['printform'])) {
            $wa_transaction_data = $this->formalizeData($order_data);
            $wa_transaction_data['printform'] = $this->id;
            $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_PRINTFORM, $wa_transaction_data);
            $this->getAdapter()->setOrderParams($order_data->id, array(
                'billing_'.$company => $payment_form_data['company'],
                'billing_'.$inn => $payment_form_data['inn'],
            ));
            wa()->getResponse()->redirect($url);
        }
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function capture($transaction_raw_data)
    {
        //TODO
        }

    public function getPrintForms()
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
                'company' => ifset($params['billing_'.$company], $order->contact_id ? $order->getContactField($company) : ''),
                'inn'     => ifset($params['billing_'.$inn], $order->contact_id ? $order->getContactField($inn) : ''),
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
}
