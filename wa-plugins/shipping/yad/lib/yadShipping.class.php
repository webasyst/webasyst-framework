<?php

/**
 * @link https://yandex.ru/dev/delivery-3/doc/dg/concepts/access-docpage/
 *
 * @property-read string    $oauth          токен авторизации
 * @property-read string    $cabinetId      идентификатор клиента
 * @property-read string    $senderId       идентификатор магазина
 * @property-read string    $companyId      номер компании
 * @property-read string    $warehouseId    идентификатор склада
 * @property-read string    $city           город отправления
 * @property-read array[]   $size           настройки размеров
 * @property-read string    $insurance      оценочная стоимость
 * @property-read boolean   $cash_service   комиссия за перечисление денежных средств
 * @property-read boolean[] $integration    настройка интеграции
 * @property-read string    $shipping_type  способ отгрузки
 * @property-read string    $map            показывать карту выбора ПВЗ
 * @property-read string    $taxes          передача ставок НДС
 * @property-read boolean   $debug          отладка
 */
class yadShipping extends waShipping
{
    /** @var string https://api.delivery.yandex.ru/<request> */
    private $url = 'https://api.delivery.yandex.ru/%s';

    private $cache_key = null;

    private $api_error = array();

    private $api_callback = null;

    /**
     * @var array
     */
    private $raw_address = array();

    private $result = null;

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function allowedLinearUnit()
    {
        return 'cm';
    }

    public function allowedAddress()
    {
        return array(
            array(
                'country' => 'rus',
            ),
        );
    }

    public function getPromise()
    {
        return $this->result;
    }

    private function getCacheKey($key = null)
    {
        return sprintf('wa-plugins/shipping/yad/%s/%s/%s', $this->app_id, $this->key, $key ? $key : $this->cache_key);
    }

    // REQUEST TO YANDEX API
    protected static function apiRequestList($type = null)
    {
        $requests = array(
            'searchDeliveryList' => array(
                'method' => waNet::METHOD_PUT,
                'url' => 'delivery-options',
            ),
            'getPickupPoints' => array(
                'method' => waNet::METHOD_PUT,
                'url' => 'pickup-points',
            ),
            'getOrder' => array(
                'method' => waNet::METHOD_GET,
                'url' => '/orders/{id}',
            ),
            'createOrder' => array(
                'method' => waNet::METHOD_POST,
                'url' => 'orders',
            ),
            'updateOrder' => array(
                'method' => waNet::METHOD_PUT,
                'url' => 'orders/{id}',
            ),
            'deleteOrder' => array(
                'method' => waNet::METHOD_DELETE,
                'url' => 'orders/{id}',
            ),
            'searchOrder' => array(
                'method' => waNet::METHOD_PUT,
                'url' => 'orders/search',
            ),
            'confirmSenderOrders' => array(
                'method' => waNet::METHOD_POST,
                'url' => 'orders/submit',
            ),
            'getSenderOrderLabel' => array(
                'method' => waNet::METHOD_GET,
                'url' => 'orders/{id}/label',
            ),
            'getSenderParcelDocs' => array(
                'method' => waNet::METHOD_GET,
                'url' => 'shipments/applications/{id}/act?cabinetId={cabinetId}',
            ),
            'autocomplete' => array(
                'method' => waNet::METHOD_GET,
                'url' => 'location?term={term}',
            ),
            'getSenderOrderStatus' => array(
                'method' => waNet::METHOD_PUT,
                'url' => 'orders/status',
            ),
        );

        if (isset($type)) {
            return $requests[$type];
        } else {
            return $requests;
        }
    }

    private function apiQuery($type, $data = array(), $callback = null)
    {
        $data = self::format($data);

        $request = self::apiRequestList($type);
        $request_url = $request['url'];
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $request_url = str_replace('{' . $key . '}', $value, $request_url);
            }
        }

        $url = sprintf($this->url, $request_url);
        $md5_cache_key = md5($url . var_export($data, true));
        $cache_ttl = array(
            'searchDeliveryList' => 3600,
            'autocomplete'       => 3600 * 24 * 7,
        );
        $cache = null;

        if (isset($cache_ttl[$type])) {
            $cache = new waVarExportCache($this->getCacheKey($md5_cache_key), $cache_ttl[$type], 'webasyst', true);
            if ($cache->isCached()) {
                return $cache->get();
            }
        }
        try {
            $options = array(
                'authorization' => true,
                'auth_type' => 'OAuth',
                'auth_key' => $this->oauth,
            );
            $custom_headers = array();
            if (in_array($type, array('getSenderOrderLabel', 'deleteOrder', 'getSenderParcelDocs'))) {
                $options['format'] = waNet::FORMAT_RAW;
            } else {
                $options['format'] = waNet::FORMAT_JSON;
                $custom_headers = array(
                    'Content-Type' => 'application/json',
                );
            }

            if (!empty($callback)) {
                $this->api_callback = compact('callback', 'cache');
            }

            $net = new waNet($options, $custom_headers);

            $response = $net->query($url, $data, $request['method'], empty($callback) ? null : array($this, 'handleApiQuery'));
            if ($response instanceof waNet) {
                return $this;
            } else {
                $this->api_callback = null;

                return $this->handleApiQuery($net, $response, $cache);
            }
        } catch (waException $ex) {
            $this->handleApiException(empty($net) ? null : $net, $ex, $data);
            throw $ex;
        }

        return $response;
    }

    /**
     * @param waNet                   $net
     * @param waNet|waException|array $result
     * @param waiCache                $cache
     * @return $this|mixed|null
     */

    public function handleApiQuery($net, $result, $cache = null)
    {
        if ($result instanceof waNet) {
            return $this;
        } elseif ($result instanceof waException) {
            return $this->handleApiException($net, $result);
        } else {
            $response = $result;
            try {
                if ($this->debug) {
                    $debug = var_export($response, true);
                    waLog::log($debug, 'wa-plugins/shipping/yad/api.debug.log');
                }

                if (!empty($cache)) {
                    $cache->set($response);
                }

                if (empty($this->api_callback)) {
                    return $response;
                } else {
                    if (empty($cache) && isset($this->api_callback['cache'])) {
                        $cache = $this->api_callback['cache'];
                    }

                    if ($cache instanceof waiCache) {
                        $cache->set($response);
                    }

                    return call_user_func_array($this->api_callback['callback'], array($net, $response, $cache));
                }

            } catch (waException $ex) {
                return $this->handleApiException($net, $ex);
            }
        }
    }

    private function handleApiException($net, $ex, $data = null)
    {
        $message = $ex->getMessage();

        if ($net) {
            $response = $net->getResponse();
            if (empty($response)) {
                $response = $net->getResponse(true);
            }
            $message .= "\n".var_export(compact('response'), true);
        }

        if ($data) {
            $message .= "\n".var_export(compact('data'), true);
        }

        waLog::log($message, 'wa-plugins/shipping/yad/api.error.log');

        if (empty($this->api_callback)) {
            throw $ex;
        } else {
            $callback = $this->api_callback['callback'];
            call_user_func_array($callback, array($net, $ex));
        }

        return null;
    }
    // END REQUEST TO YANDEX API

    protected function calculate()
    {
        try {
            if ($this->debug === 'demo') {
                $response = $this->path . '/lib/config/debug/response.php';
                if (file_exists($response)) {
                    $response = include($response);

                    return $this->handleCalculateResponse(null, $response);
                } else {
                    return 'Demo data not available.';
                }
            }

            $empty_fields = $this->validateSettingsFields($this->getSettings());
            if ($empty_fields) {
                return array();
            }

            $delivery_options = $this->getDeliveryOptionsParams();
            if (isset($delivery_options['simple_location'])) {
                $options = $this->getExactAddressesForMainSelector($delivery_options['params']['to']['location']);
                $addresses_do_not_match = true;
                foreach ($options as $option) {
                    if (mb_strpos(mb_strtolower($option['value']), mb_strtolower($delivery_options['simple_location'])) !== false) {
                        $addresses_do_not_match = false;
                    }
                }
                if (count($options) > 1 && $addresses_do_not_match && wa()->getEnv() !== 'backend') {
                    return array(
                        array(
                            'rate' => null,
                            'comment' => $this->_w('Уточните адрес'),
                            'possible_addresses' => $options,
                        ),
                    );
                }
            }

            try {
                $callback = array($this, 'handleCalculateResponse');
                $services = $this->apiQuery('searchDeliveryList', $delivery_options['params'], $callback);

                return $this->handleCalculateResponse(null, $services);
            } catch (waException $ex) {
                return $this->handleCalculateResponse(null, $ex);
            }
        } catch (waException $ex) {
            return array(
                array(
                    'rate'    => null,
                    'comment' => $ex->getMessage(),
                ),
            );
        }
    }

    private function prepareAddress()
    {
        $address = array(
            'from' => array('location' => mb_strtolower($this->city)),
            'to'   => array('location' => mb_strtolower($this->getAddress('city'))),
        );
        if (empty($address['from']['location'])) {
            throw new waException('Не указан город магазина в настройках плагина.');
        }

        if (empty($address['to']['location'])) {
            throw new waException('Не указан населённый пункт доставки.');
        }

        $params = $this->getPackageProperty('shipping_params');
        if (!empty($params) && !empty($params['geo_id_to'])) {
            $location = explode('/', $params['geo_id_to']);
            $geo_id = (int)$location[0];
            if ($geo_id > 0) {
                $address['to']['geoId'] = $geo_id;
                $address['to']['location'] = $location[1];
            }
        }

        $this->raw_address = $address;

        return $address;
    }

    private function getPackageSize($weight = null)
    {
        $data = array();
        $size = $this->size;
        switch ($size['type']) {
            case 'passed':
                $size = $this->getTotalSize();
                if ($size) {
                    $data = $size;
                }
                break;
            case 'fixed':
                $data = reset($size['table']);
                break;
            case 'table':
                $matched_size = array();
                uasort($size['table'], array($this, 'sortSizes'));
                $table = array_reverse($size['table']);
                foreach ($table as $sizes) {
                    if ($weight <= floatval($sizes['weight'])) {
                        $matched_size = $sizes;
                    } else {
                        break;
                    }
                }
                if (empty($matched_size)) {
                    throw new waException('Не найдена подходящая упаковка.');
                }
                $data = $matched_size;
                break;
        }

        $data = array_map('intval', $data);
        foreach ($data as $type => $value) {
            if (empty($value)) {
                unset($data[$type]);
            }
        }
        foreach (['length', 'width', 'height'] as $required_param) {
            if (!isset($data[$required_param])) {
                return array();
            }
        }

        return $data;
    }

    /**
     * Add places or dimensions
     * @param $data
     * @param bool $check_empty
     * @throws waException
     */
    protected function addDimensions(&$data, $check_empty = true)
    {
        if ($this->size['type'] != 'places') {
            $total_weight = $this->getTotalWeight();
            if (!empty($total_weight)) {
                $package_size = $this->getPackageSize($total_weight);
                if (!empty($package_size)) {
                    $data['dimensions'] = [
                        'weight' => $total_weight
                    ];
                    $data['dimensions'] += $package_size;
                }
            }
        }
        if (empty($data['dimensions'])) {
            foreach ($this->getItems() as $item) {
                if (!empty($item['weight'])) {
                    $place = [
                        'dimensions' => $this->getItemDimensions($item)
                    ];

                    if (!isset($data['places'])) {
                        $data['places'] = [];
                    }

                    $data['places'][] = $place;
                }
            }
        }
        if ($check_empty && empty($data['places']) && empty($data['dimensions'])) {
            $data['dimensions'] = [
                'length' => 1,
                'width' => 1,
                'height' => 1,
                'weight' => 1,
            ];
        }
    }

    /**
     * @param $item
     * @return array
     */
    private function getItemDimensions($item)
    {
        return [
            'weight' => $item['weight'],
            'length' => empty($item['length']) || ceil($item['length']) <= 1 ? 1 : ceil($item['length']),
            'width'  => empty($item['width']) || ceil($item['width']) <= 1 ? 1 : ceil($item['width']),
            'height' => empty($item['height']) || ceil($item['height']) <= 1 ? 1 : ceil($item['height']),
        ];
    }

    private function getAssessedPrice($string)
    {
        $cost = 0.0;

        $string = preg_replace('@\\s+@', '', $string);

        foreach (preg_split('@\+|(\-)@', $string, null, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY) as $chunk) {
            $value = str_replace(',', '.', trim($chunk[0]));
            if (strpos($value, '%')) {

                $price = array(
                    $this->getTotalRawPrice(),
                    $this->getTotalPrice(),
                );

                $value = round(max($price) * floatval($value) / 100.0, 2);
            } else {
                $value = floatval($value);
            }
            if ($chunk[1] && (substr($string, $chunk[1] - 1, 1) == '-')) {
                $cost -= $value;
            } else {
                $cost += $value;
            }
        }

        return round(max(0.0, $cost), 2);
    }

    /**
     * @return array|array[]
     * @throws waException
     */
    private function getDeliveryOptionsParams()
    {
        $params = [
            'senderId' => $this->senderId,
            'shipment' => [
                'warehouseId' => $this->warehouseId
            ],
            'cost' => [
                'assessedValue' => $this->getAssessedPrice($this->insurance),
                'itemsSum' => $this->getTotalPrice(),
            ]
        ];

        $params += $this->prepareAddress();

        $this->addDimensions($params, false);

        /** @var string $departure_datetime SQL DATETIME */
        $departure_datetime = $this->getPackageProperty('departure_datetime');
        if ($departure_datetime) {
            $params['shipment']['date'] = date('Y-m-d', strtotime($departure_datetime));
        }

        $country_id = $this->getAddress('country');
        $region_id = $this->getAddress('region');
        $to_location = null;
        if (!empty($country_id) && !empty($region_id)) {
            if (is_numeric($region_id)) {
                $region_model = new waRegionModel();
                $region_data = $region_model->getByField(array(
                    'country_iso3' => $country_id,
                    'code' => $region_id,
                ));
                $name_region = $region_data['name'];
            } else {
                // here region_id is the name of the region
                $name_region = $region_id;
            }
            $to_location = $params['to']['location'];
            if (!empty($name_region)) {
                $params['to']['location'] = $name_region . ', ' . $to_location;
            }
            $zip = $this->getAddress('zip');
            if (!empty($zip)) {
                $params['to']['location'] .= ', ' . $zip;
            } elseif (!empty($params['to']['geoId'])) {
                $params['to']['location'] .= ', ' . $params['to']['geoId'];
            }
        }

        return [
            'simple_location' => $to_location,
            'params' => $params
        ];
    }

    public function handleCalculateResponse($net, $result)
    {
        if ($result instanceof waException) {
            switch ($result->getCode()) {
                case 500:
                    $message = 'При расчёте стоимости доставки произошла ошибка. Повторите попытку позднее.';
                    break;
                case 403:
                    $message = 'При расчёте стоимости доставки произошла ошибка. Проверьте параметры доступа.';
                    break;
                default:
                    $message = 'При расчёте стоимости доставки произошла ошибка.';
                    break;
            }
            $rates = array(
                array(
                    'rate'    => null,
                    'comment' => $message,
                ),
            );
            $this->api_callback = null;

            return $this->result = $rates;
        } elseif ($result instanceof waNet) {
            return $this;
        } elseif ($result instanceof self) {
            return $this;
        } else {
            $services = $result;
            if (empty($services)) {
                $rates = 'Доставка по указанному адресу недоступна.';
            } else {
                // Если API вернул службу доставки без точек самовывоза, то удаляем такую службу из массива
                foreach ($services as $key => $item) {
                    if ($item['delivery']['type'] == 'PICKUP' && empty($item['pickupPointIds'])) {
                        unset($services[$key]);
                    }
                }
                foreach ($services as $key => $service) {
                    $points = array();
                    if (is_array($service['pickupPointIds'])) {
                        $count = count($service['pickupPointIds']) / 100;
                        for ($i = 0; $i < $count; $i++) {
                            $pickup_point_ids = array(
                                'pickupPointIds' => array_slice($service['pickupPointIds'], $i * 100, 100)
                            );
                            $points = array_merge($points, $this->apiQuery('getPickupPoints', $pickup_point_ids));
                        }
                    }

                    $services[$key]['pickupPointIds'] = $points;
                }

                $rates = array();
                foreach ($services as $service) {
                    $rates += $this->formatRate($service);
                }
                uasort($rates, array($this, 'sortServices'));

                $data = array();
                if (!empty($rates)) {
                    foreach ($rates as $rate_id => $rate) {
                        if (!empty($rate['custom_data']) && (count($rate['custom_data']) > 1)) {
                            $data[$rate_id] = $rate['custom_data'];
                        }
                    }
                }

                $key_cache = $this->getCacheKey();
                if ($key_cache) {
                    $cache = new waVarExportCache($key_cache . '.data', 600, 'webasyst', true);
                    $cache->set($data);
                }
            }

            $this->api_callback = null;

            return $this->result = $rates;
        }
    }

    private function formatRate($service)
    {
        $delivery_date = array(
            strtotime($service['delivery']['calculatedDeliveryDateMin']),
            strtotime($service['delivery']['calculatedDeliveryDateMax']),
        );

        $min_delivery_date = min($delivery_date);

        $human_delivery_date = array(
            'minDays' => waDateTime::format('humandate', min($delivery_date)),
            'maxDays' => waDateTime::format('humandate', max($delivery_date)),
        );

        $delivery_date = array_unique(array(min($delivery_date), max($delivery_date)));
        if (count($delivery_date) == 1) {
            $delivery_date = reset($delivery_date);
        }

        $delivery_cost = $service['cost']['deliveryForCustomer'];

        $rate = array(
            'name'          => array($service['delivery']['partner']['name'], $service['tariffName']),
            'service'       => $service['delivery']['partner']['name'],
            'id'            => sprintf('%s:%s', $service['delivery']['partner']['id'], $service['tariffId']),
            'est_delivery'  => implode(' - ', array_unique($human_delivery_date)),
            'delivery_date' => self::formatDatetime($delivery_date),
            'rate'          => $delivery_cost,
            'currency'      => 'RUB',
        );

        # recalc cost WithRules
        $payment_types = $this->getSelectedPaymentTypes();
        if (!empty($payment_types) && $this->cash_service) {
            if ($rate['rate']) {
                $rate['rate'] += self::calculateServiceCost($service, compact('payment_types'));
            }
        }

        if (empty($rate['name'][1]) && !empty($rate['name'][0])) {
            $rate['name'][1] = $rate['name'][0];
        }
        $rate['name'] = implode(': ', array_unique($rate['name']));
        $type = strtolower($service['delivery']['type']);

        // type courier = todoor
        if ($type == 'courier') {
            $type = waShipping::TYPE_TODOOR;
        }

        $rate['custom_data'] = array(
            'type' => $type,
        );

        $rates = array();

        switch ($type) {
            case 'post': // почта России
                $rate['type'] = self::TYPE_POST;
                break;

            case 'todoor': // курьерская
                $rate['type'] = self::TYPE_TODOOR;

                $delivery_date_min = strtotime($service['delivery']['calculatedDeliveryDateMin']) - time();
                $min_days = date('j', $delivery_date_min);
                $rate['custom_data']['courier'] = array(
                    'intervals' => array(),
                    'offset'    => $min_days,
                    'payment'   => array(),
                );

                $payment = &$rate['custom_data']['courier']['payment'];
                foreach ($service['services'] as $_service) {
                    if ($_service['code'] === 'CASH_SERVICE') {
                        $payment[self::PAYMENT_TYPE_CARD] = "Оплата картой";
                        $payment[self::PAYMENT_TYPE_CASH] = "Оплата наличными";
                        break;
                    }
                }
                $payment[self::PAYMENT_TYPE_PREPAID] = "Предоплата";
                unset($payment);

                $intervals = &$rate['custom_data']['courier']['intervals'];
                $schedules = ifset($service['delivery']['courierSchedule']['schedule'], array());
                foreach ($schedules as $schedule) {
                    $schedule['timeFrom'] = preg_replace('@\:00$@', '', $schedule['timeFrom']);
                    $schedule['timeFrom'] = preg_replace('@^(\d:)@', '0$1', $schedule['timeFrom']);
                    $schedule['timeTo'] = preg_replace('@:00$@', '', $schedule['timeTo']);
                    $schedule['timeTo'] = preg_replace('@^(\d:)@', '0$1', $schedule['timeTo']);
                    $interval = sprintf('%s-%s', $schedule['timeFrom'], $schedule['timeTo']);
                    if (!isset($intervals[$interval])) {
                        $intervals[$interval] = array();
                    }
                    if (isset($schedule['day'])) {
                        $intervals[$interval][] = $schedule['day'] - 1;
                    } else {
                        $intervals[$interval] = array(0, 1, 2, 3, 4, 5, 6);
                    }
                }

                foreach ($intervals as &$interval) {
                    $interval = array_unique(array_map('intval', $interval));
                    asort($interval);
                    $interval = array_values($interval);
                    unset($interval);
                }

                ksort($intervals, defined('SORT_NATURAL') ? constant('SORT_NATURAL') : SORT_REGULAR);
                unset($intervals);

                $date_format = waDateTime::getFormat('date');
                $offset = sprintf('+ %d days', $rate['custom_data']['courier']['offset']);
                $rate['custom_data']['courier']['placeholder'] = waDateTime::format($date_format, $offset);

                $rate['custom_data'][waShipping::TYPE_TODOOR] = &$rate['custom_data']['courier'];
                break;

            case 'pickup': // пункт самовывоза
                $rate['type'] = self::TYPE_PICKUP;
                $pickup_points = ifset($service['pickupPointIds'], array());
                $rate['custom_data']['pickup'] = array();
                foreach ($pickup_points as $pickup_point) {
                    $rate['custom_data']['pickup'] = $this->formatPickupPoint($pickup_point, $min_delivery_date);
                    $pickup_rate = $rate;
                    $pickup_rate['name'] .= sprintf(' %s', $pickup_point['address']['shortAddressString']);
                    $pickup_rate['comment'] = ifset($pickup_point, 'address', 'addressString', '');
                    $id = 'pickup.'.$rate['id'].'.'.$pickup_point['id'];
                    $rates[$id] = $pickup_rate;
                }
                break;
        }

        return $rates ? $rates : array($type.'.'.$rate['id'] => $rate);
    }

    private function formatPickupPoint($pickup_point, $delivery_date = null)
    {
        $schedule = array();
        $days = array(
            'Пн',
            'Вт',
            'Ср',
            'Чт',
            'Пт',
            'Сб',
            'Вс',
        );

        $schedule_array = array();

        $formatted_schedule = array();

        foreach ($pickup_point['schedule'] as $schedule_item) {
            $day = intval($schedule_item['day']) - 1;
            $from = preg_replace('@\:00$@', '', $schedule_item['from']);
            $to = preg_replace('@\:00$@', '', $schedule_item['to']);
            $formatted_schedule[$day] = array(
                'start_work' => $schedule_item['from'],
                'end_work'   => $schedule_item['to'],
            );
            $schedule[$day] = array(
                'days' => array($day),
                'time' => sprintf('%s - %s', $from, $to),
            );
        }
        if ($delivery_date && $formatted_schedule) {
            $first_day = date('N', $delivery_date) - 1;
            for ($i = 0; count($schedule_array) < 7; $i++) {
                $day = ($i + $first_day) % 7;
                if (isset($formatted_schedule[$day])) {
                    $sql_time = $formatted_schedule[$day];
                    $sql_day = date('Y-m-d', strtotime(sprintf('+%d days', $i), $delivery_date));
                    $schedule_array[] = array(
                        'start_work' => $sql_day.' '.$sql_time['start_work'],
                        'end_work'   => $sql_day.' '.$sql_time['end_work'],
                        'type'       => 'workday',
                    );
                }
            }
        }

        $prev = null;

        foreach ($schedule as $day => $schedule_item) {
            if (($prev !== null) && (strcmp($schedule[$prev]['time'], $schedule_item['time'])) === 0) {
                $schedule[$prev]['days'][] = $day;
                unset($schedule[$day]);
            } else {
                $prev = $day;
            }
        }

        $template = <<<HTML
<div class="yad-list-item">%s: %s</div>
HTML;

        foreach ($schedule as $day => &$schedule_item) {
            $schedule_days = array(
                $days[min($schedule_item['days'])],
                $days[max($schedule_item['days'])],
            );
            $day = implode(' - ', array_unique($schedule_days));
            $schedule_item = sprintf($template, $day, $schedule_item['time']);
        }
        $schedule = implode($schedule);

        $payment = array();
        if ($pickup_point['supportedFeatures']['card']) {
            $payment[self::PAYMENT_TYPE_CARD] = "Оплата картой";
        }
        if ($pickup_point['supportedFeatures']['cash']) {
            $payment[self::PAYMENT_TYPE_CASH] = "Оплата наличными";
        }
        if ($pickup_point['supportedFeatures']['prepay']) {
            $payment[self::PAYMENT_TYPE_PREPAID] = "Предоплата";
        }

        $comment = ifset($pickup_point['address']['comment'], '');

        return array(
            'id'            => $pickup_point['id'],
            'lat'           => $pickup_point['address']['latitude'],
            'lng'           => $pickup_point['address']['longitude'],
            'title'         => ifset($pickup_point['name'], $pickup_point['id']),
            'description'   => ifset($pickup_point['address']['addressString'], ''),
            'comment'       => htmlentities($comment, ENT_QUOTES, 'UTF-8'),
            'payment'       => $payment,
            'schedule'      => $delivery_date ? array('weekdays' => $schedule_array) : $schedule,
            'schedule_html' => $schedule,
        );
    }

    private static function calculateServiceCost($service, $options)
    {
        $cost = 0;
        foreach ($service['services'] as $_service) {
            if ($_service['code'] === 'CASH_SERVICE') {
                $payment_types = array(
                    waShipping::PAYMENT_TYPE_CASH,
                    waShipping::PAYMENT_TYPE_CARD,
                );

                $required = array_intersect($payment_types, (array)$options['payment_types']);
                if ($required) {
                    $cost += floatval($_service['cost']);
                }
            }
        }
        return $cost;
    }

    private function sortServices($a, $b)
    {
        $sort = array(
            'todoor' => 1,
            'pickup' => 2,
            'post'   => 3,
        );
        $sort = max(-1, min(1, ifset($sort[strtolower($a['type'])]) - ifset($sort[strtolower($b['type'])])));

        if ($sort == 0) {
            $sort = strcasecmp(ifset($a['name'], ''), ifset($b['name'], ''));
        }

        if ($sort == 0) {
            $sort = max(-1, min(1, ceil($a['rate'] - $b['rate'])));
        }

        return $sort;
    }

    // FIELDS
    public function requestedAddressFieldsForService($service)
    {
        if (!isset($service['type'])) {
            $fields = parent::requestedAddressFieldsForService($service);
        } else {
            $fields = array(
                'city' => array(
                    'cost'     => true,
                    'required' => true,
                ),
            );
            switch ($service['type']) {
                case self::TYPE_PICKUP:
                    break;
                case self::TYPE_TODOOR:
                    $fields['street'] = array(
                        'required' => true,
                    );
                    break;
                case self::TYPE_POST:
                    $fields['street'] = array(
                        'required' => true,
                    );
                    $fields['zip'] = array(
                        'cost'     => true,
                        'required' => true,
                    );
                    break;
            }
        }

        return $fields;
    }
    public function requestedAddressFields()
    {
        return array(
            'city'   => array(
                'cost'     => true,
                'required' => true,
            ),
            'street' => array(),
            'zip'    => array(),
        );
    }

    /**
     * used only on the frontend
     * @param waOrder $order
     * @param $service
     * @return array|array[]|array[][]
     */
    public function customFieldsForService(waOrder $order, $service)
    {
        $fields = parent::customFields($order);
        $fields += $this->getGeoIdField($order);
        if (ifset($service['type']) === self::TYPE_TODOOR) {
            $fields += $this->getDeliveryIntervalField($order, $service);
        }

        return $fields;
    }

    public function customFields(waOrder $order)
    {
        $fields = parent::customFields($order);

        $fields += $this->getGeoIdField($order);
        $fields += $this->getDeliveryIntervalField($order);

        $fields['_js'] = array(
            'value'        => null,
            'title'        => '',
            'description'  => '',
            'control_type' => 'YandexFilterControl',
            'options'      => array(
                array(
                    'value'       => 'todoor',
                    'title'       => 'Все курьерские службы',
                    'group'       => 'Курьером',
                    'description' => 'description_placeholder',
                ),
                array(
                    'value'       => 'pickup',
                    'title'       => 'Все пункты самовывоза',
                    'group'       => 'Самовывоз',
                    'description' => 'description_placeholder',
                ),
                array(
                    'value'       => 'post',
                    'title'       => 'Все почтовые службы',
                    'group'       => 'Почтовой службой',
                    'description' => 'description_placeholder',
                ),

            ),
        );

        if (wa()->getEnv() !== 'backend') {
            $this->registerControl('YandexFilterControl', array($this, 'settingFilterControl'));
        } else {
            $this->registerControl('YandexFilterControl', array($this, 'settingFilterBackendControl'));
            $fields['_js']['subtype'] = waHtmlControl::HIDDEN;
        }

        return $fields;
    }

    private function getGeoIdField(waOrder $order)
    {
        $address = '';
        if (isset($order->shipping_address['address']) && !empty($order->shipping_address['address'])) {
            $address = $order->shipping_address['address'];
        } else {
            foreach (array('region_name', 'city') as $key => $address_field) {
                if (isset($order->shipping_address[$address_field]) && !empty($order->shipping_address[$address_field])) {
                    $address .= $order->shipping_address[$address_field];
                    if ($key < 1) {
                        $address .= ', ';
                    }
                }
            }
        }
        $options = $this->getExactAddressesForAdditionalSelector($address);
        if (count($options) > 1) {
            $options = array(
                'options' => $options,
            );
        } else {
            return array();
        }

        $shipping_params = $order->shipping_params;
        return array(
            'geo_id_to' => $options + array(
                'value'        => ifset($shipping_params, 'geo_id_to', null),
                'title'        => 'Населённый пункт доставки',
                'control_type' => waHtmlControl::SELECT,
                'description'  => $this->_w('Уточните адрес'),

                'data' => array(
                    'affects-rate' => true,
                ),
            ),
        );
    }

    /**
     * @param string $customer_address
     * @return array
     */
    protected function getExactAddressesForMainSelector($customer_address = '')
    {
        $options = array();
        $result = $this->getAutocompleteAddresses($customer_address);
        foreach ($result as $item) {
            $exact_address = array(
                'value' => array()
            );
            foreach ($item['addressComponents'] as $address) {
                $address_name = $address['name'];
                if ($address['kind'] == 'PROVINCE') {
                    $exact_address['region'] = $address_name;
                } elseif ($address['kind'] == 'AREA' || $address['kind'] == 'LOCALITY' || $address['kind'] == 'DISTRICT') {
                    $exact_address['value'][] = $address_name;
                }
            }
            if ($exact_address['value']) {
                $id = $item['geoId'];
                $exact_address['value'] = implode(', ', $exact_address['value']);
                $exact_address['city'] = $exact_address['value'];
                $options[$id] = $exact_address;
            }
        }

        return $options;
    }

    /**
     * used if, after selecting an address in the main selector, it was not possible to find the exact address
     * @param string $customer_address
     * @return array
     */
    protected function getExactAddressesForAdditionalSelector($customer_address = '')
    {
        $options = array();
        $result = $this->getAutocompleteAddresses($customer_address);
        foreach ($result as $item) {
            $locality = [];
            foreach ($item['addressComponents'] as $address) {
                if ($address['kind'] == 'AREA' || $address['kind'] == 'LOCALITY' || $address['kind'] == 'DISTRICT') {
                    $locality[] = $address['name'];
                }
            }
            if ($locality) {
                $id = $item['geoId'];
                $full_address = $item['address'];
                $locality_string = implode(', ', $locality);
                $options[$id] = array(
                    'title' => $full_address,
                    'description' => $full_address,
                    'value' => sprintf('%d/%s', $id, $locality_string),
                    'data' => array(
                        'city' => $locality_string,
                        'address' => sprintf('%d/%s (%s)', $id, $locality_string, $full_address),
                    ),
                );
            }
        }

        return $options;
    }

    protected function getAutocompleteAddresses($customer_address)
    {
        $result = array();

        if (empty($customer_address)) {
            if (!empty($this->raw_address['to']['location'])) {
                $customer_address = $this->raw_address['to']['location'];
            } else {
                $customer_address = waRequest::post('city', '', waRequest::TYPE_STRING);
            }
        }

        if (is_string($customer_address)) {
            $customer_address = trim($customer_address);
        }

        if (!empty($customer_address)) {
            $params = array(
                'term' => urlencode($customer_address),
            );

            try {
                $result = $this->apiQuery('autocomplete', $params);
                return $result;
            } catch (waException $ex) {}
        }

        return $result;
    }

    private function getDeliveryIntervalField(waOrder $order, $service = array())
    {
        $value = array();

        $shipping_params = $order->shipping_params;
        if (!empty($shipping_params['desired_delivery.interval'])) {
            $value['interval'] = $shipping_params['desired_delivery.interval'];
            $value['interval'] = preg_replace('@(^\-)(\d:)@', '$10$2', $value['interval']);
        }
        if (!empty($shipping_params['desired_delivery.date_str'])) {
            $value['date_str'] = $shipping_params['desired_delivery.date_str'];
        }
        if (!empty($shipping_params['desired_delivery.date'])) {
            $value['date'] = $shipping_params['desired_delivery.date'];
        }

        if ($service) {
            $intervals = ifset($service, 'custom_data', 'courier', 'intervals', array());
        } else {
            $service_variant_id = ifset($shipping_params, '%service_variant_id', null);
            if (($service_variant_id !== null) && ($key = $this->getCacheKey())) {
                $cache = new waVarExportCache($key.'.data', 600, 'webasyst', true);
                $data = $cache->get();
                if (isset($data[$service_variant_id])) {
                    $service = array(
                        'custom_data' => $data[$service_variant_id],
                    );
                }
            }

            $intervals = array(
                null => array(0, 1, 2, 3, 4, 5, 6),
            );

            $intervals = ifset($service, 'custom_data', 'courier', 'intervals', $intervals);
        }
        foreach ($intervals as $time => $week_days) {
            if (empty($week_days)) {
                $intervals[$time] = array(0, 1, 2, 3, 4, 5, 6);
            }
        }

        return array(
            'desired_delivery' => array(
                'value'        => $value,
                'title'        => 'Желаемые дата и время доставки',
                'control_type' => waHtmlControl::DATETIME,
                'params'       => array(
                    'date'      => (int)ifset($service, 'custom_data', 'courier', 'offset', 0),
                    'interval'  => true,
                    'intervals' => $intervals,
                ),
            ),
        );
    }

    /**
     * used only in the old checkout
     */
    public function inputDataAction()
    {
        $key = waRequest::request('key', $this->getCacheKey(), waRequest::TYPE_STRING_TRIM);
        $data = null;
        if (strlen($key)) {
            $cache = new waVarExportCache($key.'.data', 600, 'webasyst', true);
            if ($cache->isCached()) {
                $data = $cache->get($data);
            }
            $cache->delete();
        }

        if (is_array($data)) {
            $response = array(
                'services' => $data,
                'options'  => $this->getExactAddressesForAdditionalSelector(),
            );
            self::sendJsonData($response);
        } else {
            self::sendJsonError('No data cached');
        }
    }

    public function getStateFields($state, waOrder $order = null, $params = array())
    {
        $fields = parent::getStateFields($state, $order, $params);
        switch ($state) {
            case self::STATE_READY:
                $fields['shipment_date'] = array(
                    'value'        => date('Y-m-d', strtotime('tomorrow')),
                    'title'        => 'Дата отгрузки',
                    'description'  => 'Укажите дату отгрузки заказа в службу доставки',
                    'control_type' => waHtmlControl::INPUT,
                );
                break;
        }

        return $fields;
    }

    public function settingFilterControl($name, $params = array())
    {
        $html = '';
        $html .= ifset($params['description'], '');
        $wrappers = array(
            'title'           => '',
            'title_wrapper'   => false,
            'description'     => '',
            'control_wrapper' => "%s%3\$s\n%2\$s\n",
            'value'           => '',
        );

        $params = array_merge($params, $wrappers);

        $filter_params = $params;
        $filter_params['style'] = 'display: none;';
        $filter_name = preg_replace('@([_\w]+)(\]?)$@', '$1.filter$2', $name);
        $html .= waHtmlControl::getControl(waHtmlControl::SELECT, $filter_name, $filter_params);
        waHtmlControl::makeId($filter_params, $filter_name);


        $pickup_name = preg_replace('@([_\w]+)(\]?)$@', '$1.pickup_id$2', $name);
        $params['options'] = array(
            array(
                'value'       => '-1',
                'title'       => 'title_placeholder',
                'description' => 'description_placeholder',
            ),
        );
        $radio = waHtmlControl::getControl(waHtmlControl::RADIOGROUP, $pickup_name, $params);

        $map = false;
        switch ($this->map) {
            case 'desktop':
                if (!waRequest::isMobile()) {
                    $map = true;
                }
                break;
            case 'always':
                $map = true;
                break;
        }

        $url_params = array(
            'action_id' => 'inputData',
            'plugin_id' => $this->key,
            'key'       => $this->getCacheKey(),
        );
        $url = json_encode(wa()->getRouteUrl(sprintf('%s/frontend/shippingPlugin', $this->app_id), $url_params, true));

        $css = file_get_contents($this->path.'/css/frontend.css');
        $html .= <<<HTML
<style>{$css}
</style>
HTML;
        if ($map) {

            $map_params = $params;
            waHtmlControl::makeId($map_params, $name, 'cart');

            $html .= <<<HTML
<section class="yad-loading-section" style="display:none;">
    <i class="icon16 loading"></i>
</section>

<section class="yad-map-section" style="display:none;">
    <div class="yad-map-wrapper">
        <div class="yad-map" id="{$map_params['id']}">
            <i class="icon16 loading"></i>
        </div>
    </div>
    <div class="yad-aside-wrapper">
        <div class="yad-aside">
            <ul>
                <li class="js-yad-variant">{$radio}<input class="js-yad-button" type="button" value="Выбрать" style="display: none;"></li>
            </ul>
        </div>
    </div>
</section>
HTML;
        } else {
            $map_params = array(
                'id' => '',
            );
        }
        $html .= <<<HTML
<section class="yad-pickup-section" style="display: none;">
    <div class="line" style="font-weight: bold; font-size: 120%; margin-bottom: 20px;">Вы выбрали пункт самовывоза:</div>
    <div class="yad-pickup-header">Name placeholder</div>
    <div class="line js-yad-address">Address placeholder</div>
    <div class="line js-yad-payment">Payment placeholder</div>
    <div class="line hint">Road description placeholder</div>
    <div class="line js-yad-schedule">Schedule</div>
    <div class="line">
        <a href="javascript:void(0);" class="js-yad-change">Изменить</a>
    </div>
</section>

HTML;
        $js_url = json_encode(wa()->getRootUrl(true, true).'wa-plugins/shipping/yad/js/frontend.js?v='.$this->getProperties('version'));

        $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        'use strict';

        function init() {
            var _instance = new ShippingYad(
                '$this->key',
                {
                    map: '{$map_params['id']}',
                    filter: '{$filter_params['id']}'
                },
                {$url}
            );
        }

        if (typeof(ShippingYad) === 'undefined') {
            $.getScript({$js_url}, init);
        } else {
            init();
        }

    });
</script>
HTML;

        return $html;
    }

    public function settingFilterBackendControl($name, $params = array())
    {
        $html = '';
        $html .= ifset($params['description'], '');
        $wrappers = array(
            'title'           => '',
            'title_wrapper'   => false,
            'description'     => '',
            'control_wrapper' => "%s%3\$s\n%2\$s\n",
            'value'           => '',
            'disabled'        => 'disabled',
        );

        $html .= waHtmlControl::getControl(waHtmlControl::HIDDEN, $name, $wrappers + $params);
        waHtmlControl::makeId($params, $name);

        $js_url = json_encode(wa()->getRootUrl(true, true).'wa-plugins/shipping/yad/js/backend.js?v='.$this->getProperties('version'));

        $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        'use strict';

        function init() {
            var _instance = new ShippingYadBackend(
                '$this->key',
                '{$params['id']}'
            );
        }

        if (typeof(ShippingYadBackend) === 'undefined') {
            $.getScript({$js_url}, init);
        } else {
            init();
        }

    });
</script>
HTML;
        return $html;
    }
    // END FIELDS

    /**
     * Set package state into waShipping::STATE_DRAFT
     * @param waOrder $order
     * @param array   $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function draftPackage(waOrder $order, $shipping_data = array())
    {
        $integration = $this->integration;
        if (empty($integration[self::STATE_DRAFT])) {
            return null;
        }
        $data = array();
        if (empty($order->shipping_data['order_id'])) {
            $method = 'createOrder';
        } else {
            $method = 'updateOrder';
            $data['id'] = $order->shipping_data['order_id'];
        }
        if ($order->id && strlen((string)$order->id) <= 10) {
            $data['externalId'] = (string)$order->id;
        }

        $shipping_discount = $this->correctItems();

        $delivery_type = substr($order->shipping_rate_id, 0, strpos($order->shipping_rate_id, '.'));
        if ($delivery_type == 'todoor') {
            $delivery_type = 'courier';
        }
        $delivery_type = strtoupper($delivery_type);
        $data += array(
            'senderId' => $this->senderId,
            'comment' => $order->comment,
            'deliveryType' => $delivery_type,
            'cost' => array(
                'manualDeliveryForCustomer' => $shipping_discount,
                'assessedValue' => $this->getAssessedPrice($this->insurance),
                'fullyPrepaid' => (bool)$order->paid_datetime,
            ),
            'deliveryOption' => array(
                'delivery' => max(0, number_format($order->shipping - $shipping_discount, 0, '.', '')),
            ),
            'recipient' => [],
        );

        $order_shipping_type = preg_replace('@\..+$@', '', $order->shipping_rate_id);

        $delivery = explode(':', preg_replace('@[^\.]+\.([^\.]+)(\.[^\.]+)?$@', '$1', $order->shipping_rate_id));
        if ($order_shipping_type == 'pickup') {
            $data['recipient']['pickupPointId'] = preg_replace('@^pickup\.[^\.]+\.(.+)$@', '$1', $order->shipping_rate_id);
        }
        $data['deliveryOption'] += array(
            'tariffId'  => ifset($delivery[1]),
            'partnerId' => $delivery[0],
        );

        $data['shipment']['type'] = $this->shipping_type;
        if (strtolower($data['shipment']['type']) != 'import') {
            $data['shipment']['partnerTo'] = $delivery[0];
            $data['shipment']['warehouseFrom'] = $this->warehouseId;
        }

        // RECIPIENT
        $shipping_address = $order->shipping_address;
        $data['recipient'] += $this->getContactFields($shipping_address, $order, 'recipient');
        foreach (['country' => 'country_name', 'region' => 'region_name',
                     'locality' => 'city', 'street' => 'street', 'postalCode' => 'zip'] as $yad_field => $wa_field
        ) {
            if (!empty($shipping_address[$wa_field])) {
                if (!isset($data['recipient']['address'])) {
                    $data['recipient']['address'] = [];
                }
                $data['recipient']['address'][$yad_field] = $shipping_address[$wa_field];
            }
        }
        if (!empty($order['shipping_params']['geo_id_to'])) {
            $data['recipient']['address']['geoId'] = (int)$order['shipping_params']['geo_id_to'];
        } elseif (!empty($order['shipping_params_geo_id_to'])) {
            $data['recipient']['address']['geoId'] = (int)$order['shipping_params_geo_id_to'];
        }

        // CONTACTS
        $contacts_fields = $this->getContactFields($shipping_address, $order, 'contacts');
        if ($contacts_fields) {
            $data['contacts'][0]['type'] = 'RECIPIENT';
            $data['contacts'][0] += $contacts_fields;
        }

        $this->addDimensions($data);

        foreach ($this->getItems() as $item) {
            $item_params = array(
                'externalId' => $item['id'],
                'tax'        => $this->getTaxType($item),
            );
            $quantity = explode('.', (string)$item['quantity']);
            if (!empty($quantity[1])) {
                $item_params['name'] = $item['name'] . sprintf(" (%.3f %s)", $item['quantity'], $item['stock_unit']);
                $item_params['count'] = 1;
                $item_params['price'] = round(max(0, $item['price'] * $item['quantity'] - $item['discount'] * $item['quantity']), 2);
            } else {
                $item_params['name'] = $item['name'];
                $item_params['count'] = $item['quantity'];
                $item_params['price'] = max(0, round($item['price'] - $item['discount'], 2));
            }

            if (!empty($item['weight'])) {
                $item_params['dimensions'] = $this->getItemDimensions($item);
            }

            $data['items'][] = $item_params;
        }

        if (!empty($data['recipient']['pickupPointId'])) {
            $search_delivery_params = $this->getDeliveryOptionsParams()['params'];
            $search_delivery_params['to']['pickupPointIds'] = [
                $data['recipient']['pickupPointId']
            ];
            $search_delivery_params['cost']['fullyPrepaid'] = (bool)$order->paid_datetime;
            $search_delivery_params['deliveryType'] = $delivery_type;
            $search_delivery_params['cost']['manualDeliveryForCustomer'] = $shipping_discount;

            // Get missing data in the waOrder
            $delivery_list = $this->apiQuery('searchDeliveryList', $search_delivery_params);
            foreach ($delivery_list as $item) {
                if (is_array($item['pickupPointIds']) && in_array($data['recipient']['pickupPointId'], $item['pickupPointIds'])) {
                    $data['deliveryOption'] += [
                        'calculatedDeliveryDateMin' => $item['delivery']['calculatedDeliveryDateMin'],
                        'calculatedDeliveryDateMax' => $item['delivery']['calculatedDeliveryDateMax'],
                    ];
                    foreach ($item['services'] as $service) {
                        if (!isset($data['deliveryOption']['services'])) {
                            $data['deliveryOption']['services'] = [];
                        }
                        $data['deliveryOption']['services'][] = [
                            'code' => $service['code'],
                            'cost' => $service['cost'],
                            'customerPay' => $service['customerPay'],
                        ];
                    }

                    $delivery_for_customer = $item['cost']['deliveryForCustomer'];
                    if (!empty($delivery_for_customer)) {
                        $data['deliveryOption']['deliveryForCustomer'] = $delivery_for_customer;
                    }
                }
            }
        }

        try {
            $response = $this->apiQuery($method, $data);

            if (is_int($response)) {
                $request = array(
                    'senderId' => $this->senderId,
                    'orders' => array(
                        array('id' => $response)
                    )
                );
                try {
                    $order_status = $this->apiQuery('getSenderOrderStatus', $request);
                    if (isset($order_status[0]['status']['description'])) {
                        $status = $order_status[0]['status']['description'];
                    }
                } catch (waException $e) {
                    $status = 'Заказ доступен для редактирования.';
                }
            }
            if (empty($order->shipping_data['order_id'])) {
                $template = 'Создан';
            } else {
                $template = 'Обновлён';
            }
            if (empty($this->companyId)) {
                $template .= ' заказ в статусе «%s» <a href="https://partner.market.yandex.ru/delivery/" target="_blank">№%s<i class="icon16 new-window"></i></a>';
                $view_data = sprintf($template, $status, $response);
            } else {
                $template .= ' заказ в статусе «%s» <a href="https://partner.market.yandex.ru/delivery/%d/orders/item/%d" target="_blank">№%s<i class="icon16 new-window"></i></a>';
                $view_data = sprintf($template, $status, $this->companyId, $response, $response);
            }

            $order_info = $this->apiQuery('getOrder', ['id' => $response]);
            $shipping_data = array(
                'order_id'        => $response,
                'status'          => 1,
                'client_id'       => $this->client_id,
                'view_data'       => $view_data,
                'tracking_number' => !empty($order_info['deliveryServiceExternalId']) ? $order_info['deliveryServiceExternalId'] : $response,
            );

            return $shipping_data;
        } catch (waException $ex) {
            return $ex->getMessage();
        }
    }

    protected function getContactFields($shipping, $order, $request_key)
    {
        $fields = array(
            'firstName'  => 'firstname',
            'lastName'   => 'lastname',
            'middleName' => 'middlename',
        );
        if ($request_key == 'recipient') {
            $fields['email'] = 'email';
        } elseif ($request_key == 'contacts') {
            $fields['phone'] = 'phone';
        }
        $result = array();

        foreach ($fields as $yandex_key => $shop_script_key) {
            $contact_value = ifempty($shipping[$shop_script_key], $order->getContactField($shop_script_key));

            if (!empty($contact_value)) {
                $result[$yandex_key] = $contact_value;
            }
        }

        return $result;
    }

    /**
     * @return float
     */
    protected function correctItems()
    {
        $items_discount = $this->getPackageProperty('items_discount');
        $total_discount = $this->getPackageProperty('total_discount');
        if ($items_discount != $total_discount) {
            $total = $this->getTotalRawPrice();
            $items = $this->getItems();
            $discount = min(1.0, $total_discount / $total);
            foreach ($items as &$item) {
                // weight property is required by usps, so if not exist set to default 1
                $item['discount'] = $discount * $item['price'];
                $item['total_discount'] = $item['discount'] * $item['quantity'];
            }
            unset($item);


            $this->setItems($items);
            if ($total_discount > $total) {
                return $total_discount;
            }
        }

        return 0.0;
    }

    private function getTaxType($item)
    {
        switch ($this->taxes) {
            case 'map':
                $rate = ifset($item['tax_rate']);
                if (in_array($rate, array(null, false, ''), true)) {
                    $rate = -1;
                }
                switch ($rate) {
                    case 20:
                        $type = 'VAT_20';
                        break;
                    case 10:
                        $type = 'VAT_10';
                        break;
                    case 0:
                        $type = 'VAT_0';
                        break;
                    default:
                        $type = 'NO_VAT';
                        break;
                }
                break;
            case 'no':
            case 'skip':
            default:
                $type = 'NO_VAT';
                break;
        }

        return $type;
    }

    /**
     * Set package state into waShipping::STATE_READY
     * @param waOrder $order
     * @param array   $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function readyPackage(waOrder $order, $shipping_data = array())
    {
        $integration = $this->integration;
        $order_shipping_type = preg_replace('@\..+$@', '', $order->shipping_rate_id);
        if (empty($integration[self::STATE_READY])
            || empty($order->shipping_data['order_id']) || $order_shipping_type == self::TYPE_PICKUP
        ) {
            return null;
        }

        try {
            $order_id = $order->shipping_data['order_id'];
            $data = array(
                'orderIds' => array($order_id),
            );

            $response = $this->apiQuery('confirmSenderOrders', $data);
            if (isset($response['errors']) && !empty($response['errors'])) {
                return $this->getErrorMessage($response['errors']);
            } elseif (isset($response['violations']) && !empty($response['violations'])) {
                return $this->getErrorMessage($response['violations']);
            } elseif (!empty($response['orderId'])) {
                $shipment_date = ifempty($shipping_data['shipment_date'], date('Y-m-d', strtotime('tomorrow')));
                $order_info = $this->apiQuery('getOrder', ['id' => $order_id]);
                $data = array(
                    'tracking_number' => $order_info['deliveryServiceExternalId'],
                    'status'          => 'CREATED',
                    'shipment_date'   => $shipment_date,
                    'view_data'       => sprintf('Ожидаемая дата отгрузки заказа в службу доставки: %s.', $shipment_date),
                );

                return $data;
            } else {
                return null;
            }
        } catch (waException $ex) {
            if ($this->api_error) {
                return 'Ошибка при автоматическом подтверждении отправления. Подтвердите отправление вручную в личном кабинете «Яндекс.Доставки».';
            } else {
                return $ex->getMessage();
            }
        }
    }

    private function getErrorMessage($warnings)
    {
        $message = 'Ошибка при автоматическом подтверждении отправления. Подтвердите отправление вручную в личном кабинете «Яндекс.Доставки».';
        $hint_text = is_array($warnings) ? implode(',', $warnings) : $warnings;
        $hint = htmlentities($hint_text, ENT_NOQUOTES, 'utf-8');
        $message .= sprintf('<span class="hint">%s</span>', $hint);

        return $message;
    }

    /**
     * Set package state into waShipping::STATE_CANCELED
     * @param waOrder $order
     * @param array   $shipping_data
     * @return null|string|string[] null, error or shipping data array
     */
    protected function cancelPackage(waOrder $order, $shipping_data = array())
    {
        $integration = $this->integration;
        $order_shipping_type = preg_replace('@\..+$@', '', $order->shipping_rate_id);
        if (empty($integration[self::STATE_CANCELED])
            || empty($order->shipping_data['order_id'])
            || $order_shipping_type == self::TYPE_PICKUP
        ) {
            return null;
        }

        try {
            $data = array(
                'id' => $order->shipping_data['order_id'],
            );
            $this->apiQuery('deleteOrder', $data);

            return array(
                'view_data'       => 'Заказ отменён в службе доставки.',
                'order_id'        => null,
                'status'          => 'CANCELED',
                'tracking_number' => null,
            );
        } catch (waException $ex) {
            if (!empty($this->api_error)) {
                return $this->api_error;
            }

            return $ex->getMessage();
        }
    }

    public function tracking($tracking_id = null)
    {
        if (!empty($tracking_id)) {
            if (is_numeric($tracking_id)) {
                return $this->getStatusMessage($tracking_id);
            } elseif (is_string($tracking_id)) {
                $params = [
                    'senderIds' => [
                        $this->senderId
                    ],
                    'term' => $tracking_id
                ];
                try {
                    $response = $this->apiQuery('searchOrder', $params);
                    if ($response['totalElements'] == 1 && isset($response['data'][0]['id'])) {
                        return $this->getStatusMessage($response['data'][0]['id']);
                    }
                } catch (waException $e) {
                    return 'Статус отправления: «Заказ не найден».';
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $order_id
     * @return string|void
     */
    private function getStatusMessage($order_id)
    {
        $params = array(
            'senderId' => $this->senderId,
            'orders' => [
                ['id' => $order_id]
            ]
        );
        try {
            $response = $this->apiQuery('getSenderOrderStatus', $params);
            if (isset($response[0]['status']['description']) && !empty($response[0]['status']['description'])) {
                return sprintf('Статус отправления: «%s».', $response[0]['status']['description']);
            }
        } catch (waException $e) {
            return 'Статус отправления: «Заказ не найден».';
        }
    }

    // FORMS
    public function getPrintForms(waOrder $order = null)
    {
        $integration = $this->integration;
        $forms = array();
        if (!empty($integration[self::STATE_SHIPPING])) {
            if (!empty($order->shipping_data['order_id'])) {
                $forms['label'] = array(
                    'name'        => 'Ярлык заказа',
                    'description' => '',
                );
            }
            if (!empty($order->shipping_data['parcel_id'])) {
                $forms['parcel'] = array(
                    'name'        => 'Сопроводительные документы',
                    'description' => '',
                );
            }
        }

        return $forms;
    }

    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        switch ($id) {
            case 'label':
                $this->getLabelPrintform($order);
                break;
            case 'parcel':
                $this->getParcelPrintform($order);
                break;
            default:
                throw new waException('Invalid printform ID');
        }
    }

    private function getLabelPrintform(waOrder $order)
    {
        if (!empty($order->shipping_data['order_id'])) {
            $order_id = $order->shipping_data['order_id'];
            $path = wa()->getDataPath('shipping-plugins/yad/');
            $path .= sprintf('label/%d/%d.pdf', $order_id % 100, $order_id);
            if (!file_exists($path)) {
                $params = array(
                    'id' => $order_id,
                );
                $form = $this->apiQuery('getSenderOrderLabel', $params);
                waFiles::write($path, $form);
            }
            $template = 'order_label_%d.pdf';
            $name = sprintf($template, $order_id);
            waFiles::readFile($path, $name);
        } else {
            throw new waException('Заказ ещё не сформирован в службе доставки.');
        }
    }

    private function getParcelPrintform(waOrder $order)
    {
        if (!empty($order->shipping_data['parcel_id'])) {
            $parcel_id = $order->shipping_data['parcel_id'];
            $path = wa()->getDataPath('shipping-plugins/yad/');
            $path .= sprintf('parcel/%d/%d.pdf', $parcel_id % 100, $parcel_id);
            if (!file_exists($path)) {
                $data = array(
                    'id' => $parcel_id,
                    'cabinetId' => $this->cabinetId
                );
                $form = $this->apiQuery('getSenderParcelDocs', $data);
                waFiles::write($path, $form);
            }
            $template = 'parcel_%d.pdf';
            $name = sprintf($template, $parcel_id);
            waFiles::readFile($path, $name);
        } else {
            throw new waException('Заказ ещё не сформирован в службе доставки.');
        }
    }
    // END FORMS

    // HELPERS
    private function sortSizes($a, $b)
    {
        if ($a['weight'] > $b['weight']) {
            return 1;
        } elseif ($a['weight'] < $b['weight']) {
            return -1;
        } else {
            return 0;
        }
    }

    private static function sendJsonError($error)
    {
        $response = array(
            'status' => 'fail',
            'errors' => array($error),
        );
        self::sendJsonResponse($response);
    }

    private static function sendJsonData($data)
    {
        $response = array(
            'status' => 'ok',
            'data'   => $data,
        );
        self::sendJsonResponse($response);
    }

    private static function sendJsonResponse($response)
    {

        if (waRequest::isXMLHttpRequest()) {
            wa()->getResponse()->addHeader('Content-Type', 'application/json')->sendHeaders();
        }
        $options = 0;
        $option_names = array(
            'JSON_PRETTY_PRINT',
            'JSON_UNESCAPED_UNICODE',
        );
        foreach ($option_names as $option_name) {
            if (defined($option_name)) {
                $options = $options | constant($option_name);
            }
        }
        echo json_encode($response, $options);
        exit;
    }

    private static function format($data)
    {
        if (is_array($data)) {
            ksort($data);
            foreach ($data as $key => $value) {
                if ($value === null) {
                    unset($data[$key]);
                } else {
                    $data[$key] = self::format($value);
                }
            }
        }

        return $data;
    }

    private function implode($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        ksort($data);

        return implode('', array_map(array($this, 'implode'), $data));
    }
    // END HELPERS

    // SETTINGS
    public function saveSettings($settings = array())
    {
        if (isset($settings['size'])) {
            switch (ifset($settings['size']['type'])) {
                case 'table':
                    uasort($settings['size']['table'], array($this, 'sortSizes'));
                    break;
                case 'fixed':
                    $size = reset($settings['size']['table']);
                    $settings['size']['table'] = array($size);
                    break;
            }
        }


        $empty_fields = $this->validateSettingsFields($settings);
        if ($empty_fields) {
            throw new waException('Следующие поля не должны быть пустыми или содержать нулевое значение: ' . implode(', ', $empty_fields));
        }

        return parent::saveSettings($settings);
    }

    protected function validateSettingsFields($settings)
    {
        $required_fields = array('oauth', 'cabinetId', 'senderId', 'companyId', 'warehouseId');
        $empty_fields = array();
        foreach ($required_fields as $field) {
            if (empty($settings[$field])) {
                $empty_fields[] = $field;
            }
        }

        return $empty_fields;
    }

    protected function initControls()
    {
        parent::initControls();
        $this->registerControl('SizeControl');
    }

    public static function settingSizeControl($name, $params = array())
    {
        $default_params = array(
            'title'         => '',
            'title_wrapper' => false,
            'description'   => '',
        );

        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }

        waHtmlControl::makeId($params, $name);

        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }

        $params = array_merge($params, $default_params);
        waHtmlControl::addNamespace($params, $name);
        unset($name);


        $html = '';

        $radio_params = $params;
        $radio_params['value'] = ifset($params['value']['type']);
        $radio_params['options'] = array(
            array(
                'value'       => 'places',
                'title'       => 'Передавать габариты товаров отдельными позициями',
                'description' => '',
            )
        );
        if (isset($params['instance'])) {
            /** @var yadShipping $instance */
            $instance = $params['instance'];
            if ($instance->getAdapter()->getAppProperties('dimensions')) {
                $radio_params['options'][] = array(
                    'value'       => 'passed',
                    'title'       => 'Рассчитанные дополнительным плагином',
                    'description' => '',
                );
            }
        }
        $radio_params['options'][] = array(
            'value'       => 'fixed',
            'title'       => 'Фиксированное значение',
            'description' => '',
        );
        $html .= waHtmlControl::getControl(waHtmlControl::RADIOGROUP, 'type', $radio_params);


        $html .= ifset($params['control_separator']);
        $radio_params['options'] = array(
            array(
                'value'       => 'table',
                'title'       => 'Таблица размеров',
                'description' => '',
            ),
        );
        $html .= waHtmlControl::getControl(waHtmlControl::RADIOGROUP, 'type', $radio_params);
        $html .= ifset($params['control_separator']);
        waHtmlControl::makeId($radio_params, 'type');

        $html .= <<<HTML
<table class="zebra" id="{$params['id']}">
<thead>
    <tr>
        <th class="js-weight">Вес</th>
        <th colspan="2">Высота</th>
        <th colspan="2">Ширина</th>
        <th>Длина</th>
        <th class="js-weight">&nbsp;</th>
    </tr>
</thead>
<tfoot>
    <tr class="white js-weight">
        <td><a href="#" class="inline inline-link js-add-size"><i class="icon16 plus"></i> <b><i>Добавить размер</i></b></a></td>
        <td colspan="4"><span class="hint">Сохраните размеры ваших упаковок в зависимости от веса заказа. Или выберите единый фиксированный размер.</span></td>
    </tr>
</tfoot>
HTML;
        $html .= '<tbody>';
        $default_row = array(
            'weight' => 1,
            'height' => 10,
            'width'  => 20,
            'length' => 30,
        );
        $table = ifset($params['value']['table'], array($default_row));
        $params['title'] = '';

        $params['title_wrapper'] = '%s';
        $params['description'] = 'см';
        $params['control_wrapper'] = '<td>%s%s&nbsp;%s</td>';
        $params['description_wrapper'] = '%s';
        $params['size'] = 6;

        $dimensions = array('height', 'width', 'length');
        waHtmlControl::addNamespace($params, 'table');

        foreach ($table as $id => $row) {
            $html .= '<tr class="js-size">';
            $row_params = $params;
            waHtmlControl::addNamespace($row_params, $id);
            $weight_params = $row_params;
            $weight_params['description'] = 'кг';
            $weight_params['control_wrapper'] = '<td class="js-weight">%s%s&nbsp;%s</td>';
            $weight_params['title'] = '≤ ';
            $weight_params['value'] = floatval(isset($row['weight']) ? $row['weight'] : $default_row['weight']);
            $html .= waHtmlControl::getControl(waHtmlControl::INPUT, 'weight', $weight_params);
            foreach ($dimensions as $dimension) {
                $row_params['value'] = floatval(isset($row[$dimension]) ? $row[$dimension] : $default_row[$dimension]);
                $html .= waHtmlControl::getControl(waHtmlControl::INPUT, $dimension, $row_params);
                if ($dimension != 'length') {
                    $html .= '<td>×</td>';
                }
            }

            $html .= '<td class="js-weight"><a href="#" class="js-delete-size"><i class="icon16 delete"></i></a></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= "</table>";

        $html .= <<<HTML
<script type="text/javascript">
    $(function () {
        'use strict';
        var radio = $(':input[type="radio"][name$="\[size\]\[type\]"]');
        var container = $('#{$params['id']}');

        container.on('click', '.js-add-size', function () {
            var el = $(this);
            var table = el.parents('table:first');
            var last = table.find('tr.js-size:last');
            var clone = last.clone();

            clone.find('input').each(function () {
                var input = $(this);

                // increase index inside input name
                var name = input.attr('name');
                input.attr('name', name.replace(/\[table]\[(\d+)]/, function (str, p1) {
                    return '[table][' + (parseInt(p1, 10) + 1) + ']';
                }));
                var value = parseFloat(input.val());
                input.val(value >= 10 ? value + 5 : (value < 2 ? value + 0.5 : value + 1));
            });

            last.after(clone);
            return false;
        });

        container.on('click', '.js-delete-size', function () {
            /**
             * @this HTMLInputElement
             */
            var el = $(this);
            var table = el.parents('table:first');
            if (table.find('tr.js-size').length > 1) {
                el.parents('tr:first').remove();
            } else {
                el.parents('tr:first').find('input').each(function () {
                    this.value = this.defaultValue;
                });
            }
            return false;
        });

        radio.change(function (event) {

            if (!event.originalEvent) {
            }
            if (this.checked) {
                var rows = container.find('tr.js-size');
                var scope = container.find('.js-weight');
                switch (this.value) {
                    case 'fixed':
                        container.show();
                        if (rows.length > 1) {
                            rows.filter(':not(:first)').hide();
                        }
                        scope.hide();
                        break;
                    case 'table':
                        container.show();
                        scope.show();
                        rows.show();
                        break;
                    default:
                        container.hide();
                        break;

                }
            }

        }).change();
    });
</script>
HTML;

        return $html;
    }

    public function integrationOptions()
    {
        require_once 'config/options/yadShippingOptions.class.php';
        return yadShippingOptions::integrationOptions($this->getAdapter());
    }

    public function taxesOptions()
    {
        require_once 'config/options/yadShippingOptions.class.php';
        return yadShippingOptions::taxesOptions($this->getAdapter());
    }
    // END SETTINGS
}
