<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2013 Webasyst LLC
 * @package wa-system
 * @subpackage shipping
 */
abstract class waShipping extends waSystemPlugin
{

    const PLUGIN_TYPE = 'shipping';

    private $address = array();

    private $items = array();

    private $params = array();

    /**
     *
     * @var waAppShipping
     */
    private $app_adapter;

    protected $app_id;

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
    }

    /**
     *
     * Sets destination address
     * @param array $address
     * @return waShipping
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     *
     * @param array $item
     * @param array[string]mixed $item package item
     * @param array[string]string $item['id'] ID of package item
     * @param array[string]string $item['name'] name of package item
     * @param array[string]mixed $item['weight'] weight of package item
     * @param array[string]mixed $item['price'] price of package item
     * @param array[string]mixed $item['quantity'] quantity of packate item
     * @return waShipping
     */
    public function addItem($item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     *
     * @param array $items
     * @param array[][string]mixed $items package item
     * @param array[][string]string $items['id'] ID of package item
     * @param array[][string]string $items['name'] name of package item
     * @param array[][string]mixed $items['weight'] weight of package item
     * @param array[][string]mixed $items['price'] price of package item
     * @param array[][string]mixed $items['quantity'] quantity of packate item
     * @return waShipping
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    protected function getPackageProperty($property)
    {
        $property_value = null;
        switch ($property) {
            case 'price':
                /*TODO use currency code and etc*/
            case 'weight':
                if (isset($this->params['total_'.$property])) {
                    $property_value = $this->params['total_'.$property];
                } else {
                    foreach ($this->items as $item) {
                        $property_value += $item[$property] * $item['quantity'];
                    }
                }
                break;
        }
        return $property_value;
    }

    protected function getTotalWeight()
    {
        return $this->getPackageProperty('weight');
    }

    protected function getTotalPrice()
    {
        return $this->getPackageProperty('price');
    }

    protected function getAddress($field = null)
    {
        return ($field === null) ? $this->address : (isset($this->address[$field]) ? $this->address[$field] : null);
    }

    /**
     *
     * Returns available shipping options info, rates, and estimated delivery times
     * @param array $items
     * @param array[][string]mixed $items package item
     * @param array[][string]string $items['id'] ID of package item
     * @param array[][string]string $items['name'] name of package item
     * @param array[][string]mixed $items['weight'] weight of package item
     * @param array[][string]mixed $items['price'] price of package item
     * @param array[][string]mixed $items['quantity'] quantity of packate item
     *
     * @param array[string]string $address shipping adress
     *
     *
     * @param array[mixed]mixed $params
     * @param array[string]float $params['total_price'] package total price
     * @param array[string]float $params['total_weight'] package total weight
     *
     * @return string
     * @return array[string]array
     * @return array[string]['name']string
     * @return array[string]['desription']string
     * @return array[string]['est_delivery']string
     * @return array[string]['currency']string
     * @return array[string]['rate']mixed float or array for min-max
     */
    public function getRates($items = array(), $address = array(), $params = array())
    {
        if (!empty($address)) {
            $this->address = $address;
        }
        $this->params = array_merge($this->params, $params);
        try {
            $match = true;
            foreach ($this->allowedAddress() as $address) {
                $match = true;
                foreach ($address as $field => $value) {
                    if (!empty($value) && !empty($this->address[$field])) {
                        if (is_array($value)) {
                            if (!in_array($this->address[$field], $value)) {
                                $match = false;
                                break;
                            }
                        } elseif ($value != $this->address[$field]) {
                            $match = false;
                            break;
                        }
                    }
                }
                if ($match) {
                    break;
                }
            }
            $rates = $match ? $this->addItems($items)->calculate() : false;
        } catch (waException $ex) {
            $rates = $ex->getMessage();
        }
        return $rates;
    }

    /**
     * @return array[string]array
     * @return array[string]['name']string название печатной формы
     * @return array[string]['desription']string описание печатной формы
     */
    public function getPrintForms()
    {
        return array();
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param waOrder $order
     * @param array $params
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {

    }

    /**
     *
     * @return waShipping
     */
    public function flush()
    {
        $this->items = array();
        $this->params = array();
        $this->address = array();
        return $this;
    }

    /**
     *
     * @return string ISO3 currency code or array of ISO3 codes
     */
    abstract public function allowedCurrency();

    /**
     *
     * @return string Weight units or array of weight units
     */
    abstract public function allowedWeightUnit();

    /**
     *
     * List of allowed address paterns
     * @return array
     */
    public function allowedAddress()
    {
        return array();
    }

    public function requestedAddressFields()
    {
        return array();
    }

    public function customFields(waOrder $order)
    {
        return array();
    }

    /**
     *
     */
    abstract protected function calculate();

    /**
     *
     * Returns shipment current tracking info
     * @return string Tracking information (HTML)
     */
    public function tracking($tracking_id = null)
    {
        return null;
    }

    /**
     *
     * External shipping service callback handler
     * @param array $params
     * @param string $module_id
     */
    public static function execCallback($params, $module_id)
    {
        ;
    }

    public static function settingCurrencySelect()
    {
        $options = array();
        $options[''] = '-';
        $app_config = wa()->getConfig();
        if (method_exists($app_config, 'getCurrencies')) {
            $currencies = $app_config->getCurrencies();
            foreach ($currencies as $code => $currency) {
                $options[$code] = array(
                    'value'       => $code,
                    'title'       => $currency['title'] . ' (' . $code . ')',
                    'description' => $currency['code'],
                );
            }
        } else {
            $currencies = waCurrency::getAll();
            foreach ($currencies as $code => $currency_name) {
                $options[$code] = array(
                    'value'       => $code,
                    'title'       => $currency_name . ' (' . $code . ')',
                    'description' => $code,
                );
            }
        }
        return $options;
    }

    public static function settingCountrySelect()
    {
        $country_model = new waCountryModel();
        return $country_model->select('iso3letter AS value, name AS title')->fetchAll('value');
    }

    public static function settingCountryControl($name, $params = array())
    {

    }

    /**
     *
     * Get shipping plugin
     * @param string $id
     * @param waiPluginSettings $adapter
     * @return waShipping
     */
    public static function factory($id, $key = null, $app_adapter = null)
    {
        $instance = parent::factory($id, $key, self::PLUGIN_TYPE);
        if ($app_adapter && ($app_adapter instanceof waAppShipping)) {
            $instance->app_adapter = $app_adapter;
        } elseif ($app_adapter && is_string($app_adapter)) {
            $instance->app_id = $app_adapter;
        }
        $instance->init();
        return $instance;
    }

    /**
     * The list of available shipping options
     * @param $options array
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
     * @return array[string]string
     * @return array['name']string
     * @return array['description']string
     * @return array['version']string
     * @return array['build']string
     * @return array['logo']string
     * @return array['icon'][int]string
     * @return array['img']string
     */
    final public static function info($id, $options = array(), $type = null)
    {
        return parent::info($id, $options, self::PLUGIN_TYPE);
    }

    /**
     *
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
            $app_class = $this->app_id.'Shipping';
            if (!class_exists($app_class)) {
                throw new waException(sprintf('Application adapter %s not found for %s', $app_class, $this->app_id));
            }
            $instance = new $app_class();
            if (!($instance instanceof waAppShipping)) {
                throw new waException(sprintf('Application adapter %s not found for %s', $app_class, $this->app_id));
            }
            $this->app_adapter = $instance;
        }

        return $this->app_adapter;
    }

}
