<?php

/**
 * @property-read array $delivery_table
 * @property-read array|string $weights
 * @property-read string $currency
 * @property-read string $weight_dimension
 * @property-read string $service_name
 */
class worldwideShipping extends waShipping
{
    /**
     * @see waShipping::getSettingsHTML()
     * @param array $params
     * @return string HTML
     */
    public function getSettingsHTML($params = array())
    {
        $view = wa()->getView();

        $namespace = '';
        if (!empty($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namespace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namespace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }
        }

        $values = $this->getSettings();
        $values['delivery_table'] = $this->delivery_table;
        $values['weights'] = $this->weights;

        $app_config = wa()->getConfig();
        $currencies = array();
        if (method_exists($app_config, 'getCurrencies')) {
            $currencies = $app_config->getCurrencies();
        }

        $countries = $this->getCountryList(true);
        $country_names = array();
        foreach ($countries as $c) {
            $country_names[$c['iso3letter']] = $c['name'];
        }

        $view->assign(
            array(
                'services_by_type'   => $this->getAdapter()->getAppProperties('desired_date'),
                'countries'          => $countries,
                'country_names'      => $country_names,
                'regions'            => $this->getRegionsForJs(),
                'currencies'         => $currencies,
                'plid'               => $this->id.time(),
                'namespace'          => $namespace,
                'values'             => $values,
                'p'                  => $this,
                'xhr_url'            => wa()->getAppUrl('webasyst').'?module=backend&action=regions',
                'delivery_countries' => $this->getDeliveryCountries(),
                'transits'           => array(
                    array(
                        'value' => '+3 hour',
                        'title' => $this->_w('Same day'),
                    ),
                    array(
                        'value' => '+1 day',
                        'title' => $this->_w('Next day'),
                    ),
                    array(
                        'value' => '+2 day, +3day',
                        'title' => $this->_w('2-3 days'),
                    ),
                    array(
                        'default' => true,
                        'value'   => '+1 week',
                        'title'   => $this->_w('1 week'),
                    ),
                    array(
                        'value' => '+2 week',
                        'title' => $this->_w('2 weeks'),
                    ),
                    array(
                        'value' => '+2 week, +3 week',
                        'title' => $this->_w('2-3 weeks'),
                    ),
                    array(
                        'value' => '+2 week, +4 week',
                        'title' => $this->_w('2-4 weeks'),
                    ),
                    array(
                        'value' => '+4 week, +6 week',
                        'title' => $this->_w('4-6 weeks'),
                    ),
                    array(
                        'value' => '+4 week, +8 week',
                        'title' => $this->_w('4-8 weeks'),
                    ),
                    array(
                        'value' => '+2 month, +3 month',
                        'title' => $this->_w('2-3 months'),
                    ),
                    array(
                        'value' => '',
                        'title' => $this->_w('Undefined'),
                    ),
                )
            )
        );

        $html = '';
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    protected function getCountryList($with_fav = false)
    {
        $cm = new waCountryModel();
        return $with_fav ? $cm->allWithFav() : $cm->all();
    }

    protected function getDeliveryCountries()
    {
        $countries = array();
        foreach ($this->delivery_table as $row) {
            $countries[$row['country']] = $row['country'];
        }
        return $countries;
    }

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return $this->weight_dimension;
    }

    public function requestedAddressFields()
    {
        return array(
            'zip'     => array('cost' => false),
            'street'  => array('cost' => false),
            'city'    => array('cost' => false),
            'country' => array('cost' => true),
        );
    }

    protected function allowedCountries()
    {
        $countries = array();
        foreach ($this->delivery_table as $item) {
            switch ($item['country']) {
                case '%AL':
                    $countries = array();
                    break 2;
                case '%EU':
                    $countries = array_merge($countries, $this->getEuropeanCountries());
                    break;
                case '%RW':
                    $countries = array_merge($countries, $this->getNotEuropeanCountries());
                    break;
                default:
                    $countries[] = $item['country'];
                    break;
            }
        }
        return array_unique($countries);
    }

    protected function findItemByCountryAndRegion($country, $region = null)
    {
        $res = false;
        $delivery_table = array();
        foreach ($this->delivery_table as $item) {
            $delivery_table[$item['country']] = $item;
        }
        if (isset($delivery_table[$country])) {
            $res = $delivery_table[$country];
        }
        if (!$res && isset($delivery_table['%RW'])) {
            $not_eur = array_fill_keys($this->getNotEuropeanCountries(), true);
            if (isset($not_eur[$country])) {
                $res = $delivery_table['%RW'];
            }
        }
        if (!$res && isset($delivery_table['%EU'])) {
            $eur = array_fill_keys($this->getEuropeanCountries(), true);
            if (isset($eur[$country])) {
                $res = $delivery_table['%EU'];
            }
        }
        if (!$res && isset($delivery_table['%AL'])) {
            $res = $delivery_table['%AL'];
        }
        if ($region && $res) {
            if (!empty($res['items'])) {
                foreach ($res['items'] as $item) {
                    if ($item['region'] == $region) {
                        return $item;
                    }
                }
            }
            return false;
        }
        return $res;
    }

    public function allowedAddress()
    {
        return array(
            array(
                'country' => $this->allowedCountries()
            )
        );
    }

    public function isAllowedAddress($address = array())
    {
        if ($this->isOwnCountryAddress($address)) {
            return false;
        }
        return parent::isAllowedAddress($address);
    }

    protected function isOwnCountryAddress($address = array())
    {
        if (empty($address)) {
            $address = $this->address;
        }
        $own_country = $this->getSettings('own_country');
        if ($own_country && isset($address['country']) && $address['country'] == $own_country) {
            return true;
        }
        return false;
    }

    protected function calculate()
    {
        $price = null;

        $country = $this->getAddress('country');
        $allowed_countries = $this->allowedCountries();
        if (!empty($allowed_countries) && !in_array($country, $allowed_countries)) {
            return false;
        }
        $region = $this->getAddress('region');

        $item = $this->findItemByCountryAndRegion($country, $region);
        if (!$item) {
            $item = $this->findItemByCountryAndRegion($country);
            if (!$item) {
                return false;
            }
        } elseif (isset($item['rate']) && empty($item['rate']) && empty($item['disabled'])) {
            $item = $this->findItemByCountryAndRegion($country);
            unset($item['items']);
        }

        if (!empty($item['disabled'])) {
            return false;
        }

        if (!isset($item['rate'])) {
            return false;
        }
        $price = '';
        $prices = array();
        $package_weight = $this->getTotalWeight();

        if (is_array($this->weights)) {
            if (empty($this->weights)) {
                return false;
            }

            $rates = $item['rate'];
            if (!is_array($rates)) {
                return false;
            }
            if ($package_weight !== null) {
                $rate = false;
                foreach ($this->weights as $i => $weight) {
                    if ($package_weight >= $weight) {
                        if (isset($rates[$i])) {
                            if (!$rate || ($rates[$i] !== '')) {
                                $rate = $rates[$i];
                            }
                        }
                    } else {
                        break;
                    }
                }

                if ($rate === false) {
                    return false;
                } elseif ($rate < 0) {
                    return $this->_w('Package weight exceeds maximum allowed weight');
                } else {
                    $price = $this->parseCost($rate);
                }

            } else {
                $prices = array($this->parseCost(min($rates)), $this->parseCost(max($rates)));
            }
        } else {
            $rate = $item['rate'];
            if (is_array($rate)) {
                $rate = reset($rate);
            }
            $price = $this->parseCost($rate);
        }

        $est_delivery = '';
        $delivery_date = null;
        if (!empty($item['transit_time'])) {
            /** @var string $departure_datetime SQL DATETIME */
            $departure_datetime = $this->getPackageProperty('departure_datetime');
            /** @var  int $departure_timestamp */
            if ($departure_datetime) {
                $departure_timestamp = max(strtotime($departure_datetime), time());
            } else {
                $departure_timestamp = time();
            }
            $delivery_date = array_unique(explode(',', $item['transit_time'], 2));
            $est_delivery = array();
            foreach ($delivery_date as &$date) {
                $date = strtotime(trim($date), $departure_timestamp);
                $est_delivery[] = waDateTime::format('humandate', $date);
            }
            unset($date);
            $est_delivery = implode(' — ', $est_delivery);
            $delivery_date = self::formatDatetime($delivery_date);
        }

        $service = array(
            'est_delivery'  => $est_delivery,
            'delivery_date' => $delivery_date,
            'currency'      => $this->currency,
            'rate'          => $prices ? $prices : $price,
            'type'          => 'post',
        );

        if (strlen($this->service_name)) {
            $service['service'] = $this->service_name;
        }

        return array('delivery' => $service);
    }

    protected function getEuropeanCountries()
    {
        return array(
            'aut',
            'bel',
            'bgr',
            'cyp',
            'cze',
            'dnk',
            'est',
            'fin',
            'fra',
            'deu',
            'grc',
            'hun',
            'irl',
            'ita',
            'lva',
            'ltu',
            'lux',
            'mlt',
            'nld',
            'pol',
            'prt',
            'rou',
            'svk',
            'svn',
            'esp',
            'swe',
            'gbr',
        );
    }

    protected function getNotEuropeanCountries()
    {
        $eur_map = array_fill_keys($this->getEuropeanCountries(), true);
        $res = array();
        foreach (array_keys($this->getCountryList()) as $iso) {
            if (!isset($eur_map[$iso])) {
                $res[] = $iso;
            }
        }
        return $res;

    }

    /**
     * @param $string
     * @return float
     */
    private function parseCost($string)
    {
        $cost = 0.0;

        $string = preg_replace('@\\s+@', '', $string);

        foreach (preg_split('@\+|(\-)@', $string, null, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY) as $chunk) {
            $value = str_replace(',', '.', trim($chunk[0]));
            if (strpos($value, '%')) {
                $value = round($this->getTotalPrice() * floatval($value) / 100.0, 2);
            } else {
                $value = floatval($value);
            }
            if ($chunk[1] && (substr($string, $chunk[1] - 1, 1) == '-')) {
                $cost -= $value;
            } else {
                $cost += $value;
            }
        }
        return max(0.0, $cost);
    }

    public function getRegionsForJs()
    {
        $countries = array();
        foreach ($this->delivery_table as $item) {
            $countries[] = $item['country'];
        }
        $res = array();
        $rm = new waRegionModel();
        foreach ($rm->getByCountry($countries) as $r) {
            $res[$r['country_iso3']][$r['code']] = $r['name'];
        }
        foreach ($res as $c_iso3 => $regions) {
            $res[$c_iso3] = array(
                'oOrder'  => array_keys($regions),
                'options' => $regions
            );
        }
        return $res;
    }

    public function __get($name)
    {
        if ($name === 'delivery_table' || $name === 'weights') {
            $delivery_table = $this->getSettings('delivery_table');
            $weights = $this->getSettings('weights');
            if (is_array($weights) && count($weights)) {
                $this->formatWeights($weights);
                $this->formatDeliveryTable($delivery_table);
                $this->sortDeliveryTable($delivery_table, $weights);
            } else {
                $weights = 'all';
            }
            if ($name === 'delivery_table') {
                return $delivery_table;
            }
            if ($name === 'weights') {
                return $weights;
            }
        }
        return parent::__get($name);
    }


    protected function formatDeliveryTable(&$items)
    {
        foreach ($items as &$item) {
            foreach ($item['rate'] as &$rate) {
                $rate = str_replace("'", "", str_replace("\"", "", $rate));
                $rate = str_replace(',', '.', $rate);
            }
            unset($rate);
        }
        unset($item);
    }

    protected function formatWeights(&$weights)
    {
        foreach ($weights as &$weight) {
            $weight = str_replace("'", "", str_replace("\"", "", $weight));
            $weight = str_replace(',', '.', $weight);
        }
        unset($weight);
    }

    protected function sortDeliveryTable(&$delivery_table, &$weights)
    {
        asort($weights);
        $indexes = array_keys($weights);
        $this->sortDeliveryTableItems($delivery_table, $indexes);
        $weights = array_values($weights);
    }

    private function sortDeliveryTableItems(&$items, $indexes = array())
    {
        foreach ($items as &$item) {
            $rate = array();
            foreach ($indexes as $index) {
                $rate[] = isset($item['rate'][$index]) ? $item['rate'][$index] : array();
            }
            $item['rate'] = $rate;
            if (!empty($item['items'])) {
                $this->sortDeliveryTableItems($item['items'], $indexes);
            }
        }
        unset($item);
    }
}
