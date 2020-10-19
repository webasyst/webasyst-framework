<?php

/**
 * @property string $currency
 * @property string $processing
 * @property string $latitude
 * @property string $longitude
 * @property string $photos
 * @property string $storage_days
 * @property string $additional
 * @property string service
 * @property array payment_type
 *
 * //Weight
 * @property string $weight_unit
 * @property string $max_weight
 * @property string $markup_weight
 *
 * //Size
 * @property string $length_unit
 * @property string $markup_length
 * @property string $markup_width
 * @property string $markup_height
 * @property string $max_length
 * @property string $max_width
 * @property string $max_height
 *
 * //Cost
 * @property string $free_shipping
 * @property string $basic_shipping
 * @property string $markup_weight_price
 * @property string markup_size_price
 *
 * //Address
 * @property string $address
 * @property string $country
 * @property string $region
 * @property string $city
 * @property string $way
 *
 * //Time
 * @property string $timezone
 * @property array $workdays
 * @property array $weekend
 * @property array $weekdays
 *
 */
class sdShipping extends waShipping
{
    ###################
    # CALCULATE BLOCK #
    ###################

    protected $days = array();
    protected $est_delivery = null;

    /**
     * @return array|string
     * @throws waException
     */
    protected function calculate()
    {
        if (!$this->isValidWeight()) {
            return $this->_w('Weight values above the limit.');
        }
        if (!$this->isValidSize()) {
            return $this->_w('Size values above the limit.');
        }

        $timestamp = $this->getShippingCompleteDate();

        $est_delivery = waDateTime::format('humandate', $timestamp['pickup']);
        $schedule = $this->getSchedule($timestamp['pickup']);

        $result = array(
            array(
                'rate'          => $this->getShippingRate(),
                'est_delivery'  => $est_delivery,
                'delivery_date' => self::formatDatetime($timestamp['server']),
                'service'       => $this->service,
                'currency'      => $this->currency,
                'type'          => self::TYPE_PICKUP,
                'custom_data'   => array(
                    self::TYPE_PICKUP => array(
                        'id'          => $this->id,
                        'timezone'    => $this->timezone,
                        'lat'         => $this->latitude,
                        'lng'         => $this->longitude,
                        'schedule'    => $schedule,
                        'photos'      => $this->photos,
                        'way'         => $this->way,
                        'additional'  => $this->additional,
                        'description' => $this->address,
                        'storage'     => $this->getStorageInfo(),
                        'payment'     => $this->getPayment(),
                    )
                ),
            )
        );

        return $result;
    }

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function allowedWeightUnit()
    {
        return $this->weight_unit;
    }

    public function allowedLinearUnit()
    {
        return $this->length_unit;
    }

    public function allowedAddress()
    {
        $address = array();
        $country = $this->country;
        $region = $this->region;
        $city = $this->getCity();

        if ($country) {
            $address['country'] = $country;
        }

        if ($region) {
            $address['region'] = $region;
        }

        if ($city) {
            $address['city'] = count($city) == 1 ? $city[0] : $city;
        }

        return array($address);
    }

    public function requestedAddressFields()
    {
        $value = array('cost' => true, 'required' => true);

        $fields = array(
            'country' => $value
        );

        if ($this->region) {
            $fields['region'] = $value;
        }

        if ($this->getCity()) {
            $fields['city'] = $value;
        }

        return $fields;
    }

    /**
     * Check if the weight is exceeded.
     * @return bool
     */
    protected function isValidWeight()
    {
        $weight = (float)$this->getTotalWeight();
        $saved_weight = (float)$this->max_weight;

        if ($saved_weight > 0 && $weight > $saved_weight) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks one item for oversize
     *
     * @return bool
     */
    protected function isValidSize()
    {
        $items = $this->getItems();
        $total_size = $this->getTotalSize();

        $max_l = $this->max_length;
        $max_w = $this->max_width;
        $max_h = $this->max_height;

        $is_valid = true;

        if ($total_size === null) {
            foreach ($items as $item) {
                $item_l = ifset($item, 'length', 0);
                $item_w = ifset($item, 'width', 0);
                $item_h = ifset($item, 'height', 0);

                if (($max_l && $max_l < $item_l) || ($max_w && $max_w < $item_w) || ($max_h && $max_h < $item_h)) {
                    $is_valid = false;
                    break;
                }
            }
        } elseif (is_array($total_size)) {
            if (($max_l && $max_l < $total_size['length']) || ($max_w && $max_w < $total_size['width']) || ($max_h && $max_h < $total_size['height'])) {
                $is_valid = false;
            }
        } else {
            $is_valid = false;
        }

        return $is_valid;
    }

    /**
     * @return int|float
     */
    protected function getShippingRate()
    {
        $total_price = (float)$this->getTotalPrice();
        $free_shipping = (float)$this->free_shipping;
        $rate = (float)$this->basic_shipping;

        $markup = $this->getMarkup();
        $rate += $markup;

        if ($free_shipping && $total_price >= $free_shipping) {
            $rate = 0;
        }

        return round($rate, 4) + 0;

    }

    /**
     * @return int
     */
    protected function getMarkup()
    {
        $total_weight = $this->getTotalWeight();
        $markup = 0;

        if ((float)$total_weight > (float)$this->markup_weight) {
            $markup = (float)$this->markup_weight_price;
        }

        $markup += $this->getMarkupPrice();


        return $markup;
    }

    /**
     * Get markup for oversizing
     * Primarily uses data from other plugins.
     *
     * @return int
     */
    protected function getMarkupPrice()
    {
        $markup_price = (float)$this->markup_size_price;
        $markup_l = $this->markup_length;
        $markup_w = $this->markup_width;
        $markup_h = $this->markup_height;
        $markup = 0;

        $items = $this->getItems();
        $total_size = $this->getTotalSize();

        if ($total_size === null) {
            foreach ($items as $item) {
                $item_l = ifset($item, 'length', 0);
                $item_w = ifset($item, 'width', 0);
                $item_h = ifset($item, 'height', 0);

                if (($markup_l && $markup_l < $item_l) || ($markup_w && $markup_w < $item_w) || ($markup_h && $markup_h < $item_h)) {
                    $markup = $markup_price;
                    break;
                }
            }
        } elseif ($total_size) {
            if (($markup_l && $markup_l < $total_size['length']) || ($markup_w && $markup_w < $total_size['width']) || ($markup_h && $markup_h < $total_size['height'])) {
                $markup = $markup_price;
            }
        }

        return $markup;
    }


    /**
     * Get the next day in the time zone of the point when you can pick up the package.
     * Time converted to plug-in time zone
     * @return array
     * @throws Exception
     */
    protected function getShippingCompleteDate()
    {
        $time_with_processing = $this->getTimeWithProcessing();

        //convert to pickup timezone
        $datetime = $this->changeTimezone('U', $time_with_processing, date_default_timezone_get(), $this->timezone);
        $date = $this->changeTimezone('U', date('Y-m-d', $datetime), $this->timezone);

        for ($i = 0; $i < 365; $i++) {
            //Get day type for time
            $type = $this->getDayType($date);
            $end_process = $this->getEndProcessTime($type, $date);
            $start_work = $this->setStartWorkTime($type, $date);

            //Time to create an order should be no earlier than the start of work pickup point.
            //This is required to correctly display the time when the order can be picked up.
            if ($start_work >= $datetime) {
                $datetime = $start_work;
            }

            //The order must be created before the end of processing time. Otherwise, go the next day.
            if ($end_process && $end_process > $datetime) {
                break;
            }

            $date = strtotime('+1 day', $date);
        }

        return array(
            'server' => $this->changeTimezone('U', $datetime, $this->timezone, date_default_timezone_get()),
            'pickup' => $date,
        );
    }

    /**
     * @param $type
     * @param $date
     * @return bool|int
     */
    protected function getEndProcessTime($type, $date)
    {
        $end_process = false;

        if ($type === 'extra_workday') {
            $end_process = ifset($this->days, 'workdays', $date, 'end_process', false);
        } elseif ($type === 'workday') {
            $day_name_code = date('N', $date);
            $end_process = ifset($this->days, 'weekdays', $day_name_code, 'end_process', false);

            if ($end_process && is_numeric($end_process)) {
                $end_process = $date + $end_process;
            }
        }

        return $end_process;
    }

    /**
     * Get start work time
     * @param $type
     * @param $date
     * @return int
     */
    protected function setStartWorkTime($type, $date)
    {
        if ($type === 'extra_workday') {
            $date = ifset($this->days, 'workdays', $date, 'start_work', $date);
        } elseif ($type === 'workday') {
            $day_name_code = date('N', $date);
            $date = $date + ifset($this->days, 'weekdays', $day_name_code, 'start_work', 0);
        }

        return $date;
    }

    /**
     * Add order processing time to estimated system time.
     * @return int
     */
    protected function getTimeWithProcessing()
    {
        $departure_datetime = $this->getPackageProperty('departure_datetime');
        $processing = (float)$this->processing;
        $processing = round($processing * 3600);

        $time = strtotime($departure_datetime) + $processing;
        return (int)$time;
    }

    /**
     * Check day type.
     * @param $date
     * @return bool|string If weekend return false
     */
    protected function getDayType($date)
    {
        $type = false;

        if ($this->isExtraWorkday($date)) {
            $type = 'extra_workday';
        } elseif ($this->isExtraWeekend($date)) {
            $type = 'extra_weekend';
        } elseif ($this->isWorkday($date)) {
            $type = 'workday';
        } elseif ($this->isWeekend($date)) {
            $type = 'weekend';
        }

        return $type;
    }

    /**
     * Check if it is a extra working day.
     * @param $time
     * @return bool
     */
    protected function isExtraWorkday($time)
    {
        $workdays = $this->getExtraWorkdays();

        if ($workdays && isset($workdays[$time])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if it time is a extra weekend day.
     * @param $date
     * @return bool
     */
    protected function isExtraWeekend($date)
    {
        $weekend = $this->getExtraWeekend();

        if ($weekend && isset($weekend[$date])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if it time is a working day.
     * @param $date
     * @return bool
     */
    protected function isWorkday($date)
    {
        $weekdays = $this->getSavedWeekdays();
        $day_name_code = date('N', $date);

        $weekday = ifset($weekdays, $day_name_code, false);

        if ($weekday && $weekday['works']) {
            return true;
        } else {
            return false;
        }
    }

    protected function isWeekend($date)
    {
        $weekdays = $this->getSavedWeekdays();
        $day_name_code = date('N', $date);

        $weekday = ifset($weekdays, $day_name_code, false);

        if ($weekday && !$weekday['works']) {
            return true;
        } else {
            return false;
        }
    }

    protected function hoursToSecond($time)
    {
        if ($time) {
            $times = explode(':', $time);

            $hours = ifset($times, 0, 0);
            $minutes = ifset($times, 1, 0);

            $time = ($hours * 3600) + ($minutes * 60);
        }

        return $time;
    }

    protected function getSavedWeekdays()
    {
        $weekdays = ifset($this->days, 'weekdays', null);

        if ($weekdays === null) {
            $weekdays = array();
            $raw_weekdays = $this->weekdays;

            if ($raw_weekdays) {
                foreach ($raw_weekdays as $day_id => $weekday) {
                    $weekdays[$day_id] = array(
                        'works'       => ifset($weekday, 'works', '0'),
                        'start_work'  => $this->hoursToSecond(ifset($weekday, 'start_work', '10:00')),
                        'end_work'    => $this->hoursToSecond(ifset($weekday, 'end_work', '19:00')),
                        'end_process' => $this->hoursToSecond(ifset($weekday, 'end_process', '14:00')),
                        'additional'  => ifset($weekday, 'additional', ''),
                    );
                }
            }

            $this->days['weekdays'] = $weekdays;
        }

        return $weekdays;
    }

    /**
     * Get saved extra workdays formatted to timestamp
     * @return array
     */
    protected function getExtraWorkdays()
    {
        $extra_workdays = ifset($this->days, 'workdays', null);

        if ($extra_workdays === null) {
            $extra_workdays = array();
            $workdays = $this->workdays;

            if (is_array($workdays)) {
                foreach ($workdays as $workday) {

                    $date_timestamp = strtotime($workday['date']);
                    $extra_workdays[$date_timestamp] = array(
                        'start_work'  => strtotime($workday['date'].' '.$workday['start_work']),
                        'end_work'    => strtotime($workday['date'].' '.$workday['end_work']),
                        'end_process' => strtotime($workday['date'].' '.ifset($weekday, 'end_process', '14:00')),
                        'additional'  => ifset($workday, 'additional', ''),
                    );
                }
            }

            $this->days['workdays'] = $extra_workdays;
        }

        return $extra_workdays;
    }

    /**
     * Get saved extra weekend formatted to timestamp
     * @return array
     */
    protected function getExtraWeekend()
    {
        $extra_weekend = ifset($this->days, 'weekend', null);

        if ($extra_weekend === null) {
            $extra_weekend = array();
            $weekend = $this->weekend;

            if (is_array($weekend)) {
                foreach ($weekend as $day) {
                    $date_timestamp = strtotime($day['date']);

                    $extra_weekend[$date_timestamp] = array(
                        'additional' => ifset($day, 'additional', ''),
                    );
                }
            }

            $this->days['weekend'] = $extra_weekend;
        }

        return $extra_weekend;
    }

    /**
     * Check the mode of operation after the day of delivery
     * @param int first_day timestamp
     * @return array timezone and days list.
     * @throws Exception
     */
    protected function getSchedule($first_day)
    {
        $date = $this->changeTimezone('U', date('Y-m-d', $first_day), $this->timezone);

        $result = array();

        for ($i = 0; $i <= 6; $i++) {
            $type = $this->getDayType($date);

            $day_info = array(
                'type'       => 'weekend',
                'start_work' => date('Y-m-d H:i', $date),
                'end_work'   => date('Y-m-d H:i', $date),
                'additional' => '',
            );

            switch ($type) {
                case 'extra_workday':
                    $extra_workday = $this->days['workdays'][$date];
                    $day_info = array(
                        'type'       => 'workday',
                        'start_work' => date('Y-m-d H:i', $extra_workday['start_work']),
                        'end_work'   => date('Y-m-d H:i', $extra_workday['end_work']),
                        'additional' => $extra_workday['additional'],

                    );
                    break;
                case 'workday':
                    $day_name_code = date('N', $date);
                    $day = $this->days['weekdays'][$day_name_code];

                    $day_info = array(
                        'type'       => 'workday',
                        'start_work' => date('Y-m-d H:i', $date + $day['start_work']),
                        'end_work'   => date('Y-m-d H:i', $date + $day['end_work']),
                        'additional' => $day['additional'],

                    );
                    break;
                case 'extra_weekend':
                    $day_info['additional'] = $this->days['weekend'][$date]['additional'];
                    break;
                case 'weekend':
                    $day_name_code = date('N', $date);

                    $day_info['additional'] = $this->days['weekdays'][$day_name_code]['additional'];
                    break;
            }

            $result['weekdays'][$i] = $day_info;

            //add day
            $date = strtotime('+ 1 day', $date);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getStorageInfo()
    {
        $info = array(
            'storage_days' => $this->storage_days,
        );

        return $info;
    }

    //Get save Settings

    /**
     * @return array
     */
    protected function getCity()
    {
        $cities = $this->getSettings('city');
        $result = array();

        if ($cities && is_string($cities)) {
            $cities = explode(',', $cities);
            foreach ($cities as $city) {
                $result[] = trim(mb_strtolower($city));
            }
        } else {
            $result = $cities;
        }

        return $result;
    }


    ##################
    # SETTINGS BLOCK #
    ##################

    /**
     * @param array $params
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public function getSettingsHTML($params = array())
    {
        $view = wa()->getView();

        $settings = $this->getSettings();
        $countries = $this->getCountries();
        $saved_country = ifset($settings, 'country', null);

        if (!$saved_country) {
            $saved_country = ifset($countries, 0, 'iso3letter', false);
        }

        $view->assign(array(
            'obj'          => $this,
            'payment_type' => $this->getPaymentTypeSettings(),
            'currencies'   => $this->getCurrencies(),
            'countries'    => $countries,
            'regions'      => $this->getRegions($saved_country),
            'weight_units' => $this->getWeightUnits(),
            'length_units' => $this->getAdapter()->getAvailableLinearUnits(),
            'namespace'    => waHtmlControl::makeNamespace($params),
            'regions_url'  => wa()->getAppUrl('webasyst').'?module=backend&action=regions', //request to webasyst app
            'settings'     => $settings,
            'weekdays'     => $this->getWeekdays($settings),
            'timezones'    => waDateTime::getTimeZones(),
        ));

        $html = '';
        $html .= $view->fetch($this->path.'/templates/settings.html');
        $html .= parent::getSettingsHTML($params);
        return $html;
    }

    /**
     * @return array
     */
    public function getPaymentTypeSettings()
    {
        return array(
            array(
                'value' => self::PAYMENT_TYPE_CASH,
                'title' => $this->_w('cash on receipt'),
            ),
            array(
                'value' => self::PAYMENT_TYPE_CARD,
                'title' => $this->_w('card on receipt'),
            ),
            array(
                'value' => self::PAYMENT_TYPE_PREPAID,
                'title' => $this->_w('prepayment'),
            ),
        );
    }

    /**
     * @return array
     * @throws waException
     */
    protected function getCurrencies()
    {
        $app_config = wa()->getConfig();
        $currencies = array();
        if (method_exists($app_config, 'getCurrencies')) {
            $currencies = $app_config->getCurrencies();
        }

        return $currencies;
    }

    protected function getWeightUnits()
    {
        return array(
            'kg'  => $this->_w('kg'),
            'lbs' => $this->_w('lbs'),
        );
    }

    protected function getCountries()
    {
        $cm = new waCountryModel();
        $countries = $cm->allWithFav();

        return $countries;
    }

    protected function getRegions($country)
    {
        $rm = new waRegionModel();
        $regions = $rm->getByCountry($country);

        return $regions;
    }

    protected function getWeekdays($settings = array())
    {
        $saved_weekdays = ifset($settings, 'weekdays', null);
        $result = array();

        $weekdays = waDateTime::getWeekdayNames();

        $output = array(
            5 => '0',
            6 => '0',
        );

        foreach ($weekdays as $id => $day) {
            if ($saved_weekdays !== null) {
                $result[$id] = array(
                    'name'        => $day,
                    'works'       => ifset($saved_weekdays, $id, 'works', '0'),
                    'start_work'  => ifset($saved_weekdays, $id, 'start_work', '10:00'),
                    'end_work'    => ifset($saved_weekdays, $id, 'end_work', '19:00'),
                    'end_process' => ifset($saved_weekdays, $id, 'end_process', '14:00'),
                    'additional'  => ifset($saved_weekdays, $id, 'additional', ''),
                );
            } else {
                $result[$id] = array(
                    'name'        => $day,
                    'works'       => ifset($output, $id, '1'),
                    'start_work'  => '10:00',
                    'end_work'    => '19:00',
                    'end_process' => '14:00',
                    'additional'  => '',
                );
            }
        }

        return $result;
    }

    #######################
    # SAVE SETTINGS BLOCK #
    #######################
    public function saveSettings($saved_settings = array())
    {
        $saved_settings = $this->parseSettings($saved_settings);

        return parent::saveSettings($saved_settings);
    }

    /**
     * @param $saved_settings
     * @return mixed
     * @throws waException
     */
    protected function parseSettings($saved_settings)
    {
        $required_fields = $this->getRequiredFields();

        //VALIDATE REQUIRED
        foreach ($saved_settings as $settings_key => $setting) {
            if (isset($required_fields[$settings_key]) && ($setting === false || $setting === '')) {
                throw new waException($this->_w('Fill in the required field.'));
            }
        }

        $saved_settings['weekdays'] = $this->parseDays('weekdays', $saved_settings);
        $saved_settings['workdays'] = $this->parseDays('workdays', $saved_settings);
        $saved_settings['weekend'] = $this->parseDays('weekend', $saved_settings);

        //VALIDATE WORKING DAY
        if (!$saved_settings['weekdays'] && !$saved_settings['workdays']) {
            throw new waException($this->_w('At least one working day is required.'));
        }

        //validate photos
        if (empty($saved_settings['photos'])) {
            $saved_settings['photos'] = null;
        }

        return $saved_settings;
    }

    /**
     * @param $key
     * @param $settings
     * @return mixed|null
     * @throws waException
     */
    protected function parseDays($key, $settings)
    {
        $days = ifset($settings, $key, array());

        foreach ($days as $id => $day) {
            if ($key === 'workdays' && empty($day['date'])) {
                unset($days[$id]);
                continue;
            }

            if ($key === 'weekdays' && empty($day['works'])) {
                $days[$id]['works'] = '0';
            }

            if (isset($day['date'])) {
                $days[$id]['date'] = $this->parseDayFormat($day['date']);
            }
            if (isset($day['start_work'])) {
                $this->isValidateTimeFormat($day['start_work']);
            }

            if (isset($day['end_work'])) {
                $this->isValidateTimeFormat($day['end_work']);
            }
            if (isset($day['end_process'])) {
                $this->isValidateTimeFormat($day['end_process']);
            }
        }

        return $days;
    }

    /**
     * @param $date
     * @return string
     * @throws waException
     */
    protected function parseDayFormat($date)
    {
        $date = trim($date);
        $new_date = waDateTime::parse('date', $date, null, 'ru_RU');

        if (!$new_date) {
            $new_date = waDateTime::parse('date', $date, null, 'en_US');

            if (!$new_date) {
                throw new waException($this->_w('Invalid date'));
            }
        }

        return $new_date;
    }

    /**
     * @param $time
     * @return bool
     * @throws waException
     */
    protected function isValidateTimeFormat($time)
    {
        if ($time && !preg_match('/(^[01]?[0-9]|2[0-3])($|:([0-5][0-9]$))/ui', $time)) {
            throw new waException($this->_w('Invalid time'));
        } else {
            return true;
        }
    }

    protected function getRequiredFields()
    {
        return array(
            'country'        => true,
            'basic_shipping' => true,
        );
    }

    /**
     * @param $format
     * @param $time
     * @param $from
     * @param null $to
     * @return false|int|string
     * @throws Exception
     */
    protected function changeTimezone($format, $time, $from, $to = null)
    {
        if (is_numeric($time)) {
            $time = date('Y-m-d H:i:s', $time);
        }

        $date_time = new DateTime($time, new DateTimeZone($from));

        if ($to) {
            $date_time->setTimezone(new DateTimeZone($to));
        }

        if ($format === 'U') {
            return strtotime($date_time->format('Y-m-d H:i:s'));
        } else {
            return $date_time->format($format);
        }
    }

    /**
     * Returns settings for filtering payment types
     * @return array
     */
    protected function getPayment()
    {
        $saved_payment = $this->payment_type;
        $result = [];
        if (in_array(self::PAYMENT_TYPE_PREPAID, $saved_payment)) {
            $result[self::PAYMENT_TYPE_PREPAID] = true;
        }

        if (in_array(self::PAYMENT_TYPE_CARD, $saved_payment)) {
            $result[self::PAYMENT_TYPE_CARD] = true;
        }

        if (in_array(self::PAYMENT_TYPE_CASH, $saved_payment)) {
            $result[self::PAYMENT_TYPE_CASH] = true;
        }

        return $result;
    }
}
