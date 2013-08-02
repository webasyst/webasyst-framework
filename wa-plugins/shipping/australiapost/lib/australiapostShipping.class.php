<?php


/**
 * @property string $test_mode
 * @property string $api_key
 * @property string $zip
 * @property string $length
 * @property string $height
 * @property string $width
 */
class australiapostShipping extends waShipping
{
    /**
     * @var string
     */
    private $currency = 'AUD';
    
    /**
     *
     * @var float
     */
    private $min_weight = 0.1;

    /**
     *
     * @return string ISO3 currency code or array of ISO3 codes
     */
    public function allowedCurrency()
    {
        return $this->currency;
    }

    /**
     *
     * @return string Weight units or array of weight units
     */
    public function allowedWeightUnit()
    {
        return 'kg';
    }

    protected function getTotalWeight() {
        $w = parent::getTotalWeight();
        if ($w === null) {
            return $this->min_weight;
        } else {
            return max($w, $this->min_weight);
        }
    }
    
    /**
     * @return array|string
     */
    protected function calculate()
    {
        $request = $this->prepareRequest();
        $url = $this->getUrl();
        $response = $this->sendRequest($url, $request);
        return $this->parseResponse($response);
    }

    /**
     * @param $response
     * @return array|string
     */
    protected function parseResponse($response)
    {
        $xml = new SimpleXMLElement($response);

        if ($xml->errorMessage) {
            return (string) $xml->errorMessage;
        }

        $rates = array();
        foreach ($xml->xpath('service') as $service) {
            $item = array(
                'id' => (string) $service->code,
                'name' => (string) $service->name,
                'rate' => (string) $service->price,
                'est_delivery' => '',
                'currency' => $this->currency
            );
            $rates[$item['id']] = $item;
        }

        return $rates;
    }

    /**
     * @param string $url
     * @param string $request
     * @return string
     * @throws waException
     */
    protected function sendRequest($url, $request = '')
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
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  0);
        @curl_setopt($ch, CURLOPT_TIMEOUT,        15);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_URL, $url . ($request ? '?'.$request : ''));
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'AUTH-KEY: ' . $this->api_key
        ));

        $result = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $error = $this->_w("Curl executing error");
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
            throw new waException($error);
        }

        curl_close($ch);

        return $result;
    }

    /**
     * @return string
     * @throws waException
     */
    protected function prepareRequest()
    {
        $country = $this->getAddress('country');
        if (strtolower($country) == 'aus') {
            return $this->prepareDomesticRequest();
        } else {
            return $this->prepareInternationalRequest();
        }
    }

    /**
     * @return string
     * @throws waException
     */
    protected function prepareDomesticRequest()
    {
        if (!$this->zip) {
            throw new waException(
                $this->_w("Cannot calculate shipping rate because origin (sender's) ZIP code is not defined in Australia Post module settings.")
            );
        }
        $zip = $this->getAddress('zip');
        if (preg_match('/([\d]{1,5})/', $zip, $m)) {
            $zip = $m[1];
        }
        if (!$zip) {
            throw new waException($this->_w(
                "Enter ZIP code to get shipping estimate"
            ));
        }

        $params = array();
        $params['from_postcode'] = $this->zip;
        $params['to_postcode'] = $zip;
        $params['length'] = min(1, $this->length);
        $params['width']  = min(1, $this->width);
        $params['height'] = min(1, $this->height);
        $params['weight'] = number_format(
                min(0.1, $this->getTotalWeight()), 2
        );

        return http_build_query($params);

    }

    /**
     * @return string
     * @throws waException
     */
    protected function prepareInternationalRequest()
    {
        $params = array();
        $country = $this->getAddress('country');
        if (!$country) {
            throw new waException($this->_w("Select country to get shipping estimate"));
        }
        $params['country_code'] = $this->getCountryISO2Code($country);
        $params['weight'] = number_format(
                min(max(0.1, $this->getTotalWeight()), 20), 2
        );
        
        return http_build_query($params);
    }

    /**
     * @return array
     */
    public function requestedAddressFields()
    {
        return array(
            'zip' => array('cost' => true),
            'country' => array('cost' => true)
        );
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        $url = $this->test_mode ? 'https://test.npe.auspost.com.au' : 'https://auspost.com.au';
        $country = $this->getAddress('country');
        if (strtolower($country) == 'aus') {
            return $url . '/api/postage/parcel/domestic/service.xml';
        } else {
            return $url . '/api/postage/parcel/international/service.xml';
        }
    }

    public function __get($name)
    {
        if ($name == 'api_key' && $this->test_mode) {
            return '28744ed5982391881611cca6cf5c2409';
        }
        return $this->getSettings($name);
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
