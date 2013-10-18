<?php


class blogImportPluginWebasystRemoteTransport extends blogImportPluginWebasystTransport
{
    const TIMEOUT_SOCKET = 10;
    private $stage_data_stack;

    protected function initOptions()
    {
        if (!extension_loaded('curl')) {
            throw new waException(_wp('PHP extension curl required'));
        }
        $this->addOption('url', array(
            'title'        => _wp('Data access URL'),
            'description'  => _wp('For WebAsyst <strong>PHP software</strong> installed on your server:<br /> 1. Download data access script <a href="http://www.webasyst.com/wa-data/public/site/downloads/old-webasyst-export-php.zip">export.php</a>, and upload it to your Webasyst published/ folder via FTP.<br /> 2. Enter the complete URL to this file, which should look like this: <strong>http://YOUR_WEBASYST_ROOT_URL/published/export.php</strong><br /><br /> For <strong>hosted accounts</strong>: get your Secure Data Access URL in your ACCOUNT.webasyst.net backend’s “Account (link in the top right corner) &gt; System Settings” page.'),
            'value'        => 'http://',
            'control_type' => waHtmlControl::INPUT,
        ));
        $this->addOption('login', array(
            'title'        => _wp('Login'),
            'value'        => '',
            'description'  => _wp('WebAsyst user login'),
            'control_type' => waHtmlControl::INPUT,
        ));
        $this->addOption('password', array(
            'title'        => _wp('Password'),
            'value'        => '',
            'description'  => _wp('WebAsyst user password'),
            'control_type' => waHtmlControl::PASSWORD,
        ));
    }


    public function validate($result, &$errors)
    {
        $fields = array();
        try {
            $url = $this->option('url');
            $fields[] = 'url';
            if (preg_match('@^https?://$@', $url) || !preg_match('@/export\\.php@', $url)) {
                throw new waException(_wp('Invalid URL'));
            }
            $fields[] = 'login';
            $fields[] = 'password';

            $this->waQuery('1');
            $this->addOption('url', array('readonly' => true));
            $this->addOption('login', array('readonly' => true));
            $this->addOption('password', array('readonly' => true));
        } catch (Exception $ex) {
            foreach ($fields as $field) {
                $errors[$field] = $ex->getMessage();
            }
            $result = false;

            $this->addOption('url', array('readonly' => false));
            $this->addOption('login', array('readonly' => false));
            $this->addOption('password', array('readonly' => false));
        }
        return parent::validate($result, $errors);
    }

    protected function waQuery($sql, $limit = true)
    {
        $debug = array();
        try {
            $sql = trim(preg_replace("/^select/is", '', $sql));
            $url = $this->getURL("sql=".($limit ? '1' : '0').urlencode(base64_encode($sql)));
            $debug['URL'] = self::logURL($url);
            $debug['method'] = __METHOD__;
            $result = '';
            $this->downloadCurl($url, $result);
            if ($result === false) {
                $debug['hint'] = 'empty server response';
                throw new waException(_wp('Invalid URL, login or password'));
            }

            $result = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $raw = $result), true);
            if ($result === null) {
                $debug['raw'] = $raw;
                $debug['hint'] = 'Error jsone_decode';
                throw new waException(_wp('Invalid server response'));
            } elseif (!$limit && !is_array($result)) {
                $debug['decoded'] = $result;
                $debug['hint'] = 'Unexpected jsone_decode result';
                throw new waException(_wp('Invalid server response'));
            }

            $debug['result'] = $result;
            $this->log(var_export($debug, true), self::LOG_DEBUG);
        } catch (Exception $ex) {
            $debug['error'] = $ex->getMessage();
            $this->log(var_export($debug, true), self::LOG_ERROR);
            throw new waException(_wp('Invalid URL, login or password'));
        }
        return $result;
    }


    private function downloadCurl($url, &$target_stream)
    {
        $ch = null;
        try {
            $this->log(__METHOD__.' :download via cURL', self::LOG_DEBUG, array('source' => self::logURL($url),));
            $content_length = 0;
            $download_content_length = 0;
            $ch = $this->getCurl($url);

            $this->stage_data_stack = array(
                'stream'              => & $target_stream,
                'stage_value'         => & $content_length,
                'stage_current_value' => & $download_content_length,
            );
            $post = array();
            parse_str(parse_url($url, PHP_URL_QUERY), $post);
            if (!empty($post['sql'])) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, array('sql' => $post['sql']));
            }

            curl_exec($ch);
            if ($errno = curl_errno($ch)) {
                $url = self::logURL($url);
                $message = "Curl error: {$errno}# ".curl_error($ch)." at [{$url}]";
                throw new waException($message);
            }

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                throw new waException(sprintf(_wp("Invalid server response with code %d"), $status), $status);
            }
            if ($target_stream && is_resource($target_stream)) {
                fclose($target_stream);
            }
            curl_close($ch);

            return $content_length;
        } catch (waException $ex) {
            if (!empty($ch)) {
                curl_close($ch);
            }
            if ($target_stream && is_resource($target_stream)) {
                fclose($target_stream);
            }

            throw $ex;
        }
    }

    /**
     * @param $ch
     * @param string $chunk
     * @return int
     * @throws waException
     */
    public function curlWriteHandler($ch, $chunk)
    {
        if ($this->stage_data_stack['stream'] && is_resource($this->stage_data_stack['stream'])) {
            $size = fwrite($this->stage_data_stack['stream'], $chunk);
            $this->stage_data_stack['stage_current_value'] += $size;
        } elseif (isset($this->stage_data_stack['stream']) && is_string($this->stage_data_stack['stream'])) {
            $size = strlen($chunk);
            $this->stage_data_stack['stage_current_value'] += $size;
            $this->stage_data_stack['stream'] .= $chunk;
        } else {
            throw new waException('Invalid write stream');
        }
        return $size;
    }

    /**
     * @param $ch
     * @param string $header
     * @return int
     */
    public function curlHeaderHandler($ch, $header)
    {
        $header_matches = null;
        if (preg_match('/content-length:\s*(\d+)/i', $header, $header_matches)) {
            $this->stage_data_stack['stage_value'] = intval($header_matches[1]);
        }
        return strlen($header);
    }

    private function curlAvailable()
    {
        return extension_loaded('curl') && function_exists('curl_init');
    }

    private function getCurl($url, $curl_options = array())
    {
        $ch = null;
        if (!$this->curlAvailable()) {
            throw new waException(_wp('PHP extension curl not loaded'));
        }
        if (!($ch = curl_init())) {
            throw new waException(_wp('Error while init Curl'));
        }

        if (curl_errno($ch) != 0) {
            throw new waException(sprintf(_wp('Curl error %d#:%s'), curl_errno($ch), curl_error($ch)));
        }
        if (!is_array($curl_options)) {
            $curl_options = array();
        }
        $curl_default_options = array(
            // CURLOPT_MAXCONNECTS => 10,
            CURLOPT_HEADER            => 0,
            CURLOPT_RETURNTRANSFER    => 1,
            CURLOPT_TIMEOUT           => self::TIMEOUT_SOCKET * 60,
            CURLOPT_CONNECTTIMEOUT    => self::TIMEOUT_SOCKET,
            CURLE_OPERATION_TIMEOUTED => self::TIMEOUT_SOCKET * 60,
            CURLOPT_BINARYTRANSFER    => true,
            CURLOPT_WRITEFUNCTION     => array(&$this, 'curlWriteHandler'),
            CURLOPT_HEADERFUNCTION    => array(&$this, 'curlHeaderHandler'),
        );

        if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
            $curl_default_options[CURLOPT_FOLLOWLOCATION] = true;
        }
        foreach ($curl_default_options as $option => $value) {
            if (!isset($curl_options[$option])) {
                $curl_options[$option] = $value;
            }
        }
        $curl_options[CURLOPT_URL] = $url;
        $options = array();

        if (isset($options['host']) && strlen($options['host'])) {
            $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
            $curl_options[CURLOPT_PROXY] = sprintf("%s%s", $options['host'], (isset($options['port']) && $options['port']) ? ':'.$options['port'] : '');

            if (isset($options['user']) && strlen($options['user'])) {
                $curl_options[CURLOPT_PROXYUSERPWD] = sprintf("%s:%s", $options['user'], $options['password']);
            }
        }
        foreach ($curl_options as $param => $option) {
            curl_setopt($ch, $param, $option);
        }
        return $ch;
    }

    private function getURL($params)
    {
        $url = $this->option('url');
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= 'auth='.urlencode(base64_encode($this->option('login').':'.$this->option('password')));
        $url .= '&'.$params;
        return $url;
    }

    private static function logURL($url)
    {
        return preg_replace('/\b(auth|key)\b=[^&]+&/', '$1=******', $url);
    }
}