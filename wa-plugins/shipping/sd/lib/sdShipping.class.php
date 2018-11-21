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
 * @property string $country
 * @property string $region
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

    protected $extra_days = array();
    protected $est_delivery = null;

    protected function calculate()
    {
        if (!$this->isValidAddress()) {
            return $this->_w('Pickup is not available for this address.');
        }
        if (!$this->isValidWeight()) {
            return $this->_w('Weight values above the limit.');
        }
        if (!$this->isValidSize()) {
            return $this->_w('Size values above the limit.');
        }

        $timestamp = $this->getShippingCompleteDate();
        $est_delivery = waDateTime::format('humandate', $timestamp);

        $schedule = $this->getSchedule($timestamp);
        //convert to server time
        $timestamp = $this->changeTimezone('U', $timestamp, $this->timezone, date_default_timezone_get());
        $result = array(
            array(
                'rate'          => $this->getShippingRate(),
                'est_delivery'  => $est_delivery,
                'delivery_date' => self::formatDatetime($timestamp),
                'service'       => $this->service,
                'currency'      => $this->currency,
                'type'          => self::TYPE_PICKUP,
                'custom_data'   => array(
                    self::TYPE_PICKUP => array(
                        'id'         => $this->id,
                        'timezone'   => $this->timezone,
                        'lat'        => $this->latitude,
                        'lng'        => $this->longitude,
                        'schedule'   => $schedule,
                        'photos'     => $this->photos,
                        'way'        => $this->way,
                        'additional' => $this->additional,
                        'storage'    => $this->getStorageInfo(),
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
        $data = array();
        $country = $this->country;
        $region = $this->region;

        if ($country) {
            $data['country'] = array($country);
        }

        if ($region) {
            $data['region'] = array($region);
        }

        return $data;
    }

    public function requestedAddressFields()
    {
        return array('country' => array('cost' => true,));
    }

    /**
     * Check whether the address is served
     * @return bool
     */
    protected function isValidAddress()
    {
        $is_country = $this->isValidCountry();
        $is_region = $this->isValidRegion();
        $is_city = $this->isValidCity();

        if ($is_country && $is_region && $is_city) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check that the country exists
     * @return bool
     */
    protected function isValidCountry()
    {
        $requested_country = $this->getAddress('country');

        if ($this->country === $requested_country) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check that the region is required and exists
     * @return bool
     */
    protected function isValidRegion()
    {
        $saved_region = mb_strtolower($this->region);
        $requested_region = mb_strtolower($this->getAddress('region'));

        if (empty($saved_region) || $saved_region === $requested_region) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check that the city is required and exists
     * @return bool
     */
    protected function isValidCity()
    {
        $saved_city = $this->getCity();
        $requested_city = mb_strtolower($this->getAddress('city'));

        if (empty($saved_city) || in_array($requested_city, $saved_city)) {
            return true;
        } else {
            return false;
        }
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
     * @return int
     */
    protected function getShippingCompleteDate()
    {
        $time_with_processing = $this->getTimeWithProcessing();

        //convert to pickup timezone
        $timestamp = $this->changeTimezone('U', date('Y-m-d', $time_with_processing), $this->timezone);
        $date_time_timestamp = $this->changeTimezone('U', $time_with_processing, date_default_timezone_get(), $this->timezone);

        //Get day type for time
        $type = $this->getDayType($timestamp);

        //Check today.
        if ($type !== false) {
            $work_time = $this->getStartWorkTime($type, $timestamp);
            //Today you can only pick up if the goods were brought before the opening.
            if ($date_time_timestamp > $work_time) {
                $type = false;
            }
        }

        //If today did not work, we are looking for the first valid day but not more than a year later.
        $i = 0;
        while ($type === false && $i <= 365) {
            //Add day
            $timestamp = strtotime('+1 day', $timestamp);
            $i++;

            $type = $this->getDayType($timestamp);
        }

        if ($type) {
            $timestamp = $this->getStartWorkTime($type, $timestamp);
        }

        return $timestamp;
    }

    /**
     * Get start work time
     * @param $type
     * @param $timestamp
     * @return int|mixed|null
     */
    protected function getStartWorkTime($type, $timestamp)
    {
        $work_time = 0;

        if ($type == 'weekday') {
            $work_time = $this->getWeekDayTimeStamp($timestamp);
        } elseif ($type == 'workday') {
            $work_time = ifset($this->extra_days, 'workdays', $timestamp, 'start_work', 0);
        }

        return $work_time;
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
     * @param $time
     * @return bool|string If weekend return false
     */
    protected function getDayType($time)
    {
        $extra_workday = $this->isExtraWorkday($time);
        $extra_weekend = $this->isExtraWeekend($time);
        $workday = $this->isWorkday($time);

        $type = false;

        if ($extra_workday) {
            $type = 'workday';
        } else {
            if ($workday && !$extra_weekend) {
                $type = 'weekday';
            }
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
        $workdays = $this->getExtendWorkdays();

        if ($workdays && isset($workdays[$time])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if it time is a extra weekend day.
     * @param $time
     * @return bool
     */
    protected function isExtraWeekend($time)
    {
        $weekend = $this->getExtendWeekend();

        if ($weekend && isset($weekend[$time])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if it time is a working day.
     * @param $time
     * @return bool
     */
    protected function isWorkday($time)
    {
        $weekdays = $this->weekdays;
        $day_name_code = date('N', $time);

        if (isset($weekdays[$day_name_code])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get date with start work time.
     * @param $time
     * @return int
     */
    protected function getWeekDayTimeStamp($time)
    {
        $weekdays = $this->weekdays;
        $day_name_code = date('N', $time);
        $start_work = ifset($weekdays, $day_name_code, 'start_work', '');

        $start_work = explode(':', $start_work);
        $hours = ifset($start_work, 0, 0);
        $minutes = ifset($start_work, 1, 0);

        $work_time = $time + ($hours * 3600) + ($minutes * 60);

        return $work_time;
    }

    /**
     * Get saved extra workdays formatted to timestamp
     * @return array
     */
    protected function getExtendWorkdays()
    {
        $extra_workdays = ifset($this->extra_days, 'workdays', null);

        if ($extra_workdays === null) {
            $extra_workdays = array();

            $workdays = $this->workdays;
            if (is_array($workdays)) {
                foreach ($workdays as $workday) {
                    $start_time = $workday['date'].' '.$workday['start_work'];
                    $start_time = strtotime($start_time);
                    $end_work = $workday['date'].' '.$workday['end_work'];
                    $end_work = strtotime($end_work);

                    $date_timestamp = strtotime($workday['date']);

                    $extra_workdays[$date_timestamp] = array(
                        'start_work' => $start_time,
                        'end_work'   => $end_work
                    );
                }
            }
        }
        $this->extra_days['workdays'] = $extra_workdays;

        return $extra_workdays;
    }

    /**
     * Get saved extra weekend formatted to timestamp
     * @return array
     */
    protected function getExtendWeekend()
    {
        $extra_weekend = ifset($this->extra_days, 'weekend', null);

        if ($extra_weekend === null) {
            $extra_weekend = array();

            $weekend = $this->weekend;
            if (is_array($weekend)) {
                foreach ($weekend as $day) {
                    $date = $day['date'];
                    $date_timestamp = strtotime($date);
                    $extra_weekend[$date_timestamp] = true;
                }
            }
        }
        $this->extra_days['weekend'] = $extra_weekend;

        return $extra_weekend;
    }

    /**
     * Check the mode of operation after the day of delivery
     * @param int first_day timestamp
     * @return array timezone and days list.
     */
    protected function getSchedule($first_day)
    {
        $date = $this->changeTimezone('U', date('Y-m-d', $first_day), $this->timezone);

        $result = array();

        for ($i = 0; $i <= 6; $i++) {
            $day_info = array();
            $type = $this->getDayType($date);

            switch ($type) {
                case 'workday':
                    {
                        $day_info['type'] = 'workday';
                        $extra_workdays = $this->extra_days['workdays'][$date];
                        $day_info['start_work'] = date('Y-m-d H:i', $extra_workdays['start_work']);
                        $day_info['end_work'] = date('Y-m-d H:i', $extra_workdays['end_work']);
                        break;
                    }
                case 'weekday':
                    {
                        $day_info['type'] = 'workday';
                        $day_name_code = date('N', $date);
                        $day = $this->weekdays[$day_name_code];

                        $day_info['start_work'] = date('Y-m-d', $date).' '.$day['start_work'];
                        $day_info['end_work'] = date('Y-m-d', $date).' '.$day['end_work'];

                        break;
                    }
                default:
                    {
                        $day_info['type'] = 'weekend';
                        $day_info['start_work'] = date('Y-m-d H:i', $date);
                        $day_info['end_work'] = date('Y-m-d H:i', $date);
                    }
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

    public function getSettingsHTML($params = array())
    {
        $view = wa()->getView();

        $settings = $this->getSettings();

        $view->assign(array(
            'obj'          => $this,
            'currencies'   => $this->getCurrencies(),
            'countries'    => $this->getCountries(),
            'regions'      => $this->getRegions($settings),
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
        $countries = $cm->all();

        return $countries;
    }

    protected function getRegions($settings)
    {
        $rm = new waRegionModel();
        $regions = $rm->getByCountry($settings['country']);

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
                    'name'       => $day,
                    'works'      => ifset($saved_weekdays, $id, 'works', '0'),
                    'start_work' => ifset($saved_weekdays, $id, 'start_work', '10:00'),
                    'end_work'   => ifset($saved_weekdays, $id, 'end_work', '19:00'),
                );
            } else {
                $result[$id] = array(
                    'name'       => $day,
                    'works'      => ifset($output, $id, '1'),
                    'start_work' => '10:00',
                    'end_work'   => '19:00',
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

    protected function parseDays($key, $settings)
    {
        $days = ifset($settings, $key, array());

        foreach ($days as $id => $day) {
            if (($key === 'weekdays' && empty($day['works']))
                || ($key === 'workdays' && empty($day['date']))) {
                unset($days[$id]);
                continue;
            }

            if (isset($day['date'])) {
                $days[$id]['date'] = $this->parseDayFormat($day['date']);
            }
            if (isset($day['start_work'])) {
                $this->validateTimeFormat($day['start_work']);
            }

            if (isset($day['end_work'])) {
                $this->validateTimeFormat($day['end_work']);
            }
        }

        return $days;
    }

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

    protected function validateTimeFormat($time)
    {
        if (!preg_match('/(^[01]?[0-9]|2[0-3])($|:([0-5][0-9]$))/ui', $time)) {
            throw new waException($this->_w('Invalid time'));
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
}
