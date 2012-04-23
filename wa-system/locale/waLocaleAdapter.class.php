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
class waLocaleAdapter
{
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
        // Bind domain
        bindtextdomain($domain, $locale_path);
        bind_textdomain_codeset($domain, 'UTF-8');
        // Set default domain
        if ($textdomain) {
            textdomain($domain);
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
}
