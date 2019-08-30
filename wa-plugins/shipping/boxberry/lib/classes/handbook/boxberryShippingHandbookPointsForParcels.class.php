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
        return 'points_for_parcels';
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $points = $this->api_manager->downloadPointsForParcels();

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

        return $result;
    }
}