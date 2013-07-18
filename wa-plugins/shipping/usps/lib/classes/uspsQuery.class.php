<?php

abstract class uspsQuery
{
    /**
     * @var uspsShipping
     */
    protected $plugin;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param uspsShipping $plugin
     * @param array $params params from uspsShipping
     */
    public function __construct(uspsShipping $plugin, array $params)
    {
        $this->plugin = $plugin;
        $this->params = $params;
    }

    /**
     * @return array|string
     */
    public function execute()
    {
        return $this->parseResponse(
            $this->sendRequest(
                $this->prepareRequest()
            )
        );
    }

    /**
     * @return $string
     */
    abstract protected function prepareRequest();

    /**
     * @param string $request
     * @throws waException
     * @return string
     */
    protected function sendRequest($request)
    {
        if (!$request) {
            throw new waException($this->plugin->_w("Empty request"));
        }

        if (!extension_loaded('curl')) {
            throw new waException($this->plugin->_w('Curl extension not loaded'));
        }

        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new waException($this->plugin->_w("Can't init curl"));
        }

        if (curl_errno($ch) != 0) {
            $error = $this->plugin->_w("Can't init curl");
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch);
            throw new waException($error);
        }

        $url = $this->getUrl();
        $url .= '?API=' . $this->getAPIName() . '&XML='.urlencode($request);

        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  0);
        @curl_setopt($ch, CURLOPT_TIMEOUT,        10);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_URL, $url);

        $result = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $error = $this->plugin->_w("Curl executing error");
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
            throw new waException($error);
        }

        curl_close($ch);

        return $result;
    }

    /**
     * @param string $response
     * @throws waException
     * @return array|string
     */
    protected function parseResponse($response) {
        if (!$response) {
            throw new waException($this->plugin->_w("Empty response"));
        }
    }

    /**
     * @param double $weight
     * @return array
     */
    protected function parseWeight($weight)
    {
        $weight = str_replace(',', '.', $weight);
        $pounds = floor($weight * 0.0625);
        $ounces = $weight - $pounds * 16;
        return array(
            'pounds' => $pounds,
            'ounces' => $ounces
        );
    }

    /**
     * @param string|null $unit
     * @return string|null
     */
    protected function getWeight($unit = null)
    {
        if (!is_array($this->params['weight'])) {
            $this->params['weight'] = $this->parseWeight($this->params['weight']);
        }
        return ($unit === null) ?
            $this->params['weight'] : (
                isset($this->params['weight'][$unit]) ?
                $this->params['weight'][$unit] :
                null
        );
    }

    protected function getPrice()
    {
        return round(isset($this->params['price']) ? $this->params['price'] : 0, 2);
    }

    /**
     * @param string|null $field
     * @return string|null
     */
    protected function getAddress($field = null)
    {
        if ($field === null) {
            return $this->params['address'];
        } else {
            $param = isset($this->params['address'][$field]) ?
                $this->params['address'][$field] :
                null;
            if ($param !== null && $field == 'name') {
                return substr($param, 0, 26);
            }
            return $param;
        }
    }

    /**
     * @param array $address
     */
    protected function setAddress($address)
    {
        $this->params['address'] = $address;
    }

    /**
     * @return string
     */
    abstract protected function getAPIName();

    /**
     * @return string
     */
    abstract protected function getUrl();

    /**
     * @param string $code iso3 code
     * @throws waException
     * @return string
     */
    protected function getCountryName($code) {
        $country_model = new waCountryModel();
        $country = $country_model->get($code);
        if (!$country) {
            throw new waException($this->plugin->_w("Unknown country: "). $code);
        }
        $iso2letter = strtoupper($country['iso2letter']);
        $country_list = $this->getCountryList();
        if (!isset($country_list[$iso2letter])) {
            throw new waException($this->plugin->_w("Unknown country: "). $code);
        }
        return $country_list[$iso2letter];
    }

    /**
     * @return array
     */
    protected function getCountryList()
    {
        return include($this->plugin->getPluginPath().'/lib/config/countries.php');
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
