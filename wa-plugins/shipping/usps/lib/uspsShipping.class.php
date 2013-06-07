<?php

/**
 * @property string $test_mode
 * @property string $user_id
 * @property string $zip
 * @property string $package_size
 * @property string $currency
 * @property string $services_domestic
 * @property string $services_international
 */
class uspsShipping extends waShipping
{
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
        $autload = waAutoload::getInstance();
        $autload->add("uspsQuery", "wa-plugins/shipping/usps/lib/classes/uspsQuery.class.php");
        $autload->add("uspsRatingsQuery", "wa-plugins/shipping/usps/lib/classes/uspsRatingsQuery.class.php");
        $autload->add("uspsTrackingQuery", "wa-plugins/shipping/usps/lib/classes/uspsTrackingQuery.class.php");
        $autload->add("uspsLabelsUspsTrackingQuery", "wa-plugins/shipping/usps/lib/classes/uspsLabelsUspsTrackingQuery.class.php");
        $autload->add("uspsServices", "wa-plugins/shipping/usps/lib/classes/uspsServices.class.php");
        return parent::init();
    }

    public function calculate()
    {
        $rates = (array)$this->executeQuery(
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

    private function executeQuery($name, $params)
    {
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
     * @param array $params
     * @throws waException
     * @return uspsQuery
     */
    private function getQuery($name, $params)
    {
        $class_name = 'usps'.ucfirst($name).'Query';
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
            'zip' =>     array('cost' => true),
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
        return 'lbs';
    }

//     public function getPrintForms()
//     {
//         return array(
//             'usps_tracking' => array(
//                 'name' => 'USPS Tracking™',
//                 'description' => 'Generate USPS Tracking barcoded labels for Priority Mail®, First-Class Mail® parcels, and package services parcels, including Standard Post™, Media Mail®, and Library Mail. '
//             )
//         );
//     }

//     public function displayPrintForm($id, waOrder $order, $params = array())
//     {
//         $suffix = implode('', array_map("ucfirst", explode('_', $id)));
//         $method = 'displayPrintForm'.$suffix;
//         if (method_exists($this, $method)) {
//             return $this->$method($order, $params);
//         } else {
//             throw new waException('Print form not found');
//         }
//     }

//     public function displayPrintFormUspsTracking(waOrder $order, $params)
//     {
//         $shipping_rate_id = $order['params']['shipping_rate_id'];
//         if (!$shipping_rate_id) {
//             throw new waException($this->_w("Shipping rate id is undefined of broken"));
//         }
//         return $this->executeQuery('labelsUspsTracking',
//             array(
//                 'shipping_rate_id' => $shipping_rate_id,
//                 'weight'           => $this->getTotalWeight(),
//                 'price'            => $this->getTotalPrice(),
//                 'address'          => $order['shipping_address']
//             )
//         );
//     }
}
