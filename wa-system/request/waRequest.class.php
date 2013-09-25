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
    const TYPE_ARRAY_INT = 'array_int';
    const TYPE_ARRAY = 'array';

    protected static $params = array();

    public function __construct () {}

    protected static function cast($val, $type = null)
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
                    $val = (array) $val;
                }
                break;
        }
        return $val;
    }

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

    public static function issetPost($name)
    {
        return isset($_POST[$name]);
    }

    public static function get($name = null, $default = null, $type = null)
    {
        return self::getData($_GET, $name, $default, $type);
    }

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

    public static function cookie($name = null, $default = null, $type = null)
    {
        return self::getData($_COOKIE, $name, $default, $type);
    }

    public static function isXMLHttpRequest()
    {
        return self::server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }

    public static function getUserAgent()
    {
        return self::server('HTTP_USER_AGENT');
    }

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
        $user_agent = self::server('HTTP_USER_AGENT');

        $desktop_platforms = array(
            'ipad' => 'ipad',
            'galaxy-tab' => 'android.*?GT\-P'
        );
        foreach ($desktop_platforms as $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                return false;
            }
        }

        $mobile_platforms = array(
            "android"       => "android",
            "blackberry"    => "blackberry",
            "iphone"        => "(iphone|ipod)",
            "opera"         => "opera (mini|mobi)",
            "palm"          => "(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
            "windows"       => "windows\sce;\s(iemobile|ppc|smartphone)",
            "generic"       => "(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)"
        );
        foreach ($mobile_platforms as $id => $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                return $id;
            }
        }

        return false;
    }

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
     * Return $_SERVER['REQUEST_METHOD']
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
            return self::getDefault($default);
        }
    }

    protected static function getDefault(&$default)
    {
        return is_array($default) && $default ? array_shift($default) : $default;
    }

    public static function param($name = null, $default = null, $type = null)
    {
        return self::getData(self::$params, $name, $default, $type);
    }

    public static function setParam($key, $value = null)
    {
        if ($value === null && is_array($key)) {
            self::$params = $key;
        } else {
            self::$params[$key] = $value;
        }
    }

    public static function getIp($get_as_int = false)
    {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = getenv('REMOTE_ADDR');
        }
        if ($get_as_int) {
            $ip = ip2long($ip);
            if ($ip > 2147483647) {
              $ip -= 4294967296;
            }
        }
        return $ip;
    }

    /**
     * Returns locale by header Accept-Language
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

    public static function getTheme()
    {
        $app_id =  wa()->getConfig()->getApplication();
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
}
