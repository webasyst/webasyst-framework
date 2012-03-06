<?php

/**
 * 3-letter ISO 3166 code is used as an id for this waLocalizedCollection
 * !!! filtering is not implemented yet.
 */
class waCountryModel extends waModel implements waLocalizedCollection
{
    /** Cached results for $this->updateLocaleDb() for different locales. */
    protected static $localeDbUpdated = array();
    
    protected static $cacheLocale = null;
    protected static $cache = null;
    
    protected static $instance;
    
    public function all($start=0, $limit=FALSE, $locale=null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
            if (!$locale) {
                $locale = 'en_US';
            }
        }
        
        if (self::$cacheLocale == $locale) {
            return self::$cache;
        }
        
        if (!$this->updateLocaleDb($locale)) {
            // no translation files for this locale
            $locale = 'en_US';
        }
        
        if($start || $limit) {
            $limit = 'LIMIT '.($start ? $start : 0).', '.($limit ? $limit : '100500'); 
        } else {
            $limit = '';
        }
        $sql = "SELECT * FROM wa_country WHERE locale=:locale ORDER BY name $limit";
        return $this->query($sql, array('locale' => $locale))->fetchAll('iso3letter');
    }
    
    public function filter($parameters, $start=0, $limit=0, $locale=null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
            if (!$locale) {
                $locale = 'en_US';
            }
        }
        if (!$this->updateLocaleDb($locale)) {
            // no translation files for this locale
            $locale = 'en_US';
        }
        
        // !!!
    }
    
    public function name($id, $locale=null)
    {
        $a = $this->get($id, $locale);
        return $a['name'];
    }
    
    public function get($id, $locale=null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
            if (!$locale) {
                $locale = 'en_US';
            }
        }
        
        if (self::$cacheLocale == $locale) {
            return isset(self::$cache[$id]) ? self::$cache[$id] : null;
        }
        
        if (!$this->updateLocaleDb($locale)) {
            // no translation files for this locale
            $locale = 'en_US';
        }
        
        $sql = "SELECT * FROM wa_country WHERE locale=:locale && iso3letter=:id";
        return $this->query($sql, array('locale' => $locale, 'id' => $id))->fetchAssoc();
    }
    
    public function count($parameters = null)
    {
        if ($parameters === null) {
            if (self::$cacheLocale) {
                return count(self::$cache);
            }
            $sql = "SELECT COUNT(*) FROM wa_country WHERE locale='en_US'";
        } else {
            // !!!
            return 0;
        }
        return $this->query($sql)->fetchField();
    }
    
    public function preload($locale=null)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
            if (!$locale) {
                $locale = 'en_US';
            }
        }

        self::$cache = $this->all(0, FALSE, $locale);
        self::$cacheLocale = $locale;
    }
    

    /**
     * If $locale exists, make sure DB is up to date and return true.
     * If no files found for $locale, return FALSE.
     *
     * @param string $locale
     * @return bool
     */
    protected function updateLocaleDb($locale)
    {
        if ($locale == 'en_US') {
            return TRUE;
        }
        if (isset(self::$localeDbUpdated[$locale])) {
            return self::$localeDbUpdated[$locale];
        }
        
        $localeFile = waSystem::getInstance('webasyst')->getConfig()->getAppPath("locale/$locale/LC_MESSAGES/webasyst.po");
        if (!file_exists($localeFile)) {
            //throw new waException('No such file: '.$localeFile);
            self::$localeDbUpdated[$locale] = FALSE;
            return self::$localeDbUpdated[$locale];
        }
        $lastLocateUpdate = filemtime($localeFile);
        
        // lock
        $cache = waSystem::getInstance('webasyst')->getAppCachePath('config/countries_locale_updates.txt');
        if (!file_exists($cache)) {
            touch($cache);
        }
        $fd = fopen($cache, 'r+b');
        if (!flock($fd, LOCK_EX)) {
            fclose($fd);
            return FALSE;
        }
        
        $lastDbUpdate = array();
        $file = fread($fd, 99999); // file_get_contents does not wirk with flock(LOCK_EX) on windows; 100Kb is reasonable enough
        foreach(explode("\n", $file) as $line) {
            $line = trim($line);
            if (!$line || FALSE === strpos($line, ':')) {
                continue;
            }
            list($loc, $time) = explode(':', $line, 2);
            $lastDbUpdate[$loc] = $time; 
        }
        
        if (!isset($lastDbUpdate[$locale])) {
            $lastDbUpdate[$locale] = 0;
        }
        
        if ($lastDbUpdate[$locale] < $lastLocateUpdate) {
            // load default (en_US) locale, translate names to $locale with gettext and save them back to DB.
            $sql = "SELECT * FROM wa_country WHERE locale='en_US'";
            $values = '';
            $loc = $this->escape($locale);
            foreach($this->query($sql) as $row) {
                // waLocale::translate is SLOW if current locale != $locale, but this update doesn't run often 
                $name = $this->escape(waLocale::translate('webasyst', $locale, $row['name'])); 
                $iso3letter = $this->escape($row['iso3letter']);
                $iso2letter = $this->escape($row['iso2letter']);
                $isonumeric = $this->escape($row['isonumeric']);
                $values .= ($values ? ',' : '')."('$name', '$iso3letter', '$iso2letter', '$isonumeric', '$loc')"; 
            }
            
            $sql = "REPLACE INTO wa_country (name, iso3letter, iso2letter, isonumeric, locale) VALUES ".$values;
            $this->exec($sql);
            
            // save lastDbUpdate time
            ftruncate($fd, 0);
            fseek($fd, 0);
            $lastDbUpdate[$locale] = time();
            foreach($lastDbUpdate as $loc => $time) {
                fwrite($fd, "$loc:$time\n");
            }
        }
        
        flock($fd, LOCK_UN);
        fclose($fd);
        self::$localeDbUpdated[$locale] = TRUE;
        return self::$localeDbUpdated[$locale];
    }
    
    /** 
     * @return waCountryModel
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
