<?php

abstract class blogImportPluginXmlrpcTransport extends blogImportPluginTransport
{
    protected $xmlrpc_url = false;
    protected $xmlrpc_path = '';

    private $url_user = '';
    private $url_pass = '';

    protected function initOptions()
    {
        if (!extension_loaded('curl')) {
            throw new waException(_wp('PHP extension curl required'));
        }
    }

    public function setup($runtime_settings = array())
    {
        if (!extension_loaded('curl')) {
            throw new waException(_wp('PHP extension curl required'));
        }
        parent::setup($runtime_settings);

        $url = $this->setUrl($this->option('url', $this->xmlrpc_url), $this->xmlrpc_path);
        $this->log("Begin import from {$url['host']}", self::LOG_INFO);
    }


    protected function setUrl($url, $path = '')
    {

        if ($url && ($parsed_url = @parse_url($url))) {
            $this->xmlrpc_url = preg_replace('@/?$@', $path, $url, 1);
        } else {
            throw new waException(_wp("Invalid URL"));
        }
        if (!empty($parsed_url['user'])) {
            $this->url_user = $parsed_url['user'];
        }
        if (!empty($parsed_url['pass'])) {
            $this->url_pass = $parsed_url['user'];
        }
        return $parsed_url;
    }

    /**
     *
     * Call XML RPC method
     * @param string $method
     * @param mixed|null $args
     * @param mixed|null $_
     * @throws waException
     * @return mixed
     */
    protected function xmlrpc($method, $args = null, $_ = null)
    {
        static $client;
        $params = func_get_args();
        $method = array_shift($params);
        if ((count($params) == 1) && is_array(current($params))) {
            $params = current($params);
        }

        $this->log(__METHOD__."({$method}) \n".var_export($params, true), self::LOG_DEBUG);
        if (extension_loaded('curl')) {
            require_once(dirname(__FILE__).'/../../vendors/xmlrpc/lib/init.php');
        }
        if (class_exists('xmlrpc_client')) {

            if (!isset($client)) {
                $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
                $client = new xmlrpc_client($this->xmlrpc_url);
                if ($this->url_pass || $this->url_user) {
                    $client->SetCurlOptions(array(CURLOPT_USERPWD => "{$this->url_user}:{$this->url_pass}"));
                }
                $client->request_charset_encoding = 'utf-8';
            }

            $this->log(__METHOD__."({$method}) external lib", self::LOG_DEBUG);

            $request = new xmlrpcmsg($method, array(php_xmlrpc_encode($params)));
            $response = $client->send($request);

            if ($response && ($fault_code = $response->faultCode())) {
                $fault_string = $response->faultString();
                $this->log(__METHOD__."({$method}) returns {$fault_string} ({$fault_code})", self::LOG_ERROR);
                $this->log(__METHOD__.$response->raw_data, self::LOG_DEBUG);
                throw new waException("{$fault_string} ({$fault_code})", $fault_code);
            }
            return php_xmlrpc_decode($response->value(), array('dates_as_objects' => true));

        } else {
            if (!extension_loaded('xmlrpc')) {
                throw new waException("PHP extension 'xmlrpc' or 'curl' required.");
            }
            $this->log(__METHOD__."({$method}) internal PHP lib", self::LOG_DEBUG);

            $request = xmlrpc_encode_request($method, $params, array('encoding' => 'utf-8'));

            $request_options = array(
                'method'  => "POST",
                'header'  => "Content-Type: text/xml".(($this->url_pass || $this->url_user) ? "\nAuthorization: Basic ".base64_encode("{$this->url_user}:$this->url_pass") : ''),
                'content' => $request,
            );
            $context = stream_context_create(array('http' => $request_options));

            //TODO add curl support
            $retry = 0;
            do {
                ob_start();
                if ($retry) {
                    sleep(6);
                }
                $file = file_get_contents($this->xmlrpc_url, false, $context);
                $log = ob_get_clean();
                if ($log) {
                    $this->log(__METHOD__.":\t{$log}", self::LOG_WARNING);
                }
                if (!$file) {
                    $this->log(__METHOD__."({$method}) fail open stream", self::LOG_ERROR);
                }
            } while (!$file && (++$retry < 10));
            if (!$file) {
                $this->log('Fail while request WordPress', self::LOG_ERROR);
                throw new waException(sprintf(_wp("I/O error: %s"), 'Fail while request WordPress'));
            }
            $response = xmlrpc_decode($file, 'utf-8');
            $this->log(__METHOD__."({$method}) \n".var_export(compact('response', 'file'), true), self::LOG_DEBUG);
            if ($response && xmlrpc_is_fault($response)) {
                $this->log(__METHOD__."({$method}) returns {$response['faultString']} ({$response['faultCode']})", self::LOG_ERROR);
                throw new waException("{$response['faultString']} ({$response['faultCode']})", $response['faultCode']);
            }
        }
        return $response;
    }
}