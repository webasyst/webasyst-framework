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
 * @package wa-installer
 */

class waInstallerLocale
{
    private static $strings = array();
    private $locale;

    public function __construct($default = null)
    {
        $this->locale = $default ? $default : $this->detect();
        $path = dirname(__FILE__).'/../../locale/'.$this->locale.'.php';
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $this->locale) && file_exists($path)) {
            if (!isset(self::$strings[$this->locale]) || !is_array(self::$strings[$this->locale])) {
                self::$strings[$this->locale] = include($path);
            }
            if (!is_array(self::$strings[$this->locale])) {
                self::$strings[$this->locale] = array();
            }
        }
    }

    public function getLocale()
    {
        return $this->locale;
    }

    private function detect()
    {
        $lang = !empty($_POST['lang']) ? $_POST['lang'] : (!empty($_GET['lang']) ? $_GET['lang'] : false);

        $locales = self::listAvailable();
        if ($lang) {
            foreach ($locales as $l) {
                if (!strcasecmp($lang, $l)) {
                    return $l;
                }
            }
        }

        $result = reset($locales);

        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $result;
        }
        preg_match_all("/([a-z]{1,8})(?:-([a-z]{1,8}))?(?:\s*;\s*q\s*=\s*(1|1\.0{0,3}|0|0\.[0-9]{0,3}))?\s*(?:,|$)/i", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
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

    public static function listAvailable()
    {
        $available = array();
        $path = dirname(__FILE__).'/../../locale/';
        $content = scandir($path);
        foreach ($content as $item) {
            if (preg_match('/^([a-z]{2}_[A-Z]{2})\.php$/', $item, $matches)) {
                $available[$matches[1]] = $matches[1];
            }
        }
        return $available;
    }

    public function _()
    {
        $args = func_get_args();
        $string = current($args);
        $string = isset(self::$strings[$this->locale][$string]) ? self::$strings[$this->locale][$string] : $string;
        if (count($args)) {
            $format = $string;
            $string = implode(', ', $args);
            array_shift($args);
            if (count($args)) {
                $formatted = @vsprintf($format, $args);
                if ($formatted !== false) {
                    $string = $formatted;
                }
            } else {
                $string = $format;
            }
        }
        return $string;
    }
}
