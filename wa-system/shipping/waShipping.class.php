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
    const TYPE_TODOOR = 'todoor';
    const TYPE_PICKUP = 'pickup';
    const TYPE_POST = 'post';

    const PAYMENT_TYPE_CASH = 'cash';
    const PAYMENT_TYPE_PREPAID = 'prepaid';
    const PAYMENT_TYPE_CARD = 'card';

    const PLUGIN_TYPE = 'shipping';

    const STATE_DRAFT = 'draft';
    const STATE_READY = 'ready';
    const STATE_CANCELED = 'cancel';
    const STATE_SHIPPING = 'shipping';

    private $address = array();

    private $items = array();

    private $params = array();

    /** @var waAppShipping */
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

        parent::init();

        if ($this->key) {
            $this->setSettings($this->getAdapter()->getSettings($this->id, $this->key));
        }
        return $this;
    }

    protected function initControls()
    {
        $this->registerControl('DeliveryIntervalControl');
        parent::initControls();
    }

    public static function getClasses()
    {
        $plugins = self::enumerate();
        $result = array();
        foreach ($plugins as $id => $plugin) {
            $result += self::getPluginClasses(self::PLUGIN_TYPE, $id);
        }

        return $result;
    }

    /**
     * @param $params
     * @return string
     * @throws waException
     * @todo доработать тексты предупреждений
     */
    protected function getNoticeHtml($params)
    {
        $html = '';
        $info = self::info($this->id);
        if (!empty($info['sync'])) {
            $value = $this->getAdapter()->getAppProperties('sync');//флаг или time последнего запуска
            $interval = max(1, intval($info['sync']));
            $interval_str = _ws('%d hour', '%d hours', $interval);
            $interval *= 3600;


            $hint = array(
                'title'       => _ws('Scheduled tasks'),
                'value'       => '',
                'description' => '',
            );

            if (empty($value)) {
                $hint['value'] .= _ws('The app does not support scheduled updating of plugin data. This may slow down the plugin operation.');
            } else {
                $sync_time = $this->getGeneralSettings('sync_time');
                if ($sync_time) {
                    $sync_success_time = (int)$this->getGeneralSettings('sync_success_time');
                    $sync_failure_time = (int)$this->getGeneralSettings('sync_failure_time');
                    if ($sync_failure_time > $sync_success_time) {
                        $hint['value'] .= sprintf(
                            _ws('The data update of %s failed.'),
                            waDateTime::format('humandatetime', $sync_failure_time)
                        );

                    } elseif ((time() - $sync_success_time) > (2 * $interval)) {
                        $hint['value'] .= sprintf(
                            _ws("Recommended data updating interval (%s) was disrupted."),
                            $interval_str
                        );

                        $hint['value'] .= sprintf(
                            _ws('Most recent data update was attempted on %s.'),
                            waDateTime::format('humandatetime', (int)$value)
                        );
                    }
                    if ($sync_success_time) {
                        $hint['value'] .= sprintf(
                            _ws('Most recent data update occurred on %s.'),
                            waDateTime::format('humandatetime', $sync_success_time)
                        );
                    }

                } else {
                    if (($value < 2) //
                        || ((time() - $value) > (2 * $interval))
                    ) {
                        $hint['value'] .= _ws('Data update was never run for this plugin. Check the server scheduler settings.');
                    } else {
                        $hint['value'] .= _ws('Data update was never run for this plugin.');
                        $hint['value'] .= "\n";
                        $hint['value'] .= sprintf(
                            _ws('Most recent data update was attempted on %s.'),
                            waDateTime::format('humandatetime', (int)$value)
                        );
                    }
                }
            }

            $hint = array_merge($this->getSettingsDefaultParams($params), $hint);
            $html .= waHtmlControl::getControl(waHtmlControl::HELP, 'sync', $hint);
        }
        return $html;
    }

    public function getSettingsHTML($params = array())
    {
        $html = '';
        $html .= parent::getSettingsHTML($params);
        $html .= $this->getNoticeHtml($params);
        return $html;
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
     * @param array [string]mixed $item package item
     * @param array [string]string $item['id'] ID of package item
     * @param array [string]string $item['name'] name of package item
     * @param array [string]mixed $item['weight'] weight of package item
     * @param array [string]mixed $item['price'] price of package item
     * @param array [string]mixed $item['discount'] price of package item
     * @param array [string]mixed $item['quantity'] quantity of package item
     * @param array [string]mixed $item['length'] length of package item
     * @param array [string]mixed $item['width'] width of package item
     * @param array [string]mixed $item['height'] height of package item
     * @return waShipping
     */
    public function addItem($item)
    {
        $item += array(
            'id'       => null,
            'name'     => '',
            'weight'   => null,
            'price'    => 0.0,
            'discount' => 0.0,
            'quantity' => 1,
            'length'   => null,
            'width'    => null,
            'height'   => null,
        );
        $this->items[] = $item;
        return $this;
    }

    /**
     *
     * @param array $items
     * @param array [][string]mixed $items package item
     * @param array [][string]string $items[]['id'] ID of package item
     * @param array [][string]string $items[]['sku'] SKU of package item
     * @param array [][string]string $items[]['name'] name of package item
     * @param array [][string]mixed $items[]['weight'] weight of package item
     * @param array [][string]mixed $items[]['price'] price of package item
     * @param array [][string]mixed $items[]['quantity'] quantity of package item
     * @param array [][string]mixed $items[]['length'] length of package item
     * @param array [][string]mixed $items[]['width'] width of package item
     * @param array [][string]mixed $items[]['height'] height of package item
     * @return waShipping
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    public function setParams($params = array())
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @return array $items
     */
    protected function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    protected function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * Get package param setted via setParams() or getRates
     * @param string $property
     * @return mixed|null
     */
    protected function getPackageProperty($property)
    {
        $property_value = null;
        switch ($property) {
            case 'price':
                if (isset($this->params['total_'.$property])) {
                    $property_value = $this->params['total_'.$property];
                    $property_value -= $this->getPackageProperty('total_discount');
                } else {
                    foreach ($this->items as $item) {
                        $property_value += ($item['price'] - $item['discount']) * $item['quantity'];
                    }
                }
                break;
            case 'raw_price':
                if (isset($this->params['total_'.$property])) {
                    $property_value = $this->params['total_'.$property];
                } else {
                    foreach ($this->items as $item) {
                        $property_value += $item['price'] * $item['quantity'];
                    }
                }
                break;
            case 'items_discount':
                foreach ($this->items as $item) {
                    $property_value += empty($item['total_discount']) ? $item['discount'] * $item['quantity'] : $item['total_discount'];
                }
                break;
            case 'weight':
                if (isset($this->params['total_'.$property])) {
                    $property_value = $this->params['total_'.$property];
                } else {
                    foreach ($this->items as $item) {
                        $property_value += $item[$property] * $item['quantity'];
                    }
                }
                break;
            case 'quantity':
                foreach ($this->items as $item) {
                    $property_value += $item[$property];
                }
                break;
            case 'departure_datetime':
                if (isset($this->params[$property])) {
                    $property_value = $this->params[$property];
                } else {
                    // SQL DATETIME format
                    $property_value = date('Y-m-d H:i:s', time() + 900); // 15 minutes later
                }
                break;
            case 'service':
            case 'services_by_type':
            default:
                if (isset($this->params[$property])) {
                    $property_value = $this->params[$property];
                }
        }
        return $property_value;
    }

    protected function getTotalSize()
    {
        $size = array(
            'height' => $this->getPackageProperty('total_height'),
            'width'  => $this->getPackageProperty('total_width'),
            'length' => $this->getPackageProperty('total_length'),
        );
        if (in_array(false, $size, true)) {
            return false;
        } else {
            return in_array(0, $size) ? null : $size;
        }
    }

    protected function getTotalHeight()
    {
        return $this->getPackageProperty('total_height');
    }

    protected function getTotalWidth()
    {
        return $this->getPackageProperty('total_width');
    }

    protected function getTotalLength()
    {
        return $this->getPackageProperty('total_length');
    }

    protected function getTotalWeight()
    {
        return $this->getPackageProperty('weight');
    }

    /**
     * Package content price with discount
     * @return double
     */
    protected function getTotalPrice()
    {
        return $this->getPackageProperty('price');
    }

    /**
     * Package content price before discount
     * @return double
     */
    protected function getTotalRawPrice()
    {
        return $this->getPackageProperty('raw_price');
    }

    /**
     * Service variant id (key) returned by self::calculate method
     * @return string|null
     */
    public function getSelectedServiceId()
    {
        return $this->getPackageProperty('service_variant_id');
    }

    /**
     * Array of waShipping::PAYMENT_TYPE_* constants; may be empty
     * @return string[]
     */
    public function getSelectedPaymentTypes()
    {
        return (array)$this->getPackageProperty('payment_type');
    }

    protected function getAddress($field = null)
    {
        return ($field === null) ? $this->address : (isset($this->address[$field]) ? $this->address[$field] : null);
    }

    /**
     * Checks a specified address in accordance with the rules returned by waShipping::allowedAddress
     *
     * @param array $address
     * @return bool
     */
    public function isAllowedAddress($address = array())
    {
        $match = true;
        if (empty($address)) {
            $address = $this->address;
        }
        foreach ($this->allowedAddress() as $allowed_address) {
            $match = true;
            foreach ($allowed_address as $field => $value) {
                if (!empty($value) && !empty($address[$field])) {
                    $expected = trim(mb_strtolower($address[$field]));
                    if (is_array($value)) {
                        $value = array_map('trim', array_map('mb_strtolower', $value));
                        if (!in_array($expected, $value)) {
                            $match = false;
                            break;
                        }
                    } else {
                        $value = trim(mb_strtolower($value));
                        if ($expected != $value) {
                            $match = false;
                            break;
                        }
                    }
                }
            }
            if ($match) {
                break;
            }
        }

        return $match;
    }

    /**
     *
     * Returns available shipping options info, rates, and estimated delivery times
     * @param array $items
     * @param array [][string]mixed $items package item
     * @param array [][string]string $items['id'] ID of package item
     * @param array [][string]string $items['name'] name of package item
     * @param array [][string]mixed $items['weight'] weight of package item
     * @param array [][string]mixed $items['price'] price of package item
     * @param array [][string]mixed $items['quantity'] quantity of package item
     *
     * @param array [string]string $address shipping address
     *
     *
     * @param array [mixed]mixed $params
     * @param array [string]float $params['total_price'] package total price
     * @param array [string]float $params['total_weight'] package total weight
     *
     * @return string|array[string]array
     * @return array[string]['name']string
     * @return array[string]['description']string
     * @return array[string]['est_delivery']string
     * @return array[string]['currency']string
     * @return array[string]['rate']mixed float or array for min-max
     * @return array[string]['comment']string optional comment
     * @return array[string]['custom_data']mixed optional custom data for input control
     */
    public function getRates($items = array(), $address = array(), $params = array())
    {
        if (!empty($address)) {
            $this->address = $address;
        }

        $this->setParams($params);

        try {
            if ($this->isAllowedAddress()) {
                $rates = $this->addItems($items)->calculate();
            } else {
                $rates = false;
            }
        } catch (waException $ex) {
            $rates = $ex->getMessage();
        }
        return $rates;
    }

    public function setPackageState(waOrder $order, $state, $params = array())
    {
        $this->addItems($order->items);
        $this->setAddress($order->shipping_address);
        $params['total_price'] = $order->subtotal;
        $params['total_discount'] = $order->discount;
        $shipping_data = array();
        if (isset($params['shipping_data'])) {
            $shipping_data = $params['shipping_data'];
            unset($params['shipping_data']);
            if (!is_array($shipping_data)) {
                $shipping_data = array();
            }
        }

        $this->setParams($params);

        $method_name = sprintf('%sPackage', $state);
        if (method_exists($this, $method_name)) {
            /**
             * @uses waShipping::cancelPackage
             * @uses waShipping::readyPackage
             * @uses waShipping::draftPackage
             */
            return $this->{$method_name}($order, $shipping_data);
        } else {
            throw new waException(sprintf("Unknown package state %s", $state));
        }
    }

    /**
     * @param string $state one of waShipping::STATE_*
     * @param waOrder|null $order
     * @param array $params
     * @return array
     */
    public function getStateFields($state, waOrder $order = null, $params = array())
    {
        $this->addItems($order->items);
        $this->setAddress($order->shipping_address);
        $params['total_price'] = $order->total;
        $this->setParams($params);
        return array();
    }

    /**
     * Set package state into waShipping::STATE_DRAFT
     * @param waOrder $order
     * @param array $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function draftPackage(waOrder $order, $shipping_data = array())
    {
        return null;
    }

    /**
     * Set package state into waShipping::STATE_CANCELED
     * @param waOrder $order
     * @param array $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function cancelPackage(waOrder $order, $shipping_data = array())
    {
        return null;
    }

    /**
     * Set package state into waShipping::STATE_SHIPPING
     * @param waOrder $order
     * @param array $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function shippingPackage(waOrder $order, $shipping_data = array())
    {
        return null;
    }

    /**
     * Set package state into waShipping::STATE_READY
     * @param waOrder $order
     * @param array $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function readyPackage(waOrder $order, $shipping_data = array())
    {
        return null;
    }

    /**
     * @param waOrder $order
     * @return array[string]array
     * @return array[string]['name']string printform's name
     * @return array[string]['description']string printform's description
     */
    public function getPrintForms(waOrder $order = null)
    {
        return array();
    }

    /**
     *
     * Displays printable form content (HTML) by id
     * @param string $id
     * @param waOrder $order
     * @param array $params
     * @return string HTML code
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        return '';
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

    public function allowedLinearUnit()
    {
        return null;
    }

    /**
     *
     * List of allowed address patterns
     * @return array
     */
    public function allowedAddress()
    {
        return array();
    }

    /**
     * @return array
     */
    public function requestedAddressFields()
    {
        return array();
    }

    /**
     * @param array $service
     * @return array
     */
    public function requestedAddressFieldsForService($service)
    {
        return $this->requestedAddressFields();
    }

    public function customFields(waOrder $order)
    {
        return array();
    }

    public function customFieldsForService(waOrder $order, $service)
    {
        return $this->customFields($order);
    }

    public function displayCustomFields($fields, $env = null)
    {
        return null;
    }

    /**
     *
     */
    abstract protected function calculate();

    /**
     * @return null|array
     */
    public function getPromise()
    {
        return null;
    }

    /**
     * @param waOrder $order
     */
    public function prefetch($order)
    {

    }

    /**
     * @return bool|null
     */
    public function runSync()
    {
        if ($this->syncRequired()) {
            $result = $this->sync();
        } else {
            $result = null;
        }

        $this->handleSync($result);
        return $result;
    }

    /**
     * @return null|boolean if there no data to sync just return null, otherwise return boolean
     * @since 1.13 framework version
     */
    protected function sync()
    {
        return null;
    }

    /**
     * @return bool|null
     * @throws waDbException
     */
    protected function syncRequired()
    {
        $required = null;
        $info = self::info($this->id);
        if (!empty($info['sync'])) {
            $sync_time = $this->getGeneralSettings('sync_time');
            $sync_success_time = (int)$this->getGeneralSettings('sync_success_time');
            $sync_failure_time = (int)$this->getGeneralSettings('sync_failure_time');
            $plugin_run_sync = max(0, $sync_success_time, $sync_failure_time);
            $required = false;

            // interval value between 1 hour and 1 week
            $interval = min(24 * 7, max(1, $info['sync'])) * 3600;
            $time = time();
            if (empty($sync_time) // it's first run
                || ($time >= ($plugin_run_sync + $interval - 60)) // OR more than required time
            ) {
                $required = true;
            } elseif (($time > ($plugin_run_sync + 0.25 * $interval)) // quarter interval
                && ($sync_failure_time > $sync_success_time) // AND previous run failed
            ) {
                $required = true;
            }
        }

        return $required;
    }

    protected function handleSync($result = null)
    {
        $time = time();
        $update = array(
            'sync_time' => $time,
        );

        if ($result) {
            $update['sync_success_time'] = $time;
        } elseif ($result !== null) {
            $update['sync_failure_time'] = $time;
        }

        foreach ($update as $name => $value) {
            $this->setGeneralSettings($name, $value);
        }
        return $result;
    }


    /**
     *
     * Returns shipment current tracking info
     * @param string $tracking_id
     * @return string Tracking information (HTML)
     */
    public function tracking($tracking_id = null)
    {
        return null;
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
                    'title'       => $currency['title'].' ('.$code.')',
                    'description' => $currency['code'],
                );
            }
        } else {
            $currencies = waCurrency::getAll();
            foreach ($currencies as $code => $currency_name) {
                $options[$code] = array(
                    'value'       => $code,
                    'title'       => $currency_name.' ('.$code.')',
                    'description' => $code,
                );
            }
        }
        return $options;
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
     * Country/region dependent select boxes [+ city input]
     *
     * @param string $name
     * @param array $params
     * @return string
     * @example
     * Sample of params defined in proper settings.php
     *
     *    'region_zone' => array(
     *           'title' => 'Sender region',
     *           'control_type' => waHtmlControl::CUSTOM . ' waShipping::settingRegionZoneControl',
     *           'items' => array(
     *               'country' => array(
     *                       'value' => 'usa',
     *                       'description' => 'Represents the country from which the shipment will be originating'
     *               ),
     *               'region' => array(
     *                       'value' => 'NY',
     *                       'description' => 'Represents the state/province from which the shipment will be originating.<br>Required for printing labels'
     *               ),
     *               'city' => array(
     *                       'value' => 'New York',
     *                       'description' => Enter city name<br>Required for printing labels'
     *               ),
     *       )
     *    ),
     *
     *    If 'city' is not missing, city input box is presented
     *
     */
    public static function settingRegionZoneControl($name, $params = array())
    {
        $html = "";
        /** @var waShipping $plugin */
        $plugin = $params['instance'];
        if (isset($params['translate'])) {
            $translate = (!empty($params['translate']) && is_callable($params['translate'])) ? $params['translate'] : false;
        } else {
            $translate = array($plugin, '_wp');
        }


        $params['items']['country']['value'] = !empty($params['value']['country']) ? $params['value']['country'] : '';
        $params['items']['region']['value'] = !empty($params['value']['region']) ? $params['value']['region'] : '';

        if (isset($params['items']['city'])) {
            $params['items']['city']['value'] = !empty($params['value']['city']) ? $params['value']['city'] : '';
        }


        // country section
        $cm = new waCountryModel();
        if ($translate) {
            $_description = call_user_func($translate, $params['items']['country']['description']);
        } else {
            $_description = $params['items']['country']['description'];
        }
        $html .= <<<HTML
<div class='country'>
    <select name='{$name}[country]'>
        <option value=''></option>
HTML;

        foreach ($cm->all() as $country) {
            $country['name'] = htmlentities($country['name'], ENT_NOQUOTES, waHtmlControl::$default_charset);
            $_selected = ($params['items']['country']['value'] == $country['iso3letter']) ? " selected='selected'" : "";
            $html .= "<option value='{$country['iso3letter']}'{$_selected}>{$country['name']}</value>";
        }
        $html .= <<<HTML
    </select>
    <br/>
    <span class='hint'>{$_description}</span>
</div>
<br/>
HTML;


        $regions = array();
        if ($params['items']['country']['value']) {
            $rm = new waRegionModel();
            $regions = $rm->getByCountry($params['items']['country']['value']);
        }

        // region section
        $html .= <<<HTML
<div class="region">
    <i class="icon16 loading" style="display:none; margin-left: -23px;"></i>
HTML;


        $_style = !empty($regions) ? ' style="display:none;"' : '';
        $_disabled = !empty($regions) ? ' disabled="disabled"' : '';

        $html .= <<<HTML
    <div class='empty'{$_style}>
        <p class='small'>{$plugin->_w("Shipping will be restricted to the selected country")}</p>
        <input name='{$name}[region]' value='' type='hidden'{$_disabled}>
    </div>
    <div class='not-empty'{$_style}>
        <select name='{$name}[region]'{$_disabled}>
            <option value=''></option>
HTML;

        foreach ($regions as $region) {
            $_selected = ($params['items']['region']['value'] == $region['code']) ? ' selected="selected"' : "";
            $region['name'] = htmlentities($region['name'], ENT_NOQUOTES, waHtmlControl::$default_charset);
            $html .= "<option value='{$region['code']}'{$_selected}>{$region['name']}</option>";
        }
        $html .= <<<HTML
        </select>
        <br/>
        <span class='hint'>{$params['items']['region']['description']}</span>
    </div>
    <br/>
HTML;

        // city section
        if (isset($params['items']['city'])) {
            $_value = !empty($params['items']['city']['value']) ? $params['items']['city']['value'] : "";
            $_value = htmlentities($_value, ENT_QUOTES, waHtmlControl::$default_charset);
            if ($translate) {
                $_description = call_user_func($translate, $params['items']['city']['description']);
            } else {
                $_description = $params['items']['city']['description'];
            }
            $html .= <<<HTML
    <div class='city'>
        <input name='{$name}[city]' value='{$_value}' type='text'>
        <br>
        <span class='hint'>{$_description}</span>
    </div>
HTML;
        }

        $html .= <<<HTML
</div>
HTML;


        $url = wa()->getAppUrl('webasyst').'?module=backend&action=regions';

        // container id for interaction with js purpose
        $id = preg_replace("![\\[\\]]{1,2}!", "-", $name);
        if ($id[strlen($id) - 1] == "-") {
            $id = substr($id, 0, -1);
        }

        // wrap to container
        $html = "<div id='{$id}'>{$html}</div>";

        // javascript here
        $html .= <<<HTML
        <script type='text/javascript'>
        $(function() {
            'use strict';
            var name = '{$name}[region]';
            var url  = '{$url}';
            var container = $('#{$id}');

            var target  = container.find("div.region");
            var loader  = container.find(".loading");
            var old_val = target.find("select, input").val();

            container.find('select[name$="[country]"]').change(function() {
                loader.show();
                $.post(url, {
                    country: this.value }, function(r) {
                        if (r.data && r.data.options
                                && r.data.oOrder && r.data.oOrder.length)
                        {
                            var select = $(
                                    "<select name='" + name + "'>" +
                                    "<option value=''></option>" +
                                    "</select>"
                            );
                            var o, selected = false;
                            for (var i = 0; i < r.data.oOrder.length; i++) {
                                o = $("<option></option>").attr(
                                        "value", r.data.oOrder[i]
                                ).text(
                                        r.data.options[r.data.oOrder[i]]
                                ).attr(
                                        "disabled", r.data.oOrder[i] === ""
                                );
                                if (!selected && old_val === r.data.oOrder[i]) {
                                    o.attr("selected", true);
                                    selected = true;
                                }
                                select.append(o);
                            }
                            target.find(".not-empty select").replaceWith(select);
                            target.find(".not-empty").show();

                            target.find(".empty input").attr("disabled", true);
                            target.find(".empty").hide();

                        } else {
                            target.find(".empty input").attr("disabled", false);
                            target.find(".empty").show();

                            target.find(".not-empty select").attr("disabled", true);
                            target.find(".not-empty").hide();

                        }
                        loader.hide();
                    }, "json");
            });

            container.on("change", 'select[name="' + name + '"]', function() {
                old_val = this.value;
            });

        });
        </script>
HTML;

        return $html;
    }

    /**
     * @param       $name
     * @param array $params
     * @return string
     * @throws Exception
     * @since installer 1.5.14
     */
    public static function settingDeliveryIntervalControl($name, $params = array())
    {
        // container id for interaction with js purpose
        $wrappers = array(
            'title'               => '',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br>%s<br>',
            'description'         => '',
            'control_wrapper'     => "%s\n%s\n%s\n",

        );

        $params = array_merge($params, $wrappers);

        waHtmlControl::addNamespace($params, $name);

        $html = '<p class="hint">'._ws('This setting must be supported by the app; e.g., Shop-Script ver. 8.0 or higher.').'</p>';


        $interval_params = $params;
        $interval_params['value'] = ifset($params, 'value', 'interval', null);
        $interval_params['control_wrapper'] = "%2\$s\n%1\$s\n%3\$s\n";
        $interval_params['title'] = ifset($params['title_interval'], _ws('Request preferred delivery time'));
        $html .= waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'interval', $interval_params);
        waHtmlControl::makeId($interval_params, 'interval');
        $html .= ifset($params['control_separator'], '<br/>');

        $date_params = $params;
        $date_params['value'] = ifset($params, 'value', 'date', null);
        $date_params['title'] = ifset($params['title_date'], _ws('Request preferred delivery date'));
        $date_params['control_wrapper'] = "%2\$s\n%1\$s\n%3\$s\n";
        $html .= waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'date', $date_params);
        waHtmlControl::makeId($date_params, 'date');
        $html .= ifset($params['control_separator'], '<br/>');

        $default_interval = array(
            'from'   => 10,
            'from_m' => 0,
            'to'     => 12,
            'to_m'   => 0,
            'day'    => array_fill(0, 6, true),
        );

        if (!empty($params['extra_workdays'])) {
            $default_interval['day']['workday'] = true;
        }

        $intervals_params = $params;
        $intervals_params['value'] = ifempty(
            $params,
            'value',
            'intervals',
            array(
                0 => $default_interval,
            )
        );

        $rows = array();

        waHtmlControl::addNamespace($intervals_params, 'intervals');

        foreach ($intervals_params['value'] as $id => $value) {
            $interval_items_params = $intervals_params;
            waHtmlControl::addNamespace($interval_items_params, $id);

            $default_params = array(
                'title' => ifset($params['title_from'], _ws('from')),
                'class' => 'short',
                'value' => ifset($value['from']),
            );
            $from = waHtmlControl::getControl(waHtmlControl::INPUT, 'from', $default_params + $interval_items_params);
            if (!empty($params['minutes'])) {
                $default_params = array(
                    'title' => '',
                    'class' => 'short',
                    'value' => sprintf('%02d', ifset($value['from_m'], 0)),
                );
                $from .= ':'.waHtmlControl::getControl(waHtmlControl::INPUT, 'from_m', $default_params + $interval_items_params);
            }

            $default_params = array(
                'title' => ifset($params['title_till'], _ws('till')),
                'class' => 'short',
                'value' => ifset($value['to']),
            );
            $to = waHtmlControl::getControl(waHtmlControl::INPUT, 'to', $default_params + $interval_items_params);
            if (!empty($params['minutes'])) {
                $default_params = array(
                    'title' => '',
                    'class' => 'short',
                    'value' => sprintf('%02d', ifset($value['to_m'], 0)),
                );
                $to .= ':'.waHtmlControl::getControl(waHtmlControl::INPUT, 'to_m', $default_params + $interval_items_params);
            }

            $days_params = $interval_items_params;
            waHtmlControl::addNamespace($days_params, 'day');
            $days = array_fill(0, 7, array('class' => 'js-days'));

            $allow_hide = true;

            if (!empty($params['extra_holidays'])) {
                $days['holiday'] = array(
                    'class' => 'js-holiday js-days',
                    //'style' => 'border: solid 1px #FFF4F4 !important;',
                );
                $allow_hide = false;
            }

            if (!empty($params['extra_workdays'])) {
                $days['workday'] = array(
                    'class' => 'js-workday js-days',
                    //'style' => 'background-color: #F4FFF4 !important;',
                );
                $allow_hide = false;
            }

            $allow_hide = json_encode($allow_hide);

            foreach ($days as $day => &$day_info) {
                $default_params = array(
                    'value' => ifset($value['day'][$day]),
                );
                $day_info['control'] = waHtmlControl::getControl(waHtmlControl::CHECKBOX, $day, $default_params + $days_params);
                unset($day_info);
            }

            $day_pattern = '<td class="%s" style="%s">%s</td>';
            foreach ($days as &$day) {
                $day = sprintf($day_pattern, $day['class'], ifset($day['style'], ''), $day['control']);
                unset($day);
            }

            $days = implode($days);
            $rows[] = <<<HTML
<tr>
    <td>{$from}</td>
    <td>{$to}</td>
    {$days}
    <td><a class="inline-link delete-interval" href="javascript:void(0);"><i class="icon16 delete"></i></a></td>

</tr>
HTML;
        }
        $rows = implode("\n", $rows);

        $days = array(
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        );

        if (!empty($params['extra_holidays'])) {
            $days[] = 'Extra day off';
        }

        if (!empty($params['extra_workdays'])) {
            $days[] = 'Extra workday';
        }

        foreach ($days as &$day) {
            $day = _ws($day);
        }
        unset($day);
        $days = implode('</b></th><th class="js-days"><b>', $days);

        $interval_description = htmlentities(ifset($params['description_interval'], _ws('Delivery intervals')), ENT_QUOTES, waHtmlControl::$default_charset);
        $add_interval = htmlentities(ifset($params['description_interval'], _ws('Add interval')), ENT_QUOTES, waHtmlControl::$default_charset);

        waHtmlControl::makeId($params);
        $html .= <<<HTML
<table class="zebra" id="{$params['id']}">
    <thead>
    <tr>
        <th colspan="2">{$interval_description}</th>
        <th class="js-days"><b>{$days}</b></th>
        <th>&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    </tbody>
        {$rows}
    <tfoot>
    <tr class="white">
        <td colspan="2"><a class="inline-link add-interval" href="javascript:void(0);"><i class="icon16 add"></i><b><i>{$add_interval}</i></b></a></td>
        <td colspan="8">&nbsp;</td>
    </tr>
    </tfoot>
</table>
HTML;

        $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        'use strict';
        var table = $('#{$params['id']}');

        if({$allow_hide}) {
            var interval = $('#{$interval_params['id']}');
            interval.change(function (event) {
                var fast = !event.originalEvent;
                if (this.checked) {
                    table.show(fast ? null : 400);
                } else {
                    table.hide(fast ? null : 400);
                }
            }).change();

            var date = $('#{$date_params['id']}');
            date.change(function (event) {
                var fast = !event.originalEvent;
                if (this.checked) {
                    table.find('.js-days > *').animate({'opacity': '1'}, fast ? null : 300);
                    table.find('.js-days input').attr('disabled', null);
                    if (!this.defaultChecked) {
                        table.find('.js-days input').attr('checked', true);
                    }
                } else {
                    table.find('.js-days > *').animate({'opacity': '0'}, fast ? null : 300);
                    table.find('.js-days input').attr('disabled', true);
                }
            }).change();
        }

        var set = function (tr, last) {
            tr.find('input[type="checkbox"]').attr('checked', true);
            tr.find('input[type="checkbox"][name$="day\]"]').attr('checked', null);
            var value = last ? parseInt(last.find('input[name*="[to]"]').val()) : 10;
            var delta = last ? (24 + value - parseInt(last.find('input[name*="[from]"]').val())) : 2;
            tr.find('input[name*="[from]"]').val(value % 24).trigger('change');
            tr.find('input[name*="[to]"]').val((value + delta) % 24).trigger('change');
        };

        table.on('keyup, change', 'input[name*="[from]"]', function () {
            var el = $(this);
            var value = el.val();
            if (!value) {
                value = 0;
            }
            el.val(Math.max(0, Math.min(23, parseInt(value))));
        });

        table.on('keyup, change', 'input[name*="[to]"]', function () {
            var el = $(this);
            var value = el.val();
            if (!value) {
                value = 0;
            }
            el.val(Math.max(0, Math.min(23, parseInt(value))));
        });

        table.on('click', '.add-interval', function () {
            var last = table.find('tbody > tr:last');
            var clone = last.clone();

            clone.find('input').each(function () {
                var input = $(this);
                // increase index inside input name
                var name = input.attr('name');
                input.attr('name', name.replace(/\[intervals\]\[(\d+)\]/, function (str, p1) {
                    return '[intervals][' + (parseInt(p1, 10) + 1) + ']';
                }));
            });
            set(clone, last);

            last.after(clone);

            return false;
        });

        table.on('click', '.delete-interval', function () {
            var el = $(this);
            if (table.find('tbody > tr').length > 1) {
                el.parents('tr:first').remove();
            } else {
                set(el.parents('tr:first'));
            }
            return false;
        });

    });
</script>
HTML;
        return $html;
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     * @throws waException
     * @since      installer 1.5.14
     * @deprecated use waHtmlControl::DATETIME instead
     */
    public static function settingCustomDeliveryIntervalControl($name, $params = array())
    {
        $html = '';

        $wrappers = array(
            'title'           => '',
            'title_wrapper'   => '%s',
            'description'     => '',
            'control_wrapper' => "%s\n%3\$s\n%2\$s\n",

        );


        $params = array_merge($params, $wrappers);
        $available_days = array();
        $date_params = $params;
        $date_format = waDateTime::getFormat('date');
        if (isset($params['params']['date'])) {
            $date_params['style'] = "z-index: 100000;";

            $date_name = preg_replace('@([_\w]+)(\]?)$@', '$1.date_str$2', $name);
            $offset = min(365, max(0, intval(ifset($params, 'params', 'date', 0))));
            $date_params['placeholder'] = waDateTime::format($date_format, sprintf('+ %d days', $offset));
            $date_params['description'] = _ws('Date');
            $date_params['value'] = ifset($params, 'value', 'date_str', '');
            $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $date_name, $date_params);
            waHtmlControl::makeId($date_params, $date_name);

            $date_name = preg_replace('@([_\w]+)(\]?)$@', '$1.date$2', $name);
            $date_formatted_params = $params;
            $date_formatted_params['style'] = "display: none;";
            $date_formatted_params['value'] = ifset($params, 'value', 'date', '');
            $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $date_name, $date_formatted_params);
            waHtmlControl::makeId($date_formatted_params, $date_name);


            $date_format_map = array(
                'PHP' => 'JavaScript',
                'Y'   => 'yy',
                'd'   => 'dd',
                'm'   => 'mm',
            );
            $js_date_format = str_replace(array_keys($date_format_map), array_values($date_format_map), $date_format);
            $locale = wa()->getLocale();
            $localization = sprintf("wa-content/js/jquery-ui/i18n/jquery.ui.datepicker-%s.js", $locale);
            if (!is_readable($localization)) {
                $localization = '';
            }

        }

        $interval_params = $params;
        if (!empty($params['params']['interval'])) {

            $intervals = ifempty($params['params']['intervals'], array());

            $interval_params['options'] = array();
            foreach ($intervals as $interval) {
                if (is_array($interval) && isset($interval['from']) && isset($interval['to'])) {
                    $days = ifset($interval['day'], array());
                    $value = sprintf(
                        '%d:%02d-%d:%02d',
                        $interval['from'],
                        ifset($interval['from_m'], 0),
                        $interval['to'],
                        ifset($interval['to_m'], 0)
                    );
                    $interval_params['options'][] = array(
                        'value' => $value,
                        'title' => $value,
                        'data'  => array('days' => array_keys($days)),
                    );
                    $available_days = array_merge(array_keys($days), $available_days);
                }
            }

            $available_days = array_values(array_unique($available_days));

            $interval_name = preg_replace('@([_\w]+)(\]?)$@', '$1.interval$2', $name);
            if ($interval_params['options']) {
                $html .= ifset($params['control_separator']);

                $option = array(
                    'value' => '',
                    'title' => '',
                    'data'  => array('days' => $available_days),
                );
                array_unshift($interval_params['options'], $option);

                $interval_params['description'] = _ws('Time');
                $interval_params['value'] = ifset($params['value']['interval']);
                $html .= waHtmlControl::getControl(waHtmlControl::SELECT, $interval_name, $interval_params);
                waHtmlControl::makeId($interval_params, $interval_name);
            } else {
                $html .= ifset($params['control_separator']);
                $interval_params['description'] = _ws('Time');
                $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $interval_name, $interval_params);
            }
        }


        if (isset($params['params']['date']) && isset($offset)) {

            if (empty($interval_params['id'])) {
                $interval_params['id'] = '';
            }

            $available_days = json_encode($available_days);
            $root_url = wa()->getRootUrl();
            $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        'use strict';
        var date_input = $('#{$date_params['id']}');
        var date_formatted = $('#{$date_formatted_params['id']}');
        var interval = '{$interval_params['id']}' ? $('#{$interval_params['id']}') : false;
        var available_days = {$available_days};
        var init = function () {
            date_input.datepicker();
            date_input.datepicker("option", {
                "dateFormat": '{$js_date_format}',
                "minDate": {$offset},
                "onSelect": function (dateText) {
                    var d = date_input.datepicker('getDate');
                    date_input.val(dateText);
                    date_formatted.val($.datepicker.formatDate('yy-mm-dd', d));
                    if (d && interval && interval.length) {
                        /** @var int day week day (starts from 0) */
                        var day = (d.getDay() + 6) % 7;
                        /**
                         * filter select by days
                         */
                        var value = typeof(interval.val()) !== 'undefined';
                        var matched = null;
                        interval.find('option').each(function () {
                            /**
                             * @this HTMLOptionElement
                             */
                            var option = $(this);
                            var disabled = (option.data('days').indexOf(day) === -1) ? 'disabled' : null;
                            option.attr('disabled', disabled);
                            if (disabled) {
                                if (this.selected) {
                                    value = false;
                                }
                            } else {
                                matched = this;
                                if (!value) {
                                    this.selected = true;
                                    value = !!this.value;
                                    if (typeof(interval.highlight) === 'function') {
                                        interval.highlight();
                                    }
                                }
                            }
                        });

                        if (value) {
                            interval.removeClass('error');
                        } else if (matched) {
                            matched.selected = true;
                            interval.removeClass('error');
                        } else {
                            interval.addClass('error');
                        }
                    }

                },
                "beforeShowDay": function (date) {
                    if (interval && interval.length) {
                        var day = (date.getDay() + 6) % 7;
                        return [(date_input.data('available_days')||available_days||[]).indexOf(day) !== -1]
                    } else {
                        return [true];
                    }
                }
            });
            $('.ui-datepicker').css({
                "zIndex": 999999,
                "display": 'none'
            });
        };

        var init_locale = function () {
            if ('{$localization}' && (!$.datepicker.regional || ($.datepicker.regional.indexOf('{$locale}'.substr(0, 2)) === -1))) {
                $.getScript('{$root_url}{$localization}', init);
            } else {
                init();
            }
        };

        if (typeof(date_input.datepicker) !== 'function') {
            $("<link/>", {
                rel: "stylesheet",
                type: "text/css",
                href: "{$root_url}wa-content/css/jquery-ui/jquery-ui-1.7.2.custom.css"
            }).appendTo("head");
            $.getScript('{$root_url}wa-content/js/jquery-ui/jquery-ui-1.7.2.custom.min.js', init_locale);

        } else {
            init_locale();
        }
    });
</script>
HTML;
        }

        return $html;
    }

    /**
     *
     * Get shipping plugin
     * @param string $id
     * @param null $key
     * @param waAppShipping|string $app_adapter
     * @return waShipping
     * @throws waException
     */
    public static function factory($id, $key = null, $app_adapter = null)
    {
        $instance = parent::factory($id, $key, self::PLUGIN_TYPE);
        /**
         * @var waShipping $instance
         */
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
     * @param      $options array
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
     * @return array[string]string
     * @return array['name']string
     * @return array['description']string
     * @return array['version']string
     * @return array['build']string
     * @return array['logo']string
     * @return array['icon'][int]string
     * @return array['img']string
     */
    public static function info($id, $options = array(), $type = null)
    {
        return parent::info($id, $options, self::PLUGIN_TYPE);
    }

    /**
     *
     * @return waAppShipping
     * @throws waException
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

    protected static function formatDatetime($data, $format = 'Y-m-d H:i:s')
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                $item = self::formatDatetime($item);
                unset($item);
            }

        } elseif (is_int($data)) {
            $data = date($format, $data);
        } elseif ($data !== null) {
            $data = date($format, strtotime($data));
        }
        return $data;
    }

    public function uninstall($force = false)
    {
        $current_app_id = $this->app_id;
        $apps = wa()->getApps();
        foreach ($apps as $app_id => $info) {
            if (!empty($info[$this->type])) {
                $this->app_adapter = null;
                $this->app_id = $app_id;
                try {
                    $this->getAdapter()->uninstall($this->id);
                } catch (waException $ex) {
                    ;
                }
            }
        }
        $this->app_adapter = null;
        $this->app_id = $current_app_id;

        parent::uninstall($force);
    }

    public function getInteractionUrl($action = 'default', $module = 'backend')
    {
        $url = null;
        switch (wa()->getEnv()) {
            case 'backend':
                $template = 'webasyst/shipping/%s/%s/%s/?app_id=%s';
                $url = wa()->getAppUrl('webasyst').sprintf($template, $this->id, $module, $action, $this->app_id);
                break;
            case 'frontend':
                $url_params = array(
                    'action_id' => $action,
                    'plugin_id' => $this->key,
                );
                $url = wa()->getRouteUrl(sprintf('%s/frontend/shippingPlugin', $this->app_id), $url_params, true);
                break;
        }

        return $url;
    }

    protected static function log($module_id, $data)
    {
        static $id;
        if (empty($id)) {
            $id = uniqid();
        }
        $rec = '#'.$id."\n";
        $module_id = strtolower($module_id);
        if (!preg_match('@^[a-z][a-z0-9]+$@', $module_id)) {
            $rec .= 'Invalid module_id: '.$module_id."\n";
            $module_id = 'general';
        }
        $filename = 'shipping/'.$module_id.'Shipping.log';
        $rec .= "data:\n";
        if (!is_string($data)) {
            $data = var_export($data, true);
        }
        $rec .= "$data\n";
        waLog::log($rec, $filename);
    }
}
