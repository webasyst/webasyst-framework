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
     * @param array [string]mixed $item package item
     * @param array [string]string $item['id'] ID of package item
     * @param array [string]string $item['name'] name of package item
     * @param array [string]mixed $item['weight'] weight of package item
     * @param array [string]mixed $item['price'] price of package item
     * @param array [string]mixed $item['quantity'] quantity of package item
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
     * @param array [][string]mixed $items package item
     * @param array [][string]string $items['id'] ID of package item
     * @param array [][string]string $items['name'] name of package item
     * @param array [][string]mixed $items['weight'] weight of package item
     * @param array [][string]mixed $items['price'] price of package item
     * @param array [][string]mixed $items['quantity'] quantity of package item
     * @return waShipping
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
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
                    $expected = mb_strtolower($address[$field]);
                    if (is_array($value)) {
                        if (!in_array($expected, array_map('mb_strtolower', $value))) {
                            $match = false;
                            break;
                        }
                    } elseif ($expected != mb_strtolower($value)) {
                        $match = false;
                        break;
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
     */
    public function getRates($items = array(), $address = array(), $params = array())
    {
        if (!empty($address)) {
            $this->address = $address;
        }
        $this->params = array_merge($this->params, $params);
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

    /**
     *
     * List of allowed address patterns
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
     * @param $tracking_id
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
        $plugin = $params['instance'];
        /**
         * @var waShipping $plugin
         */
        $params['items']['country']['value'] =
            !empty($params['value']['country']) ? $params['value']['country'] : '';
        $params['items']['region']['value'] =
            !empty($params['value']['region']) ? $params['value']['region'] : '';

        if (isset($params['items']['city'])) {
            $params['items']['city']['value'] =
                !empty($params['value']['city']) ? $params['value']['city'] : '';
        }

        // country section
        $cm = new waCountryModel();
        $html .= "<div class='country'>";
        $html .= "<select name='{$name}[country]'><option value=''></option>";
        foreach ($cm->all() as $country) {
            $html .= "<option value='{$country['iso3letter']}'".
                ($params['items']['country']['value'] == $country['iso3letter']
                    ? " selected='selected'" : ""
                ).
                ">{$country['name']}</value>";
        }
        $html .= "</select><br>";
        $html .= "<span class='hint'>{$params['items']['country']['description']}</span></div><br>";

        $regions = array();
        if ($params['items']['country']['value']) {
            $rm = new waRegionModel();
            $regions = $rm->getByCountry($params['items']['country']['value']);
        }

        // region section
        $html .= '<div class="region">';
        $html .= '<i class="icon16 loading" style="display:none; margin-left: -23px;"></i>';
        $html .= '<div class="empty"'.
            (!empty($regions) ? 'style="display:none;"' : '').
            '><p class="small">'.
            $plugin->_w("Shipping will be restricted to the selected country").
            "</p>";
        $html .= "<input name='{$name}[region]' value='' type='hidden'".
            (!empty($regions) ? 'disabled="disabled"' : '').
            '></div>';
        $html .= '<div class="not-empty" '.
            (empty($regions) ? 'style="display:none;"' : '').">";
        $html .= "<select name='{$name}[region]'".
            (empty($regions) ? 'disabled="disabled"' : '').
            '><option value=""></option>';

        foreach ($regions as $region) {
            $html .= "<option value='{$region['code']}'".
                ($params['items']['region']['value'] == $region['code']
                    ? ' selected="selected"' : ""
                ).
                ">{$region['name']}</option>";
        }
        $html .= "</select><br>";
        $html .= "<span class='hint'>{$params['items']['region']['description']}</span></div><br>";

        // city section
        if (isset($params['items']['city'])) {
            $html .= "<div class='city'>";
            $html .= "<input name='{$name}[city]' value='".
                (!empty($params['items']['city']['value']) ? $params['items']['city']['value'] : "")."' type='text'>
                <br>";
            $html .= "<span class='hint'>{$params['items']['city']['description']}</span></div>";
        }

        $html .= "</div>";

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
     *
     * Get shipping plugin
     * @param string $id
     * @param null $key
     * @param waAppShipping|string $app_adapter
     * @return waShipping
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
