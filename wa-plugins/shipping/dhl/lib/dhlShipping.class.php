<?php

/**
 * @property-read string $test_mode
 * @property-read string $login
 * @property-read string $password
 * @property-read array $region_zone
 * @property-read string $zip
 * @property-read string $package_type
 * @property-read string $product_code
 */
class dhlShipping extends waShipping
{
    /**
     *
     * @var array
     */
    private $countries;
    
    /**
     *
     * @var string
     */
    private $currency = 'USD';
    
    /**
     *
     * @var float
     */
    private $min_weight = 0.1;
    
    protected function initControls()
    {
        $this->registerControl('RegionZoneControl');
        parent::initControls();
    }
    
    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return 'lbs';
    }

    public function getSettingsHTML($params = array())
    {
        $options = array();
        foreach ($this->getServices() as $service) {
            $options[] = array(
                'value' => $service['id'],
                'title' => $service['name']
            );
        }
        $params['options']['product_code'] = $options;

        return parent::getSettingsHTML($params);
    }
    
    public function calculate()
    {
        $request = $this->prepareRequest();
        $url = $this->getUrl();
        $response = $this->sendRequest($url, $request);
        return $this->parseResponse($response);
    }
    
    protected function getUrl()
    {
        if ($this->test_mode) {
            return 'http://xmlpitest-ea.dhl.com/XMLShippingServlet';
        } else {
            return 'https://xmlpi-ea.dhl.com/XMLShippingServlet';
        }
    }
    
    public function __get($name) {
        $test_mode = $this->getSettings('test_mode');
        if ($test_mode && $name == 'login') {
            return 'DServiceVal';
        } else if ($test_mode && $name == 'password') {
            return 'testServVal';
        } else {
            return $this->getSettings($name);
        }
    }
    
    protected function getItems() {
        $items = parent::getItems();
        foreach ($items as &$item) {
            if (empty($item['weight'])) {
                $item['weight'] = $this->min_weight;
            }
        }
        $this->setItems($items);
        return $items;
    }

    protected function prepareRequest()
    {
        $zip = $this->getAddress('zip');
        if (!$zip) {
            throw new waException($this->_w(
                    "Enter ZIP code to get shipping estimate"
            ));
        }
        
        $xml = new SimpleXMLElement(
                '<xmlns:req:ShipmentBookRatingRequest></xmlns:req:ShipmentBookRatingRequest>', 
                LIBXML_NOERROR, 
                false, 
                'req', 
                true
        );
        $xml->addAttribute('xmlns:xmlns:req', 'http://www.dhl.com');
        $xml->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xmlns:xsi:schemaLocation', 'http://www.dhl.com ship-book-rate-req.xsd');
        
        $request = $xml->addChild('Request');
        
        $header = $request->addChild('ServiceHeader');
        $header->addChild('MessageTime', date('Y-m-d\TH:i:sP'));
        $header->addChild('MessageReference', '123456789012345678901234567890');
        $header->addChild('SiteID', $this->login);
        $header->addChild('Password', $this->password);
        
        $countries = $this->getAvailableCounries();
        $shipper = $xml->addChild('Shipper');
        $shipper->addChild('City', $this->region_zone['city']);
        
        $country = strtoupper(
                $this->getCountryISO2Code($this->region_zone['country'])
        );
        if (empty($countries[$country])) {
            throw new waException("Country $country is not available");
        }
        
        if ($countries[$country]['use_division']) {
            $shipper->addChild('Division', $this->region_zone['region']);
            $shipper->addChild('PostalCode', $this->zip);
        }
        $shipper->addChild('CountryCode', $country);
        
        $consignee = $xml->addChild('Consignee');
        
        $country = $this->getCountryISO2Code($this->getAddress('country'));
        
        $consignee->addChild('City', $this->getAddress('city'));
        if ($countries[$country]['use_division']) {
            $consignee->addChild('Division', $this->getAddress('region'));
            $consignee->addChild('PostalCode', $this->getAddress('zip'));
        }
        $consignee->addChild('CountryCode', $country);
        
        $details = $xml->addChild('ShipmentDetails');
        
        $items = $this->getItems();
        $details->addChild('NumberOfPieces', count($items));
        $pieces = $details->addChild('Pieces');
        $weight = 0;
        $i = 1;
        foreach ($this->getItems() as $item) {
            $piece = $pieces->addChild('Piece');
            $piece->addChild('PieceID', $i++);
            $piece->addChild('PackageType', $this->package_type);
            $piece->addChild('Weight', str_replace(',', '.', round($item['weight'], 1)));
            $weight += round($item['weight'], 1);
        }
        $details->addChild('WeightUnit', 'L');
        $details->addChild('Weight', str_replace(',', '.', $weight));
        
        $details->addChild('ProductCode', $this->product_code);
        
        return $xml->saveXML();
        
    }
    
    protected function getAvailableCounries()
    {
        if (!$this->countries) {
            $this->countries = include($this->path.'/lib/config/countries.php');
        }
        return $this->countries;
    }
    
    protected function getServices()
    {
        if (!$this->services) {
            $this->services = include($this->path.'/lib/config/services.php');
        }
        return $this->services;
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' => array('cost' => true),
            'country' => array('cost' => true)
        );
    }

    protected function sendRequest($url, $request)
    {
        if (!extension_loaded('curl')) {
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
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  0);
        @curl_setopt($ch, CURLOPT_TIMEOUT,        20);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_POST);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

        $result = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $error = $this->_w("Curl executing error");
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
            throw new waException($error);
        }

        curl_close($ch);

        return $result;
    }
    
    protected function parseResponse($response)
    {
        $xml = new SimpleXmlElement($response);
        $name = $xml->getName();
        if (strpos($name, 'ErrorResponse') !== false) { // ErrorResponse, ShipmentRatingErrorResponse, etc.
	return (string) $xml->Response->Status->Condition->ConditionData;
        }
        if ((string) $xml->Rated != 'Y') {
            return 'Shipment is not rated';
        }
        
        $services = $this->getServices();
        
        return array(
            $this->product_code => array(
                'id' => $this->product_code,
                'currency' => (string) $xml->CurrencyCode,
                'est_delivery' => '',
                'name' => $services[$this->product_code],
                'rate' => (string) $xml->ShippingCharge
            )
        );
    }

    // XXX: for debug reasons
    static protected function dumpXml($xml)
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->loadXML($xml);
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;
        echo $doc->saveXML();
    }

}
