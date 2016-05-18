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
abstract class waAppPayment implements waiPluginApp
{
    const URL_SUCCESS = 'success';
    const URL_DECLINE = 'decline';
    const URL_FAIL = 'fail';
    const URL_CHECKOUT = 'checkout';
    const URL_PRINTFORM = 'printform';
    /**
     *
     *
     * Merchant identity alias
     * @var int
     */
    protected $merchant_id;
    protected $app_id;

    final public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        if (!$this->app_id) {
            $this->app_id = wa()->getApp();
        }
    }

    /**
     *
     * @return string
     */
    final public function getAppId()
    {
        return $this->app_id;
    }

    /**
     *
     * Callback method handler for plugin
     * @param string $method one of Confirmation, Payment
     * @throws waException
     * @return mixed
     */
    final public function execCallbackHandler($method)
    {
        $args = func_get_args();
        array_shift($args);
        $method_name = "callback".ucfirst($method)."Handler";
        if (!method_exists($this, $method_name)) {
            throw new waException('Unsupported callback handler method '.$method);
        }
        return call_user_func_array(array($this, $method_name), $args);
    }

    /**
     *
     * @param string|array $order
     * @param waPayment
     * @return waOrder
     */
    public static function getOrderData($order, $payment_plugin = null)
    {
        return waOrder::factory($order);
    }

    /**
     *
     * Set current order params
     * @param string $order_id
     * @param array $params key=>value array
     */
    public function setOrderParams($order_id, $params)
    {

    }

    /**
     *
     * @return string
     */
    final public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     *
     * Get application page for transaction result
     * @param string $type
     * @param array $transaction_data formalized transaction data
     * @return string URL of false
     */
    public function getBackUrl($type = self::URL_SUCCESS, $transaction_data = array())
    {
        return false;
    }

    /**
     *
     * Get private data storage path
     * @param int $order_id
     * @param string $path
     * @return string path or false
     */
    public function getDataPath($order_id, $path = null)
    {
        return false;
    }

    /**
     * Execute specified transaction by payment module on $request data
     *
     * @example waPayment::execTransaction(waPayment::TRANSACTION_CAPTURE,'AuthorizeNet',$adapter,$params)
     * @param $transaction
     * @param $module_id
     * @param $merchant_id
     * @param $params
     * @return mixed
     */
    public function execTransaction($transaction, $module_id, $merchant_id, $params)
    {
        $plugin = waPayment::factory($module_id, $merchant_id, $this);
        return call_user_func_array(array($plugin, $transaction), $params);
    }

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackPaymentHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackCancelHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackDeclineHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackRefundHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackCaptureHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackChargebackHandler($wa_transaction_data);

    /**
     *
     *
     * @param array $wa_transaction_data
     * @return array|null
     */
    abstract public function callbackConfirmationHandler($wa_transaction_data);
}
