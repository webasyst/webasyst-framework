<?php

class boxberryShippingHandbookAvailablePoints extends boxberryShippingHandbookManager
{
    /**
     * @return string
     */
    public function getCacheKey()
    {
        return 'available_points';
    }

    /**
     * @param $code
     * @return array
     */
    public function getPointByCode($code)
    {
        $points = $this->getHandbook();
        $point = ifset($points, 'points', $code, []);
        return (array)$point;
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $points = $this->api_manager->downloadListPoints([boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);

        if (!empty($points)) {
            $points = $this->parseAvailablePoints($points);
            $this->setToCache([
                'key'   => self::getCacheKey(),
                'ttl'   => 604800,
                'value' => $points
            ]);
        }

        return $points;
    }

    /**
     * Retrieves information about the pickup location.
     * The final list consists of a city->region->list of points of reception
     *
     * @param $points
     * @return array
     */
    protected function parseAvailablePoints($points)
    {
        $new_points = [];
        $cities = [];
        $city_and_regions = $this->getCitiesWithRegions();

        foreach ($points as $point) {
            $region = ifset($city_and_regions, $point['CityCode'], '');

            $parsed_point = $this->parsePoint($point);
            $parsed_point['region'] = $region;

            $code = $parsed_point['code'];
            $new_points[$code] = $parsed_point;

            $cities[$parsed_point['city']][$region][$code] = $code;
        }

        // To visually inspect the list of cities normally
        ksort($cities);

        $result = [
            'points' => $new_points,
            'cities' => $cities,
        ];

        return $result;
    }

    /**
     * @param $point
     * @return array
     */
    protected function parsePoint($point)
    {
        $result = [
            'name'                => ifset($point, 'Name', ''),
            'code'                => ifset($point, 'Code', ''),
            'raw_region'          => mb_strtolower(ifset($point, 'Area', '')),
            'city'                => mb_strtolower(ifset($point, 'CityName', '')),
            'phone'               => ifset($point, 'Phone', ''),
            'address'             => ifset($point, 'Address', ''),
            'max_volume'          => ifset($point, 'VolumeLimit', ''),
            'max_weight'          => ifset($point, 'LoadLimit', ''),
            'only_prepaid_orders' => mb_strtolower(ifset($point, 'OnlyPrepaidOrders', '')),
            'schedule'            => ifset($point, 'WorkSchedule', ''),
            'delivery_period'     => ifset($point, 'DeliveryPeriod', ''),
            'way'                 => ifset($point, 'TripDescription', ''),
            'metro'               => ifset($point, 'Metro', ''),
            'courier_delivery'    => mb_strtolower(ifset($point, 'NalKD', '')),
        ];

        $result += $this->parseGPS(ifset($point, 'GPS', ''));

        return $result;
    }

    /**
     * Extracts the coordinates of an point from a string
     *
     * @param string $gps
     * @return array
     */
    protected function parseGPS($gps)
    {
        $result = [
            'lat' => null,
            'lng' => null,
        ];

        if ($gps) {
            $explode = explode(',', $gps);
            if (is_array($explode) && count($explode) >= 2) {
                $result['lat'] = $explode[0];
                $result['lng'] = $explode[1];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getCitiesWithRegions()
    {
        $handbook = new boxberryShippingHandbookCityRegions($this->api_manager);
        return $handbook->getHandbook();
    }
}

