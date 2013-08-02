<?php

/**
 * @property string $test_mode
 * @property string $customer_number
 * @property string $test_key_number
 * @property string $key_number
 * @property string $zip
 */
class canadapostShipping extends waShipping
{
    private $key_numbers = array();

    private $currency = 'CAD';
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

    /**
     *
     */
    protected function calculate()
    {
        $request = $this->prepareRequest();
        $url = $this->getUrl();
        $response = $this->sendRequest($url, $request);
        return $this->parseResponse($response);
    }
    
    protected function getTotalWeight() {
        $w = parent::getTotalWeight();
        if ($w === null) {
            return $this->min_weight;
        } else {
            return max($w, $this->min_weight);
        }
    }
    
    public function __get($name)
    {
        if ($name == 'zip') {
            return $this->strip($this->getSettings($name));
        } else {
            return $this->getSettings($name);
        }
    }
    
    public function strip($zip)
    {
        return str_replace(' ', '', $zip);
    }

    /**
     * @return string
     * @throws waException
     */
    protected function prepareRequest()
    {
        if (!$this->zip) {
            throw new waException(
                $this->_w("Cannot calculate shipping rate because origin (sender's) ZIP code is not defined in Canada Post module settings.")
            );
        }

        $country = $this->getAddress('country');
        $xml = new SimpleXMLElement('<mailing-scenario></mailing-scenario>');
        $xml->addAttribute('xmlns', "http://www.canadapost.ca/ws/ship/rate-v2");
        $xml->addChild('customer-number', $this->customer_number);
        $xml->addChild('parcel-characteristics')->
            addChild('weight', number_format($this->getTotalWeight(), 2));
        $xml->addChild('origin-postal-code', $this->zip);

        $destination = $xml->addChild('destination');

        $country = strtolower($country);
        if (!$country) {
                throw new waException($this->_w(
                        "Select country to get shipping estimate"
                ));
        }
        if ($country == 'can' || $country == 'usa') {
            $zip = $this->getAddress('zip');
            $zip = $this->strip($zip);
            if (!$zip) {
                throw new waException($this->_w(
                    "Enter ZIP code to get shipping estimate."
                ));
            }
            if ($country == 'can') {
                $destination->addChild('domestic')->
                    addChild('postal-code', $zip);
            } else {
                $destination->addChild('united-states')->
                    addChild('zip-code', $zip);
            }
        } else {
            $destination->addChild('international')->
                addChild('country-code', $this->getCountryISO2Code($country));
        }

        return $xml->saveXML();
    }

    protected function getUrl()
    {
        $url = "https://%s/rs/ship/price";
        if ($this->test_mode) {
            return sprintf($url, "ct.soa-gw.canadapost.ca");
        } else {
            return sprintf($url, "soa-gw.canadapost.ca");
        }
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
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  0);
        @curl_setopt($ch, CURLOPT_TIMEOUT,        10);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_URL, $url);

        @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.cpc.ship.rate-v2+xml',
            'Content-Type: application/vnd.cpc.ship.rate-v2+xml',
            'Authorization: Basic ' . base64_encode(
                $this->test_mode ? $this->test_key_number : $this->key_number
            )
        ));

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
        $xml = new SimpleXMLElement($response);
        
        $ok = true;
        foreach ($xml->getDocNamespaces() as $ns) {
            if ($ns == 'http://www.canadapost.ca/ws/messages') {
                $ok = false;
                break;
            }
        }
        
        if ($ok) {

            $xml->registerXPathNamespace('c', 'http://www.canadapost.ca/ws/ship/rate-v2');

            $rates = array();
            foreach ($xml->xpath('//c:price-quote') as $price_quote) {
                $id = (string) $price_quote->{'service-code'};
                $id = str_replace('.', '_', $id);
                $est_delivery = (string) $price_quote->{'service-standard'}->{'expected-transit-time'};
                $rates[$id] = array(
                    'id' => $id,
                    'name' => (string) $price_quote->{'service-name'},
                    'currency' => $this->currency,
                    'rate' => (string) $price_quote->{'price-details'}->base,
                    'est_delivery' => $est_delivery ? ($est_delivery . ' day(s)') : '',
                );
            }

            return $rates;
            
        } else {
            $xml->registerXPathNamespace('c', 'http://www.canadapost.ca/ws/messages');

            $messages = array();
            foreach ($xml->xpath('//c:message') as $message) {
                $messages[] = (string) $message->description;
            }
            
            return htmlspecialchars(implode("\n", $messages));
            
        }
    }

    public function requestedAddressFields()
    {
        return array(
            'zip' => array('cost' => true),
            'country' => array('cost' => true)
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
