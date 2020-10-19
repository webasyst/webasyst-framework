<?php

/**
 * Class boxberryShippingHandbookCityZips
 */
class boxberryShippingHandbookCityZips extends boxberryShippingHandbookManager
{
    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'cities_with_zips';
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $zips = $this->api_manager->downloadListZips([boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);
        $parsed_regions = $this->parseZips($zips);

        if ($parsed_regions) {
            $cache = [
                'key'   => $this->getCacheKey(),
                'ttl'   => 604800,
                'value' => $parsed_regions,
            ];
            $this->setToCache($cache);
        }

        return $parsed_regions;
    }

    /**
     * Saves zip addresses by city and region
     *
     * @param $zips
     * @return array
     */
    protected function parseZips($zips)
    {
        $regions = $this->getRegionsMap();
        $result = [];
        $bad_regions = [];

        foreach ($zips as $zip_data) {
            $zip_city = mb_strtolower(ifset($zip_data, 'City', ''));
            $zip_region = mb_strtolower(ifset($zip_data, 'Area', ''));
            $zip = ifset($zip_data, 'Zip', false);

            // Looking for the region code on the map
            $system_region = ifset($regions, $zip_region, false);

            if ($system_region) {
                $result[$zip_city][$system_region][] = $zip;
            } elseif ($zip_region) {
                $bad_regions[] = $zip_region;
            }
        }

        // Save regions that could not match.
        // In the future for support
        if ($bad_regions) {
            waLog::log("Invalid regions: \n".var_export($bad_regions, true)."\n", 'wa-plugins/shipping/invalid_zip_regions.log');
        }

        return $result;
    }

    /**
     * A map showing the correspondence of regions and codes
     *
     * I hope someday they will begin to transmit the region code and this horror can be removed
     *
     * @return array
     */
    protected function getRegionsMap()
    {
        $path = $this->data['plugin_path'].'/lib/config/data/rus_region_codes.php';
        $regions = [];

        if (file_exists($path) && is_readable($path)) {
            $regions = include_once($path);
        }

        return $regions;
    }
}
