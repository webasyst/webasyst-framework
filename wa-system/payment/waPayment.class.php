<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage payment
 */
abstract class waPayment extends waSystemPlugin
{
    const TRANSACTION_CONFIRM = 'confirm';
    const TRANSACTION_AUTH = 'auth';
    const TRANSACTION_REFUND = 'refund';
    const TRANSACTION_CAPTURE = 'capture';
    const TRANSACTION_CANCEL = 'cancel';
    const TRANSACTION_PAYMENT = 'payment';

    /**
     *
     * Обработка оплаты
     * @var string
     */
    const CALLBACK_PAYMENT = 'payment';
    /**
     *
     * Обработка возврата
     * @var string
     */
    const CALLBACK_REFUND = 'refund';
    /**
     *
     * Валидация оплаты
     * @var string
     */
    const CALLBACK_CONFIRMATION = 'confirmation';
    /**
     *
     * Обработка подтверждения
     * @var string
     */
    const CALLBACK_CAPTURE = 'capture';
    /**
     *
     * Обработка отказа
     * @var string
     */
    const CALLBACK_DECLINE = 'decline';
    /**
     *
     * Обработка отмены
     * @var string
     */
    const CALLBACK_CANCEL = 'cancel';
    /**
     *
     * Обработка отказа
     * @var string
     */
    const CALLBACK_CHARGEBACK = 'chargeback';
    /**
     * Обработка информационного сообщения
     * @var string
     */
    const CALLBACK_NOTIFY = 'notify';


    /**
     * Operation types
     */

    /**
     *
     * Авторизация с подтверждением
     * @var string
     */
    const OPERATION_AUTH_CAPTURE = 'AUTH+CAPTURE';
    /**
     *
     * Только авторизация без подтверждения
     * @var string
     */
    const OPERATION_AUTH_ONLY = 'AUTH_ONLY';
    /**
     *
     * Подтверждение авторизованной транзакции
     * @var string
     */
    const OPERATION_CAPTURE = 'CAPTURE';
    /**
     *
     * Возврат подтвержденной транзакции
     * @var string
     */
    const OPERATION_REFUND = 'REFUND';

    /**
     *
     * Отмена неподтвержденной транзакции
     * @var string
     */
    const OPERATION_CANCEL = 'CANCEL';
    /**
     *
     * Проверка статуса оплаты по номеру заказа
     * @var string
     */
    const OPERATION_CHECK = 'CHECK';
    /**
     *
     * Прием платежных данных на текущем сервере
     * @var string
     */
    const OPERATION_INTERNAL_PAYMENT = 'INTERNAL_PAYMENT';

    /**
     *
     * Оформление заказа и оплата на стороне платежной системы
     * PayPal Express Checkout, Google Checkout
     * @todo try use HOSTED_ORDER
     * @var string
     */
    const OPERATION_HOSTED_PAYMENT_PRIOR_ORDER = 'HOSTED_PAYMENT_PRIOR_ORDER';

    /**
     *
     * Оплата заказа на стороне платежной системы
     * @todo try use HOSTED_PAYMENT
     * @var string
     */
    const OPERATION_HOSTED_PAYMENT_AFTER_ORDER = 'HOSTED_PAYMENT_AFTER_ORDER';

    const STATE_CAPTURED = 'CAPTURED';
    const STATE_AUTH = 'AUTH';
    const STATE_REFUNDED = 'REFUNDED';
    const STATE_CANCELED = 'CANCELED';
    const STATE_PARTIAL_REFUNDED = 'PARTIAL_REFUNDED';
    const STATE_DECLINED = 'DECLINED';
    const STATE_VERIFIED = 'VERIFIED';

    const TYPE_CARD = 'card';
    const TYPE_ONLINE = 'online';
    const TYPE_MANUAL = 'manual';
    const TYPE_OBSOLETE = 'obsolete';

    const PLUGIN_TYPE = 'payment';

    private static $module_pool = array();
    private static $init = false;
    protected $properties;

    /**
     *
     * Alias to plugin instance key
     * @var int
     */
    protected $merchant_id;

    /**
     *
     * @var waAppPayment
     */
    private $app_adapter;

    protected $app_id;

    private $guide = null;

    /**
     *
     * Get payment plugin instance
     * @param string $id plugin identity (e.g. cash, paypal, etc.)
     * @param int $merchant_id Merchant settings key
     * @param string|waAppPayment $app_adapter app_id or application adapter
     * @return waPayment
     */
    public static function factory($id, $merchant_id = null, $app_adapter = null)
    {
        $instance = parent::factory($id, $merchant_id, self::PLUGIN_TYPE);
        /**
         * @var waPayment $instance
         */
        if ($app_adapter && ($app_adapter instanceof waAppPayment)) {
            $instance->app_adapter = $app_adapter;
        } elseif ($app_adapter && is_string($app_adapter)) {
            $instance->app_id = $app_adapter;
        }
        $instance->init();
        return $instance;
    }

    /**
     * Enumerate available payment plugins
     * @param $options array
     * @param null $type will be ignored
     * @return array
     */
    final public static function enumerate($options = array(), $type = null)
    {
        return parent::enumerate($options, self::PLUGIN_TYPE);
    }

    /**
     *
     * Get plugin description
     * @param string $id
     * @param array $options
     * @param null $type will be ignored
     * @return string[string]
     * @return string['name']
     * @return string['description']
     * @return string['version']
     * @return string['build']
     * @return string['logo']
     * @return string[unt]['icon'][int]
     * @return string['img']
     */
    final public static function info($id, $options = array(), $type = null)
    {
        return parent::info($id, $options, self::PLUGIN_TYPE);
    }

    /**
     *
     * @return waPayment
     */
    protected function init()
    {
        if (!$this->app_id && $this->app_adapter) {
            $this->app_id = $this->app_adapter->getAppId();
        }
        if (!$this->app_id) {
            $this->app_id = wa()->getApp();
        }

        if ($this->key) {
            $this->setSettings($this->getAdapter()->getSettings($this->id, $this->key));
        }
        $this->merchant_id =& $this->key;
        return $this;
    }

    /**
     *
     * @return string|string[] ISO3 currency code
     */
    public function allowedCurrency()
    {
        return null;
    }

    /**
     * @param array $payment_form_data POST form data
     * @param waOrder $order_data formalized order data
     * @param bool $auto_submit
     * @return string HTML payment form
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        return '';
    }

    //Callback

    final public static function callback($module_id, $request = array())
    {
        self::log($module_id, $request);
        $module = null;
        try {
            $module = self::factory($module_id);
            return $module->callbackInit($request)->init()->callbackHandler($request);
        } catch (Exception $ex) {
            self::log($module ? $module_id : 'general', $ex->getMessage());
            if ($module) {
                return $module->callbackExceptionHandler($ex);
            } else {
                return array(
                    'error' => $ex->getMessage(),
                );
            }
        }
    }

    protected function callbackExceptionHandler(Exception $ex)
    {
        return array(
            'error' => $ex->getMessage(),
        );
    }

    /**
     *
     * Determine target application and merchant key
     * @param array $request
     * @return waPayment
     */
    protected function callbackInit($request)
    {
        self::log($this->id, array(
            'method'      => __METHOD__,
            'app_id'      => $this->app_id,
            'merchant_id' => $this->merchant_id,
        ));
        return $this;
    }

    /**
     *
     * @param $request array
     * @return void
     */
    protected function callbackHandler($request)
    {
        ;
    }

    /**
     *
     * Enter description here ...
     * @param string $method
     * @param array $transaction_data
     * @return array[string]mixed
     * @return array['order_id']int
     * @return array['customer_id']int
     * @return array['result']boolean
     * @return array['error']string
     */
    protected function execAppCallback($method, $transaction_data)
    {
        try {
            $result = $this->getAdapter()->execCallbackHandler($method, $transaction_data);
        } catch (Exception $e) {
            $result = array('error' => $e->getMessage());
        }
        self::log($this->id, array(
            'method'           => __METHOD__,
            'app_id'           => $this->app_id,
            'callback_method'  => $method,
            'transaction_data' => var_export($transaction_data, true),
            'result'           => var_export($result, true),
        ));

        if ($result) {
            $transaction_model = new waTransactionModel();
            $data = array();
            foreach (array('order_id', 'customer_id', 'result', 'error') as $k) {
                if (isset($result[$k])) {
                    $data[$k] = $result[$k];
                }
            }
            if ($data) {
                $transaction_model->updateById($transaction_data['id'], $data);
            }
        }
        return $result;
    }

    /**
     *
     * @return array
     */
    public function supportedOperations()
    {
        return array();
    }

    final public function getSupportedTransactions()
    {
        $transactions = array(
            self::TRANSACTION_CONFIRM,
            self::TRANSACTION_AUTH,
            self::TRANSACTION_REFUND,
            self::TRANSACTION_CAPTURE,
            self::TRANSACTION_CANCEL,
            self::TRANSACTION_PAYMENT,
        );
        return array_intersect($transactions, get_class_methods(get_class($this)));
    }

    public function getCustomerPaymentFields($customer_data = null, $payment_form_data = null, $order_data = null)
    {
        ;
    }

    public function validateCustomerPaymentData($payment_form_data)
    {
        ;
    }

    /**
     * @deprecated
     * @return array()
     */
    public function getSettingsList()
    {
        throw new waException('Use getSettingsHTML instead');
    }

    protected function checkPayments()
    {

    }

    /**
     * @deprecated use enumerate instead
     * @param $options array
     * @return array
     */
    final public static function listModules($options = array())
    {
        throw new waException('Use enumerate instead');
    }

    final public static function filterModules($methods, $properties = array(), $strict = false)
    {
        if (is_array($properties) && count($properties)) {
            foreach ($methods as $id => $module) {
                foreach ($properties as $field => $value) {
                    if (!is_array($value)) {
                        $value = array($value);
                    }

                    //$field = "_{$field}";
                    if (!isset($module[$field])) {
                        if ($strict) {
                            unset($methods[$id]);
                            continue 2;
                        }
                    } elseif (!in_array($module[$field], $value)) {
                        unset($methods[$id]);
                        continue 2;
                    }
                }
            }
        }
        return $methods;
    }

    function __call($method, $params)
    {
        //XXX back compatibilty
        if (preg_match('/^([a-z]+)Transaction$/', $method, $matches)) {
            $method = $matches[1];
        }
        $class = get_class($this);
        $transactions = array(
            self::TRANSACTION_CONFIRM,
            self::TRANSACTION_AUTH,
            self::TRANSACTION_REFUND,
            self::TRANSACTION_CAPTURE,
            self::TRANSACTION_CANCEL,
            self::TRANSACTION_PAYMENT,
        );
        if (in_array($method, $transactions)) {
            throw new waException(sprintf('Unsupported transaction %s at %s', $method, $class));
        } elseif (preg_match('/^callback(.+)Transaction$/', $method, $matches)) {
            throw new waException(sprintf('Unsupported transaction callback %s at %s', $matches[1], $class));
        } else {
            throw new waException(sprintf('Unsupported method %s at %s', $method, $class));
        }
    }

    protected static function log($module_id, $data)
    {
        $module_id = strtolower($module_id);
        $filename = 'payment/'.$module_id.'Payment.log';
        $rec = "data:\n";
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $rec .= "$key=$val&\n";
            }
        } else {
            $rec .= "$data\n";
        }
        waLog::log($rec, $filename);
    }

    /**
     * Saves formatted data and raw data to DB
     *
     * @param $wa_transaction_data
     * @param array $transaction_raw_data
     * @return array
     *
     * @tutorial $transaction_data array format:
     * type – one of: 'AUTH+CAPTURE', 'AUTH ONLY', 'CAPTURE', 'REFUND', 'CANCEL', 'CHARGEBACK'
     * amount
     * currency_id – 3-letter ISO-code
     * date_time
     * order_id
     * customer_id
     * transaction_OK — true/false flag
     * error_description – string
     * view_data – string
     * comment - string (optional)
     * native_id - original transaction id from payment gateway
     * parent_id - primary transaction id (optional)
     */
    final protected function saveTransaction($wa_transaction_data, $transaction_raw_data = null)
    {
        $transaction_model = new waTransactionModel();

        $wa_transaction_data['plugin'] = $this->id;
        $wa_transaction_data['app_id'] = $this->app_id;
        $wa_transaction_data['merchant_id'] = $this->key;

        $wa_transaction_data['id'] = $transaction_model->insert($wa_transaction_data);

        if (!empty($wa_transaction_data['parent_id']) && !empty($wa_transaction_data['parent_state'])) {
            $transaction_model->updateById($wa_transaction_data['parent_id'], array(
                'state'           => $wa_transaction_data['parent_state'],
                'update_datetime' => date('Y-m-d H:i:s')
            ));
        }
        if ($transaction_raw_data && is_array($transaction_raw_data)) {
            $transaction_data_model = new waTransactionDataModel();
            $transaction_data_model->addGroup($wa_transaction_data['id'], $transaction_raw_data);
        }
        return $wa_transaction_data;
    }

    /**
     * Get WA transaction by ID
     * @param int $wa_transaction_id
     * @return array $transaction
     */
    final public static function getTransaction($wa_transaction_id)
    {
        $transaction_model = new waTransactionModel();
        $transaction_data_model = new waTransactionDataModel();
        $data = $transaction_model->getById($wa_transaction_id);
        if ($data) {
            $data['raw_data'] = array();
            $raw_data = $transaction_data_model->getByField('transaction_id', $wa_transaction_id, true);
            foreach ($raw_data as $raw) {
                $data['raw_data'][$raw['field_id']] = $raw['value'];
            }
        }

        return $data;
    }

    /**
     * void method (optionally used in child methods)
     * @param array $transaction_data
     * @param array $transaction_raw_data
     * @return bool
     */
    protected function allowedTransactionCustomized($transaction_data, $transaction_raw_data)
    {
        return null;
    }

    /**
     * Returns available post-payment transaction types
     * @param int $wa_transaction_id
     * @return array
     */
    final public static function allowedTransaction($wa_transaction_id)
    {
        $transaction = self::getTransaction($wa_transaction_id);
        $transaction_raw_data = $transaction['raw_data'];
        unset($transaction['raw_data']);

        $instance = self::factory($transaction['plugin'], $transaction['merchant_id'], $transaction['app_id']);

        $result = $instance->allowedTransactionCustomized($transaction, $transaction_raw_data);
        if ($result) {
            return $result;
        }

        // @TODO: parse 'result' field

        if (empty($transaction['result']) || !empty($transaction['parent_id'])) {
            return null;
        }

        $operations = $instance->supportedOperations();

        switch ($transaction['state']) {
            case self::STATE_AUTH:
                $operations = array_intersect($operations, array(self::TRANSACTION_CAPTURE, self::TRANSACTION_CANCEL));
                break;
            case self::STATE_CAPTURED:
            case self::STATE_PARTIAL_REFUNDED:
                $operations = array_intersect($operations, array(self::TRANSACTION_REFUND));
                break;
            default:
                $operations = null;
                break;
        }
        return $operations;
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $transaction_raw_data
     * @return array $transaction_data
     */
    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = array(
            'plugin'      => $this->id,
            'merchant_id' => $this->merchant_id,
            'date_time'   => date('Y-m-d H:i:s'),
            'result'      => true
        );
        return $transaction_data;
    }

    /**
     * Adds order [and customer] info to wa_transaction DB table (for cases like Google Checkout)
     * @param $wa_transaction_id int
     * @param $result array
     * @return bool result
     * @deprecated
     */
    final public static function addTransactionData($wa_transaction_id, $result = null)
    {
        return true;
    }

    /**
     * Get transactions list
     * @param array $conditions - $key=>$value pairs
     * @return array $transactions - transactions list
     */
    final public static function getTransactionsByFields($conditions)
    {
        $transaction_model = new waTransactionModel();
        $transaction_data_model = new waTransactionDataModel();

        $transactions = $transaction_model->getByFields($conditions);
        $transactions_data = $transaction_data_model->getByField('transaction_id', array_keys($transactions), true);

        foreach ($transactions_data as $row) {
            $transactions[$row['transaction_id']]['raw_data'][$row['field_id']] = $row['value'];
        }
        return $transactions;
    }

    /**
     * @param bool $force_https
     * @return string callback relay url
     */
    public final function getRelayUrl($force_https = null)
    {
        $url = wa()->getRootUrl(true).'payments.php/'.$this->id.'/';
        //TODO detect - is allowed https
        if ($force_https) {
            $url = preg_replace('@^http://@', 'https://', $url);
        } elseif ($force_https === false) {
            $url = preg_replace('@^https://@', 'http://', $url);
        }
        return $url;
    }

    /**
     * Handle callback from payment gateway
     *
     * @example waPayment::execTransactionCallback(waPayment::TRANSACTION_CAPTURE,'AuthorizeNet',$request)
     * @param $module_id string Module identity
     * @param $request array
     * @return mixed
     * @deprecated
     */
    public static function execTransactionCallback($request, $module_id)
    {
        return self::callback($module_id, $request);
    }

    /**
     * @param waOrder $order
     * @return array[string]array
     * @return array[string]['name']string название печатной формы
     * @return array[string]['description']string описание печатной формы
     */
    public function getPrintForms(waOrder $order = null)
    {
        return array();
    }

    private function guide()
    {
        if ($this->guide === null) {
            $path = $this->path.'/lib/config/guide.php';
            if (file_exists($path)) {
                $this->guide = include($path);

                foreach ($this->guide as & $guide) {
                    if (isset($guide['title'])) {
                        $guide['title'] = $this->_w($guide['title']);
                    }
                    if (isset($guide['description'])) {
                        $guide['description'] = $this->_w($guide['description']);
                    }
                }
                unset($guide);
            }
            if (!is_array($this->guide)) {
                $this->guide = array();
            }
        }
        return $this->guide;
    }

    public function getGuide($params = array())
    {

        $controls = array();
        $default = array(
            'instance'            => & $this,
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'translate'           => array(&$this, '_w'),
            'readonly'            => true,
            'control_wrapper'     => '
<div class="field">
    <div class="name">%s</div>
    <div class="value">%s%s</div>
</div>
',
        );
        $options = ifempty($params['options'], array());
        $values = ifempty($params['value'], array());

        unset($params['options']);
        unset($params['value']);

        if (isset($params['namespace'])) {
            unset($params['namespace']);
        }
        $params = array_merge($default, $params);
        ifempty($params['class'], array());
        if (!is_array($params['class'])) {
            $params['class'] = array($params['class']);
        }
        $params['class'][] = 'long';

        $replace = array(
            '%RELAY_URL%'       => $this->getRelayUrl(),
            '%HTTP_RELAY_URL%'  => $this->getRelayUrl(false),
            '%HTTPS_RELAY_URL%' => $this->getRelayUrl(true),
            '%APP_ID%'          => $this->app_id,
        );

        foreach ($this->guide() as $name => $row) {
            if (is_array($row)) {
                if (isset($row['class']) && !is_array($row['class'])) {
                    $row['class'] = empty($row['class']) ? array() : array($row['class']);
                }
                if (isset($row['class'])) {
                    $params['class'] = array_merge(array_values($row['class']), array_values($params['class']));
                }
                $row = array_merge($row, $params);
                if (isset($options[$name])) {
                    $row['options'] = $options[$name];
                }
                if (isset($values[$name])) {
                    $row['value'] = $values[$name];
                }

                $row['value'] = str_replace(array_keys($replace), array_values($replace), $row['value']);
                $controls[$name] = waHtmlControl::getControl(ifempty($row['control_type'], waHtmlControl::INPUT), false, $row);
            } else {
                $controls[$name] = sprintf($params['control_wrapper'], '', '', $row);
            }
        }
        return implode("\n", $controls);
    }

    public function customFields(waOrder $order)
    {
        return array();
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param waOrder $order
     * @param array $params
     * @return string
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        return '';
    }

    /**
     *
     * @throws waException
     * @return waAppPayment
     */
    final protected function getAdapter()
    {
        if (!$this->app_adapter) {
            if (!$this->app_id) {
                throw new waException('Unknown current application');
            }

            #Init application
            waSystem::getInstance($this->app_id);
            waSystem::setActive($this->app_id);

            #check adapter class
            $app_class = $this->app_id.'Payment';
            if (!class_exists($app_class)) {
                throw new waException(sprintf('Application adapter %s not found for %s', $app_class, $this->app_id));
            }
            $instance = new $app_class();
            if (!($instance instanceof waAppPayment)) {
                throw new waException(sprintf('Application adapter %s not found for %s', $app_class, $this->app_id));
            }
            $this->app_adapter = $instance;
        }

        return $this->app_adapter;
    }

    /**
     * @param $iso3code
     * @return mixed
     * @throws waException
     */
    protected function getCountryISO2Code($iso3code)
    {
        $country_model = new waCountryModel();
        $country = $country_model->get($iso3code);
        if (!$country) {
            throw new waException($this->_w("Unknown country: ").$iso3code);
        }
        return strtoupper($country['iso2letter']);
    }

}

interface waIPayment
{

}

interface waIPaymentCancel
{
    /**
     *
     * @param array[string]mixed $transaction_raw_data['order_data']
     * @param array[string]mixed $transaction_raw_data['transaction_type']
     * @param array[string]mixed $transaction_raw_data['customer_data']
     * @param array[string]mixed $transaction_raw_data['transaction']
     * @param array[string]mixed $transaction_raw_data['refund_amount']
     */
    public function cancel($transaction_raw_data);
}

interface waIPaymentCapture
{
    /**
     *
     * @param array[string]mixed $transaction_raw_data['order_data']
     * @param array[string]mixed $transaction_raw_data['transaction_type']
     * @param array[string]mixed $transaction_raw_data['customer_data']
     * @param array[string]mixed $transaction_raw_data['transaction']
     * @param array[string]mixed $transaction_raw_data['refund_amount']
     */
    public function capture($transaction_raw_data);
}

interface waIPaymentRefund
{
    /**
     *
     * @param array[string]mixed $transaction_raw_data['order_data']
     * @param array[string]mixed $transaction_raw_data['transaction_type']
     * @param array[string]mixed $transaction_raw_data['customer_data']
     * @param array[string]mixed $transaction_raw_data['transaction']
     * @param array[string]mixed $transaction_raw_data['refund_amount']
     */
    public function refund($transaction_raw_data);
}
