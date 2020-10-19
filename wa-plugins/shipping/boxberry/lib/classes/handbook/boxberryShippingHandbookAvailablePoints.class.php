<?php

class boxberryShippingHandbookAvailablePoints extends boxberryShippingHandbookManager
{
    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->bxb->getAddress('country') . '_available_points';
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
     * @return string
     */
    protected function getAPIMethod()
    {
        return boxberryShippingApiManager::METHOD_LIST_POINT;
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $points = $this->api_manager->getByApiMethod($this->getAPIMethod(), [boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);

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
        $invalid_points = [];

        foreach ($points as $point) {
            $region = trim(ifset($city_and_regions, trim($point['CityCode']), ''));

            if (!$region) {
                $invalid_points[] = $point;
                continue;
            }

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

        $this->logInvalidPoints($invalid_points);

        return $result;
    }

    /**
     * @param $point
     * @return array
     */
    protected function parsePoint($point)
    {
        $result = [
            'name'                => ifset($point, 'AddressReduce', ''),
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

        if (empty($result['name'])) {
            $result['name'] = preg_replace('/\d+\,\s/i', '', $result['address'], 1);
        }

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
                $result['lat'] = str_replace(' ', '', $explode[0]);
                $result['lng'] = str_replace(' ', '', $explode[1]);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getCitiesWithRegions()
    {
        $handbook = new boxberryShippingHandbookCityRegions($this->api_manager, [], $this->bxb);
        return $handbook->getHandbook();
    }

    /**
     * @param $points
     */
    protected function logInvalidPoints($points)
    {
        $log = 'The region could not be found for the following points of delivery: '."\n";

        foreach ($points as $point) {
            $city_string = 'City name: '.ifset($point, 'Name', '').
                '. Area:'.ifset($point, 'Area', '').
                '. City code:'.ifset($point, 'CityCode', '');

            $log .= $city_string."\n";
        }

        $this->log($log, $this->getAPIMethod());
    }
}
