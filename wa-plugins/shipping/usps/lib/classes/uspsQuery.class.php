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
     * @param array $params params from uspsShipping
     */
    public function __construct(uspsShipping $plugin, array $params)
    {
        $this->plugin = $plugin;
        $this->params = $params;
    }

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

        $url = $this->getUrl().'&XML='.urlencode($request);

        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        @curl_setopt($ch, CURLOPT_HEADER, 0);

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
     * @return array|string
     */
    protected function parseResponse($response) {
        if (!$response) {
            throw new waException($this->plugin->_w("Empty response"));
        }
    }

    protected function getWeight($unit = null)
    {
        return ($unit === null) ?
        $this->params['weight'] : (
                isset($this->params['weight'][$unit]) ?
                $this->params['weight'][$unit] :
                null
        );
    }

    protected function getAddress($field = null)
    {
        return ($field === null) ?
        $this->params['address'] : (
                isset($this->params['address'][$field]) ?
                $this->params['address'][$field] :
                null
        );
    }

    abstract protected function getUrl();

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