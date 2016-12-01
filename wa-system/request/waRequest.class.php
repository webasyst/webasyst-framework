<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage request
 */

class waRequest
{
    const TYPE_INT = 'int';
    const TYPE_STRING = 'string';
    const TYPE_STRING_TRIM = 'string_trim';
    const TYPE_ARRAY_TRIM = 'array_trim';
    const TYPE_ARRAY_INT = 'array_int';
    const TYPE_ARRAY = 'array';

    protected static $params = array();

    /**
     * @var bool Is request from mobile device?
     */
    protected static $mobile;

    public function __construct()
    {
    }

    protected static function cast($val, $type = null, $rec_limit = 50)
    {
        $type = trim(strtolower($type));
        switch ($type) {
            case self::TYPE_INT:
                return (int)$val;
            case self::TYPE_STRING_TRIM:
                return trim(self::cast($val, self::TYPE_STRING));
            case self::TYPE_ARRAY_INT:
                if (!is_array($val)) {
                    $val = explode(",", $val);
                }
                foreach ($val as &$v) {
                    $v = self::cast($v, self::TYPE_INT);
                }
                reset($val);
                return $val;
            case self::TYPE_STRING:
                if (is_array($val)) {
                    $val = reset($val);
                    if (is_array($val)) {
                        $val = null;
                    }
                }
                break;
            case self::TYPE_ARRAY:
                if (!is_array($val)) {
                    $val = (array)$val;
                }
                break;
            case self::TYPE_ARRAY_TRIM:
                if (!is_array($val)) {
                    $val = (array)$val;
                }
                foreach($val as $k => $v) {
                    if (is_array($v)) {
                        if ($rec_limit > 0) {
                            $val[$k] = self::cast($v, self::TYPE_ARRAY_TRIM, $rec_limit - 1);
                        }
                    } else {
                        $val[$k] = self::cast($v, self::TYPE_STRING_TRIM);
                    }
                }
                break;
        }
        return $val;
    }

    /**
     * Returns POST request contents.
     *
     * @param string|null $name POST request field name. If empty, entire contents of POST request are returned.
     * @param mixed $default The default value, which is returned if no value is found for the request field
     *     specified in $name parameter.
     * @param string $type Data type to which the value of specified parameter must be converted.
     *     Acceptable data types are described for method get().
     * @see self::get()
     * @return mixed
     */
    public static function post($name = null, $default = null, $type = null)
    {
        if ($name) {
            return self::getData($_POST, $name, $default, $type);
        }

        // Remove CSRF hidden field from post data
        $data = $_POST;
        unset($data['_csrf']);
        return self::getData($data, $name, $default, $type);
    }

    /**
     * Verifies availablility of specified field in POST request.
     *
     * @param string $name POST request field
     * @return bool
     */
    public static function issetPost($name)
    {
        return isset($_POST[$name]);
    }

    /**
     * Returns the contents of the GET request.
     *
     * @param string|null $name GET request field name. If empty, entire contents of the GET request are returned.
     * @param string|null $default The default value, which is returned if no value is found for the request field
     *     specified in $name parameter.
     * @param string|null $type Data type to which the cookie record value must be converted, specified by means of one
     *     of TYPE_* constants:
     *     waRequest::TYPE_INT - integer
     *     waRequest::TYPE_STRING - string
     *     waRequest::TYPE_STRING_TRIM string with trimmed space characters
     *     waRequest::TYPE_ARRAY_TRIM array with all string values trimmed recursively
     *     waRequest::TYPE_ARRAY_INT = array of integers
     *     waRequest::TYPE_ARRAY = array of various data
     * @example waRequest::get('id', 0, waRequest::TYPE_INT)
     * @return mixed
     */
    public static function get($name = null, $default = null, $type = null)
    {
        return self::getData($_GET, $name, $default, $type);
    }

    /**
     * Returns combined contents of GET and POST requests or the value of specified request field.
     *
     * @param string|null $name Request field name. If empty, entire contents of POST and GET request are returned.
     * @param mixed $default The default value, which is returned if no value is found for the request field
     *     specified in $name parameter.
     * @param string $type Data type to which the value of specified parameter must be converted.
     *     Acceptable data types are described for method get().
     * @see self::get()
     * @return mixed
     */
    public static function request($name = null, $default = null, $type = null)
    {
        if ($name === null) {
            return $_POST + $_GET;
        }
        $r = self::post($name, $default, $type);
        if ($r !== $default) {
            return $r;
        }
        return self::get($name, $default, $type);
    }

    /**
     * Returns iterator for file
     *
     * @return waRequestFileIterator
     */
    public static function file($name)
    {
        return new waRequestFileIterator($name);
    }

    /**
     * Returns information about user's cookie files.
     *
     * @param string|null $name Cookie record id. If not specified, all cookie data received from user is returned.
     * @param string|null $default The default value, which is returned if no value is found for the cookie record
     *     specified in $name parameter.
     * @param string|null $type Data type to which the request field value must be converted. Acceptable data types are
     *     described for get() method.
     * @see self::get()
     * @return mixed
     */
    public static function cookie($name = null, $default = null, $type = null)
    {
        return self::getData($_COOKIE, $name, $default, $type);
    }

    /**
     * Verifies whether current request is an AJAX request.
     *
     * @return bool
     */
    public static function isXMLHttpRequest()
    {
        return self::server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }

    /**
     * Returns the contents of server header HTTP_USER_AGENT.
     *
     * @return string
     */
    public static function getUserAgent()
    {
        return self::server('HTTP_USER_AGENT');
    }

    /**
     * Determines the use of a mobile device.
     *
     * @param bool $check Flag requiring to check and update the value of field nomobile in user's PHP session.
     *     If set to true, the following actions are performed:
     *       - If the GET request contains variable named 'nomobile' with a value equivalent to true, then field 'nomobile'
     *         in user's PHP session is set to true. If the value of this variable is equivalent to false, then field
     *         'nomobile' is removed from user's session.
     *       - If the GET request contains no variable named 'nomobile' and does contain a variable named 'mobile' with
     *         a value equivalent to true, then field 'nomobile' is removed from user's session.
     *       - If, upon execution of the above actions, the value of field 'nomobile' in user's PHP session is equal to
     *         true, then method returns false. Otherwise the method continues its operation so as if the value of this
     *         flag were equal to false.
     *     If the flag's value is set to false, the use of a mobile device is determined by the contents of
     *     HTTP_USER_AGENT header.
     *
     * @return string|bool If mobile device is detected, one of these identifiers is returned: 'android', 'blackberry',
     *     'iphone', 'opera', 'palm', 'windows', 'generic'; otherwise method return false.
     */
    public static function isMobile($check = true)
    {
        if ($check) {
            if (self::get('nomobile') !== null) {
                if (self::get('nomobile')) {
                    waSystem::getInstance()->getStorage()->write('nomobile', true);
                } else {
                    waSystem::getInstance()->getStorage()->remove('nomobile');
                }
            } elseif (self::get('mobile')) {
                waSystem::getInstance()->getStorage()->remove('nomobile');
            }
            if (waSystem::getInstance()->getStorage()->read('nomobile')) {
                return false;
            }
        }

        if (self::$mobile !== null) {
            return self::$mobile;
        }
        $user_agent = self::server('HTTP_USER_AGENT');

        $desktop_platforms = array(
            'ipad'       => 'ipad',
            'galaxy-tab' => 'android.*?GT\-P',
        );
        foreach ($desktop_platforms as $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                self::$mobile = false;
                return false;
            }
        }

        $mobile_platforms = array(
            "google-mobile" => "googlebot\-mobile",
            "android"    => "android",
            "blackberry" => "(blackberry|rim tablet os)",
            "iphone"     => "(iphone|ipod)",
            "opera"      => "opera (mini|mobi|mobile)",
            "palm"       => "(palmos|avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
            "windows"    => "windows\sce;\s(iemobile|ppc|smartphone)",
            "generic"    => "(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)"
        );
        foreach ($mobile_platforms as $id => $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                self::$mobile = $id;
                return $id;
            }
        }

        self::$mobile = false;
        return false;
    }

    /**
     * Returns the contents of a server variable (from $_SERVER).
     *
     * @param string|null $name Server variable name. If empty, all server variables' values are returned.
     * @param mixed $default The default value, which is returned if no value is found for variable specified in $name parameter.
     * @param string $type Data type to which the value of specified variable must be converted.
     *     Acceptable data types are described for method get().
     * @see self::get()
     * @return mixed
     */
    public static function server($name = null, $default = null, $type = null)
    {
        if ($name && !isset($_SERVER[$name])) {
            $name = strtoupper($name);
        }
        return self::getData($_SERVER, $name, $default, $type);
    }

    /**
     * Alias for waRequest::getMethod()
     *
     * @see waRequest::getMethod()
     */
    public static function method()
    {
        return self::getMethod();
    }

    /**
     * Returns the type of user request.
     *
     * @return string 'post' or 'get'
     */
    public static function getMethod()
    {
        return strtolower(self::server('REQUEST_METHOD'));
    }

    protected static function getData($data, $name = null, $default = null, $type = null)
    {
        if ($name === null) {
            return $data;
        }
        if (isset($data[$name])) {
            return $type ? self::cast($data[$name], $type) : $data[$name];
        } else {
            return $default;
        }
    }

    /**
     * Returns additional request parameters.
     *
     * @param string|null $name Request parameter name. If not specified, method returns values of all available parameters.
     * @param mixed $default Default value, which is returned if no value is set for the specified parameter.
     * @param string $type Data type to which the value of specified parameter must be converted.
     *     Acceptable data types are described for method get().
     * @see self::get()
     * @return mixed
     */
    public static function param($name = null, $default = null, $type = null)
    {
        return self::getData(self::$params, $name, $default, $type);
    }

    /**
     * Sets custom values for additional request parameters.
     *
     * @param string $key Parameter name.
     * @param mixed $value Parameter value. If not specified, default value null is set.
     */
    public static function setParam($key, $value = null)
    {
        if ($value === null && is_array($key)) {
            self::$params = $key;
        } else {
            self::$params[$key] = $value;
        }
    }

    /**
     * Returns user's IP address.
     *
     * @param bool $get_as_int
     * @return string|int IP address either as string or as integer
     */
    public static function getIp($get_as_int = false)
    {
        //
        // Normally the only trusted source of client's IP address is REMOTE_ADDR.
        // Everything else (HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR) comes from headers
        // and may be forged by client himself.
        //
        // However, many hostings use reverse-proxying (such as nginx in front of apache).
        // In such case REMOTE_ADDR will contain an address of a proxy.
        // Unfortunately, we cannot automatically find out whether hosting uses
        // reverse-proxy or not.
        //
        // To control this aspect, there is a config option 'trusted_proxies' in wa-config/config.php
        // When hosting does not use reverse-proxy, 'trusted_proxies' should be set to empty array.
        // When reverse-proxying is used, it should be a list of IP addresses of proxies.
        // The result is that when REMOTE_ADDR is one of listed IPs, getIp() is allowed to look
        // into header-based sources such as HTTP_X_FORWARDED_FOR.
        //
        // The default behaviour in case 'trusted_proxies' is not set is to trust any request
        // to provide IP via headers.
        //

        // IP that directly contacted the server (may turn out to be a proxy)
        $ip = getenv('REMOTE_ADDR');
        if ($ip === '::1') { // ipv6 localhost
            $ip = '127.0.0.1';
        }

        // Check if $ip can be a proxy
        $is_trusted_proxy = true;
        $trusted_proxies = SystemConfig::systemOption('trusted_proxies');
        if (is_array($trusted_proxies)) {
            $trusted_proxies[] = '127.0.0.1';
            $is_trusted_proxy = in_array($ip, $trusted_proxies);
        }

        // get client's IP from headers if allowed
        if ($is_trusted_proxy) {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                // Contains a chain of proxy addresses, the last IP goes directly to the customer to contact the proxy server.
                $ip = array_filter(array_map('trim', explode(',', getenv('HTTP_X_FORWARDED_FOR'))));
                $ip = (string) end($ip);
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            }
        }

        // Convert to int if needed
        if ($get_as_int) {
            $ip = ip2long($ip);
            if ($ip > 2147483647) {
                $ip -= 4294967296;
            }
        }

        return $ip;
    }

    /**
     * Determines user's locale.
     *
     * @param string|bool $default Default value, which is returned if user's locale cannot be determined. If true is
     *     specified, then the same value is used for $browser_only parameter.
     * @param bool $browser_only Flag requiring to determine user's locale using browser headers only and to ignore
     *     additional request parameters set using setParam() method.
     * @return string
     */
    public static function getLocale($default = null, $browser_only = false)
    {
        if ($default === true || $default === 1) {
            $browser_only = true;
        }
        $locales = waLocale::getAll(false);
        if (!$browser_only && $lang = self::param('locale')) {
            foreach ($locales as $l) {
                if (!strcasecmp($lang, $l)) {
                    return $l;
                }
            }
        }
        if ($default && in_array($default, $locales)) {
            $result = $default;
        } else {
            $result = $locales[0];
        }
        if (!self::server('HTTP_ACCEPT_LANGUAGE')) {
            return $result;
        }
        preg_match_all("/([a-z]{1,8})(?:-([a-z]{1,8}))?(?:\s*;\s*q\s*=\s*(1|1\.0{0,3}|0|0\.[0-9]{0,3}))?\s*(?:,|$)/i",
            self::server('HTTP_ACCEPT_LANGUAGE'), $matches);
        $max_q = 0;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $lang = $matches[1][$i];
            if (!empty($matches[2][$i])) {
                $lang .= '_'.$matches[2][$i];
            }
            if (!empty($matches[3][$i])) {
                $q = (float)$matches[3][$i];
            } else {
                $q = 1.0;
            }
            $in_array = false;
            foreach ($locales as $l) {
                if (!strcasecmp($lang, $l)) {
                    $in_array = $l;
                    break;
                }
            }
            if ($in_array && ($q > $max_q)) {
                $result = $in_array;
                $max_q = $q;
            } elseif ($q * 0.8 > $max_q) {
                $n = strlen($lang);
                if (!empty($matches[2][$i])) {
                    $n -= strlen($matches[2][$i]) + 1;
                }
                foreach ($locales as $l) {
                    if (!strncasecmp($l, $lang, $n)) {
                        $result = $l;
                        $max_q = $q * 0.8;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Returns id of design theme used in current frontend page.
     *
     * @return string
     */
    public static function getTheme()
    {
        $app_id = wa()->getConfig()->getApplication();
        $key = wa()->getRouting()->getDomain().'/theme';
        if (($theme_hash = self::get('theme_hash')) && ($theme = self::get('set_force_theme')) !== null) {
            $app_settings_model = new waAppSettingsModel();
            $hash = $app_settings_model->get($app_id, 'theme_hash');
            $global_hash = $app_settings_model->get('webasyst', 'theme_hash');
            if ($theme_hash == md5($hash)) {
                if ($theme && waTheme::exists($theme)) {
                    wa()->getStorage()->set($app_id.'/'.$key, $theme);
                    return $theme;
                } else {
                    wa()->getStorage()->del($app_id.'/'.$key);
                }
            } elseif ($global_hash && $theme_hash == md5($global_hash)) {
                if ($theme && waTheme::exists($theme)) {
                    wa()->getStorage()->set($key, $theme);
                    return $theme;
                } else {
                    wa()->getStorage()->del($key);
                }
            }
        } elseif ((($theme = wa()->getStorage()->get($app_id.'/'.$key)) || ($theme = wa()->getStorage()->get($key))) && waTheme::exists($theme)) {
            return $theme;
        }
        if (self::isMobile()) {
            return self::param('theme_mobile', 'default');
        }
        return self::param('theme', 'default');
    }

    public static function isHttps()
    {
        if (!empty($_SERVER['HTTP_X_HTTPS']) && strtolower($_SERVER['HTTP_X_HTTPS']) != 'off') {
            return true;
        }
        if (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) {
            return true;
        }
        if (!empty($_SERVER['HTTP_HTTPS']) && (strtolower($_SERVER['HTTP_HTTPS']) == 'on' || $_SERVER['HTTP_HTTPS'] == '1')) {
            if (($_SERVER['HTTP_HTTPS'] != '1') && (strpos(waRequest::getUserAgent(), 'Chrome/44.0') === false)) {
                return true;
            }
        }
        if (!empty($_SERVER['HTTP_SSL']) && $_SERVER['HTTP_SSL'] == 1) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_SSL']) && (strtolower($_SERVER['HTTP_X_SSL']) == 'yes' || $_SERVER['HTTP_X_SSL'] == '1')) {
            return true;
        }
        if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_SCHEME']) && strtolower($_SERVER['HTTP_X_SCHEME']) == 'https') {
            return true;
        }
        return false;
    }
}
