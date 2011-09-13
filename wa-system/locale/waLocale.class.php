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
    protected static $adapter;

    protected static $loaded = array();

    protected static $locale_info = array();

    protected static $init = false;

    protected function __construct() {}
    protected function __clone() {}

    public static function init()
    {
        if (self::$init) {
            return false;
        }
        self::$init = true;
        // Alias to gettext

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || !function_exists('gettext')) {
            self::$adapter = new waLocalePHPAdapter();
        } else {
            self::$adapter = new waLocaleAdapter();
        }
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
            return;
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
        //if ($locale != self::$locale || !isset(self::$loaded[$locale][$domain])) {
            self::$locale = $locale;
            self::$loaded[$locale][$domain] = true;
            self::getAdapter()->load($locale, $locale_path, $domain, $textdomain);
        //}
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
            $path = dirname(__FILE__)."/data/".$locale.".php";
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
                $decimals = strlen($n) - $i - 1;
            } elseif (($i = strpos($n, ',')) !== false) {
                $decimals = strlen($n) - $i - 1;
            }
        } elseif ($decimals === null) {
            $decimals = $locale_info['frac_digits'];
        }

        return number_format($n, $decimals, $locale_info['decimal_point'], $locale_info['thousands_sep']);
    }

    public static function getAll($type = false)
    {
        $locale_config = waSystem::getInstance()->getConfigPath().'/locale.php';
        if (file_exists($locale_config)) {
            $cache = new waSystemCache('config/locale', time() - filemtime($locale_config));
            if ($cache->isCached()) {
                $data = $cache->get();
            } else {
                $data = array();
                $locales = include($locale_config);
                foreach ($locales as $locale) {
                    if ($info = self::getInfo($locale)) {
                        $data[$locale] = $info;
                    }
                }
                $cache->set($data);
            }
        } else {
            $data = array(
                'en_US' => self::getInfo('en_US'),
                'ru_RU' => self::getInfo('ru_RU'),
            );
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
            default:
                return $data;
        }

        return $data;
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
        return waLocale::getAdapter()->gettext($msgid1);
    } elseif ($n === 'm' || $n === 'f') {
        return waLocale::getAdapter()->ngettext($msgid1, $msgid2, $n === 'm' ? 1 : 2);
    } else {
        $str = waLocale::getAdapter()->ngettext($msgid1, $msgid2, $n);
        if ($sprintf && ($i = strpos($str, '%')) !== false) {
            return sprintf($str, $n);
        }
        return $str;
    }
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
        return waLocale::getAdapter()->dgettext($domain, $msgid1);
    } else {
        $str = waLocale::getAdapter()->dngettext($domain, $msgid1, $msgid2, $n);
        if ($sprintf && strpos($str, '%d') !== false) {
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
    if( ( $domain = wa()->getActiveLocaleDomain())) {
        return _wd($domain, $msgid1, $msgid2, $n, $sprintf);
    } else {
        return _w($msgid1, $msgid2, $n, $sprintf);
    }
}
