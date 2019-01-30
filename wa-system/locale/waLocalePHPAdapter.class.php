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
class waLocalePHPAdapter implements waiLocaleAdapter
{
    /**
     * Array of domains and adapter objects and the paths to the po files
     * @var array
     */
    protected static $loaded_domains = array();
    protected static $locale;
    protected static $domain;
    protected static $cache = array();

    public function load($locale, $locale_path, $domain, $textdomain = true)
    {
        $locale_file = $this->buildLocaleFile($locale_path, $locale, $domain);
        $cache_file = waSystem::getInstance()->getConfig()->getPath('cache').'/apps/'.$domain.'/locale/'.$locale.'.php';

        if (isset(self::$cache[$locale][$domain])) {

        } elseif (!file_exists($locale_file)) {
            self::$cache[$locale][$domain] = array();
        } elseif (file_exists($cache_file) && filemtime($cache_file) > filemtime($locale_file)) {
            self::$cache[$locale][$domain] = include($cache_file);
        } else {
            if (file_exists($locale_file)) {
                $gettext = new waGettext($locale_file);
                self::$cache[$locale][$domain] = $gettext->read();
            } else {
                self::$cache[$locale][$domain] = array();
            }

            waFiles::create($cache_file);
            waUtils::varExportToFile(self::$cache[$locale][$domain], $cache_file);
        }

        if (isset(self::$cache[$locale][$domain]['meta']['Plural-Forms']['plural']) && self::$cache[$locale][$domain]['meta']['Plural-Forms']['plural']) {
            self::$cache[$locale][$domain]['meta']['f'] = wa_lambda('$n', self::$cache[$locale][$domain]['meta']['Plural-Forms']['plural']);
        }

        if ($textdomain) {
            self::$domain = $domain;
            self::$locale = $locale;
        }

        if (!self::$locale) {
            self::$locale = $locale;
        }

        if (file_exists($locale_file)) {
            self::$loaded_domains[$domain][$locale] = $locale_file;
        }
    }

    public function gettext($msgid)
    {
        return $this->dgettext(self::$domain, $msgid);
    }

    public function ngettext($msgid1, $msgid2, $n)
    {
        return $this->dngettext(self::$domain, $msgid1, $msgid2, $n);
    }

    public function dgettext($domain, $msgid)
    {
        $msgid = self::asStr($msgid);
        $domain = self::asStr($domain);
        if (isset(self::$cache[self::$locale][$domain]['messages'][$msgid])) {
            $m = self::$cache[self::$locale][$domain]['messages'][$msgid];
            return is_array($m) ? current($m) : $m;
        } else {
            return $msgid;
        }
    }

    /** Returns string representation of $v without throwing fatal errors. */
    public static function asStr($v)
    {
        if (is_object($v) && !method_exists($v, '__toString')) {
            return 'Object';
        }
        return (string) $v;
    }

    public function dngettext($domain, $msgid1, $msgid2, $n)
    {
        if (isset(self::$cache[self::$locale][$domain]['messages'][$msgid1])) {
            $m = self::$cache[self::$locale][$domain]['messages'][$msgid1];
            if (is_array($m)) {
                if (isset(self::$cache[self::$locale][$domain]['meta']['f'])) {
                    $f = self::$cache[self::$locale][$domain]['meta']['f'];
                    $i = $f($n);
                    return $m[$i];
                }
            } else {
                return $m;
            }
        }
        return $n == 1 ? $msgid1 : $msgid2;
    }

    /**
     * @param string $domain
     * @param string $locale
     * @param string $locale_path
     * @return bool
     */
    public function isLoaded($locale, $locale_path, $domain)
    {
        $locale_file = $this->buildLocaleFile($locale_path, $locale, $domain);
        if (ifset(self::$loaded_domains[$domain][$locale]) == $locale_file) {
            return true;
        }
        return false;
    }

    /**
     * @param string $locale_path
     * @param string $locale
     * @param string $domain
     * @return string
     */
    protected function buildLocaleFile($locale_path, $locale, $domain)
    {
        return $locale_path.'/'.$locale.'/LC_MESSAGES/'.$domain.'.po';
    }
}
