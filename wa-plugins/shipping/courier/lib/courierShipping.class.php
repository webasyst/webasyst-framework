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
    /** @var array - holidays from 'extra_holidays.date' settings */
    private $holidays;

    /** @var array - workdays from 'extra_workdays.date' settings */
    private $workdays;
    private $time;

    public function saveSettings($settings = array())
    {
        $fields = array_keys(array_filter(ifset($settings['rate_zone'], array())));
        $settings['contact_fields'] = array_merge(ifset($settings['contact_fields'], array_combine($fields, $fields)));

        if ($error = $this->validateAdditionalAddressFields($settings)) {
            throw new waException($error);
        }

        return parent::saveSettings($settings);
    }

    /**
     * @param array &$settings
     *   By ref, cause method delete 'empty' values
     * @return string $error
     *   If empty validate is OK
     * @throws waException
     */
    protected function validateAdditionalAddressFields(&$settings = array())
    {

        if (!isset($settings['additional_address_fields']) || !is_array($settings['additional_address_fields'])) {
            return '';
        }

        foreach ($settings['additional_address_fields'] as $add_field => $addr_field) {
            if (!$addr_field) {
                unset($settings['additional_address_fields'][$add_field]);
            }
        }

        $additional_address_field_ids = $this->getAddressAdditionalFieldIds();

        $address_subfields = self::getAddressSubfields();
        $address_oneline_string_subfields = self::extractOneLineFields($address_subfields['other']);

        foreach ($settings['additional_address_fields'] as $add_field => $addr_field) {
            if (!isset($address_oneline_string_subfields[$addr_field])) {
                $add_field_name = $additional_address_field_ids[$add_field];
                return sprintf($this->_w('You have disabled address field, selected for additional field %s, in system settings. Please either enable again the selected address field or select another field.'), $add_field_name);
            }
        }

        $assign_map = array();
        foreach ($settings['additional_address_fields'] as $add_field => $addr_field) {
            if (!empty($assign_map[$addr_field])) {
                $add_field_names = array(
                    sprintf($this->_w('“%s”'), $additional_address_field_ids[$add_field]),
                    sprintf($this->_w('“%s”'), $additional_address_field_ids[$assign_map[$addr_field]])
                );
                return sprintf(
                    $this->_w('Please select different address fields for additional fields %s.'),
                    join(', ', $add_field_names)
                );
            }
            $assign_map[$addr_field] = $add_field;
        }

        return '';
    }

    /**
     * @param array $params
     * @return string HTML
     * @throws SmartyException
     * @throws waDbException
     * @throws waException
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

        $address_subfields = self::getAddressSubfields();

        $view->assign('namespace', $namespace);
        $view->assign('values', $values);
        $view->assign('p', $this);
        $view->assign('xhr_url', wa()->getAppUrl('webasyst').'?module=backend&action=regions');
        $view->assign('map_adapters', wa()->getMapAdapters());
        $view->assign('address_subfields', $address_subfields);
        $view->assign('address_oneline_string_subfields', self::extractOneLineFields($address_subfields['other']));
        $view->assign('webasyst_app_url', wa()->getAppUrl('webasyst') . 'webasyst/');
        $view->assign('additional_address_field_ids', $this->getAddressAdditionalFieldIds());

        $js = file_get_contents($this->path.'/js/settings.js');
        $js_code = sprintf('<script type="text/javascript">%s</script>', $js);

        $view->assign('js_code', $js_code);

        $html = '';
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);

        return $html;
    }

    /**
     * @param waContactField[] $fields
     * @return waContactField[]
     */
    private static function extractOneLineFields($fields)
    {
        $result = array();
        foreach ($fields as $index => $field) {
            if (self::isOneLineField($field)) {
                $result[$index] = $field;
            }
        }
        return $result;
    }

    /**
     * @param waContactField $field
     * @return bool
     */
    private static function isOneLineField($field)
    {
        return $field->getType() === 'Number' || ($field->getType() === 'String' && $field->getParameter('input_height') == 1);
    }

    /**
     * @param bool $grouped
     * @return array|waContactField
     * @throws waException
     */
    private static function getAddressSubfields($grouped = true)
    {
        $address = waContactFields::get('address');
        $fields = $address->getFields();

        if (!$grouped) {
            return $fields;
        }

        $main_address_subfield_ids = array(
            'country', 'region', 'city', 'street', 'zip'
        );

        $all_fields = array(
            'main' => array(),
            'other' => array()
        );

        // fill main sub-array
        foreach ($main_address_subfield_ids as $address_subfield_id) {
            if (isset($fields[$address_subfield_id])) {
                $all_fields['main'][$address_subfield_id] = $fields[$address_subfield_id];
            }
        }

        /**
         * fill other sub-array
         * @var waContactField[] $fields
         */
        foreach ($fields as $field_id => $field) {
            $is_disabled = $field->getParameter('_disabled');
            if (!isset($all_fields['main'][$field_id]) && !$is_disabled) {
                $all_fields['other'][$field_id] = $field;
            }
        }

        return $all_fields;

    }

    private function getAddressAdditionalFieldIds()
    {
        return [
            'entrance'      => $this->_w('Entrance'),
            'intercom_code' => $this->_w('Intercom number'),
            'floor'         => $this->_w('Floor')
        ];
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

    /**
     * Извлекаем дополнительные выходные и праздничные дни для курьера
     */
    private function setupSchedule()
    {
        if (empty($this->time)) {
            $this->holidays = array_filter(explode(';', $this->getSettings('extra_holidays.date')));
            $this->workdays = array_filter(explode(';', $this->getSettings('extra_workdays.date')));
            $this->time = time();
        }
    }

    /**
     * Calculate delivery timestamp (int) or timestamps interval (int[])
     * Получение даты/интервала дат в timestamp первой возможной доставки курьером
     *
     * @return array|int|int[]|null
     */
    private function getDeliveryTimes()
    {
        $this->setupSchedule();

        if (!$this->delivery_time) {
            /** Случай если в конфигурации плагина
             * "Время доставки" выставлено в "Не определено" */
            return null;
        }

        /**
         * Дата и время первой возможной доставки
         * (с учетом времени на "обработку магазином" и "доп. времени на комплектацию" заказа)
         * @var string $departure_datetime SQL DATETIME
         */
        $departure_datetime = $this->getPackageProperty('departure_datetime');

        /**
         * @var  int $departure_timestamp
         */
        if ($departure_datetime) {
            $departure_timestamp = max(0, strtotime($departure_datetime) - $this->time);
        } else {
            $departure_timestamp = 0;
        }

        /** Учитываем "время доставки" из настроек курьера */
        if ('exact_delivery_time' === $this->delivery_time) {
            /** если указано "Прибавить указанное количество часов ко времени готовности заказа" */
            $delivery_date = array(
                $this->time + max(0, max(0, intval($this->exact_delivery_time)) * 3600) + $departure_timestamp,
            );
        } else {
            $delivery_date = array_map('strtotime', explode(',', $this->delivery_time, 2));
            foreach ($delivery_date as &$date) {
                $date += $departure_timestamp;
            }
            unset($date);
            $delivery_date = array_unique($delivery_date);
        }

        if (count($delivery_date) == 1) {
            $delivery_date = reset($delivery_date);
        }

        /**
         * Итого в $delivery_date учитывается
         * + Количество рабочих часов на обработку заказа (в меню "Режим работы")
         * + Дополнительное время на комплектацию ("Способы доставки" в меню "Доставка")
         * + Время доставки ("Способы доставки" в меню "Доставка")
         */
        return $delivery_date;
    }

    protected function calculate()
    {
        $prices = array();
        $price = null;
        $limit = $this->getPackageProperty($this->rate_by);

        /** @var $rates array|null массив с тарифами из "Расчета стоимости доставки" курьера */
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
                /** @var $price float стоимость доставки по тарифу */
                $price = $this->parseCost($rate['cost']);
            }
            $prices[] = $this->parseCost($rate['cost']);
        }

        if (($limit !== null) && ($price === null)) {
            /** доставка считается бесплатной если не указан ни один тариф */
            return false;
        }

        $delivery_times = $this->getDeliveryTimes();

        $delivery = array(
            'est_delivery' => '',
            'currency'     => $this->currency,
            'rate'         => ($limit === null) ? ($prices ? array(min($prices), max($prices)) : null) : $price,
            'type'         => self::TYPE_TODOOR,
        );

        $services = array();

        /** @var array $setting "Интервалы доставки" из настроек курьера */
        $setting = $this->getSettings('customer_interval');

        if (!empty($setting['intervals'])) {

            $intervals = array();
            $date_format = waDateTime::getFormat('date');
            $offset = null;
            foreach ($setting['intervals'] as $interval) {
                $service_delivery_date = $this->workupInterval($interval, $delivery_times);

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
            'delivery_date' => self::formatDatetime($delivery_times),
        );

        $delivery['est_delivery'] = $this->formatEstDeliveryDate($delivery['delivery_date']);

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

        /**
         * $delivery [
         *      'est_delivery', ()
         *      'rate',         (в корзине определяется как "Стоимость доставки" текущим курьером, или в списке "Варианты доставки")
         *      'delivery_date',(в корзине определяется как "Срок доставки")
         *      'custom_data',  []
         * ]
         */
        $services['delivery'] = $delivery;

        return $services;
    }

    /**
     * @param int|int[]|string|string[] $delivery_date - delivery date or delivery date interval.
     *      Possible values: timestamp (int),
     *                      timestamps interval(int[]),
     *                      formatted date (string),
     *                      formatted dates interval (string[])
     * @return string - 'humandate' date or 'humandate' dates interval
     * @throws waException
     */
    protected function formatEstDeliveryDate($delivery_date)
    {
        if (!$delivery_date) {
            return '';
        }

        if (!is_array($delivery_date)) {
            $delivery_date = [$delivery_date];
        }

        $est_delivery = [];
        foreach ($delivery_date as $date) {
            $est_delivery[] = waDateTime::format('humandate', $date);
        }
        $est_delivery = implode(' — ', $est_delivery);

        return $est_delivery;
    }

    /**
     * Получение даты и времени доставки курьером
     * начиная с $timestamp для промежутка $interval
     *
     * @param $interval
     * @param int $timestamp дата с учетом всех 3-х временных надбавок влияющих на срок доставки
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
        $start = $timestamp ? (is_array($timestamp) ? reset($timestamp) : $timestamp) : $this->time;

        /** Формируем строковое представление интервала доставки */
        $interval_from = sprintf('%02d:%02d', $interval['from'], $interval['from_m']);
        $interval_to = sprintf('%02d:%02d', $interval['to'], $interval['to_m']);
        $interval['interval'] = sprintf('%s-%s', $interval_from, $interval_to);

        // safety loop limiter
        $limit = 60;

        // loop day by day while not found free day to delivery OR while not overstep safety loop limiter $limit
        while (empty($service_delivery_date)) {

            if ($interval['offset'] >= $limit) {
                break;
            }

            $service_datetime = strtotime(sprintf('+%d days', $interval['offset']++), $start);
            $service_date = date('Y-m-d', $service_datetime);

            $week_day = date('N', $service_datetime) - 1;

            // is extra holiday on current $service_date?
            $is_extra_holiday = false;
            $is_extra_holidays_enabled = !empty($days['holiday']);
            if ($is_extra_holidays_enabled) {
                $is_extra_holiday = in_array($service_date, $this->holidays, true);
            }

            // Extra holiday day has maximum priority.
            // If current $service_date is holiday it is not delivery day for sure
            if ($is_extra_holiday) {
                continue;
            }

            // is extra workday on current $service_date?
            $is_extra_workday = false;
            $is_extra_workday_enabled = !empty($days['workday']);
            if ($is_extra_workday_enabled) {
                $is_extra_workday = in_array($service_date, $this->workdays, true);
            }

            $is_workday = $is_extra_workday || !empty($days[$week_day]);

            if ($is_workday) {
                $is_same_day = date('Y-m-d', $this->time) === $service_date;
                if ($is_same_day) {
                    /** если доставка возможна в день заказа, то сравниваем текущий час с
                     *  часом из интервала и если уже поздно, то переходим ко следующему дню */
                    if ((int)date('H', $this->time) >= (int)$interval_to) {
                        continue;
                    }
                }
                $service_delivery_date = $service_date;
                $service_delivery_date .= ' ' . $interval_from;
            }
        }

        $interval['day'] = $days;

        return $service_delivery_date;
    }

    /**
     * Получает доступные для доставки населенные пункты
     * указанные в настройках курьера
     *
     * @return array|array[]
     */
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

    /**
     * Получение полей адреса доставки,
     * которые требуются для заполнения
     * в корзине покупателя
     *
     * @return array
     */
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

        if ($this->getSettings('additional_address_fields') && is_array($this->getSettings('additional_address_fields'))) {
            $additional_address_fields = $this->getSettings('additional_address_fields');
            foreach ($additional_address_fields as $additional_field_id) {
                $fields[$additional_field_id] = array();
            }
        }

        $fields = $this->sortAddressFields($fields);
        
        return $fields;
    }

    protected function sortAddressFields($fields)
    {
        $order = array(
            'country',
            'region',
            'city',
            'street',
            'zip',
        );

        // mix-in additional fields into $order map
        $additional_address_fields = $this->getSettings('additional_address_fields');
        if (!is_array($additional_address_fields)) {
            $additional_address_fields = array();
        }

        $address_subfields = self::getAddressSubfields();
        $address_oneline_string_subfields = self::extractOneLineFields($address_subfields['other']);

        foreach ($additional_address_fields as $add_field => $addr_field) {
            if ($addr_field && isset($address_oneline_string_subfields[$addr_field])) {
                $order[] = $addr_field;
            }
        }

        return $this->ksortAsSuggested($fields, $order);
    }

    /**
     * uksort and etc is not stable, so it is bummer
     * This method has stability property
     * Key that are not found in order array will be set in the end of result array with saving original sorting (stability)
     * @param $array
     * @param array $order
     * @return array
     */
    function ksortAsSuggested($array, $order = array())
    {
        $result = array();
        foreach ($order as $key) {
            if (isset($array[$key])) {
                $result[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        foreach ($array as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    public function customFields(waOrder $order)
    {
        $fields = parent::customFields($order);
        $errors = array();
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
                $date = DateTime::createFromFormat('Y-m-d', $value['date']);
                $date_errors = DateTime::getLastErrors();
                if ($date === false || $date_errors['warning_count'] + $date_errors['error_count'] > 0) {
                    $errors['desired_delivery.date'] = _w('Invalid date');
                }
            }

            if (!empty($setting['intervals'])) {
                $delivery_times = $this->getDeliveryTimes();
                foreach ($setting['intervals'] as &$interval) {
                    $start = $this->workupInterval($interval, $delivery_times);
                    $interval['start_date'] = date('Y-m-d', strtotime($start));
                }
                unset($interval);
            }

            $params = array(
                'date'         => empty($setting['date']) ? null : ifempty($offset, 0),
                'interval'     => ifset($setting['interval']),
                'intervals'    => ifset($setting['intervals']),
                'holidays'     => $this->holidays,
                'workdays'     => $this->workdays,
                'autocomplete' => false,
            );

            $fields['desired_delivery'] = array(
                'value'        => $value,
                'title'        => $this->_w('Preferred delivery time'),
                'control_type' => waHtmlControl::DATETIME,
                'params'       => $params,
                'errors'       => $errors
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
