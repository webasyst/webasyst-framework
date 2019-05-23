<?php

/**
 * @property string $customer_type
 * @property string $package_type
 * @property string $pickup_type
 * @property string $access_key
 * @property string $weight_dimension
 * @property string $user_id
 * @property string $password
 * @property string $country
 * @property string $city
 * @property string $zip
 */
class upsShipping extends waShipping
{
    private $currency = 'USD';

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        if ($this->weight_dimension == 'kgs') {
            return 'kg';
        }
        return $this->weight_dimension;
    }

    protected function calculate()
    {
        try {
            $query = $this->prepareQuery();
//             $this->dumpXml($query[0]);
//             $this->dumpXml($query[1]);
            $query = implode('', $query);
            $answer = $this->sendQuery($query);
            $parsed_answer = $this->parseAnswer($answer);
        } catch (Exception $e) {
            $error = $e->getMessage();
            //$this->log($error);
            return $error;
        }

        $rates = array();
        foreach ($parsed_answer as $code => $items) {
            foreach ($items as $k => $item) {
                $item['id'] = sprintf("%s%02d", $code, $k);
                if (!isset($item['currency'])) {
                    $item['currency'] = $this->currency;
                }
                $rates[$item['id']] = $item;
            }
        }
        if (empty($rates)) {
            return $this->_w("UPS web service return an empty response");
        }

        return $rates;
    }

    // XXX: for debug reasons
    private function dumpXml($xml)
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->loadXML($xml);
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;
        echo $doc->saveXML();
    }

    private function getServiceByCode($code){
        $services = $this->getShippingServices();
        return isset($services[$code]) ? $services[$code] : null;
    }

    /**
     * @throws waException
     * @return string
     */
    private function prepareQuery()
    {
        if (!$this->zip) {
            throw new waException(
                $this->_w(
                    "Cannot calculate shipping rate because origin (sender's) ZIP code is not defined in UPS module settings"
                )
            );
        }
        if (!$this->city) {
            throw new waException(
                $this->_w(
                    "Cannot calculate shipping rate because origin (sender's) city is not defined in UPS module settings"
                )
            );
        }
        if (!$this->country) {
            throw new waException(
                $this->_w(
                    "Cannot calculate shipping rate because origin (sender's) country is not defined in UPS module settings"
                )
            );
        }

        $weight = max(0.1, $this->getTotalWeight());

        $customer_type = '';
        if ($this->customer_type) {
            $customer_type = $this->customer_type;
        } else if ($this->pickup_type == 11) {
            $customer_type = '04';
        }

        $zip  = $this->getAddress('zip');
        if (preg_match('/([\d]{1,5})/', $zip, $m)) {
            $zip = $m[1];
        }
        if (!$zip) {
            throw new waException(
                $this->_w("Enter ZIP code to get shipping estimate")
            );
        }
        $city = $this->getAddress('city');
        if (!$city) {
            throw new waException(
                $this->_w("Enter city to get shipping estimate")
            );
        }
        $country = $this->getAddress('country');
        if (!$country) {
            throw new waException(
                $this->_w("Enter country to get shipping estimate")
            );
        }


        $xmls = array();

        // access request xml
        $xml = new SimpleXMLElement('<AccessRequest/>');
        $xml->addAttribute('xml:lang', 'en-US', 'http://www.w3.org/1999/xhtml');
        $xml->addChild('AccessLicenseNumber', $this->access_key);
        $xml->addChild('UserId', $this->user_id);
        $xml->addChild('Password', $this->password);
        $xmls[] = $xml->saveXML();

        // ratings of services request
        $xml = new SimpleXMLElement('<RatingServiceSelectionRequest/>');

        // RatingServiceSelectionRequest/Request
        $request = $xml->addChild('Request');
        $request->addChild('RequestAction', 'Rate');
        $request->addChild('RequestOption', 'Shop');

        $xml->addChild('PickupType')->
                addChild('Code', $this->pickup_type);
        if ($customer_type) {
            $xml->addChild('CustomerClassification')->
                addChild('Code', $customer_type);
        }

        // RatingServiceSelectionRequest/Shipment
        $shipment = $xml->addChild('Shipment');

        // RatingServiceSelectionRequest/Shipment/Shipper
        $shipper = $shipment->addChild('Shipper');
        $address = $shipper->addChild('Address');
        $address->addChild('PostalCode', $this->zip);
        $address->addChild('City', $this->city);
        $address->addChild('CountryCode', $this->getISO2CountryCode($this->country));

        // RatingServiceSelectionRequest/Shipment/ShipTo
        $shipto = $shipment->addChild('ShipTo');
        $address = $shipto->addChild('Address');
        $address->addChild('PostalCode', $zip);
        $address->addChild('City', $city);
        $address->addChild('CountryCode', $this->getISO2CountryCode($country));

        // RatingServiceSelectionRequest/Shipment/Package
        $package = $shipment->addChild('Package');
        $package->addChild('PackagingType')->
                    addChild('Code', $this->package_type);
        $package_weight = $package->addChild('PackageWeight');
        $package_weight->addChild('UnitOfMeasurement')->
                    addChild('Code', strtoupper($this->weight_dimension));
        $package_weight->addChild('Weight', str_replace(',', '.', $weight));

        $xmls[] = $xml->saveXML();

        return $xmls;
    }

    private function sendQuery($xml)
    {
        if (!$xml) {
            throw new waException($this->_w("Empty query"));
        }

        if(!extension_loaded('curl')) {
            throw new waException($this->_w('Curl extension not loaded'));
        }

        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new waException($this->_w("Can't init curl"));
        }

        if (curl_errno($ch) != 0) {
            $error = $this->_w("Can't init curl");
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch);
            throw new waException($error);
        }
        
        $url = 'https://onlinetools.ups.com/ups.app/xml/Rate';
        @curl_setopt($ch, CURLOPT_URL, $url );
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        //initCurlProxySettings($ch);

        $result = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $error = $this->_w("Curl executing error");
            $error .= ": ".curl_errno($ch) . " - " . curl_error($ch).". Url: {$url}";
            throw new waException($error);
        }

        curl_close($ch);

        return $result;
    }

    public function parseAnswer($answer)
    {
        if (!$answer) {
            throw new waException($this->_w("Empty answer"));
        }

        $rates = array();
        try {
            $xml = new SimpleXMLElement($answer);
        } catch (Exception $ex) {
            throw new waException($this->_w("Xml isn't well-formed"));
        }

        foreach ($xml->xpath('RatedShipment') as $shipment) {
            $rate = array();
            $comment = (string)$shipment->RatedShipmentWarning;
            if ($comment) {
                $rate['comment'] = $comment;
            }
            $code = (string) $shipment->Service->Code;
            $service = $this->getServiceByCode($code);
            $rate['name'] = $service['name'];

            $rate['rate'] = (string) $shipment->TotalCharges->MonetaryValue;
            $rate['currency'] = (string) $shipment->TotalCharges->CurrencyCode;

            $delivary = (string) $shipment->ScheduledDeliveryTime;
            $rate['est_delivery'] = $delivary ? $delivary : '';

            $rates[$code][] = $rate;
        }

        foreach ($xml->xpath('Response/Error') as $error) {
            $rates["error"][] = array(
                'rate' => null,
                'comment' => (string) $error->ErrorDescription,
                'est_delivery' => ''
            );
        }

        return $rates;
    }

    /**
     * @param string $iso3
     * @throws waException
     * @return string
     */
    private function getISO2CountryCode($iso3)
    {
        $country_model = new waCountryModel();
        $iso2 = $country_model->select('iso2letter')->where(
            'iso3letter = :iso3', array('iso3' => $iso3)
        )->fetchField('iso2letter');
        if (!$iso2) {
            throw new waException($this->_w("Unknown country"));
        }
        return $iso2;
    }

    public static function getPackageTypes()
    {
        return array(
            array(
                'title' => 'UPS letter/ UPS Express Envelope',
                'value' => '01'
            ),
            array(
                'title' => 'Customer package',
                'value' => '02'
            ),
            array(
                'title' => 'UPS Tube',
                'value' => '03'
            ),
            array(
                'title' => 'UPS Pak',
                'value' => '04'
            ),
            array(
                'title' => 'UPS Express Box',
                'value' => '21'
            ),
            array(
                'title' => 'UPS 25Kg Box',
                'value' => '24'
            ),
            array(
                'title' => 'UPS 10Kg Box',
                'value' => '25'
            ),
        );
    }

    public static function getPickupTypes()
    {
        return array(
            array(
                'title' => 'Daily Pickup',
                'value' => '01',
            ),
            array(
                'title' => 'Customer Counter',
                'value' => '03',
            ),
            array(
                'title' => 'One Time Pickup',
                'value' => '06',
            ),
            array(
                'title' => 'On Call Air Pickup',
                'value' => '07',
            ),
            array(
                'title' => 'Suggested Retail Rates (UPS Store)',
                'value' => '11',
            ),
            array(
                'title' => 'Letter Center',
                'value' => '19',
            ),
            array(
                'title' => 'Air Service Center',
                'value' => '20',
            ),
        );
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' =>     array('cost' => true),
            'country' => array('cost' => true),
            'city' =>    array('cost' => true)
        );
    }

    private function getShippingServices()
    {
        if (!$this->services) {
            $this->services = include($this->path.'/lib/config/services.php');
        }
        return $this->services;

    }

    private function getShippingZones()
    {
        if (!$this->zones) {
            $this->zones = include($this->path.'/lib/config/zones.php');
        }
        return $this->zones;
    }
}
