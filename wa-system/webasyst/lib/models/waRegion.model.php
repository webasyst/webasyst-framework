<?php

class waRegionModel extends waModel
{
    protected $id = array('country_iso3', 'code');
    protected $table = 'wa_region';

    public function getCountries()
    {
        $result = $this->query("SELECT DISTINCT country_iso3 FROM {$this->table}")->fetchAll('country_iso3');
        return $result ? array_keys($result) : array();
    }

    public function getByCountry($country)
    {
        $sql = "SELECT * FROM {$this->table} WHERE country_iso3=:country ORDER BY name";
        return $this->query($sql, array('country' => $country))->fetchAll('code');
    }

    public function saveForCountry($country, $regions)
    {
        $this->deleteByField('country_iso3', $country);
        $data = array();
        foreach($regions as $code => $name) {
            $data[] = array(
                'code' => $code,
                'name' => $name,
                'country_iso3' => $country,
            );
        }
        if ($data) {
            $this->multipleInsert($data);
        }
    }
}

