<?php

class teamOffice365Curl
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
             CURLOPT_HEADER => 1,       // don't change it!
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

        $response = $this->parseBody($body);

        $result = curl_getinfo($ch);

        $result['response'] = $response;
        $result['body'] = $result['response']['body'];

        $result['body'] = @json_decode($result['body'], true);
        if ($result['body'] === null && $result['response']['body']) {
            $result['body'] = array(
                'error' => array('.tag' => 'error'),
                'message' => $result['response']['body']
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

    private function parseBody($body)
    {
        $chunks = array();

        $len = strlen($body);
        $offset = 0;
        while ($offset <= $len) {
            $start = strpos($body, 'HTTP/1.1', $offset);
            if ($start === false) {
                break;
            }
            $end = strpos($body, "HTTP/1.1", $start + 1);
            if ($end === false || $end < $start) {
                $chunk = substr($body, $start, $len - $start);
                $offset = $len;
            } else {
                $chunk = substr($body, $start, $end - $start);
                $offset = $end;
            }
            $chunks[] = $chunk;
        }

        $empty_response = array(
            'status' => '',
            'headers' => array(),
            'body' => ''
        );
        $responses = array();

        foreach ($chunks as $chunk) {

            $response = $empty_response;

            $lines = array();
            foreach (explode("\n", $chunk) as $line) {
                $lines[] = trim($line);
            }

            $count = count($lines);
            if ($count <= 0) {
                continue;
            }

            $response['status'] = array_shift($lines);
            $count -= 1;

            while ($count > 0) {
                $line = array_shift($lines);
                $count -= 1;
                if (!$line) {
                    break;
                }
                $response['headers'][] = $line;
            }

            $response['body'] = (string) join("\n", $lines);

            $responses[] = $response;
        }

        if (!$responses) {
            $responses[] = $empty_response;
        }

        return array_pop($responses);
    }
}
