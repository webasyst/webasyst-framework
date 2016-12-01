<?php

class teamCaldavCurl
{
    private $options;

    public function __construct($options = array())
    {
        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new waException("Can't init curl");
        }

        if (curl_errno($ch) != 0) {
            $error = "Can't init curl";
            $error .= ": " . curl_errno($ch) . " - " . curl_error($ch);
            throw new waException($error);
        }

        foreach (array(
             CURLOPT_CONNECTTIMEOUT => 10,
             CURLOPT_RETURNTRANSFER => 1,
             CURLOPT_SSL_VERIFYPEER => 0,
             CURLOPT_TIMEOUT => 10,
             CURLOPT_HEADER => 0
         ) as $option => $value) {
            if (!isset($options[$option])) {
                $options[$option] = $value;
            }
        }

        $this->options = $options;
    }

    /**
     * Get method, load json data
     * @param string $url
     * @param array $options
     * @return array json encoded data
     * @throws waException
     */
    public function get($url, $options = array())
    {
        $options[CURLOPT_URL] = $url;
        $ch = $this->getCurl($options);
        $result = $this->executeCurl($ch);
        return $result;
    }

    public function options($url, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = "OPTIONS";
        $options[CURLOPT_URL] = $url;
        $options[CURLINFO_HEADER_OUT] = true;
        $options[CURLOPT_VERBOSE] = true;
        $options[CURLOPT_HEADER] = true;
        $ch = $this->getCurl($options);
        $res = $this->executeCurl($ch);

        $request_headers = array();
        foreach (explode("\n", ifset($res['request_header'], '')) as $header) {
            $header = trim($header);
            if ($header) {
                $request_headers[] = $header;
            }
        }
        unset($res['request_header']);
        $res['request_headers'] = $request_headers;

        $body = $res['body'];
        unset($res['body']);

        $body['message'] = ifset($body['message']);
        $response_headers = array();
        foreach (explode("\n", $body['message']) as $header) {
            $header = trim($header);
            if ($header) {
                $response_headers[] = $header;
            }
        }
        $res['response_headers'] = $response_headers;

        return $res;
    }

    public function propfind($url, $xml, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = "PROPFIND";
        $options[CURLOPT_URL] = $url;
        if (empty($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = array();
        }
        //$options[CURLOPT_HTTPHEADER][] = 'Depth: 1';
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/xml; charset=utf-8';
        $options[CURLOPT_POSTFIELDS] = $xml;

        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    public function report($url, $xml, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = "REPORT";
        $options[CURLOPT_URL] = $url;
        if (empty($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = array();
        }
        //$options[CURLOPT_HTTPHEADER][] = 'Depth: 0';
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/xml; charset=utf-8';
        $options[CURLOPT_POSTFIELDS] = $xml;

        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    /**
     * Delete method
     * @param string $url
     * @param array $options
     * @return array associative array
     * @see executeCurl for returned array format
     */
    public function delete($url, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = "DELETE";
        $options[CURLOPT_URL] = $url;
        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    public function patch($url, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $options[CURLOPT_URL] = $url;
        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    /**
     * Put method
     * @param string $url
     * @param array $options
     * @return array associative array
     * @see executeCurl for returned array format
     */
    public function put($url, $options = array())
    {
        $options[CURLOPT_PUT] = true;
        $options[CURLOPT_URL] = $url;
        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    /**
     * Post method
     * @param string $url
     * @param array $options
     * @return array associative array
     * @see executeCurl for returned array format
     */
    public function post($url, $options = array())
    {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_URL] = $url;
        $ch = $this->getCurl($options);
        return $this->executeCurl($ch);
    }

    /**
     * Post method with application/json content type
     * @param $url
     * @param $options
     * @return array
     */
    public function jsonPost($url, $options)
    {
        if (empty($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = array();
        }
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        return $this->post($url, $options);
    }

    /**
     * Curl
     * @param array
     * @return resource (curl)
     */
    private function getCurl($options = array())
    {
        $ch = curl_init();

        foreach ($this->options as $option => $value) {
            if (!isset($options[$option])) {
                $options[$option] = $value;
            } elseif ($option === CURLOPT_HTTPHEADER) {
                $options[$option] = array_merge((array) $value, $options[$option]);
            }
        }

        foreach ($options as $option => $value) {
            @curl_setopt($ch, $option, $value);
        }
        return $ch;
    }

    /**
     * @param resource (curl) $ch
     * @return array associative array, all the same keys as returned by curl_getinfo, plus key 'body'
     */
    private function executeCurl($ch, $close = true)
    {
        $body = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $body = '';
        }
        $result = curl_getinfo($ch);
        $result['body'] = @json_decode($body, true);
        if ($result['body'] === null && $body) {
            $result['body'] = array(
                'error' => array('.tag' => 'error'),
                'message' => $body
            );
        }
        if ($close) {
            curl_close($ch);
        }
        if (!isset($result['http_code'])) {
            $result['http_code'] = '';
        }
        return $result;
    }
}
