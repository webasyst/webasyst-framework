<?php

/**
 * Class boxberryShippingCalculateCourier
 */
class boxberryShippingCalculateCourier extends boxberryShippingCalculateHelper implements boxberryShippingCalculateInterface
{
    const VARIANT_PREFIX = 'toodor';

    /**
     * @return array
     */
    public function getVariants()
    {
        $result = [];

        if ($this->getErrors()) {
            return $result;
        }

        $zips_list = $this->getZipsByCheckoutAddress();
        if ($zips_list && is_array($zips_list)) {
            $result = $this->parseZips($zips_list);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->bxb->getSettings('courier_mode');
    }

    /**
     * @param $zips_list
     * @return array
     */
    public function parseZips($zips_list)
    {
        $additional_info = $this->getAdditionalInfo($this->getZipAddress($zips_list));

        $result = [];

        // If the preliminary calculation is wrong, then you do not need to show the courier
        if (!$additional_info && !$this->isVariantSelected()) {
            return $result;
        }

        $courier = [
            'name'          => $this->bxb->courier_title,
            'service'       => $this->bxb->service,
            'type'          => waShipping::TYPE_TODOOR,
            'currency'      => $this->bxb->allowedCurrency(),
            'delivery_date' => ifset($additional_info, 'delivery_date', ''),
            'est_delivery'  => $this->bxb->_w('from').' '.ifset($additional_info, 'est_delivery', ''),
            'rate'          => $this->getRate(ifset($additional_info, 'price', null), $zips_list),
            'custom_data'   => [
                waShipping::TYPE_TODOOR => [
                    'payment' => $this->getPayment(),
                ]
            ],
        ];

        // If the calculation is incorrect after specifying the zip, then we show a special error
        if (is_null($courier['rate'])) {
            $courier['comment'] = $this->bxb->_w('Delivery is not available for specified ZIP code.');
        }

        $result = [
            $this->getPrefix().self::getVariantSeparator().'bxb_courier' => $courier
        ];

        return $result;
    }

    /**
     * Returns shipping costs
     * If the delivery index is not specified, it calculates the preliminary cost for the city
     *
     * @param int|float $raw_rate
     * @param array $zips_list
     * @return float|null
     */
    protected function getRate($raw_rate, $zips_list)
    {
        $checkout_zip = $this->bxb->getAddress('zip');
        $zip = $this->getZipAddress($zips_list);

        $zip_found = in_array($zip, $zips_list);

        //Not selected and not free
        if ($zip_found && !$checkout_zip && $raw_rate !== 0) {
            $rate = [$raw_rate, $raw_rate + self::MAGIC_NUMBER_TO_MAKE_RANGE];
        } elseif ($zip_found && ($checkout_zip || $raw_rate === 0)) {
            //Selected or Free
            $rate = $raw_rate;
        } else {
            // Not selected and not found in array
            $rate = null;
        }

        return $rate;
    }

    /**
     * Returns either the zip specified by the user or the first from the list
     *
     * @param array $zips_list
     * @return string
     */
    protected function getZipAddress($zips_list)
    {
        $zip = $this->bxb->getAddress('zip');

        if (!$zip) {
            $zip = reset($zips_list);
        }

        return $zip;
    }

    /**
     * Calculates the price and the approximate date of delivery to the client.
     *
     * @param $zip
     * @return array|bool
     */
    protected function getAdditionalInfo($zip)
    {
        $delivery_costs = $this->getDeliveryCostsAPI(['zip' => $zip]);

        $result = false;
        if ($delivery_costs['price'] !== false) {
            $delivery_days = ifset($delivery_costs, 'delivery_period', 0);

            try {
                $delivery = new DateTime($this->bxb->getPackageProperty('departure_datetime'));

                // Add days that boxberry gives
                // Ignore saturday and sunday
                while ($delivery_days) {
                    $delivery->modify("+ 1 days");
                    $day = $delivery->format('N');

                    if ($day != 6 && $day != 7) {
                        $delivery_days--;
                    }
                }

                $delivery_date = $delivery->format("Y-m-d H:i:s");
            } catch (Exception $e) {
                $day_to_second = $delivery_days * 3600;
                $delivery_date = date("Y-m-d H:i:s", time() + $day_to_second);
            }

            try {
                $est_delivery = waDateTime::format('humandate', $delivery_date);
            } catch (Exception $e) {
                $est_delivery = '';
            }
            $result = [
                'price'         => $delivery_costs['price'],
                'delivery_date' => $delivery_date,
                'est_delivery'  => $est_delivery,
            ];
        }

        return $result;
    }

    /**
     * Returns all available zip in the specified city and region
     *
     * @return array
     */
    public function getZipsByCheckoutAddress()
    {
        $handbook_manager = new boxberryShippingHandbookCityZips($this->getApiManager(), ['plugin_path' => $this->bxb->getPluginPath()]);
        $cities_with_zips = $handbook_manager->getHandbook();

        $region = $this->bxb->getAddress('region');
        $city = mb_strtolower($this->bxb->getAddress('city'));
        $zips = ifset($cities_with_zips, $city, $region, []);

        // workaround city name like Орел/Орёл or Йошкар Ола/Йошкар-Ола
        if (!$zips && $this->bxb->getAddress('country') === 'rus') {
            $found_city = self::findCityName($city, array_keys($cities_with_zips));
            if ($found_city) {
                $zips = ifset($cities_with_zips, $found_city, $region, []);
            }
        }

        return $zips;
    }

    /**
     * Static method cannot be called from abstract method
     * @return string
     */
    public function getPrefix()
    {
        return self::VARIANT_PREFIX;
    }
}
