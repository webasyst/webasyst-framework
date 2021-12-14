<?php

class boxberryShippingCountriesAdapter
{
    /**
     * @param string|array $country_codes
     * @return array
     */
    public static function getRegions($country_codes = null)
    {
        $region_model = new waRegionModel();
        $country_codes = self::validateCountryCodes($country_codes);
        $regions = $region_model->getByCountry($country_codes);
        $all_region_codes = self::getAllRegionCodes();

        foreach ($regions as $key => $region) {
            $country_iso3 = $region['country_iso3'];
            if (array_search($region['code'], $all_region_codes[$country_iso3]) === false) {
                unset($regions[$key]);
            }
        }

        return $regions;
    }

    /**
     * @param string|array $country_codes
     * @return array
     */
    public static function getCountries($country_codes = null)
    {
        $country_model = new waCountryModel();
        $country_codes = self::validateCountryCodes($country_codes);
        return $country_model->getCountriesByIso3($country_codes);
    }

    public static function getAllRegionCodes()
    {
        $countries = self::getAllowedCountries();

        $regions = array();
        foreach ($countries as $iso3 => $country) {
            $path = wa()->getConfig()->getPath('plugins') . '/shipping/boxberry/lib/config/data/' . $iso3 . '_region_codes.php';
            if (file_exists($path) && is_readable($path)) {
                $regions[$iso3] = include_once($path);
            }
        }

        return $regions;
    }

    public static function getRegionCodes($country_code)
    {
        static $regions;
        $countries = array_flip(self::getAllowedCountries());
        $country_iso3_code = $countries[$country_code];

        if (isset($regions[$country_iso3_code])) {
            return $regions[$country_iso3_code];
        } else {
            $path = wa()->getConfig()->getPath('plugins') . '/shipping/boxberry/lib/config/data/' . $country_iso3_code . '_region_codes.php';

            if (file_exists($path) && is_readable($path)) {
                $regions[$country_iso3_code] = include_once($path);
            }

            return $regions[$country_iso3_code];
        }
    }

    public static function validateCountryCodes($country_codes)
    {
        if (empty($country_codes)) {
            $country_codes = array_flip(self::getAllowedCountries());
        } elseif (!is_array($country_codes)) {
            $country_codes = array($country_codes);
        }

        return $country_codes;
    }

    public static function getAllowedCountries()
    {
        return array('rus' => '643', 'kaz' => '398', 'blr' => '112');
    }
}