<?php

/**
 * Flat-rate shipping plugin.
 *
 * @property-read float  $cost
 * @property-read string $currency
 * @property-read string $delivery
 * @property-read bool   $prompt_address
 * @property-read string $service_type
 * @property-read string $service_name
 * @property-read array  $shipping_address
 *
 */
class flatrateShipping extends waShipping
{
    /**
     * Main shipping rate calculation method.
     * Returns array of estimated shipping rates and transit times.
     * or error message to be displayed to customer,
     * or false, if this shipping option must not be available under certain conditions.
     *
     * @example <pre>
     * //return array of shipping options
     * return array(
     *     'option_id_1' => array(
     *          'name'         => $this->_w('...'),
     *          'description'  => $this->_w('...'),
     *          'est_delivery' => '...',
     *          'currency'     => $this->currency,
     *          'rate'         => $this->cost,
     *      ),
     *      ...
     * );
     *
     * //return error message
     * return 'Для расчета стоимости доставки укажите регион доставки';
     *
     * //shipping option is unavailable
     * return false;</pre>
     *
     * Useful parent class (waShipping) methods to be used in calculate() method:
     *
     *     <pre>
     *     // total package price
     *     $price = $this->getTotalPrice();
     *
     *     // total package weight
     *     $weight = $this->getTotalWeight();
     *
     *     // order items array
     *     $items = $this->getItems();
     *
     *     // obtain either full address info array or specified address field value
     *     $address = $this->getAddress($field = null);</pre>
     *
     * @return mixed
     */
    protected function calculate()
    {
        if ($this->delivery === '') {
            $est_delivery = null;
            $delivery_date = null;
        } else {
            /** @var string $departure_datetime SQL DATETIME */
            $departure_datetime = $this->getPackageProperty('departure_datetime');
            /** @var  int $departure_timestamp */
            if ($departure_datetime) {
                $departure_timestamp = max(strtotime($departure_datetime), time());
            } else {
                $departure_timestamp = time();
            }
            $timestamp = strtotime($this->delivery, $departure_timestamp);
            $est_delivery = waDateTime::format('humandate', $timestamp);
            $delivery_date = self::formatDatetime($timestamp);
        }
        $service = array(
            //'name'         => $this->_w('Ground shipping'), optional shipping service name
            'description'   => '',
            'est_delivery'  => $est_delivery, //string
            'delivery_date' => $delivery_date,
            'currency'      => $this->currency,
            'rate'          => $this->parseCost($this->cost),
            'type'          => $this->service_type,
        );

        if (strlen($this->service_name)) {
            $service['service'] = $this->service_name;
        }
        return array(
            'ground' => $service,
        );
    }

    /**
     * Returns ISO3 code of the currency (or array of ISO3 codes) this plugin supports.
     *
     * @see waShipping::allowedCurrency()
     * @return array|string
     */
    public function allowedCurrency()
    {
        // return array('USD', 'EUR') or simple string: 'USD'
        return $this->currency;

    }

    /**
     * Returns the weight unit (or array of weight units) this plugin supports.
     *
     * @see waShipping::allowedWeightUnit()
     * @return array|string
     */
    public function allowedWeightUnit()
    {
        // return array('kg','lbs') or simple string: 'kg'
        return 'kg';
    }

    /**
     * Returns general tracking information (HTML).
     *
     * @see     waShipping::tracking()
     * @example return _wp('Online shipment tracking: <a href="link">link</a>.');
     * @param string $tracking_id Optional tracking id specified by user.
     * @return string
     */
    public function tracking($tracking_id = null)
    {
        // this shipping plugin does not provide shipping tracking information
        return '';
    }

    /**
     * Returns array of printable forms this plugin offers
     *
     * @example return <pre>array(
     *    'form_id' => array(
     *        'name' => _wp('Printform name'),
     *        'description' => _wp('Printform description'),
     *    ),
     * );</pre>
     * @param waOrder $order Object containing order data
     * @return array
     */
    public function getPrintForms(waOrder $order = null)
    {
        // this shipping plugin does not generate printable forms
        return array();
    }

    /**
     * Returns HTML code of specified printable form.
     *
     * @param string  $id     Printform id as defined in method getPrintForms()
     * @param waOrder $order  Order data object
     * @param array   $params Optional parameters to be passed to printform generation template
     * @throws waException
     * @return string Printform HTML
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ('flatrate_form' == $id) {
            $view = wa()->getView();
            $view->assign('order', $order);
            $view->assign('params', $params);
            $view->assign('plugin', $this);
            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException($this->_w('Printable form not found'));
        }
    }

    /**
     * Limits the range of customer addresses to allow shipping to.
     *
     * @example <pre>return array(
     *   'country' => 'usa', # or array('usa', 'can')
     *   'region'  => 'NY',
     *   # or array('NY', 'PA', 'CT');
     *   # or omit 'region' item if you do not want to limit shipping to certain regions
     * );</pre>
     * @return array Return array() to allow shipping anywhere
     */
    public function allowedAddress()
    {
        //this plugin allows shipping orders to any addresses without limitations
        $address = array();
        if ($this->shipping_address) {
            if (!empty($this->shipping_address['country'])) {
                $address['country'] = $this->shipping_address['country'];
                if (!empty($this->shipping_address['region'])) {
                    $address['region'] = $this->shipping_address['region'];
                }
            }

            if (!empty($this->shipping_address['city'])) {
                $city = array_filter(array_map('trim', preg_split('@,+@', $this->shipping_address['city'])), 'strlen');
                if ($city) {
                    $address['city'] = $city;
                }
            }
        }
        return $address ? array($address) : $address;
    }

    /**
     * Returns array of shipping address fields which must be requested during checkout.
     *
     * @see     waShipping::requestedAddressFields()
     * @example <pre>return array(
     *     #requested field
     *     'zip'     => array(),
     *
     *     #hidden field with pre-defined value;
     *     'country' => array('hidden' => true, 'value' => 'rus', 'cost' => true),
     *
     *     #'cost' parameter means that field is used for calculation of approximate shipping cost during checkout
     *     'region'  => array('cost' => true),
     *     'city'    => array(),
     *
     *     #field is not requested
     *     'street'  => false,
     * );</pre>
     * @return array|bool Return false if plugin does not request any fields during checkout;
     * return array() if all address fields must be requested
     */
    public function requestedAddressFields()
    {
        //request either all or no address fields depending on the value of the corresponding plugin settings option
        $fields = false;
        if ($this->prompt_address) {
            $fields = array();

            $data = array('required' => true);

            $shipping_address = $this->shipping_address;
            if (is_array($shipping_address)) {
                foreach ($shipping_address as $field => $value) {
                    if (!empty($value)) {
                        $fields[$field] = array(
                            'required' => true,
                            'cost'     => true,
                        );
                    }
                }
            }

            $fields += array(
                'country' => $data,
                'region'  => $data,
                'city'    => $data,
                'street'  => $data,
            );
            if ($this->service_type == self::TYPE_POST) {
                $fields += array(
                    'zip' => $data,
                );
            }
        }
        return $fields;
    }

    /**
     * @param $string
     * @return float
     */
    private function parseCost($string)
    {
        $cost = 0.0;

        $string = preg_replace('@\\s+@', '', $string);

        foreach (preg_split('@\+|(\-)@', $string, null, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY) as $chunk) {
            $value = str_replace(',', '.', trim($chunk[0]));
            if (strpos($value, '%')) {
                $value = round($this->getTotalPrice() * floatval($value) / 100.0, 2);
            } else {
                $value = floatval($value);
            }
            if ($chunk[1] && (substr($string, $chunk[1] - 1, 1) == '-')) {
                $cost -= $value;
            } else {
                $cost += $value;
            }
        }
        return max(0.0, $cost);
    }
}
