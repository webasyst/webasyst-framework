<?php

/**
 * Class waNet
 * @todo header handler
 * @todo error handler
 * @todo body handler
 *
 * @todo waBrowser class (with cookie support)
 */
class waNet
{
    const TRANSPORT_CURL = 'curl';
    const TRANSPORT_FOPEN = 'fopen';
    const TRANSPORT_SOCKET = 'socket';

    /**
     * @see https://tools.ietf.org/html/rfc2616#section-5.1.1
     */

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_RAW = 'raw';
    const FORMAT_CUSTOM = 'custom';

    protected $user_agent = 'Webasyst Framework';
    protected $accept_cookies = false;
    protected $cookies = null;
    protected $request_headers = array();
    protected $options = array(
        'timeout'        => 15,
        'format'         => null,
        'charset'        => 'utf-8',
        'verify'         => true,
        'md5'            => false,
        'log'            => false,
        'authorization'  => array(),
        'proxy_host'     => null,
        'proxy_port'     => null,
        'proxy_user'     => null,
        'proxy_password' => null,
        'proxy_auth'     => 'basic',
        'priority'       => array(
            'curl',
            'fopen',
            'socket',
        ),
        'ssl'            => array(
            'key'      => '',
            'cert'     => '',
            'password' => '',
        ),

        //TODO add support for auth data
        //auth data
    );

    protected $raw_response;
    protected $decoded_response;

    protected $response_header = array();

    private $ch;

    /**
     * waNet constructor.
     * @param array $options
     * @param array $custom_headers key => value format
     */
    public function __construct($options = array(), $custom_headers = array())
    {
        $this->user_agent = sprintf('Webasyst-Framework/%s', wa()->getVersion('webasyst'));
        //TODO read proxy settings from generic config
        $this->options = array_merge($this->options, $options);
        $this->request_headers = array_merge($this->request_headers, $custom_headers);
    }

    /**
     * @param string $user_agent
     * @return string
     */
    public function userAgent($user_agent = null)
    {
        $current_user_agent = $this->user_agent;
        if ($user_agent != null) {
            $this->user_agent = $user_agent;
        }
        return $current_user_agent;
    }

    public function cookies($path)
    {
        $this->accept_cookies = true;
        $this->cookies = $path;
    }

    public function sendFile($url, $path)
    {

    }

    public function query($url, $content = array(), $method = self::METHOD_GET)
    {
        $transport = $this->getTransport($url);

        $this->buildRequest($url, $content, $method);
        $this->startQuery();

        switch ($transport) {
            case self::TRANSPORT_CURL:
                $response = $this->runCurl($url, $content, $method);
                break;
            case self::TRANSPORT_FOPEN:
                $response = $this->runStreamContext($url, $content, $method);
                break;
            case self::TRANSPORT_SOCKET:
                $response = $this->runSocketContext($url, $content, $method);
                break;
            default:
                throw new waException('There no suitable network transports', 500);
                break;
        }

        $this->decodeResponse($response);

        if (empty($this->response_header['http_code']) || ($this->response_header['http_code'] != 200)) {
            throw new waException($response, $this->response_header['http_code']);
        }

        return $this->getResponse();
    }

    protected function buildRequest(&$url, &$content, &$method)
    {
        if (!$content) {
            $content = self::getPost($url);
        }

        if ($content) {
            if ($method == self::METHOD_GET) {
                $method = self::METHOD_POST;
            }
            $content = $this->encodeRequest($content);
        }
    }

    protected function buildHeaders($transport, $raw = true)
    {
        $this->request_headers['Connection'] = 'close';
        $this->request_headers['Date'] = date('c');
        switch ($this->options['format']) {
            case self::FORMAT_JSON:
                $this->request_headers["Accept"] = "application/json";
                break;

            case self::FORMAT_XML:
                $this->request_headers["Accept"] = "text/xml";
                break;

            default:
                $this->request_headers['Accept'] = '*/*';
                break;
        }

        $this->request_headers['Accept-Charset'] = $this->options['charset'];

        /**
         * Accept
         * | Accept-Charset           ; Section 14.2
         * | Accept-Encoding          ; Section 14.3
         * | Accept-Language          ; Section 14.4
         * | Authorization            ; Section 14.8
         * | Expect                   ; Section 14.20
         * | From                     ; Section 14.22
         * | Host                     ; Section 14.23
         * | If-Match                 ; Section 14.24
         */

        if (!empty($this->options['authorization'])) {
            $this->request_headers["Authorization"] = "Basic ".urlencode(base64_encode("{$this->options['login']}:{$this->options['password']}"));
        }

        $this->request_headers['User-Agent'] = $this->user_agent;
        $this->request_headers['X-Framework-Method'] = $transport;

        if ($raw) {
            return $this->request_headers;
        } else {
            $headers = array();
            foreach ($this->request_headers as $header => $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
            return $headers;
        }
    }

    /**
     * @param array $content
     * @return string
     */
    protected function encodeRequest($content)
    {
        if (!is_string($content)) {
            switch ($this->options['format']) {
                case self::FORMAT_JSON:
                    $content = json_encode($content);
                    break;
                case self::FORMAT_XML:
                    if (is_object($content)) {
                        $class = get_class($content);
                        if ($class == 'SimpleXMLElement') {
                            /**
                             * @var SimpleXMLElement $content
                             */
                            $content = (string)$content->asXML();
                        } elseif ($class == 'DOMDocument') {
                            /**
                             * @var DOMDocument $content
                             */
                            if (!empty($this->options['charset'])) {
                                $content->encoding = $this->options['charset'];
                            }
                            $content->preserveWhiteSpace = false;
                            $content = (string)$content->saveXML();
                        }
                    }
                    break;
                default:
                    $content = http_build_query($content);
                    break;
            }
        }

        $this->request_headers['Content-Length'] = strlen($content);
        switch ($this->options['format']) {
            case self::FORMAT_JSON:
                $this->request_headers['Content-Type'] = 'application/json';
                break;

            case self::FORMAT_XML:
                $this->request_headers['Content-Type'] = 'application/xml';
                break;
            case self::FORMAT_CUSTOM:
                //$this->request_headers['Content-Type'] ='application/'.$this->options['custom_content_type'];
                break;
            default:
                $this->request_headers['Content-Type'] = 'application/x-www-form-urlencoded';
                break;
        }
        if (!empty($this->options['md5'])) {
            $this->request_headers['Content-MD5'] = base64_encode(md5($content, true));
        }
        return $content;
    }

    protected function decodeResponse($response)
    {
        $this->raw_response = $response;
        $this->decoded_response = null;
        switch ($this->options['format']) {
            case self::FORMAT_JSON:
                $this->decoded_response = @json_decode($this->raw_response, true);
                if (function_exists('json_last_error')) {
                    if (JSON_ERROR_NONE !== json_last_error()) {
                        if (function_exists('json_last_error_msg')) {
                            $message = json_last_error_msg();
                        } else {
                            $message = json_last_error();
                            $codes = array(
                                'JSON_ERROR_DEPTH'            => 'The maximum stack depth has been exceeded',
                                'JSON_ERROR_STATE_MISMATCH'   => 'Invalid or malformed JSON',
                                'JSON_ERROR_CTRL_CHAR'        => 'Control character error, possibly incorrectly encoded',
                                'JSON_ERROR_SYNTAX'           => 'Syntax error',
                                'JSON_ERROR_UTF8'             => 'Malformed UTF-8 characters, possibly incorrectly encoded',//PHP 5.3.3
                                'JSON_ERROR_RECURSION'        => 'One or more recursive references in the value to be encoded',//PHP 5.5.0
                                'JSON_ERROR_INF_OR_NAN'       => 'One or more NAN or INF values in the value to be encoded',//PHP 5.5.0
                                'JSON_ERROR_UNSUPPORTED_TYPE' => 'A value of a type that cannot be encoded was given',//PHP 5.5.0
                            );

                            foreach ($codes as $code => $_message) {
                                if (defined($code) && (constant($code) == $message)) {
                                    $message = $_message;
                                    break;
                                }
                            }

                        }
                        throw new waException('Error while decode JSON response: '.$message);
                    }
                } else {
                    if ($this->decoded_response === null) {
                        throw new waException('Error while decode JSON response');
                    }
                }
                break;
            case self::FORMAT_XML:
                libxml_use_internal_errors(true);
                libxml_clear_errors();
                $this->decoded_response = @simplexml_load_string($this->raw_response);

                if ($this->decoded_response === false) {
                    $error = libxml_get_last_error();
                    /**
                     * @var LibXMLError $error
                     */
                    $this->log($error->message);
                    throw new waException('Error while decode XML response: '.$error->message, $error->code);
                }
                break;
            default:
                $this->decoded_response = $this->raw_response;
                break;
        }
    }

    public function getResponse($raw = false)
    {
        return $raw ? $this->raw_response : $this->decoded_response;
    }

    public function getResponseHeader($header = null)
    {
        if (!empty($header)) {
            $header = str_replace('-', '_', strtolower($header));
            return ifset($this->response_header[$header]);
        }
        return $this->response_header;
    }

    protected function parseHeader($http_response_header)
    {
        foreach ($http_response_header as $header) {
            $t = explode(':', $header, 2);
            if (isset($t[1])) {
                $this->response_header[trim($t[0])] = trim($t[1]);
            } elseif (strlen($header)) {
                $this->response_header[] = $header;
                if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $header, $out)) {
                    $this->response_header['http_code'] = intval($out[1]);
                }
            }
        }
    }

    protected function getTransport($url)
    {
        $available = array();

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (extension_loaded('curl') && function_exists('curl_init')) {
            $available[self::TRANSPORT_CURL] = true;
        } else {
            $hint[] = '';
        }

        if (@ini_get('allow_url_fopen')) {
            if (in_array($scheme, stream_get_wrappers(), true)) {
                $available[self::TRANSPORT_FOPEN] = true;
            } else {
                $hint[] = '';
            }

        } elseif (empty($available)) {
            $hint[] = '';
        }

        if (function_exists('fsockopen')) {
            if (in_array('tcp', stream_get_transports(), true)) {
                $available[self::TRANSPORT_SOCKET] = true;
            } else {
                $hint[] = '';
            }
        } elseif (empty($available)) {
            $hint[] = 'Enable fsockopen';
        }

        foreach ($this->options['priority'] as $transport) {
            if (!empty($available[$transport])) {
                return $transport;
            }
        }

        return false;
    }

    private function runCurl($url, $params, $method, $curl_options = array())
    {
        $this->getCurl($url, $params, $method, $curl_options);
        $response = @curl_exec($this->ch);

        if (empty($response)) {
            $error_no = curl_errno($this->ch);
            $error_str = curl_error($this->ch);
            throw new waException(sprintf('Curl error %d: %s', $error_no, $error_str));
        } else {
            $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
            $this->parseHeader(preg_split('@[\r\n]+@', substr($response, 0, $header_size)));
            $body = substr($response, $header_size);
        }

        return $body;
    }

    private function getCurl($url, $content, $method, $curl_options = array())
    {
        if (extension_loaded('curl') && function_exists('curl_init')) {
            if (empty($this->ch)) {
                if (!($this->ch = curl_init())) {
                    throw new waException(_ws("Error cUrl init"));
                }

                if (curl_errno($this->ch) != 0) {
                    throw new waException(_ws("Error cUrl init").' '.curl_errno($this->ch).' '.curl_error($this->ch));
                }
                if (!is_array($curl_options)) {
                    $curl_options = array();
                }
                $curl_default_options = array(
                    CURLOPT_HEADER            => 1,
                    CURLOPT_RETURNTRANSFER    => 1,
                    CURLOPT_TIMEOUT           => $this->options['timeout'],
                    CURLOPT_CONNECTTIMEOUT    => $this->options['timeout'],
                    CURLE_OPERATION_TIMEOUTED => $this->options['timeout'],
                    CURLOPT_DNS_CACHE_TIMEOUT => 3600,
                    CURLOPT_USERAGENT         => $this->user_agent,
                );

                if ($this->options['verify']) {

                    $curl_default_options[CURLOPT_SSL_VERIFYHOST] = 2;
                    $curl_default_options[CURLOPT_SSL_VERIFYPEER] = true;
                    if (is_string($this->options['verify'])) {
                        if (!file_exists($this->options['verify'])) {
                            throw new InvalidArgumentException(
                                "SSL CA bundle not found: {$this->options['verify']}"
                            );
                        }
                        $curl_default_options[CURLOPT_CAINFO] = $this->options['verify'];

                    }
                } else {
                    $curl_default_options[CURLOPT_SSL_VERIFYHOST] = 0;
                    $curl_default_options[CURLOPT_SSL_VERIFYPEER] = false;
                }

                if (array_filter($this->options['ssl'], 'strlen')) {

                    if (!empty($this->options['ssl']['key'])) {
                        $curl_default_options[CURLOPT_SSLKEY] = $this->options['ssl']['key'];
                    }
                    if (!empty($this->options['ssl']['cert'])) {
                        $curl_default_options[CURLOPT_SSLCERT] = $this->options['ssl']['cert'];
                    }
                    if (!empty($this->options['ssl']['password'])) {
                        $curl_default_options[CURLOPT_SSLCERTPASSWD] = $this->options['ssl']['password'];
                    }

                }

                if ($this->accept_cookies) {
                    $curl_default_options[CURLOPT_COOKIEFILE] = $this->cookies;
                }

                foreach ($curl_default_options as $option => $value) {
                    if (!isset($curl_options[$option])) {
                        $curl_options[$option] = $value;
                    }
                }

                if (isset($this->options['proxy_host']) && strlen($this->options['proxy_host'])) {
                    $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
                    if (isset($this->options['proxy_port']) && $this->options['proxy_port']) {
                        $curl_options[CURLOPT_PROXY] = sprintf("%s:%s", $this->options['proxy_host'], $this->options['proxy_port']);
                    } else {
                        $curl_options[CURLOPT_PROXY] = $this->options['proxy_host'];
                    }

                    if (isset($this->options['proxy_user']) && strlen($this->options['proxy_user'])) {
                        $curl_options[CURLOPT_PROXYUSERPWD] = sprintf("%s:%s", $this->options['proxy_user'], $this->options['proxy_password']);
                    }
                }
                foreach ($curl_options as $param => $option) {
                    curl_setopt($this->ch, $param, $option);
                }

            }

            $curl_options = array();

            if ($content) {
                switch ($method) {
                    case self::METHOD_POST:
                        $curl_options[CURLOPT_POST] = 1;
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                        break;
                    case self::METHOD_PUT:
                        $curl_options[CURLOPT_POST] = 0;
                        $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                        break;
                    case self::METHOD_DELETE:
                        $curl_options[CURLOPT_POST] = 0;
                        $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                        break;
                    default:
                        $curl_options[CURLOPT_POST] = 0;
                        $curl_options[CURLOPT_CUSTOMREQUEST] = null;
                        $curl_options[CURLOPT_POSTFIELDS] = null;
                }
            }

            $headers = $this->buildHeaders('curl', false);
            if ($headers) {
                $curl_options[CURLOPT_HTTPHEADER] = $headers;
            }

            if (empty($curl_options[CURLOPT_POST]) && empty($curl_options[CURLOPT_POSTFIELDS])) {
                if (version_compare(PHP_VERSION, '5.4', '>=') || (!ini_get('safe_mode') && !ini_get('open_basedir'))) {
                    $curl_options[CURLOPT_FOLLOWLOCATION] = true;
                }
            }

            $curl_options[CURLOPT_URL] = $url;

            foreach ($curl_options as $param => $option) {
                curl_setopt($this->ch, $param, $option);
            }
        }
    }

    private function startQuery()
    {
        wa()->getStorage()->close();
    }

    private function runStreamContext($url, $content, $method)
    {
        $context = $this->getStreamContext($content, $method);
        $response = @file_get_contents($url, false, $context);

        $response_code = 'unknown';
        $hint = '';
        if (!empty($http_response_header)) {
            /**
             * @link http://php.net/manual/en/reserved.variables.httpresponseheader.php
             * @var string[] $http_response_header
             */
            $this->parseHeader($http_response_header);

            foreach ($http_response_header as $header) {
                /* HTTP/1.1 404 Not Found*/
                if (preg_match('@^HTTP/\d(\.\d)?\s+(\d{3})\s+(.+)$@i', $header, $matches)) {
                    $response_code = (int)$matches[2];
                    $hint = " Hint: {$matches[3]}";
                } elseif (preg_match('@^status:\s+(\d+)\s+(.+)$@i', $header, $matches)) {
                    $response_code = (int)$matches[1];
                    $hint = " Hint: {$matches[2]}";
                    break;
                }
            }
        }

        if (!$response || !in_array($response_code, array('unknown', 200), true)) {
            if (empty($hint)) {
                $hint = $this->getHint(__LINE__);
            }
            throw new waException("Invalid server response with code {$response_code} while request {$url}.{$hint}\n\t(fopen used)");
        }
        return $response;
    }

    /**
     * @param $content
     * @param $method
     * @return resource
     */
    private function getStreamContext($content, $method)
    {
        $context_params = array(
            'ignore_errors' => true,//PHP >= 5.2.10
            'timeout'       => $this->options['timeout'],
            'user_agent'    => $this->user_agent,
        );

        $headers = $this->buildHeaders('fopen', false);

        if (isset($this->options['proxy_host']) && strlen($this->options['proxy_host'])) {
            $proxy = $this->options['proxy_host'];
            if (isset($this->options['proxy_port']) && intval($this->options['proxy_port'])) {
                $proxy .= ':'.intval($this->options['proxy_port']);
            }
            $context_params['proxy'] = $proxy;

            if (!empty($this->options['proxy_user'])) {
                $auth = base64_encode(sprintf('%s:%s', $this->options['proxy_user'], $this->options['proxy_password']));
                $headers[] = "Proxy-Authorization: Basic $auth";
            }
        }

        $context_params['header'] = implode("\r\n", $headers); //5.2.10 array support

        if (in_array($method, array(self::METHOD_POST, self::METHOD_PUT))) {
            $context_params += array(
                'method'  => $method,
                'content' => $content,
            );
        }
        $context_params += array(
            'follow_location' => true,//PHP >= 5.3.4
            'max_redirects'   => 5,
        );

        //SSL
        if (!empty($this->options['verify'])) {
            $context_params['ssl']['verify_peer'] = true;
            $context_params['ssl']['verify_peer_name'] = true;
            $context_params['ssl']['allow_self_signed'] = false;
            if (is_string($this->options['verify'])) {
                if (!file_exists($this->options['verify'])) {
                    throw new RuntimeException("SSL CA bundle not found: {$this->options['verify']}");
                }
                $context_params['ssl']['cafile'] = $this->options['verify'];

            } else {
                // PHP 5.6 or greater will find the system cert by default. When
                // < 5.6, try load it
                if (PHP_VERSION_ID < 50600) {
                    //TODO try default system path with ca files
                    //$context_params['ssl']['cafile'] = '';
                }
            }
        } else {
            $context_params['ssl']['verify_peer'] = false;
            $context_params['ssl']['verify_peer_name'] = false;
        }

        return stream_context_create(array('http' => $context_params,));
    }

    /**
     * @todo not implemented
     * @param $url
     * @param $content
     * @param $method
     * @return bool|string
     * @throws waException
     */
    private function runSocketContext($url, $content, $method)
    {
        $host = parse_url($url, PHP_URL_HOST);

        $port = parse_url($url, PHP_URL_PORT);
        if (empty($port)) {
            if (true) {
                $port = 80;
            } else {
                $port = 81;
            }
        }

        $headers = array(
            "Host" => $host.(($port == 80) ? '' : ':'.$port),
        );

        $headers = array_merge($headers, $this->buildHeaders('socket'));

        $error_no = null;
        $error_str = null;
        $response_code = 'unknown';
        $body = null;

        $socket = @fsockopen($host, $port, $error_no, $error_str, $this->options['timeout']);
        $response = '';
        if ($socket) {
            $path = parse_url($url, PHP_URL_PATH);
            $request = parse_url($url, PHP_URL_QUERY);
            if (strlen($request)) {
                $path .= '?'.$request;
            }
            $out = "{$method} {$path} HTTP/1.1\r\n";
            foreach ($headers as $header => $value) {
                $out .= sprintf("%s: %s\r\n", $header, $value);
            }

            $out .= "\r\n";
            $out .= $content;
            fwrite($socket, $out);

            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            fclose($socket);

        }

        if (!$response) {
            if ($error_no) {
                $hint = "Socket error: #{$error_no} {$error_str}";

            } else {
                $hint = $this->getHint(__LINE__);
            }
            $this->log($hint);

            throw new waException("Invalid server response with code {$response_code} while request {$url}.{$hint}\n\t(fsockopen used)");
        } else {
            list($header, $body) = explode("\r\n\r\n", $response, 2);
            $this->parseHeader(preg_split('@[\r\n]+@', $header));
        }
        return $body;
    }

    /**
     * 413 Entity Too Large error workaround
     * @see http://stackoverflow.com/questions/686217/maximum-on-http-header-values
     * @param string $url
     * @param string[] $required_get_fields
     * @return array POST data
     */
    private static function getPost(&$url, $required_get_fields = array())
    {
        $post = array();
        /**
         * Apache 2.0/2.2 8K
         * @see http://httpd.apache.org/docs/2.2/mod/core.html#limitrequestfieldsize
         *
         * nginx 4K-8K
         * @see http://nginx.org/en/docs/http/ngx_http_core_module.html#large_client_header_buffers
         *
         * IIS 4K-8K
         */
        if (strlen($url) > 2096) {
            parse_str(parse_url($url, PHP_URL_QUERY), $post);
            $url = preg_replace('@\?.+$@', '', $url);
            $get = array();

            foreach ($required_get_fields as $field) {
                if (isset($post[$field])) {
                    $get[$field] = $post[$field];
                    unset($post[$field]);
                }
                unset($value);
            }

            if ($get) {
                $url .= '?'.http_build_query($get);
            }
        }
        return $post ? http_build_query($post) : null;
    }

    private function getHint($line)
    {
        $hint = '';
        if (($error = error_get_last())
            && (abs($line - $error['line']) < 30)
            && ($error['file'] == __FILE__)
        ) {
            $hint = strip_tags($error['message']);
        }
        return $hint;
    }

    private function log($message)
    {
        waLog::log($message, ifempty($this->options['log'], 'waNet.error.log'));
    }

    public function __destruct()
    {
        if (!empty($this->ch)) {
            curl_close($this->ch);
        }
    }
}
