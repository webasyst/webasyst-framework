<?php

/**
 * @property string $test_mode
 * @property string $user_id
 * @property string $package_size
 * @property string $currency
 * @property string $services_domestic
 * @property string $services_international
 *
 * @property string $content_type
 * @property string $other_content_type
 *
 * @property string $name
 * @property array $region_zone associative array with keys: country, string, city
 * @property string $address
 * @property string $zip
 * @property string $phone
 * @property string $po_zip
 */
class uspsShipping extends waShipping
{
    protected function initControls()
    {
        $this->registerControl('RegionZoneControl');
        parent::initControls();
    }

    public function getSettingsHTML($params = array())
    {
        $options = array();
        foreach (uspsServices::getServices('Domestic') as $service) {
            $options[] = array(
                'value' => $service['id'],
                'title' => $service['name']
            );
        }
        $params['options']['services_domestic'] = $options;

        $options = array();
        foreach (uspsServices::getServices('International') as $service) {
            $options[] = array(
                'value' => $service['id'],
                'title' => $service['name']
            );
        }
        $params['options']['services_international'] = $options;

        return parent::getSettingsHTML($params);
    }

    protected function init()
    {
        $autoload = waAutoload::getInstance();
        foreach (
            array(
                 'uspsLabelsExpressMailQuery',
                 'uspsLabelsInternationalShippingQuery',
                 'uspsLabelsUspsTrackingQuery',
                 'uspsLabelsQuery',
                 'uspsLabelsSignatureConfirmationQuery',
                 'uspsQuery',
                 'uspsRatingsQuery',
                 'uspsServices',
                 'uspsTrackingQuery'
             ) as
         $class_name)
        {
            $autoload->add(
                $class_name,
                "wa-plugins/shipping/usps/lib/classes/$class_name.class.php"
            );
        }
        parent::init();
    }

    /**
     * @return array
     */
    protected function correctItems()
    {
        $items = $this->getItems();
        foreach ($items as &$item) {
            // weight property is required by usps, so if not exist set to default 1
            if (empty($item['weight'])) {
                $item['weight'] = 1;
            } else {
                $item['weight'] = round($item['weight']);
            }
        }
        unset($item);

        $this->setItems($items);

        return $items;
    }

    public function calculate()
    {
        $this->correctItems();
        $rates = $this->executeQuery(
            'ratings', array(
                'weight' => $this->getTotalWeight(),
                'address' => $this->getAddress()
            )
        );

        if (empty($rates)) {
            return $this->_w("USPS web service return an empty response");
        }

        return $rates;
    }

    public function tracking($tracking_id = null)
    {
        return $this->executeQuery('tracking', array('tracking_id' => $tracking_id));
    }

    private function executeQuery($name, $params, $catch = true)
    {
        if (!$catch) {
            return $this->getQuery($name, $params)->execute();
        }

        $response = null;
        try {
            $response = $this->getQuery($name, $params)->execute();
        } catch (Exception $e) {
            $error = $e->getMessage();
            //$this->log($error);
            return $error;
        }
        return $response;
    }

    /**
     * @param string $name
     * @param array|boolean $params if array - create instance of query with params, if true return class name just
     * @throws waException
     * @internal param bool $static
     * @return uspsQuery|string
     */
    private function getQuery($name, $params)
    {
        $class_name = 'usps' . ucfirst($name) . 'Query';
        if ($params === true) {
            return $class_name;
        }
        $params = (array)$params;
        if (!class_exists($class_name)) {
            throw new waException($this->_w("Unsupported API"));
        }
        return new $class_name($this, $params);
    }

    public function getPluginPath()
    {
        return $this->path;
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' => array('cost' => true),
            'country' => array('cost' => true)
        );
    }

    public function allowedAddress()
    {
        return array();
    }

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function allowedWeightUnit()
    {
        return 'oz';
    }

    protected function getServiceCodeByOrder(waOrder $order)
    {
        $shipping_rate_id = $order['params']['shipping_rate_id'];
        $service_code = array();
        foreach (explode('_', $shipping_rate_id) as $part) {
            if (is_numeric($part)) {
                break;
            }
            $service_code[] = $part;
        }
        $service_code = implode(' ', $service_code);
        return uspsServices::getServiceByCode($service_code);
    }

    public function getPrintForms(waOrder $order = null)
    {
        $all_forms = array(
            'usps_tracking' => array(
                'name' => 'USPS Tracking™',
                'description' => 'Generate USPS Tracking barcoded labels for Priority Mail®, First-Class Mail® parcels, and package services parcels, including Standard Post™, Media Mail®, and Library Mail. '
            ),
            'express_mail' => array(
                'name' => 'Express Mail®',
                'description' => 'Generate a single-ply Express Mail shipping label complete with return and delivery addresses, a barcode, and a mailing record for your use.'
            ),
            'signature_confirmation' => array(
                'name' => 'Signature Confirmation™ Labels',
                'description' => 'Generate a Signature Confirmation barcoded label for Priority Mail, First-Class Mail parcels, Standard Post, Media Mail, and Library Mail services, and we’ll provide the complete address label, including the Signature Confirmation Service barcode.'
            ),
            'international_shipping' => array(
                'name' => 'International Shipping Labels',
                'description' => 'Send documents and packages globally. USPS® offers reliable, affordable shipping to more than 180 countries. Generate Express Mail International®, Priority Mail International®, First-Class Mail International®, or First-Class Package International Service shipping labels complete with addresses, barcode, customs form, and mailing receipt.'
            ),
        );

        $forms = array();
        foreach ($all_forms as $name => $form) {
            $query_name = implode('', array_map("ucfirst", explode('_', $name)));
            $query = $this->getQuery('labels' . $query_name, true);
            $service = $this->getServiceCodeByOrder($order);
            $type = uspsServices::getServiceType($service['id']);

            if ($name == 'international_shipping') {
                if ($type == uspsServices::TYPE_INTERNATIONAL) {
                    $forms[$name] = $form;
                }
            } else {

                if ($type == uspsServices::TYPE_DOMESTIC && call_user_func_array(array($query,'isSupportedService'),array($service['code']))) {
                    $forms[$name] = $form;
                }
            }
        }

        return $forms;
    }

    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        $suffix = implode('', array_map("ucfirst", explode('_', $id)));

        $shipping_rate_id = $order['params']['shipping_rate_id'];
        if (!$shipping_rate_id) {
            throw new waException($this->_w("Shipping rate id is undefined of broken"));
        }

        if ($id == 'international_shipping') {
            $service_name = $order['shipping_name'];
        } else {
            $service = $this->getServiceCodeByOrder($order);
            $service_name = $service['code'];
        }

        $this->setItems($order['items']);
        $this->correctItems();


        $address = $order['shipping_address'];
        $address['name'] = htmlspecialchars(!empty($address['name']) ? $address['name'] : $order->contact_name);

        try {
            $response = $this->executeQuery('labels' . $suffix,
                array(
                    'service' => $service_name,
                    'order_id' => $order['id'],
                    'weight' => $this->getTotalWeight(),
                    'price' => $this->getTotalPrice(),
                    'items' => $this->getItems(),
                    'address' => $address,
                ), false
            );
            header('Content-type: application/pdf');
            echo $response['label'];
            exit;
        } catch (waException $e) {
            return $e->getMessage();
        }
    }
}
