<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2018 Webasyst LLC
 * @package wa-system
 * @subpackage locale
 */

class waLocaleGettextAdapter implements waiLocaleAdapter
{
    /**
     * Array of domains and adapter objects and the paths to the po files
     * @var array
     */
    protected static $loaded_domains = array();

    public function load($locale, $locale_path, $domain, $textdomain = true)
    {
        // Get lang from locale
        $lang = preg_replace('!_.*!', '', $locale);
        // Put LC_ALL and LANG to environment
        putenv('LC_ALL='.$locale);
        putenv('LANG='.$locale);
        putenv('LANGUAGE='.$locale);

        // Set locale
        if (!setlocale (LC_ALL, $locale.".utf8", $locale.".utf-8", $locale.".UTF8", $locale.".UTF-8", $lang.'.UTF-8', $locale, $lang)) {
            // Set current locale
            if (!setlocale(LC_ALL, '')) {
                // ...
            }
        }

        // Always use dot separator when formatting floats
        setlocale(LC_NUMERIC, 'C');

        // Bind domain
        bindtextdomain($domain, $locale_path);
        bind_textdomain_codeset($domain, 'UTF-8');
        // Set default domain
        if ($textdomain) {
            textdomain($domain);
        }

        $locale_file = $this->buildLocaleFile($locale_path, $locale, $domain);
        if (file_exists($locale_file)) {
            self::$loaded_domains[$domain][$locale] = $locale_file;
        }
    }

    public function gettext($msgid)
    {
        return gettext($msgid);
    }

    public function ngettext($msgid1, $msgid2, $n)
    {
        return ngettext($msgid1, $msgid2, $n);
    }

    public function dgettext($domain, $msgid)
    {
        return dgettext($domain, $msgid);
    }

    public function dngettext($domain, $msgid1, $msgid2, $n)
    {
        return dngettext($domain, $msgid1, $msgid2, $n);
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