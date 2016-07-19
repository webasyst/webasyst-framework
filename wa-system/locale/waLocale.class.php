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
 * @subpackage locale
 */
class waLocale
{
    protected static $locale;
    protected static $domain;
    /**
     * @var waLocaleAdapter
     */
    public static $adapter;

    protected static $loaded = array();

    protected static $locale_info = array();

    protected static $init = false;

    protected static $strings = array();

    protected function __construct() {}
    protected function __clone() {}

    public static function init($adapter = null)
    {
        if (!self::$init) {
            self::$init = true;
            // Alias to gettext

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || !function_exists('gettext')) {
                self::$adapter = new waLocalePHPAdapter();
            } else {
                self::$adapter = $adapter ? $adapter :  new waLocaleAdapter();
            }
        }
    }

    public static function getLocale()
    {
        return self::$locale;
    }

    public static function setStrings(array $strings)
    {
        self::$strings = $strings;
    }

    public static function getString($id)
    {
        return ifset(self::$strings[$id]);
    }

    /**
     * Don't use this function!
     *
     * @param string $domain
     * @param string $locale
     * @param string $msgid
     * @deprecated
     * @return string translated string $msgid
     */
    public static function translate($domain, $locale, $msgid)
    {
        $old_locale = null;
        // load new locale
        if (self::$locale != $locale) {
            $old_locale = self::$locale;
        }
        self::loadByDomain($domain, $locale);
        $result = _wd($domain, $msgid);
        // load old locale
        if ($old_locale) {
            self::$locale = $old_locale;
            self::loadByDomain($domain, $old_locale);
        }
        return $result;
    }

    public static function loadByDomain($domain, $locale = null)
    {
        if ($locale === null) {
            $locale = self::$locale;
        }
        if (is_array($domain)) {
            $locale_path = waSystem::getInstance()->getAppPath('plugins/'.$domain[1].'/locale', $domain[0]);
            $domain = $domain[0].'_'.$domain[1];
        } else {
            $locale_path = waSystem::getInstance()->getAppPath('locale', $domain);
        }
        if (isset(self::$loaded[$locale][$domain])) {
//            todo: do something
//            return;
        }
        if (file_exists($locale_path)) {
            self::load($locale, $locale_path, $domain, false);
        }
    }

    /**
     * Returns locale adapter
     *
     * @return waLocaleAdapter|waLocalePHPAdapter
     */
    public static function getAdapter()
    {
        return self::$adapter;
    }

    public static function load($locale, $locale_path, $domain, $textdomain = true)
    {
        if (!self::$locale || $textdomain) {
            self::$locale = $locale;
        }
        self::$loaded[$locale][$domain] = true;
        self::getAdapter()->load($locale, $locale_path, $domain, $textdomain);
        if ($textdomain) {
            self::$domain = $domain;
        }
    }

    public static function getDomain()
    {
        return self::$domain;
    }

    public static function getFirstDay($locale = null)
    {
        if (!$locale) {
            $locale = self::$locale;
        }
        $locale = self::getInfo($locale);
        return isset($locale['first_day']) ? $locale['first_day'] : 1;
    }

    public static function getInfo($locale)
    {
        if (!isset(self::$locale_info[$locale])) {
            if (strpbrk($locale, '/\:')) {
                return null;
            }
            $path = wa()->getConfig()->getPath('system')."/locale/data/".$locale.".php";
            if (file_exists($path)) {
                self::$locale_info[$locale] = include($path);
            } else {
                return null;
            }
        }
        return self::$locale_info[$locale];
    }

    public static function format($n, $decimals = null, $locale = null)
    {
        if ($locale === null) {
            $locale = self::$locale;
        }
        $locale_info = self::getInfo($locale);

        if ($decimals === false) {
            $decimals = 0;
            if (($i = strpos($n, '.')) !== false) {
                $decimals = strlen(rtrim($n, '0')) - $i - 1;
            } elseif (($i = strpos($n, ',')) !== false) {
                $decimals = strlen(rtrim($n, '0')) - $i - 1;
            }
        } elseif ($decimals === null) {
            $decimals = $locale_info['frac_digits'];
        }

        return number_format($n, $decimals, $locale_info['decimal_point'], $locale_info['thousands_sep']);
    }

    /**
     * @param bool $type
     * @param bool $enabled_only
     * @return array|null
     * @throws waException
     */
    public static function getAll($type = false, $enabled_only = true)
    {
        $locale_config = waSystem::getInstance()->getConfigPath().'/locale.php';
        if (file_exists($locale_config)) {
            $enabled_locales = include($locale_config);
            $ttl = time() - filemtime($locale_config);
        } else {
            $enabled_locales = array('en_US', 'ru_RU');
            $ttl = 86400;
        }

        $cache = new waSystemCache('config/locale', $ttl);
        if ($cache->isCached()) {
            $data = $cache->get();
        } else {
            $data = array();
            foreach ($enabled_locales as $locale) {
                if ($info = self::getInfo($locale)) {
                    $data[$locale] = $info;
                }
            }
            $files = waFiles::listdir(dirname(__FILE__)."/data/");
            foreach ($files as $file) {
                if (preg_match('/^([a-zA-Z_]+)\.php$/', $file, $matches)) {
                    $locale = $matches[1];
                    if (!isset($data[$locale]) && ($info = self::getInfo($locale))) {
                        $data[$locale] = $info;
                    }
                }
            }
            $cache->set($data);
        }

        if ($enabled_only) {
            $result = array();
            foreach ($enabled_locales as $locale) {
                if (isset($data[$locale])) {
                    $result[$locale] = $data[$locale];
                }
            }
            $data = $result;
        }

        if ($type === true) {
            $type = 'all';
        }

        switch ($type) {
            case 'name_region':
                foreach ($data as &$d) {
                    $d = $d['name']." (".$d['region'].')';
                }
                asort($data);
                break;
            case 'name':
                foreach ($data as &$d) {
                    $d = $d['name'];
                }
                asort($data);
                break;
            case false:
                return array_keys($data);
            default:
                return $data;
        }

        return $data;
    }

    public static function getByISO3($iso3)
    {
        switch ($iso3) {
            case 'rus':
                $l = 'ru_RU'; break;
            default:
                $l = 'en_US'; break;
        }

        if (self::getInfo($l)) {
            return $l;
        }

        return null;
    }

    /**
     * Return string from an array depending on locale.
     *
     * When $arr is not an array, return it.
     * Otherwise return one of (in order of priority):
     * - $arr[$locale]
     * - $arr['en_US']
     * - first element in $arr
     * - ''
     *
     * @param array|string $arr strings in different locales, locale => string
     * @param string $locale defaults to current active locale
     * @return string
     */
    public static function fromArray($arr, $locale=null)
    {
        if (!is_array($arr)) {
            return $arr;
        } else if (!$arr) {
            return '';
        }

        if (!$locale) {
            $locale = wa()->getLocale();
        }

        if(isset($arr[$locale])) {
            return $arr[$locale];
        }
        if(isset($arr['en_US'])) {
            return $arr['en_US'];
        }
        return reset($arr);
    }

    /**
     * Transliterate value using transliteration table from locale settings (if exists).
     * Recursively applies self to arrays.
     *
     * @param string|array $value
     * @param string $locale defaults to current system locale
     * @return string|array transliterated $value
     */
    public static function transliterate($value, $locale=null)
    {
        if (!$locale) {
            $locale = self::getLocale();
        }

        $t = self::getInfo($locale);
        if (!isset($t['translit_table'])) {
            return $value;
        }
        /**
         * @var $t array
         */
        $t = $t['translit_table'];

        if (is_array($value)) {
            foreach($value as &$v) {
                $v = self::transliterate($v, $locale);
            }
            return $value;
        }

        return str_replace(array_keys($t), array_values($t), $value);
    }
}

/**
 * Translate string
 *
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param bool $sprintf
 * @return string
 */
function _w($msgid1, $msgid2 = null, $n = null, $sprintf = true)
{
    if ($msgid1 === '' || $msgid1 === null) {
        return $msgid1;
    }
    if ($msgid2 === null) {
        return waLocale::$adapter->gettext($msgid1);
    } elseif ($n === 'm' || $n === 'f') {
        return waLocale::$adapter->ngettext($msgid1, $msgid2, $n === 'm' ? 1 : 2);
    } else {
        $str = waLocale::$adapter->ngettext($msgid1, $msgid2, $n);
        if ($sprintf && strpos($str, '%') !== false) {
            return sprintf($str, $n);
        }
        return $str;
    }
}

/** Copy of sprintf() with the first (string) argument passed to _wp() beforehand. */
function sprintf_wp()
{
    $args = func_get_args();
    array_unshift($args, _wp(array_shift($args), null, null, false));
    return call_user_func_array('sprintf', $args);
}

/**
 * Translate string using system locale
 *
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param bool $sprintf
 * @return string
 */
function _ws($msgid1, $msgid2 = null, $n = null, $sprintf = true)
{
    return _wd('webasyst', $msgid1, $msgid2, $n, $sprintf);
}

/**
 * Translate string using locale of domain
 *
 * @param string $domain
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param bool $sprintf
 * @return string
 */
function _wd($domain, $msgid1, $msgid2 = null, $n = null, $sprintf = true)
{
    if ($msgid1 === '' || $msgid1 === null) {
        return $msgid1;
    }
    if ($msgid2 === null) {
        return waLocale::$adapter->dgettext($domain, $msgid1);
    } else {
        $str = waLocale::$adapter->dngettext($domain, $msgid1, $msgid2, $n);
        if ($sprintf && strpos($str, '%') !== false) {
            return sprintf($str, $n);
        }
        return $str;
    }
}

/**
 * Translate string in domain of current active plugin, if any.
 * Otherwise fall back to _w()
 *
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param bool $sprintf
 * @return string
 */
function _wp($msgid1, $msgid2 = null, $n = null, $sprintf = true)
{
    if ($domain = wa()->getActiveLocaleDomain()) {
        return _wd($domain, $msgid1, $msgid2, $n, $sprintf);
    } else {
        return _w($msgid1, $msgid2, $n, $sprintf);
    }
}
