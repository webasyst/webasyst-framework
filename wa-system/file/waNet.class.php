<?php
/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2017 Webasyst LLC
 * @package wa-system
 * @subpackage files
 */

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
        'timeout'             => 15,
        'format'              => null,
        'request_format'      => null,
        'required_get_fields' => array(),
        'charset'             => 'utf-8',
        'verify'              => true,
        'md5'                 => false,
        'log'                 => false,
        'authorization'       => false,
        'login'               => null,
        'password'            => null,
        # proxy settings
        'proxy_host'          => null,
        'proxy_port'          => null,
        'proxy_user'          => null,
        'proxy_password'      => null,
        'proxy_auth'          => 'basic',
        # specify custom interface
        'interface'           => null,
        # string with list of expected codes separated comma or space; null to accept any code
        'expected_http_code'  => 200,
        'priority'            => array(
            'curl',
            'fopen',
            'socket',
        ),
        'ssl'                 => array(
            'key'      => '',
            'cert'     => '',
            'password' => '',
        ),
    );

    private static $master_options = array();

    protected $raw_response;
    protected $decoded_response;

    protected $response_header = array();

    private $ch;

    private static $mh;
    /** @var waNet[] */
    private static $instances = array();

    /**
     * waNet constructor.
     * @param array $options key => value format
     *      - $options['format'] - expected format of response
     *      - $options['request_format'] - format of request data
     *      ...
     * @param array $custom_headers key => value format
     * @throws waException
     */
    public function __construct($options = array(), $custom_headers = array())
    {
        $this->user_agent = sprintf('Webasyst-Framework/%s', wa()->getVersion('webasyst'));

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->options['verify'] = false;
        }

        $this->options = array_merge(
            $this->options,
            $this->getDefaultOptions(),
            $options,
            self::$master_options
        );

        $this->request_headers = array_merge($this->request_headers, $custom_headers);
    }

    private function getDefaultOptions()
    {
        $config = wa()->getConfigPath().'/net.php';
        $options = array();
        if (file_exists($config)) {
            $options = include($config);
        }
        if (!is_array($options)) {
            $options = array();
        }

        return $options;
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

    /**
     * @param $url
     * @param array|string|SimpleXMLElement|DOMDocument $content Parameter type relate to request format (xml/json/etc)
     * @param string $method
     * @param callable $callback
     * @return string|array|SimpleXMLElement|waNet Type related to response for response format (json/xml/etc). Self return for promises
     * @throws waException
     */
    public function query($url, $content = array(), $method = self::METHOD_GET, $callback = null)
    {
        $transport = $this->getTransport($url);

        $this->buildRequest($url, $content, $method);
        $this->startQuery();

        switch ($transport) {
            case self::TRANSPORT_CURL:
                $response = $this->runCurl($url, $content, $method, array(), $callback);
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

        if ($response instanceof self) {
            return $response;
        } else {

            $this->onQueryComplete($response);

            return $this->getResponse();
        }
    }

    protected function onQueryComplete($response)
    {
        $this->decodeResponse($response);

        if ($this->options['expected_http_code'] !== null) {
            $expected = $this->options['expected_http_code'];
            if (!is_array($expected)) {
                $expected = preg_split('@[,:;.\s]+@', $this->options['expected_http_code']);
            }
            if (empty($this->response_header['http_code']) || !in_array($this->response_header['http_code'], $expected)) {
                throw new waException($response, $this->response_header['http_code']);
            }
        }
    }

    /**
     * @param string $url
     * @param array|string|SimpleXMLElement|DOMDocument $content
     * @param string $method
     */
    protected function buildRequest(&$url, &$content, &$method)
    {
        if ($content && $method == self::METHOD_GET) {
            //
            // Unable to encode FORMAT_XML and FORMAT_JSON for METHOD_GET.
            // Have to deal with it here.
            //
            $format = ifempty($this->options['request_format'], $this->options['format']);
            if (in_array($format, array(self::FORMAT_XML), true)) {
                // FORMAT_XML, METHOD_GET becomes FORMAT_XML, METHOD_POST
                $method = self::METHOD_POST;
            } else {
                // FORMAT_JSON, METHOD_GET becomes FORMAT_RAW, METHOD_GET
                $get = is_string($content) ? $content : http_build_query($content);
                $url .= strpos($url, '?') ? '&' : '?'.$get;
                $content = array();
            }
        }

        // When URL is too long, move some fields to POST body
        $post = self::getPost($url, $this->options['required_get_fields']);
        if ($post) {
            switch ($method) {
                // GET becomes POST
                // PUT and POST are ok as is
                // DELETE don't know what to do
                case self::METHOD_GET:
                    $method = self::METHOD_POST;
                    break;
                case self::METHOD_DELETE:
                    throw new waException('Too long URL for METHOD_DELETE');
            }
            $content = array_merge($post, $content);
        }

        switch ($method) {
            case self::METHOD_POST:
            case self::METHOD_PUT:
                $content = $this->encodeRequest($content);
                break;
        }
    }

    protected function buildHeaders($transport, $raw = true)
    {
        $this->request_headers['Connection'] = 'close';
        $this->request_headers['Date'] = date('c');
        if (empty($this->request_headers['Accept'])) {
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
            $authorization = sprintf("%s:%s", $this->options['login'], $this->options['password']);
            $this->request_headers["Authorization"] = "Basic ".base64_encode($authorization);
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
     * @param array|string|SimpleXMLElement|DOMDocument $content
     * @return string
     * @throws waException
     */
    protected function encodeRequest($content)
    {
        $format = ifempty($this->options['request_format'], $this->options['format']);

        if (!is_string($content)) {
            switch ($format) {
                case self::FORMAT_JSON:
                    $content = json_encode($content);
                    break;
                case self::FORMAT_XML:
                    if (is_object($content)) {
                        $class = get_class($content);

                        if (class_exists('SimpleXMLElement') && ($content instanceof SimpleXMLElement)) {
                            /**
                             * @var SimpleXMLElement $content
                             */
                            $content = (string)$content->asXML();
                        } elseif (class_exists('DOMDocument') && ($content instanceof DOMDocument)) {
                            /**
                             * @var DOMDocument $content
                             */
                            if (!empty($this->options['charset'])) {
                                $content->encoding = $this->options['charset'];
                            }
                            $content->preserveWhiteSpace = false;
                            $content = (string)$content->saveXML();
                        } else {
                            $message = 'Unsupported class "%s" of content object. Expected instance of SimpleXMLElement or DOMDocument classes.';
                            throw new waException(sprintf($message, $class));
                        }
                    } else {
                        throw new waException('XML content must be an instance of SimpleXMLElement or DOMDocument classes.');
                    }
                    break;
                default:
                    $content = http_build_query($content);
                    break;
            }
        }

        $this->request_headers['Content-Length'] = strlen($content);
        if (empty($this->request_headers['Content-Type'])) {
            switch ($format) {
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
        }
        if (!empty($this->options['md5'])) {
            $this->request_headers['Content-MD5'] = base64_encode(md5($content, true));
        }

        return $content;
    }

    /**
     * @param string $response
     * @throws waException
     */
    protected function decodeResponse($response)
    {
        $this->raw_response = $response;
        $this->decoded_response = null;
        switch ($this->options['format']) {
            case self::FORMAT_JSON:
                $this->decoded_response = waUtils::jsonDecode($this->raw_response, true);
                break;
            case self::FORMAT_XML:
                $xml_options = LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NONET;
                libxml_use_internal_errors(true);
                libxml_disable_entity_loader(false);
                libxml_clear_errors();
                $this->decoded_response = @simplexml_load_string($this->raw_response, null, $xml_options);

                if ($this->decoded_response === false) {
                    if ($error = libxml_get_last_error()) {
                        /**
                         * @var LibXMLError $error
                         */
                        $this->log($error->message);
                        throw new waException('Error while decode XML response: '.$error->message, $error->code);
                    }
                }
                break;
            default:
                $this->decoded_response = $this->raw_response;
                break;
        }
    }

    /**
     * @param bool $raw If param is true method returns raw response string
     * @return string|array|SimpleXMLElement Type related to response for response format (json/xml/etc)
     */
    public function getResponse($raw = false)
    {
        return $raw ? $this->raw_response : $this->decoded_response;
    }

    /**
     * @param string|null $header
     * @return array|mixed|null
     */
    public function getResponseHeader($header = null)
    {
        if (!empty($header)) {
            if (array_key_exists($header, $this->response_header)) {
                return $this->response_header[$header];
            }

            $header_alias = $header = str_replace('-', '_', $header);

            foreach ($this->response_header as $field => $value) {
                // Ignore register of headers according to RFC
                if (strcasecmp($field, $header) === 0 || strcasecmp($header_alias, $header) === 0) {
                    return $value;
                }
            }

            return null;
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

    /**
     * @param string $url
     * @return string|bool
     */
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

    protected function runCurl($url, $params, $method, $curl_options = array(), $callback = null)
    {
        $this->getCurl($url, $params, $method, $curl_options);
        if (!empty($callback) && is_callable($callback) && !empty(self::$namespace) && !empty(self::$mh[self::$namespace])) {
            return $this->addMultiCurl($callback);
        } else {
            $response = @curl_exec($this->ch);

            return $this->handleCurlResponse($response);
        }
    }

    private function handleCurlResponse($response)
    {
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

    protected function onMultiQueryComplete($callback)
    {
        $content = curl_multi_getcontent($this->ch);
        try {
            $body = $this->handleCurlResponse($content);
            curl_multi_remove_handle(self::$mh[self::$namespace], $this->ch);
            $this->onQueryComplete($body);

            $response = $this->getResponse();
            call_user_func_array($callback, array($this, $response));
        } catch (waException $ex) {
            call_user_func_array($callback, array($this, $ex));
        }
    }

    private static $namespace = null;

    private function addMultiCurl($callback)
    {
        $id = curl_multi_add_handle(self::$mh[self::$namespace], $this->ch);
        self::$instances[self::$namespace][$id] = array(
            'instance' => $this,
            'callback' => $callback,
        );

        return $this;
    }


    public static function multiQuery($namespace = null, $options = array())
    {
        if ($options) {
            self::$master_options = $options;
        }

        if ($namespace && !isset(self::$mh[$namespace])) {
            if (empty(self::$mh[$namespace]) && function_exists('curl_multi_init')) {
                self::$mh[$namespace] = curl_multi_init();
                if (!isset(self::$instances[$namespace])) {
                    self::$instances[$namespace] = array();
                }
                self::$namespace = $namespace;
            }
        } elseif ((($namespace === null) || (self::$namespace === $namespace)) && isset(self::$mh[self::$namespace])) {
            $namespace = self::$namespace;
            if (self::$instances[$namespace]) {
                $active = null;

                do {
                    $mrc = curl_multi_exec(self::$mh[$namespace], $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);

                while ($active && $mrc == CURLM_OK) {
                    if (curl_multi_select(self::$mh[$namespace]) == -1) {
                        usleep(100);
                    }

                    do {
                        $mrc = curl_multi_exec(self::$mh[$namespace], $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }

                foreach (self::$instances[$namespace] as $id => $data) {
                    $instance = $data['instance'];
                    /** @var waNet $instance */
                    $instance->onMultiQueryComplete($data['callback']);
                }
                self::$instances = array();
            }

            unset(self::$instances[$namespace]);

            curl_multi_close(self::$mh[$namespace]);
            unset(self::$mh[$namespace]);
        }

        self::$master_options = [];
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

                if (isset($this->options['interface'])) {
                    $curl_default_options[CURLOPT_INTERFACE] = $this->options['interface'];
                }

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

            switch ($method) {
                case self::METHOD_POST:
                    $curl_options[CURLOPT_POST] = 1;
                    if ($content) {
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                    }
                    break;
                case self::METHOD_PUT:
                    $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
                    if ($content) {
                        $curl_options[CURLOPT_POST] = 0;
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                    }
                    break;
                case self::METHOD_DELETE:
                    $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
                    if ($content) {
                        $curl_options[CURLOPT_POST] = 0;
                        $curl_options[CURLOPT_POSTFIELDS] = $content;
                    }
                    break;
                default:
                    if ($content) {
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
                if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
                    // This is a useful option to have, but it is disabled in paranoid environments
                    // (which emits a warning). Also, does not apply to POST requests.
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

    protected function runStreamContext($url, $content, $method)
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

        if ($this->options['expected_http_code'] !== null) {
            if (!$response || !in_array($response_code, array('unknown', $this->options['expected_http_code']), true)) {
                if (empty($hint)) {
                    $hint = $this->getHint(__LINE__);
                }
                throw new waException("Invalid server response with code {$response_code} while request {$url}.{$hint}\n\t(fopen used)");
            }
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
     * @param $url
     * @param $content
     * @param $method
     * @return bool|string
     * @throws waException
     */
    protected function runSocketContext($url, $content, $method)
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

        return $post;
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

    /**
     * @since PHP 5.6.0
     * @return array
     */
    public function __debugInfo()
    {
        return array(
            'options'          => $this->options,
            'request_headers'  => $this->request_headers,
            'response_headers' => $this->response_header,
            'raw'              => $this->raw_response,
            'preview'          => $this->decoded_response,
        );
    }

    public function getResponseDebugInfo()
    {
        return [
            'headers' => $this->response_header,
            'body'    => $this->raw_response,
        ];
    }
}
