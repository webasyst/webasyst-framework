<?php

/**
 * Class boxberryShippingHandbookPointsForParcels
 */
class boxberryShippingHandbookPointsForParcels extends boxberryShippingHandbookManager
{
    /**
     * @return string
     */
    protected function getCacheKey()
    {
        if ($this->bxb) {
            return $this->bxb->getAddress('country') . '_points_for_parcels';
        } else {
            return 'rus_points_for_parcels';
        }
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $points = $this->api_manager->downloadPointsForParcels([boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);

        if (!empty($points)) {
            $points = $this->parsePointsForParcels($points);
            $this->setToCache([
                'key'   => $this->getCacheKey(),
                'ttl'   => 604800,
                'value' => $points
            ]);
        }

        return $points;
    }

    /**
     * @param $points
     * @return array
     */
    protected function parsePointsForParcels($points)
    {
        $result = [];

        foreach ($points as $point) {
            $city = ifset($point, 'City', '');
            $code = ifset($point, 'Code', '');
            $name = ifset($point, 'Name', '');

            if (!$city || !$code || !$name) {
                continue;
            }
            $result[$city][$code] = $name;
        }

        // sort by street name
        $sorted_result = [];
        foreach ($result as $city => &$city_points) {
            asort($city_points);

            // Sort for JS json encode
            foreach ($city_points as $point_code => $city_point) {
                $sorted_result[$city][] = ['code' => $point_code, 'name' => $city_point];
            }
        }

        return $sorted_result;
    }
}
