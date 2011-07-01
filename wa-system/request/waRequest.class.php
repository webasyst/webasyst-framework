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

    protected static $params = array();

    public function __construct () {}

    protected static function cast($val, $type = false)
    {
        $type = trim(strtolower($type));
        switch ($type) {
            case self::TYPE_INT: {
                return (int)$val;
            }
            case self::TYPE_STRING_TRIM: {
                return trim($val);
            }
            case self::TYPE_ARRAY_INT: {
                if (!is_array($val)) {
                    $val = explode(",", $val);
                }
                foreach ($val as &$v) {
                    $v = self::cast($v, self::TYPE_INT);
                }
                return $val;
            }
            case self::TYPE_STRING:
            default: {
                return $val;
            }
        }
    }

    public static function post($name = null, $default = null, $type = null)
    {
        return self::getData($_POST, $name, $default, $type);
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
        foreach ($desktop_platforms as $id => $pattern) {
            if (preg_match('/'.$pattern.'/i', $user_agent)) {
                return false;
            }
        }

        $mobile_platforms = array(
            "android"       => "android",
            "blackberry"    => "blackberry",
            "iphone"        => "(iphone|ipod)",
            "opera"         => "opera mini",
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

    protected static function getData($data, $name = false, $default = false, $type = false)
    {
        if (!$name) {
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
        if ($value === null) {
            self::$params = $key;
        } else {
            self::$params[$key] = $value;
        }
    }
}
