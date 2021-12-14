<?php

class waCountryModel extends waModel
{
    protected $table = 'wa_country';
    protected static $cacheLocale = null;
    protected static $cache = null;

    protected static $instance;

    public function all($start=0, $limit=FALSE, $locale=null)
    {
        if($start || $limit) {
            $locale = $this->ensureLocale($locale);
            $limit = 'LIMIT '.($start ? $start : 0).', '.($limit ? $limit : '100500');
            $sql = "SELECT * FROM wa_country ORDER BY name $limit";
            return $this->translate($locale, $this->query($sql));
        } else {
            $this->preload($locale);
            return self::$cache;
        }
    }

    public function allWithFav($all=null)
    {
        if (!$all || !is_array($all)) {
            $all = array_values($this->all());
        }
        $fav = array();
        foreach($all as $c) {
            if (!empty($c['fav_sort'])) {
                $fav[] = array('fav_sort' => $c['fav_sort']) + $c;
            }
        }
        if ($fav) {
            sort($fav); // sort by fav_sort, name
            $fav[] = $this->getEmptyRow(); // delimeter
        }
        return array_merge($fav, $all);
    }

    public function name($id, $locale=null)
    {
        $a = $this->get($id, $locale);
        return isset($a['name']) ? $a['name'] : null;
    }

    public function get($id, $locale=null)
    {
        // self::$cache works only after full locale preload
        // It's good when it's there, but we can not rely on it.
        $locale = $this->ensureLocale($locale);
        if (self::$cacheLocale == $locale) {
            return isset(self::$cache[$id]) ? self::$cache[$id] : null;
        }

        // Local cache saves individual lines.
        // Profiling shows that it is reasonable to have such cache here: it saves
        // several queries per page, when there are several addresses rendered on a page.
        // E.g. any contact form.
        static $cache = array();
        if ($locale === $this->ensureLocale(null) && !empty($cache[$id])) {
            return $cache[$id];
        }

        $sql = "SELECT * FROM wa_country WHERE iso3letter=:id";
        $r = $this->translate($locale, $this->query($sql, array('id' => $id)));

        if ($r && !empty($r[$id])) {
            if ($locale === $this->ensureLocale(null)) {
                $cache[$id] = $r[$id];
            }
            return $r[$id];
        }
        return null;
    }

    public function count($parameters = null)
    {
        if ($parameters === null) {
            if (self::$cacheLocale) {
                return count(self::$cache);
            }
            $sql = "SELECT COUNT(*) FROM wa_country";
            return $this->query($sql)->fetchField();
        } else {
            throw new waException('Not implemented.'); // !!!
        }
    }

    public function preload($locale=null)
    {
        $locale = $this->ensureLocale($locale);
        if (self::$cacheLocale !== $locale) {
            self::$cache = $this->all(0, 100500, $locale); // limit set to force loading from db, not calling preload()
            self::$cacheLocale = $locale;
        }
    }

    // Return $locale if it exists, or en_US otherwise.
    protected function ensureLocale($locale)
    {
        if (!$locale) {
            $locale = waSystem::getInstance()->getLocale();
            if (!$locale) {
                $locale = 'en_US';
            }
        }
        $localeFile = waSystem::getInstance('webasyst')->getConfig()->getAppPath("locale/$locale/LC_MESSAGES/webasyst.po");
        if (!file_exists($localeFile)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    /**
     * @param string $locale
     * @param mixed $iterable anything foreach'able (e.g. an array or db result) containing wa_country rows
     * @return array iso3letter => wa_country row, sorted by row[name]
     */
    protected function translate($locale, $iterable)
    {
        $current_locale = wa()->getLocale();
        $result = array();
        foreach ($iterable as $row) {
            if ($locale != 'en_US') {
                if ($current_locale === $locale) {
                    $row['name'] = _ws($row['name']);
                } else {
                    // !!! waLocale::translate is SLOW if $current_locale != $locale
                    $row['name'] = waLocale::translate('webasyst', $locale, $row['name']);
                }
            }
            $result[$row['iso3letter']] = $row;
        }
        if ($locale != 'en_US') {
            uasort($result, array($this, 'sortHelper'));
        }
        return $result;
    }

    public function sortHelper($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }

    public function getCountriesByIso3($iso_3_codes, $locale = null)
    {
        if (!$iso_3_codes) {
            return array();
        }
        $locale = $this->ensureLocale($locale);
        $sql = "SELECT * FROM {$this->table} WHERE iso3letter IN (:iso_3_codes) ORDER BY name";
        $countries = $this->query($sql, array('iso_3_codes' => $iso_3_codes))->fetchAll();
        return $this->translate($locale, $countries);
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
