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
        return $this->bxb->getAddress('country') . '_cities_with_regions';
    }

    /**
     * @return string
     */
    protected function getAPIMethod()
    {
        return boxberryShippingApiManager::METHOD_LIST_CITIES_FULL;
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $cities = $this->api_manager->getByApiMethod($this->getAPIMethod(), [boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);
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
            $city_code = ifset($city, 'Code', null);

            if ($city['CountryCode'] != '643') {
                $country_regions = boxberryShippingCountriesAdapter::getRegionCodes($city['CountryCode']);
                $region = mb_strtolower($city['Region']);
                $region_code = $country_regions[$region];
                $result[$city_code] = $region_code;
            } else {
                $kladr = ifset($city, 'Kladr', '');
                if ($kladr && $city_code) {
                    $region_code = mb_substr($kladr, 0, 2);
                    $result[$city_code] = $region_code;
                } else {
                    $city_name = ifset($city, 'Name', '');
                    $log = "Error getting information about the city of {$city_name}({$city_code}). ";

                    if (!$kladr) {
                        $log .= 'KLADR not transferred.';
                    }

                    $this->log($log, $this->getAPIMethod());
                }
            }
        }

        return $result;
    }
}
