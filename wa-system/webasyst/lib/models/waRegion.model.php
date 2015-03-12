<?php

class waRegionModel extends waModel
{
    protected $id = array('country_iso3', 'code');
    protected $table = 'wa_region';

    public function get($country, $code)
    {
        // Profiling shows that it is reasonable to have such cache here:
        // it saves several queries on page with contact form or use of field formatters.
        static $cache = array();
        if (empty($cache[$country][$code])) {
            $cache[$country][$code] = $this->getByField(array(
                'country_iso3' => $country,
                'code' => $code,
            ));
        }
        return $cache[$country][$code];
    }

    public function getCountries()
    {
        $result = $this->query("SELECT DISTINCT country_iso3 FROM {$this->table}")->fetchAll('country_iso3');
        return $result ? array_keys($result) : array();
    }

    public function getByCountry($country)
    {
        if (!$country) {
            return array();
        }
        $sql = "SELECT * FROM {$this->table} WHERE country_iso3 IN (:country) ORDER BY name";
        return $this->query($sql, array('country' => $country))->fetchAll(is_array($country) ? null : 'code');
    }

    public function getByCountryWithFav($country_or_regions)
    {
        if (!is_array($country_or_regions)) {
            $all = array_values($this->getByCountry($country_or_regions));
        } else {
            $all = $country_or_regions;
        }
        $fav = array();
        foreach($all as $r) {
            if ($r['fav_sort']) {
                $fav[] = array('fav_sort' => $r['fav_sort'], 'name' => $r['name']) + $r;
            }
        }
        if ($fav) {
            sort($fav); // sort by fav_sort, name
            $fav[] = $this->getEmptyRow(); // delimeter
        }
        return array_merge($fav, $all);
    }

    public function saveForCountry($country, $regions)
    {
        $this->deleteByField('country_iso3', $country);
        $data = array();
        foreach($regions as $code => $name) {
            if (is_array($name)) {
                $fav_sort = $name['fav_sort'];
                $name = $name['name'];
            } else {
                $fav_sort = null;
            }
            $data[] = array(
                'code' => $code,
                'name' => $name,
                'country_iso3' => $country,
                'fav_sort' => $fav_sort,
            );
        }
        if ($data) {
            $this->multipleInsert($data);
        }
    }
}

