<?php

/**
 *
 * @property-read array  $rate_zone
 * @property-read array  $contact_fields
 * @property-read bool   $required_fields
 * @property-read string $rate_by
 * @property-read string $currency
 * @property-read string $weight_dimension
 * @property-read array  $rate
 * @property-read string $delivery_time
 * @property-read string $customer_interval
 * @property-read int    $exact_delivery_time
 *
 */
class courierShipping extends waShipping
{
    public function saveSettings($settings = array())
    {
        $fields = array_keys(array_filter(ifset($settings['rate_zone'], array())));
        $settings['contact_fields'] = array_merge(ifset($settings['contact_fields'], array_combine($fields, $fields)));
        return parent::saveSettings($settings);
    }

    /**
     * @param array $params
     * @return string HTML
     * @see waShipping::getSettingsHTML()
     */
    public function getSettingsHTML($params = array())
    {
        $params += array(
            'translate' => array(&$this, '_w'),
        );
        $values = $this->getSettings();

        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        if (!$values['rate_zone']['country']) {
            $l = substr(wa()->getUser()->getLocale(), 0, 2);
            if ($l == 'ru') {
                $values['rate_zone']['country'] = 'rus';
                $values['rate_zone']['region'] = '77';
                $values['city'] = '';
            } else {
                $values['rate_zone']['country'] = 'usa';
            }
        }

        $view = wa()->getView();

        $cm = new waCountryModel();
        $view->assign('countries', $cm->all());

        if (!empty($values['rate_zone']['country'])) {
            $rm = new waRegionModel();
            $view->assign('regions', $rm->getByCountry($values['rate_zone']['country']));
        }

        if (!empty($values['rate'])) {
            self::sortRates($values['rate']);
            if ($values['rate_by'] == 'price') {
                $values['rate'] = array_reverse($values['rate']);
            }
        } else {
            $values['rate'] = array();
            $values['rate'][] = array(
                'limit' => 0,
                'cost'  => 0.0,
            );
        }

        $app_config = wa()->getConfig();
        if (method_exists($app_config, 'getCurrencies')) {
            $view->assign('currencies', $app_config->getCurrencies());
        }

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
        $view->assign('namespace', $namespace);
        $view->assign('values', $values);
        $view->assign('p', $this);
        $html = '';

        $view->assign('xhr_url', wa()->getAppUrl('webasyst').'?module=backend&action=regions');

        $view->assign('map_adapters', wa()->getMapAdapters());

        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    /**
     * Sort rates per orderWeight
     * @param &array $rates
     * @return void
     */
    private static function sortRates(&$rates)
    {
        uasort($rates, array(__CLASS__, 'sortRatesHandler'));
    }

    private static function sortRatesHandler($a, $b)
    {
        $a = array_map("floatval", $a);
        $b = array_map("floatval", $b);
        $a = isset($a["limit"]) ? $a["limit"] : 0;
        $b = isset($b["limit"]) ? $b["limit"] : 0;
        return ($a > $b) ? 1 : ($a < $b ? -1 : 0);
    }

    private $holidays;
    private $workdays;
    private $time;

    private function setupSchedule()
    {
        if (empty($this->time)) {
            $this->holidays = array_filter(explode(';', $this->getSettings('extra_holidays.date')));
            $this->workdays = array_filter(explode(';', $this->getSettings('extra_workdays.date')));
            $this->time = time();
        }
    }

    private function getDeliveryTimes()
    {
        $this->setupSchedule();
        if ($this->delivery_time) {
            /** @var string $departure_datetime SQL DATETIME */
            $departure_datetime = $this->getPackageProperty('departure_datetime');
            /** @var  int $departure_timestamp */
            if ($departure_datetime) {
                $departure_timestamp = max(0, strtotime($departure_datetime) - $this->time);
            } else {
                $departure_timestamp = 0;
            }
            if ('exact_delivery_time' === $this->delivery_time) {
                $delivery_date = array(
                    $this->time + max(0, max(0, intval($this->exact_delivery_time)) * 3600) + $departure_timestamp,
                );
            } else {
                $delivery_date = array_map('strtotime', explode(',', $this->delivery_time, 2));
                foreach ($delivery_date as & $date) {
                    $date += $departure_timestamp;
                }
                unset($date);
                $delivery_date = array_unique($delivery_date);
            }

            $est_delivery = array();
            foreach ($delivery_date as $date) {
                $est_delivery[] = waDateTime::format('humandate', $date);
            }
            $est_delivery = implode(' — ', $est_delivery);

            if (count($delivery_date) == 1) {
                $delivery_date = reset($delivery_date);
            }
        } else {
            $delivery_date = null;
            $est_delivery = null;
        }


        return array(
            'timestamp' => $delivery_date,
            'estimate'  => $est_delivery,
        );
    }

    protected function calculate()
    {
        $prices = array();
        $price = null;
        $limit = $this->getPackageProperty($this->rate_by);
        $rates = $this->rate;
        if (!$rates) {
            $rates = array();
        }
        self::sortRates($rates);
        $rates = array_reverse($rates);
        foreach ($rates as $rate) {
            $rate['limit'] = floatval(preg_replace('@[^\d\.]+@', '', str_replace(',', '.', $rate['limit'])));
            if (($limit !== null)
                && ($price === null)
                && (
                    ($rate['limit'] < $limit)
                    || (($rate['limit'] == 0) && (floatval($limit) == 0))
                )
            ) {
                $price = $this->parseCost($rate['cost']);
            }
            $prices[] = $this->parseCost($rate['cost']);
        }

        if (($limit !== null) && ($price === null)) {
            return false;
        }

        $delivery_times = $this->getDeliveryTimes();

        $delivery = array(
            'est_delivery' => $delivery_times['estimate'],
            'currency'     => $this->currency,
            'rate'         => ($limit === null) ? ($prices ? array(min($prices), max($prices)) : null) : $price,
            'type'         => self::TYPE_TODOOR,
        );

        $services = array();

        $setting = $this->getSettings('customer_interval');

        if (!empty($setting['intervals'])) {

            $intervals = array();
            $date_format = waDateTime::getFormat('date');
            $offset = null;
            foreach ($setting['intervals'] as $interval) {
                $service_delivery_date = $this->workupInterval($interval, $delivery_times['timestamp']);

                if (!empty($service_delivery_date)) {
                    $key = $interval['interval'];
                    $intervals[$key] = array_keys($interval['day']);
                    $intervals[$key]['offset'] = $interval['offset'];
                    if (!isset($delivery['delivery_date'])
                        || (strtotime($delivery['delivery_date']) > strtotime($service_delivery_date))
                    ) {
                        $delivery['delivery_date'] = $service_delivery_date;
                    }

                    if (($offset === null) || ($offset > $interval['offset'])) {
                        $offset = $interval['offset'];
                    }
                }
            }
        }

        $delivery += array(
            'delivery_date' => self::formatDatetime($delivery_times['timestamp']),
        );

        if (!empty($setting['intervals'])) {
            $custom_data = array(
                'offset'      => $offset,
                'intervals'   => $intervals,
                'placeholder' => waDateTime::format($date_format, is_array($delivery['delivery_date']) ? reset($delivery['delivery_date']) : $delivery['delivery_date']),
                'holidays'    => '',
                'workdays'    => '',
            );

            $delivery += array(
                'custom_data' => array(
                    self::TYPE_TODOOR => $custom_data,
                ),
            );
        }

        $services['delivery'] = $delivery;

        return $services;
    }

    /**
     * @param $interval
     * @param $timestamp
     * @return false|string|null
     */
    private function workupInterval(&$interval, $timestamp)
    {
        $interval += array(
            'from_m' => '00',
            'to_m'   => '00',
            'day'    => array(),
            'offset' => 0,
        );

        $days = array_filter(array_map('intval', $interval['day']));
        unset($interval['day']);
        $interval = array_map('trim', $interval);

        $service_delivery_date = null;
        $interval['offset'] = 0;
        $start = $timestamp ? (is_array($timestamp) ? reset($timestamp) : $timestamp) : $this->time;

        $interval['from'] = sprintf('%02d:%02d', $interval['from'], $interval['from_m']);
        unset($interval['from_m']);
        $interval['to'] = sprintf('%02d:%02d', $interval['to'], $interval['to_m']);
        unset($interval['to_m']);

        $interval['interval'] = sprintf('%s-%s', $interval['from'], $interval['to']);

        do {
            $service_datetime = strtotime(sprintf('+%d days', $interval['offset']++), $start);
            $service_date = date('Y-m-d', $service_datetime);

            $week_day = date('N', $service_datetime) - 1;

            $is_holiday = in_array($service_date, $this->holidays, true);

            $is_extra_holiday = $is_holiday && !empty($days['holiday']);

            if (!$is_holiday || $is_extra_holiday) {

                $is_extra_workday = !empty($days['workday']) && in_array($service_date, $this->workdays, true);

                $is_workday = $is_extra_holiday || $is_extra_workday || !empty($days[$week_day]);

                if ($is_workday) {
                    $is_same_day = date('Y-m-d', $this->time) === $service_date;
                    if ($is_same_day) {
                        if ((int)date('H', $this->time) >= (int)$interval['to']) {
                            continue;
                        }
                    }
                    $service_delivery_date = $service_date;
                    $service_delivery_date .= sprintf(' %02d:00', $interval['from']);
                }
            }
            if ($interval['offset'] > 60) {
                break;
            }
        } while (empty($service_delivery_date));

        //  unset($days['holiday']);
        //  unset($days['workday']);

        $interval['day'] = $days;

        return $service_delivery_date;
    }

    public function allowedAddress()
    {
        $rate_zone = $this->rate_zone;
        $address = array();
        foreach ($rate_zone as $field => $value) {
            if (!empty($value)) {
                $address[$field] = strpos($value, ',') ? array_filter(array_map('trim', explode(',', $value)), 'strlen') : trim($value);
            }
        }
        return array($address);
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
        $addresses = $this->allowedAddress();

        $address = reset($addresses);

        if ($this->getSettings('required_fields')) {
            $fields = array();
            foreach ($address as $field => $value) {
                if (is_array($value)) {
                    $fields[$field] = array(
                        'required' => true,
                    );
                } else {
                    $fields[$field] = array(
                        'hidden' => true,
                        'value'  => $value,
                    );
                }
            }

            $value = array(
                'required' => true,
            );
            if ($this->contact_fields) {
                foreach ($this->contact_fields as $field => $enabled) {
                    if ($enabled) {
                        $fields += array($field => $value);
                    }

                }
            }
        } else {
            $fields = array(
                'country' => array('cost' => true, 'required' => true,),
                'region'  => array('cost' => true, 'required' => true,),
            );

            $contact_fields = $this->contact_fields;
            foreach (array('country', 'region', 'city', 'street', 'zip') as $field) {
                if (in_array($field, $contact_fields)) {
                    $fields += array(
                        $field => array(
                            'required' => true,
                        ),
                    );
                }
            }
        }

        uksort($fields, array($this, 'sortAddressFields'));
        return $fields;
    }

    private function sortAddressFields($a, $b)
    {
        $order = array(
            'country',
            'region',
            'city',
            'street',
        );
        $order = array_flip($order);
        return max(1, min(-1, ifset($order[$b], 0) - ifset($order[$a], 0)));
    }

    public function customFields(waOrder $order)
    {
        $fields = parent::customFields($order);

        $setting = $this->getSettings('customer_interval');

        if (!empty($setting['interval']) || !empty($setting['date'])) {
            $this->setupSchedule();
            if (!strlen($this->delivery_time)) {
                $from = time();
            } else {
                $from = strtotime(preg_replace('@,.+$@', '', $this->delivery_time));
            }
            $offset = max(0, round(($from - time()) / (24 * 3600)));
            $shipping_params = $order->shipping_params;
            $value = array();

            if (!empty($shipping_params['desired_delivery.interval'])) {
                $value['interval'] = $shipping_params['desired_delivery.interval'];
            }
            if (!empty($shipping_params['desired_delivery.date_str'])) {
                $value['date_str'] = $shipping_params['desired_delivery.date_str'];
            }
            if (!empty($shipping_params['desired_delivery.date'])) {
                $value['date'] = $shipping_params['desired_delivery.date'];
            }

            if (!empty($setting['intervals'])) {
                $delivery_times = $this->getDeliveryTimes();
                foreach ($setting['intervals'] as &$interval) {
                    $start = $this->workupInterval($interval, $delivery_times['timestamp']);
                    $interval['start_date'] = date('Y-m-d', strtotime($start));
                }
                unset($interval);
            }

            $params = array(
                'date'      => empty($setting['date']) ? null : ifempty($offset, 0),
                'interval'  => ifset($setting['interval']),
                'intervals' => ifset($setting['intervals']),
                'holidays'  => $this->holidays,
                'workdays'  => $this->workdays,
            );

            $fields['desired_delivery'] = array(
                'value'        => $value,
                'title'        => $this->_w('Preferred delivery time'),
                'control_type' => waHtmlControl::DATETIME,
                'params'       => $params,
            );
        }
        return $fields;
    }

    public function getPrintForms(waOrder $order = null)
    {
        return array(
            'delivery_list' => array(
                'name'        => _wd('shipping_courier', 'Packing list'),
                'description' => _wd('shipping_courier', 'Order summary for courier shipping'),
            ),
        );
    }

    /**
     * @param string  $id
     * @param waOrder $order
     * @param array   $params
     * @return string
     * @throws waException
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        if ($id == 'delivery_list') {
            $view = wa()->getView();
            $main_contact_info = array();
            foreach (array('email', 'phone',) as $f) {
                if (($v = $order->getContact()->get($f, 'top,html'))) {
                    $main_contact_info[] = array(
                        'id'    => $f,
                        'name'  => waContactFields::get($f)->getName(),
                        'value' => is_array($v) ? implode(', ', $v) : $v,
                    );
                }
            }

            $formatter = new waContactAddressSeveralLinesFormatter();
            $shipping_address = array();
            foreach (waContactFields::get('address')->getFields() as $k => $v) {
                if (isset($order->params['shipping_address.'.$k])) {
                    $shipping_address[$k] = $order->params['shipping_address.'.$k];
                }
            }

            $shipping_address_text = array();
            foreach (array('country_name', 'region_name', 'zip', 'city', 'street') as $k) {
                if (!empty($order->shipping_address[$k])) {
                    $value = $order->shipping_address[$k];
                    if (in_array($k, array('city'))) {
                        $value = ucfirst($value);
                    }
                    $shipping_address_text[] = $value;
                }
            }

            $shipping_address_text = implode(', ', $shipping_address_text);
            $map = '';
            if ($shipping_address_text) {
                $map_adapter = $this->getSettings('map');
                if (!$map_adapter) {
                    $map_adapter = 'google';
                }
                try {
                    $map = wa()->getMap($map_adapter)->getHTML($shipping_address_text, array(
                        'width'  => '100%',
                        'height' => '350pt',
                        'zoom'   => 16,
                    ));
                } catch (waException $e) {
                    $map = '';
                }
            }
            $view->assign('map', $map);

            $shipping_address = $formatter->format(array('data' => $shipping_address));
            $shipping_address = $shipping_address['value'];

            $view->assign(compact('shipping_address_text', 'shipping_address', 'main_contact_info', 'order', 'params'));
            $view->assign('p', $this);
            return $view->fetch($this->path.'/templates/form.html');
        } else {
            throw new waException('Print form not found', 404);
        }
    }

    private function parseCost($string)
    {
        $cost = 0.0;
        foreach (explode('+', $string, 2) as $chunk) {
            $chunk = str_replace(',', '.', trim($chunk));
            if (strpos($chunk, '%')) {
                $cost += round($this->getTotalPrice() * floatval($chunk) / 100.0, 2);
            } else {
                $cost += floatval($chunk);
            }
        }
        return $cost;
    }
}
