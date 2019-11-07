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

/**
 * A hybrid adapter, which is a waLocalePHPAdapter, if .po file has been edited recently (15 minutes)
 * or a waLocaleGettextAdapter, if edited long ago
 */
class waLocaleAdapter implements waiLocaleAdapter
{
    /**
     * Array of domains and adapter objects and the paths to the po files
     * @var array
     */
    protected static $loaded_domains = array();
    protected static $selected_domain = 'webasyst';
    protected static $last_locale;
    protected $freshness_time = 900; // 15 mins

    protected static $php_adapter;
    protected static $gettext_adapter;

    protected static $adapter_by_file = array();

    public function load($locale, $locale_path, $domain, $textdomain = true)
    {
        $adapter = $this->getAdapter($domain, $locale, $locale_path);
        $adapter->load($locale, $locale_path, $domain, $textdomain);

        if ($textdomain) {
            self::$selected_domain = $domain;
            self::$last_locale = $locale;
        }
        if (!self::$last_locale) {
            self::$last_locale = $locale;
        }

        $locale_file = $this->buildLocaleFile($locale_path, $locale, $domain);
        if (file_exists($locale_file)) {
            self::$loaded_domains[$domain][$locale] = $locale_file;
        }
    }

    public function gettext($msgid)
    {
        return $this->getAdapter(self::$selected_domain)->gettext($msgid);
    }

    public function ngettext($msgid1, $msgid2, $n)
    {
        return $this->getAdapter(self::$selected_domain)->ngettext($msgid1, $msgid2, $n);
    }

    public function dgettext($domain, $msgid)
    {
        return $this->getAdapter($domain)->dgettext($domain, $msgid);
    }

    public function dngettext($domain, $msgid1, $msgid2, $n)
    {
        return $this->getAdapter($domain)->dngettext($domain, $msgid1, $msgid2, $n);
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
        if (isset(self::$loaded_domains[$domain][$locale]) && self::$loaded_domains[$domain][$locale] == $locale_file) {
            return true;
        }
        return false;
    }

    /**
     * @param string $domain
     * @param null|string $locale
     * @param null|string $locale_path
     * @return waLocaleGettextAdapter|waLocalePHPAdapter
     */
    protected function getAdapter($domain, $locale = null, $locale_path = null)
    {
        if (!$locale) {
            $locale = self::$last_locale;
        }
        $locale_file = !empty(self::$loaded_domains[$domain][$locale]) ? self::$loaded_domains[$domain][$locale] : null;
        if (!$locale_file) {
            $locale_file = $this->buildLocaleFile($locale_path, $locale, $domain);
        }

        if (!isset(self::$adapter_by_file[$locale_file])) {
            if ($this->isChangedRecently($locale_file)) {
                $adapter = $this->getPhpAdapter();
            } else {
                $adapter = $this->getGettextAdapter();
            }
            self::$adapter_by_file[$locale_file] = $adapter;
        }
        return self::$adapter_by_file[$locale_file];
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function isChangedRecently($file) {
        $global_config = wa()->getConfig()->getPath('config').'/config.php';
        if ($this->freshness_time > (time() - @filemtime($global_config))) {
            return true;
        }
        if (!file_exists($file) || $this->freshness_time > (time() - @filemtime($file))) {
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

    /**
     * @return waLocaleGettextAdapter
     */
    protected function getGettextAdapter()
    {
        if (self::$gettext_adapter instanceof waLocaleGettextAdapter) {
            return self::$gettext_adapter;
        }
        return self::$gettext_adapter = new waLocaleGettextAdapter();
    }

    /**
     * @return waLocalePHPAdapter
     */
    protected function getPhpAdapter()
    {
        if (self::$php_adapter instanceof waLocalePHPAdapter) {
            return self::$php_adapter;
        }
        return self::$php_adapter = new waLocalePHPAdapter();
    }
}
