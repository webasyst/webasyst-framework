<?php

class waInstallerFile
{
    private static $cache_ttl = 600;

    public function __construct()
    {
    }


    /**
     *
     * @param string $path URI
     * @param string $encoding
     * @param bool $values
     * @throws Exception
     * @return array|null
     */
    public function getData($path, $encoding = 'base64', $values = true)
    {
        //TODO add local sources support
        if (!empty($_GET['refresh']) || !($data = self::getCacheValue($path, array()))) {
            if (!($encoded = $this->getContent($path))) {
                throw new Exception("Error while get server response {$path}");
            }
            switch ($encoding) {
                case 'json':
                    $data = @json_decode($encoded, true);
                    if (($data === false) || ($data === null)) {
                        $hint = preg_replace('/a:\d+:\{.+}$/', '', $encoded, 1);
                        throw new Exception("Error while decode server json response {$path}:\n {$hint}");
                    }
                    break;
                case 'base64':
                default:

                    if (!($serialized = base64_decode($encoded, true))) {
                        $hint = preg_replace('/[\w\d]{8,}=/', '', $encoded, 1);
                        throw new Exception("Error while decode server response {$path}:\n {$hint}");
                    }
                    if (($data = @unserialize($serialized)) === false) {
                        $hint = preg_replace('/a:\d+:\{.+}$/', '', $serialized, 1);
                        throw new Exception("Error while unserialize server response {$path}:\n {$hint}");
                    }
                    break;
            }
            if (!is_array($data)) {
                $hint = 'array expected, but get '.var_export(strip_tags($data), true);
                throw new Exception("Invalid server response {$path}:\n {$hint}");
            }
            self::setCacheValue($path, $data);
        }
        return $values ? array_values($data) : $data;
    }

    public function getContent($path, $allow_caching = false)
    {
        //TODO enable caching
        //TODO check response code 4xx/200
        $is_url = preg_match('@^https?://@', $path);
        if ($is_url && ($ch = self::getCurl($path))) {
            if (session_id()) {
                session_write_close();
            }
            $content = curl_exec($ch);

            if ($errno = curl_errno($ch)) {
                $message = "Curl error: {$errno}# ".curl_error($ch)." at [{$path}]";
                curl_close($ch);
                throw new Exception($message);
            }
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response_code != 200) {
                throw new Exception("Invalid server response with code {$response_code} while request {$path}");
            }
            curl_close($ch);
        } elseif ($is_url && @ini_get('allow_url_fopen')) {
            if (session_id()) {
                session_write_close();
            }

            if ($post = self::getPost($path)) {
                $context = stream_context_create(array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $post
                    ),
                ));
                $content = @file_get_contents($path, false, $context);
            } else {
                $content = @file_get_contents($path);
            }

            if (!$content) {
                $response_code = 'unknown';
                $hint = '';
                if (!empty($http_response_header)) {
                    foreach ($http_response_header as $header) {
                        if (preg_match('@^status:\s+(\d+)\s+(.+)$@i', $header, $matches)) {
                            $response_code = $matches[1];
                            $hint = " Hint: {$matches[2]}";
                            break;
                        }
                    }
                }
                throw new Exception("Invalid server response with code {$response_code} while request {$path}.{$hint}");
            }
        } elseif (!$is_url) {
            $content = @file_get_contents($path);
        } else {
            $path = preg_replace('@\?.+$@', '', $path);
            throw new Exception("Couldn't read {$path} Please check allow_url_fopen setting or PHP extension Curl are enabled");
        }
        return $content;
    }


    private static function getCurl($url, $curl_options = array())
    {
        $ch = null;
        if (extension_loaded('curl') && function_exists('curl_init')) {
            if (!($ch = curl_init())) {
                throw new Exception(_wd('system', "Error cUrl init"));
            }

            if (curl_errno($ch) != 0) {
                throw new Exception(_wd('system', "Error cUrl init").' '.curl_errno($ch).' '.curl_error($ch));
            }
            if (!is_array($curl_options)) {
                $curl_options = array();
            }
            $curl_default_options = array(
                CURLOPT_HEADER            => 0,
                CURLOPT_RETURNTRANSFER    => 1,
                CURLOPT_TIMEOUT           => 10,
                CURLOPT_CONNECTTIMEOUT    => 10,
                CURLE_OPERATION_TIMEOUTED => 10,
                CURLOPT_DNS_CACHE_TIMEOUT => 3600,
            );

            if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
                $curl_default_options[CURLOPT_FOLLOWLOCATION] = true;
            }

            foreach ($curl_default_options as $option => $value) {
                if (!isset($curl_options[$option])) {
                    $curl_options[$option] = $value;
                }
            }
            if ($post = self::getPost($url)) {
                $curl_options[CURLOPT_POST] = 1;
                $curl_options[CURLOPT_POSTFIELDS] = $post;
            }
            $curl_options[CURLOPT_URL] = $url;
            //TODO read proxy settings from generic config
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
        }
        return $ch;
    }

    /**
     * @param string $url
     * @return array POST data
     */
    private static function getPost(&$url)
    {
        $post = array();
        if (strlen($url) > 1024) {
            parse_str(parse_url($url, PHP_URL_QUERY), $post);
            $url = preg_replace('@\?.+$@', '', $url);
            $get = array_fill_keys(array('hash', 'domain', 'locale', 'signature'), null);

            foreach ($get as $field => &$value) {
                if (isset($post[$field])) {
                    $value = $post[$field];
                    unset($post[$field]);
                }
                unset($value);
            }
            $url .= '?'.http_build_query(array_filter($get));
        }
        return $post ? http_build_query($post) : null;
    }


    private static function setCacheValue($path, $value)
    {

        $key = __CLASS__.'.'.md5($path);
        if (class_exists('waSerializeCache')) {
            $cache = new waSerializeCache($key, self::$cache_ttl, 'installer');
            $cache->set($value);
        }
        return $value;
    }


    private static function getCacheValue($path, $default = null)
    {
        $key = __CLASS__.'.'.md5($path);
        $value = $default;
        if (self::$cache_ttl && class_exists('waSerializeCache')) {
            $cache = new waSerializeCache($key, self::$cache_ttl, 'installer');
            if ($cache->isCached()) {
                $value = $cache->get();
            }
        }
        return $value;
    }
}