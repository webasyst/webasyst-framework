<?php

class boxberryShippingCalculatePoints extends boxberryShippingCalculateHelper implements boxberryShippingCalculateInterface
{
    const VARIANT_PREFIX = 'pickup';

    /**
     * @return array
     * @throws waException
     */
    public function getVariants()
    {
        $result = [];

        if ($this->getErrors()) {
            return $result;
        }

        $points = $this->getPointsBySettingsAndCustomerData();
        $code = $this->getPointCode($points);

        if ($code) {
            $result = $this->parsePoints($points, $code);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->bxb->getSettings('point_mode');
    }

    /**
     * @param $points
     * @param $code
     * @return array
     * @throws waException
     */
    public function parsePoints($points, $code)
    {
        $result = [];

        $is_sd_selected = $this->isVariantSelected();
        $info_by_point = $this->getAdditionalInfoByPoint($code);

        if (!$info_by_point) {
            return $result;
        }

        $default_payment = $this->getPayment();
        foreach ($points as $code => $point) {
            $c = $this->getPrefix().self::getVariantSeparator().$code;

            $payment = $default_payment;
            if ($point['only_prepaid_orders'] === 'yes') {
                $payment = [waShipping::PAYMENT_TYPE_PREPAID => true];
            }

            $result[$c] =
                [
                    'service'       => $this->bxb->service,
                    'name'          => $point['name'],
                    'est_delivery'  => $info_by_point['est_delivery'],
                    'delivery_date' => $info_by_point['delivery_date'],
                    'timezone'      => date_default_timezone_get(),
                    'currency'      => $this->bxb->allowedCurrency(),
                    'type'          => waShipping::TYPE_PICKUP,
                    'custom_data'   => [
                        waShipping::TYPE_PICKUP => [
                            'id'          => $code,
                            'timezone'    => $this->getTimezone(),
                            'lat'         => $point['lat'],
                            'lng'         => $point['lng'],
                            'schedule'    => $info_by_point['schedule'],
                            'photos'      => $info_by_point['photos'],
                            'way'         => $point['way'],
                            'description' => $point['address'],
                            'payment'     => $payment,
                        ]
                    ],
                ];

            if ($is_sd_selected || $info_by_point['rate'] === 0) {
                $result[$c]['rate'] = $info_by_point['rate'];
            } else {
                // add a magic number to get a range in the new checkout
                $result[$c]['rate'] = [$info_by_point['rate'], $info_by_point['rate'] + self::MAGIC_NUMBER_TO_MAKE_RANGE];
            }
        }

        return $result;
    }

    /**
     * Calculates the cost of delivery to a specified pick-up points
     *
     * @param $code
     * @return array|bool
     * @throws waException
     */
    public function getAdditionalInfoByPoint($code)
    {
        // Get the cost and delivery time
        $delivery_costs = $this->getDeliveryCostsAPI(['target' => $code]);
        $result = false;

        if ($delivery_costs['price'] !== false) {
            // Get information about the pick-up point
            $point_description = $this->getPointDescription($code);

            if (!empty($point_description['schedule'])) {
                $delivery_date = $this->getDeliveryDate($point_description['schedule'], $delivery_costs['delivery_period']);
                $schedule = $this->parseSchedule($point_description['schedule'], $delivery_date);

                try {
                    $est_delivery = waDateTime::format('humandate', $delivery_date);
                } catch (Exception $e) {
                    $est_delivery = '';
                }

                $result = [
                    'rate'          => $delivery_costs['price'],
                    'delivery_date' => $delivery_date,
                    'est_delivery'  => $est_delivery,
                    'photos'        => $point_description['photos'],
                    'schedule'      => $schedule,
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the point code for which you want to calculate the cost.
     * If a specific point is not selected, the first one from the list is taken.
     *
     * @param $points
     * @return bool|string
     */
    public function getPointCode($points)
    {
        $code = false;

        if ($this->isVariantSelected()) {
            $variant_id = $this->bxb->getSelectedServiceId();

            if ($variant_id) {
                $variant = explode(self::getVariantSeparator(), $variant_id);
                if (is_array($variant) && count($variant) > 1) {
                    $code = end($variant);
                }
            }
        } else {
            $first_point = reset($points);
            $code = ifset($first_point, 'code', false);
        }

        return $code;
    }

    /**
     * @param $code
     * @return array
     * @throws waException
     */
    protected function getPointDescription($code)
    {
        $api_manager = new boxberryShippingHandbookPointDescription($this->getApiManager(), ['code' => $code, 'id' => $this->bxb->getId()]);
        $description = $api_manager->getHandbook();

        return $description;
    }

    /**
     * @param $schedule
     * @param $period
     * @return string
     */
    protected function getDeliveryDate($schedule, $period)
    {
        //We take for the condition that the parcel can be returned on the day when she arrived
        try {
            $delivery = new DateTime($this->bxb->getPackageProperty('departure_datetime'));
        } catch (Exception $e) {
            // if something went wrong, then we will return the next day
            return date('Y-m-d H:i:s', time() + 86400);
        }

        // Add days that boxberry gives
        // Ignore saturday and sunday
        while ($period) {
            $delivery->modify("+ 1 days");
            $day = $delivery->format('N');

            if ($day != 6 && $day != 7) {
                $period--;
            }
        }

        //We only go 7 days, because we do not have information about additional working days
        for ($i = 0; $i <= 6; $i++) {
            //Looking for the day by number
            $day_number = $delivery->format('w');
            $schedule_day = ifset($schedule, $day_number, false);

            if ($schedule_day['type'] === 'workday') {
                break;
            }
            $delivery->modify('+ 1 days');
        }

        $date = $delivery->format('Y-m-d H:i:s');

        return $date;
    }

    /**
     * Returns the work schedule for the week after the delivery date
     *
     * @param array $schedule
     * @param $date
     * @return array
     */
    protected function parseSchedule($schedule, $date)
    {
        $result = [];

        try {
            $delivery = new DateTime($date);

            for ($i = 0; $i <= 6; $i++) {
                //We get the number of the day of the week and look for it in the cache
                $day_number = $delivery->format('w');
                $schedule_day = ifset($schedule, $day_number, false);

                //If one of the days is not found, then something went wrong.
                if (!$schedule_day) {
                    $result = [];
                    break;
                }

                $result['weekdays'][$day_number] = [
                    'type'       => $schedule_day['type'],
                    'start_work' => $delivery->format('Y-m-d').' '.$schedule_day['start_work'],
                    'end_work'   => $delivery->format('Y-m-d').' '.$schedule_day['end_work'],
                ];
                $delivery->modify('+ 1 days');
            }
        } catch (Exception $e) {
            $result = [];
        }

        return $result;
    }

    public function getPrefix()
    {
        return self::VARIANT_PREFIX;
    }

    /**
     * Return points that match the address, size and weight
     *
     * @return array
     */
    public function getPointsBySettingsAndCustomerData()
    {
        $weight = $this->bxb->getTotalWeight();
        $volume = $this->getVolume();
        $points_by_city = $this->getPointsByCity();

        if ($points_by_city) {
            foreach ($points_by_city as $code => $point) {
                $volume_exceeded = $point['max_volume'] < $volume || empty($volume);
                $weight_exceeded = (float)$point['max_weight'] * 1000 < $weight;

                //remove points that do not fit in weight and size
                if ($volume_exceeded || $weight_exceeded) {
                    unset($points_by_city[$code]);
                }
            }
        }

        return $points_by_city;
    }

    /**
     * Returns the volume of the package.
     * Takes sizes from a special plugin or default
     *
     * @return float
     */
    protected function getVolume()
    {
        $volume = 0;
        if ($this->bxb->isPluginDimensions()) {
            //get special volume
            $sizes = $this->bxb->getTotalSize();
            $volume = ifset($sizes, 'length', 0) * ifset($sizes, 'width', 0) * ifset($sizes, 'height', 0);
        }

        if (!$volume) {
            $volume = (float)$this->bxb->default_length * (float)$this->bxb->default_width * (float)$this->bxb->default_height;
        }

        $volume = round($volume, 6);

        return $volume;
    }

    /**
     * Returns points depending on the city and region
     *
     * @return array
     */
    protected function getPointsByCity()
    {
        $handbook_manager = new boxberryShippingHandbookAvailablePoints($this->getApiManager(), [], $this->bxb);
        $points = $handbook_manager->getHandbook();

        $city = mb_strtolower($this->bxb->getAddress('city'));
        $region_code = $this->bxb->getAddress('region');

        $cities_points = ifset($points, 'cities', []);

        // Retrieving Point Codes by City and Region
        $codes_by_cities = ifset($cities_points, $city, $region_code, []);

        // workaround city name like Орел/Орёл or Йошкар Ола/Йошкар-Ола
        if (!$codes_by_cities) {
            $found_city = self::findCityName($city, array_keys($cities_points));
            if ($found_city) {
                $codes_by_cities = ifset($cities_points, $found_city, $region_code, []);
            }
        }

        $result = [];
        if ($codes_by_cities) {
            $result = array_intersect_key($points['points'], $codes_by_cities);
        }

        // register insensitive sort by street address with taking into account that strings are mb strings
        $comparator = function ($a, $b) {
            return strcmp(mb_strtoupper($a['name']), mb_strtoupper($b['name']));
        };
        uasort($result, $comparator);

        return $result;
    }
}
