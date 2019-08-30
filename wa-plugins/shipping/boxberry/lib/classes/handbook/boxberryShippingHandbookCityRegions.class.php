<?php

/**
 * Class boxberryShippingHandbookCityRegions
 */
class boxberryShippingHandbookCityRegions extends boxberryShippingHandbookManager
{
    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'cities_with_regions';
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $cities = $this->api_manager->downloadListCitiesFull();
        $city_with_regions = [];

        if (!empty($cities)) {
            $city_with_regions = $this->parseFullCityList($cities);
            $this->setToCache([
                'key'   => $this->getCacheKey(),
                'ttl'   => 604800,
                'value' => $city_with_regions
            ]);
        }

        return $city_with_regions;
    }

    /**
     * Saves regions for cities
     *
     * @param $cities
     * @return array
     */
    protected function parseFullCityList($cities)
    {
        $result = [];

        foreach ($cities as $city) {
            $kladr = ifset($city, 'Kladr', '');
            $city_code = ifset($city, 'Code', null);

            if ($kladr && $city_code) {
                $region_code = mb_substr($kladr, 0, 2);
                $result[$city_code] = $region_code;
            }
        }

        return $result;
    }

}