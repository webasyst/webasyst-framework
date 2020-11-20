<?php

/**
 * Плагин расчета доставки «Почтой России».
 *
 * @see http://www.russianpost.ru/rp/servise/ru/home/postuslug/bookpostandparcel/parcelltariff
 * @link https://tracking.pochta.ru/specification API отслеживания
 *
 * @property-read $api_login
 * @property-read $api_password
 * @property-read $region
 * @property-read $exclude_cities
 * @property-read $halfkilocost
 * @property-read $currency
 * @property-read $overhalfkilocost
 * @property-read $difficult_charge
 * @property-read $caution
 * @property-read $caution_percent
 * @property-read $max_weight
 * @property-read $max_volume
 * @property-read $max_side_length
 * @property-read $complex_calculation_weight
 * @property-read $complex_calculation_percent
 * @property-read $commission
 * @property-read $extra_charge
 * @property-read $cash_on_delivery
 * @property-read $cod
 *
 * @property-read bool $parcel Отправлять посылки
 *
 * @property-read bool $bookpost Отправлять бандероли
 * @property-read float $bookpost_max_weight Максимальный вес бандероли
 * @property-read float $bookpost_max_price Максимальная сумма бандероли
 * @property-read string $bookpost_simple_cost Стоимость отправки бандероли весом 0,1 кг
 * @property-read string $bookpost_weight_simple_cost Стоимость отправки каждых 0,02 кг
 * @property-read string $bookpost_ordered_cost Стоимость отправки бандероли весом 0,1 кг
 * @property-read string $bookpost_weight_ordered_cost Стоимость отправки каждых 0,02 кг
 * @property-read string $bookpost_weight_declared_cost Стоимость отправки каждых 0.5 килограмм бандероли с объявленной ценностью
 * @property-read string $bookpost_declared_commission Плата за сумму объявленной ценности бандероли (%)
 *
 * @property-read $delivery_date_show
 * @property-read $delivery_date_min
 * @property-read $delivery_date_max
 *
 * @property-read $document
 * @property-read $document_series
 * @property-read $document_number
 * @property-read $document_issued
 * @property-read $document_issued_day
 * @property-read $document_issued_month
 * @property-read $document_issued_year
 *
 * @property string $company_name
 * @property string $company_name2
 * @property string $address1
 * @property string $address2
 * @property string $zip
 * @property string $zip_distribution_center
 * @property string $phone
 * @property string $inn
 * @property string $bank_kor_number
 * @property string $bank_name
 * @property string $bank_account_number
 * @property string $bik
 * @property string $color
 * @property array required_address_fields
 */
class russianpostShipping extends waShipping
{
    private $wsdl = 'https://tracking.russianpost.ru/rtm34?wsdl';

    /**
     * Регистрирует пользовательские элементы управления плагина для использования в интерфейсе настроек.
     * Элементы управления формируются методами класса плагина, имена которых начинаются на 'settings'
     * и содержат идентификатор, указанный при вызове метода registerControl().
     * Параметры указанных элементов управления настроек должны содержаться в файле плагина lib/config/settings.php.
     */
    protected function initControls()
    {
        $this
            ->registerControl('WeightCosts')
            ->registerControl('RegionRatesControl')
            ->registerControl('CashDelivery');
        parent::initControls();
    }

    /**
     * Формирует HTML-код пользовательского элемента управления с идентификатором 'WeightCosts'.
     *
     * @param string $name Идентификатор элемента управления, указанный в файле настроек
     * @param array $params Параметры элемента управления, указанные в файле настроек
     * @return string HTML-код элемента управления
     * @see waHtmlControl::getControl()
     */
    public static function settingWeightCosts($name, $params = array())
    {
        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }
        $control = '';
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }
        $costs = $params['value'];

        waHtmlControl::addNamespace($params, $name);
        $control .= '<table class="zebra">';
        $params['description_wrapper'] = '%s';
        $currency = waCurrency::getInfo('RUB');
        $params['title_wrapper'] = '%s';
        $params['control_wrapper'] = '<tr title="%3$s"><td>%1$s</td><td>&rarr;</td><td>%2$s '.$currency['sign'].'</td></tr>';
        $params['size'] = 6;
        for ($zone = 1; $zone <= 5; $zone++) {
            $params['value'] = self::floatval(isset($costs[$zone]) ? $costs[$zone] : 0.0);
            $params['title'] = "Пояс {$zone}";
            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, $zone, $params);
        }
        $control .= "</table>";

        return $control;
    }

    /**
     * @param $name
     * @param array $params
     * @return string
     * @throws Exception
     */
    public static function settingCashDelivery($name, $params = array())
    {
        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }
        $control = '';
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }
        waHtmlControl::addNamespace($params, $name);
        $value = $params['value'];
        $ns = $params['namespace'][0];
        $title = array(
            0 => array('range' => '0ー1000'),
            1 => array('range' => '1000ー5000'),
            2 => array('range' => '5000ー20000'),
            3 => array('range' => '20000ー500000'),
        );
        $control .= '<table class="zebra">';
        $control .= '<tr><th>Сумма заказа</th><th></th><th>Фиксированная часть</th><th></th><th>Процент от суммы заказа</th></tr>';
        $currency = waCurrency::getInfo('RUB');
        $params['title_wrapper'] = '%s';
        $params['size'] = 6;
        for ($cod = 0; $cod <= 3; $cod++) {
            $params['control_wrapper'] = '<tr title="%3$s"><td>%1$s</td><td>&rarr;</td><td>%2$s '.$currency['sign'].'</td><td>+</td>';
            $params['title'] = $title[$cod]['range'];
            $params['value'] = $value[$cod]['rate'];
            $params['namespace'][0] = $ns."[$cod]";
            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, 'rate', $params);
            $params['control_wrapper'] = '<td>%2$s %%</td></tr>';
            $params['value'] = $value[$cod]['percent'];
            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, 'percent', $params);

        }
        $control .= "</tbody>";
        $control .= "</table>";

        return $control;
    }

    /**
     * Формирует HTML-код пользовательского элемента управления с идентификатором 'RegionRatesControl'.
     *
     * @param string $name Идентификатор элемента управления
     * @param array $params Параметры элемента управления
     * @return string
     * @see waHtmlControl::getControl()
     */
    public static function settingRegionRatesControl($name, $params = array())
    {
        foreach ($params as $field => $param) {
            if (strpos($field, 'wrapper')) {
                unset($params[$field]);
            }
        }

        if (empty($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }
        $control = '';

        waHtmlControl::addNamespace($params, $name);

        $rm = new waRegionModel();
        if ($regions = $rm->getByCountry('rus')) {

            $control .= "<table class=\"zebra\"><thead>";
            $control .= "<tr class=\"gridsheader\"><th colspan=\"3\">";
            $control .= htmlentities('Распределите регионы по тарифным поясам «Почты России»', ENT_QUOTES, 'utf-8');
            $control .= "</th></tr></thead><tbody>";

            $params['control_wrapper'] = '<tr><td>%s</td><td>&rarr;</td><td>%s</td></tr>';
            $params['title_wrapper'] = '%s';
            $params['description_wrapper'] = '%s';
            $params['options'] = array();
            $params['options'][0] = _wp('*** не доставлять ***');
            for ($region = 1; $region <= 5; $region++) {
                $params['options'][$region] = sprintf(_wp('Пояс %d'), $region);
            }

            foreach ($regions as $region) {
                $name = 'zone';
                $id = $region['code'];
                if (empty($params['value'][$id])) {
                    $params['value'][$id] = array();
                }
                $params['value'][$id] = array_merge(array($name => 0), $params['value'][$id]);
                $region_params = $params;

                waHtmlControl::addNamespace($region_params, $id);

                $region_params['value'] = max(0, min(5, $params['value'][$id][$name]));
                $region_params['title'] = $region['name'];
                if ($region['code']) {
                    $region_params['title'] .= " ({$region['code']})";
                }
                $control .= waHtmlControl::getControl(waHtmlControl::SELECT, 'zone', $region_params);
            }
            $control .= "</tbody>";
            $control .= "</table>";
        } else {
            $control .= 'Для Российской Федерации не указаны регионы.';
        }
        return $control;
    }

    /**
     * Ограничивает диапазон адресов, по которым возможна доставка с помощью этого плагина.
     *
     * @return array Верните пустой array(), если необходимо разрешить доставку по любым адресам без ограничений
     * @example <pre>return array(
     *   'country' => 'rus', // или array('rus', 'ukr')
     *   'region'  => '77',
     *   // или array('77', '50', '69', '40');
     *   // либо не указывайте элемент 'region', если вам не нужно ограничивать доставку отдельными регионами указанной страны
     * );</pre>
     */
    public function allowedAddress()
    {
        $address = array(
            'country' => 'rus',
            'region'  => array(),
        );
        foreach ($this->region as $region => $options) {
            if (!empty($options['zone'])) {
                $address['region'][] = $region;
            }
        }
        return array($address);
    }

    /**
     * Возвращает массив полей формы запроса адреса доставки, которые должны запрашиваться у покупателя во время оформления заказа.
     *
     * @return array|bool Верните false, если плагин не длолжен запрашивать адрес доставки;
     * верните пустой array(), если все поля адреса должны запрашиваться у покупателя
     * @example <pre>return array(
     *     // поле запрашивается
     *     'zip'     => array(),
     *
     *     // скрытое поле с указанным вручную значением
     *     'country' => array('hidden' => true, 'value' => 'rus', 'cost' => true),
     *
     *     // параметр 'cost' означает, что значение данного поля используется для предварительного расчета стоимости доставки
     *     'region'  => array('cost' => true),
     *     'city'    => array(),
     *
     *     // поле не запрашивается
     *     'street'  => false,
     *
     *     // Поле обязательно
     *     'street' => array('required' => true)
     *
     * );</pre>
     * @see waShipping::requestedAddressFields()
     */
    public function requestedAddressFields()
    {
        $required_address = $this->required_address_fields;

        return array(
            'zip'     => isset($required_address['zip']) ? array('required' => true) : array(),
            'country' => array('hidden' => true, 'value' => 'rus', 'cost' => true),
            'region'  => array('cost' => true),
            'city'    => array(),
            'street'  => isset($required_address['street']) ? array('required' => true) : array(),
        );
    }

    /**
     * Получение стоимости вариантов доставки, доступных для указанного тарифного пояса.
     *
     * @see http://www.russianpost.ru/rp/servise/ru/home/postuslug/bookpostandparcel/parcelltariff
     * @param float $weight Суммарный вес отправления
     * @param float $price Суммарная стоимость отправляемых товаров
     * @param int $zone Идентификатор тарифного пояса: от 1 до 5
     * @return array
     */
    private function getZoneRates($weight, $price, $zone)
    {
        $zone = max(1, min(5, $zone));
        $rate = array();
        $halfkilocost = $this->halfkilocost;
        $overhalfkilocost = $this->overhalfkilocost;

        $zone_halfkilocost = self::floatval(isset($halfkilocost[$zone]) ? $halfkilocost[$zone] : 0);
        $zone_overhalfkilocost = self::floatval(isset($overhalfkilocost[$zone]) ? $overhalfkilocost[$zone] : 0);

        $extra_weight = round(max(0.5, $weight) - 0.5, 3);

        $rate_parcel = $zone_halfkilocost + $zone_overhalfkilocost * ceil($extra_weight / 0.5);
        $rate['parcel'] = $rate_parcel;
        $rate['bookpost'] = 0;

        if ($weight <= min($this->bookpost_max_weight, 2)) {
            $rate['bookpost'] = $this->getBookpostRate($weight, $zone);
        }

        if ($this->complex_calculation_weight > 0 && $weight > $this->complex_calculation_weight || !$this->isValidSize()) {
            $ccp = $this->complex_calculation_percent;
            $percent = ifset($ccp, 0) / 100;
            $rate['parcel'] += $rate_parcel * $percent;
        }

        if ($this->caution && $this->caution_percent) {
            $cp = $this->caution_percent;
            $percent = ifset($cp, 0) / 100;
            $rate['parcel'] += $rate_parcel * $percent;
        }

        //recalculate rate if delivery in region is difficult
        if ($this->isDifficult()) {
            $dc = $this->difficult_charge;
            $rate['parcel'] += $rate_parcel * (ifset($dc, 0) / 100);
            $rate['bookpost'] += ($rate['bookpost'] * (ifset($dc, 0) / 100)) * 2;

        }
        $commission = !empty($this->commission) ? $this->commission : 0;
        $extra_charge = !empty($this->extra_charge) ? $this->extra_charge : 0;
        $rate['parcel'] += $price * ($commission / 100) + $extra_charge;

        if ($this->bookpost == 'declared' && $this->bookpost_declared_commission) {
            $rate['bookpost'] += $price * ($this->bookpost_declared_commission / 100);
        }

        $rate['bookpost'] += $extra_charge;

        return $rate;
    }

    /**
     * Основной метод расчета стоимости доставки.
     * Возвращает массив предварительно рассчитанной стоимости и сроков доступных вариантов доставки,
     * либо сообщение об ошибке для отображения покупателю,
     * либо false, если способ доставки в текущих условиях не дложен быть доступен.
     *
     *
     * @return mixed
     * @example <pre>
     * //возврат массива вариантов доставки
     * return array(
     *     'option_id_1' => array(
     *          'name'         => $this->_w('...'),
     *          'description'  => $this->_w('...'),
     *          'est_delivery' => '...',
     *          'currency'     => $this->currency,
     *          'rate'         => $this->cost,
     *      ),
     *      ...
     * );
     *
     * //сообщение об ошибке
     * return 'Для расчета стоимости доставки укажите регион доставки';
     *
     * //способ доставки недоступен
     * return false;</pre>
     *
     * Полезные методы базового класса (waShipping), которые можно использовать в коде метода calculate():
     *
     *     <pre>
     *     // суммарная стоимость отправления
     *     $price = $this->getTotalPrice();
     *
     *     // суммарный вес отправления
     *     $weight = $this->getTotalWeight();
     *
     *     // массив с информацией о заказанных товарах
     *     $items = $this->getItems();
     *
     *     // массив с полной информацией об адресе получателя либо значение указанного поля адреса
     *     $address = $this->getAddress($field = null);
     *     </pre>
     *
     */
    protected function calculate()
    {
        $home_city = array_map('strtolower', array_filter(preg_split('@,\s*@', $this->exclude_cities), 'strlen'));
        $weight = (float)$this->getTotalWeight();
        if ($weight > $this->max_weight) {
            $services = sprintf("Вес отправления (%0.2f) превышает максимально допустимый (%0.2f)", $weight, $this->max_weight);
        } else {
            $region_id = $this->getAddress('region');
            if ($region_id) {
                if (!empty($this->region[$region_id])
                    && !empty($this->region[$region_id]['zone'])
                    && (empty($home_city) || !in_array(strtolower($this->getAddress('city')), $home_city, true))
                ) {
                    $services = array();

                    $delivery_date = null;
                    $est_delivery = null;

                    if ($this->delivery_date_show) {
                        $delivery_date = array();
                        /** @var string $departure_datetime SQL DATETIME */
                        $departure_datetime = $this->getPackageProperty('departure_datetime');
                        /** @var  int $departure_timestamp */
                        if ($departure_datetime) {
                            $departure_timestamp = max(time(), strtotime($departure_datetime));
                        } else {
                            $departure_timestamp = time();
                        }
                        if ($this->delivery_date_min) {
                            $delivery_date[] = strtotime(sprintf('+%d days', $this->delivery_date_min), $departure_timestamp);
                        }
                        if ($this->delivery_date_max) {
                            $delivery_date[] = strtotime(sprintf('+%d days', $this->delivery_date_max), $departure_timestamp);
                        }
                        if (!$delivery_date) {
                            $delivery_date = null;
                        } else {
                            $delivery_date = array_unique($delivery_date);
                            # format estimate delivery date
                            $est_delivery = $delivery_date;
                            foreach ($est_delivery as &$date) {
                                $date = waDateTime::format('humandate', $date);
                            }
                            unset($date);
                            $est_delivery = implode(' - ', $est_delivery);

                            # format delivery date
                            if (count($delivery_date) == 1) {
                                $delivery_date = reset($delivery_date);
                            }
                            $delivery_date = self::formatDatetime($delivery_date);
                        }
                    }

                    $rate = $this->getZoneRates($weight, $this->getTotalPrice(), $this->region[$region_id]['zone']);

                    if (($weight <= $this->bookpost_max_weight) && ($this->getTotalPrice() < $this->bookpost_max_price)) {
                        switch ($this->bookpost) {
                            case 'simple':
                                $services['bookpost_simple'] = array(
                                    'name'          => 'Бандероль простая',
                                    'id'            => 'bookpost_simple',
                                    'est_delivery'  => $est_delivery,
                                    'delivery_date' => $delivery_date,
                                    'rate'          => $rate['bookpost'],
                                    'currency'      => 'RUB',
                                    'type'          => self::TYPE_POST,
                                );
                                break;
                            case 'ordered':
                                $services['bookpost_ordered'] = array(
                                    'name'          => 'Бандероль заказная',
                                    'id'            => 'bookpost_ordered',
                                    'est_delivery'  => $est_delivery,
                                    'delivery_date' => $delivery_date,
                                    'rate'          => $rate['bookpost'],
                                    'currency'      => 'RUB',
                                    'type'          => self::TYPE_POST,
                                );

                                break;
                            case 'declared':
                                $services['bookpost_declared'] = array(
                                    'name'          => 'Бандероль с объявленной ценностью',
                                    'id'            => 'bookpost_declared',
                                    'est_delivery'  => $est_delivery,
                                    'delivery_date' => $delivery_date,
                                    'rate'          => $rate['bookpost'],
                                    'currency'      => 'RUB',
                                    'type'          => self::TYPE_POST,
                                );
                                break;
                        }
                    }

                    switch ($this->parcel) {
                        case 'none':
                        case 'false':
                        case '0':
                        case false:
                            break;
                        case 'otherwise':
                            if (isset($services['bookpost_declared'])) {
                                break;
                            }
                        /* no break */
                        case true:
                        case 'always':
                            $services['parcel'] = array(
                                'name'          => 'Посылка',
                                'id'            => 'parcel',
                                'est_delivery'  => $est_delivery,
                                'delivery_date' => $delivery_date,
                                'rate'          => $rate['parcel'],
                                'currency'      => 'RUB',
                                'type'          => self::TYPE_POST,
                            );
                            break;
                    }

                    if (empty($services)) {
                        $services = false;
                    }

                } else {
                    $services = false;
                }
            } else {
                $services = array(
                    array(
                        'rate'    => null,
                        'comment' => 'Для расчета стоимости доставки укажите регион доставки',
                    ),
                );
            }
        }

        //Cash on delivery
        if ($this->cod) {
            wa()->getStorage()->set($this->getCacheKey(), $services);
            wa()->getStorage()->set($this->getCacheKey('price'), $this->getTotalPrice());
        }

        return $services;
    }

    /**
     * Получение стоимости доставки бандеролей
     * @param $weight
     * @param $zone
     * @return float|int
     */
    private function getBookpostRate($weight, $zone)
    {
        $extra_weight = round(max(0.1, $weight) - 0.1, 3);
        $rate = 0;
        switch ($this->bookpost) {
            case 'simple':
                $rate = $this->bookpost_simple_cost;
                $rate += ceil($extra_weight / 0.02) * $this->bookpost_weight_simple_cost;
                break;
            case 'ordered':
                $rate = $this->bookpost_ordered_cost;
                $rate += ceil($extra_weight / 0.02) * $this->bookpost_weight_ordered_cost;
                break;
            case 'declared':
                $extra_weight = round(max(0.5, $weight) - 0.5, 3);
                $base_cost = $this->bookpost_weight_declared_cost;
                $rate = $base_cost[$zone] * (1 + ceil($extra_weight / 0.5));
                break;
        }
        return $rate;
    }

    /**
     * @param waOrder $order
     * @return array
     */
    public function customFields(waOrder $order)
    {
        $fields = parent::customFields($order);

        if ($this->cod) {
            $this->registerControl('RussianPostCOD', array($this, 'settingCOD'));

            $fields['cod'] = array(
                'value'        => null,
                'title'        => 'Комиссия «Почты России» за почтовый перевод',
                'control_type' => 'RussianPostCOD',
            );
        }

        return $fields;
    }

    /**
     * Рассчет коммиссии для наложенного платежа
     * @return array
     */
    protected function calculateCODRates()
    {
        $key = $this->getCacheKey();
        $storage = wa()->getStorage();
        $services = $storage->get($key);
        $price = $this->getCacheKey('price');
        $price = $storage->get($price);

        //Cash on delivery
        $key = null;
        switch ($price) {
            case $price <= 1000:
                $key = 0;
                break;
            case $price <= 5000:
                $key = 1;
                break;
            case $price <= 20000:
                $key = 2;
                break;
            case $price <= 500000:
                $key = 3;
                break;
        }
        $cash_on_delivery = $this->cash_on_delivery;
        $cod_data = ifset($cash_on_delivery, $key, null);

        $rate = array();

        if ($cod_data) {
            foreach ($services as $key => $service) {
                $rate[$key] = ($price + $service['rate']) * ($cod_data['percent'] / 100);
                $rate[$key] += $cod_data['rate'];
            }
        }

        return $rate;
    }

    /**
     * Вернуть в фронт массив с комиссиями наложенного платежа
     */
    public function rateCODAction()
    {
        $response = array(
            'status' => 'ok',
            'data'   => $this->calculateCODRates(),
        );
        $this->sendJsonResponse($response);
    }

    /**
     * @param $response
     */
    private function sendJsonResponse($response)
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

    /**
     * Получить ключ, для извлечения информации из кэша.
     * @param null $key
     * @return string
     */
    private function getCacheKey($key = null)
    {
        return sprintf('wa-plugins/shipping/russianpost/%s/%s/%s', $this->app_id, $this->key, $key ? $key : $this->cache_key);
    }

    /**
     * Формирования HTML Для вывода информации об наложенном платеже.
     * @param $name
     * @param array $params
     * @return string
     */
    public function settingCOD($name, $params = array())
    {
        //URL для перерасчета коммисии
        $url_params = array(
            'action_id' => 'rateCOD',
            'plugin_id' => $this->key,
        );
        $url = wa()->getRouteUrl(sprintf('%s/frontend/shippingPlugin', $this->app_id), $url_params, true);

        $rates = $this->calculateCODRates();
        $count_rates = count($rates);
        $delivery = key($rates);
        $rates = json_encode($rates);
        $locale = wa()->getUser()->getLocale();

        $currency = waCurrency::getInfo('RUB');
        $override = array(
            'title'           => '',
            'title_wrapper'   => false,
            'description'     => '',
            'control_wrapper' => "%s%3\$s\n%2\$s\n",
            'readonly'        => true,
            'style'           => 'border: 0px',
        );

        $params = array_merge($params, $override);

        $html = waHtmlControl::getControl(waHtmlControl::INPUT, $name, $params);

        waHtmlControl::makeId($params, $name);

        $html .= <<<HTML
<script type="text/javascript">
var shipping = $('.shipping-{$this->key}'),
    rates = shipping.find('.shipping-rates'),
    form = shipping.find('.wa-form'),
    currency_sign = '{$currency['sign']}',
    delivery = '{$delivery}',
    count_rates = {$count_rates},
    value = null,
    locale = '{$locale}';

if (rates.length > 0) {
    form = rates;
}

form.live('change', function() {
            $.ajax({
                "type": 'POST',
                "url": '{$url}',
                "success": function (response) {
                setCODRates(response.data)
                }});
});
function setCODRates(data) {
   if (count_rates > 1) {
    delivery = shipping.find('.shipping-rates :selected').val();
   }

    value = data[delivery];
    if (value === undefined){
        value = 0;
    }
    value = value.toFixed(2);
    
    if (locale == 'ru_RU') {
        value = value.replace(".",",");
    }
    
    $('#{$params['id']}').val(value + ' ' + currency_sign);
}
setCODRates({$rates});
</script>
HTML;
        return $html;
    }

    /**
     * Возвращает массив с информацией о печатных формах, формируемых плагином.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @return array
     * @example return <pre>array(
     *    'form_id' => array(
     *        'name' => _wp('Printform name'),
     *        'description' => _wp('Printform description'),
     *    ),
     * );</pre>
     */
    public function getPrintForms(waOrder $order = null)
    {
        return extension_loaded('gd') ? array(
            7   => array(
                'name'        => 'Форма №7-п',
                'description' => 'Бланк адресного ярлыка к посылке',
            ),
            107 => array(
                'name'        => 'Форма №107',
                'description' => 'Бланк описи вложения',
            ),
            112 => array(
                'name'        => 'Форма №112ЭП',
                'description' => 'Бланк приема переводов в адрес физических и юридических лиц',
            ),
            113 => array(
                'name'        => 'Форма №113',
                'description' => 'Бланк почтового перевода наложенного платежа',
            ),
            116 => array(
                'name'        => 'Форма №116',
                'description' => 'Бланк сопроводительного адреса к посылке',
            ),
        ) : array();
    }

    /**
     * Возвращает HTML-код указанной печатной формы.
     *
     * @param string $id Идентификатор формы, определенный в методе getPrintForms()
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params Дополнительные необязательные параметры, передаваемые в шаблон печатной формы
     * @return string HTML-код формы
     * @throws waException
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        $method = 'displayPrintForm'.$id;
        if (method_exists($this, $method)) {
            if (extension_loaded('gd')) {
                return $this->$method($order, $params);
            } else {
                throw new waException('PHP extension GD not loaded');
            }
        } else {
            throw new waException('Print form not found');
        }
    }

    /**
     * Вспомогательный метод для печати формы с идентификатором 113.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params
     * @return string HTML-код формы
     */
    private function displayPrintForm113(waOrder $order, $params = array())
    {
        $strict = true;
        $request = waRequest::request();

        $order['rub'] = intval(waRequest::request('rub', round(floor($order->total))));
        $order['cop'] = min(99, max(0, intval(waRequest::request('cop', round($order->total * 100 - $order['rub'] * 100)))));

        switch ($side = waRequest::get('side', ($order ? (waRequest::get('mass_print') ? 'print' : '') : 'print'), waRequest::TYPE_STRING)) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f113en_front.gif', $image_info)) {
                    if ($this->color) {
                        if ($image_stripe = $this->read('f113en_stripe.gif', $image_info)) {
                            imagecopy($image, $image_stripe, 808, 663, 0, 0, $image_info[0], $image_info[1]);
                        }
                    }

                    $format = '%.W{n0} %.2{f0}';
                    $this->printOnImage($image, sprintf('%d', $order['rub']), 1730, 670);
                    $this->printOnImage($image, sprintf('%02d', $order['cop']), 1995, 670);
                    $this->printOnImage($image, waRequest::request('order_amount', waCurrency::format($format, $order->total, $order->currency)), 856, 735, 30);
                    $this->printOnImage($image, $this->company_name, 915, 800);
                    $this->printOnImage($image, $this->company_name2, 850, 857);
                    $this->printOnImage($image, $this->address1, 915, 910);
                    $this->printOnImage($image, $this->address2, 824, 975);
                    $this->printOnImagePersign($image, $this->zip, 1985, 1065, 34, 35);
                    $this->printOnImagePersign($image, $this->inn, 920, 1135, 34, 35);
                    $this->printOnImagePersign($image, $this->bank_kor_number, 1510, 1135, 34, 35);
                    $long = mb_strlen($this->bank_name) > 30;
                    $this->printOnImage($image, $this->bank_name, 1160, $long ? 1210 : 1194, $long ? 20 : 35);
                    $this->printOnImagePersign($image, $this->bank_account_number, 1018, 1250, 34, 35);
                    $this->printOnImagePersign($image, $this->bik, 1885, 1250, 34, 35);

                    #
                    $this->printOnImage($image, $order->contact_lastname, 1000, 1660);
                    $this->printOnImage($image, $order->contact_firstname.' '.$order->contact_middlename, 850, 1715);

                    $size = 35;
                    $sizes = array(35, 30, 30);

                    $billing_address = $order->billing_address;
                    if (!$this->buildAddress($billing_address)) {
                        $billing_address = $order->shipping_address;
                    }
                    $full_address = waRequest::request('shipping_address',  $this->buildAddress($billing_address));
                    $address = $this->adjustSizes($full_address, $sizes, $size);

                    $this->printOnImage($image, $address[0], 1200, 1770, $size);
                    $this->printOnImage($image, $address[1], 850, 1840, $size);
                    $this->printOnImage($image, $address[2], 850, 1910, $size);

                    $this->printOnImagePersign($image, $billing_address['zip'], 1990, 1900, 34, 35);

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'back':
                $image_info = null;
                if ($image = $this->read('f113en_back.gif', $image_info)) {
                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'print':
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign(
                    array(
                        'src_front' => http_build_query(array_merge($request, array('side' => 'front'))),
                        'src_back'  => http_build_query(array_merge($request, array('side' => 'back'))),
                    )
                );
                $this->view()->assign('editable', false);
                break;
            default:
                $this->view()->assign(
                    array(
                        'src_front' => http_build_query(array_merge($request, array('side' => 'front'))),
                        'src_back'  => http_build_query(array_merge($request, array('side' => 'back'))),
                    )
                );
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('order', $order);
                $this->view()->assign('editable', waRequest::post() ? false : true);
                break;
        }

        return $this->view()->fetch($this->path.'/templates/form113.html');
    }

    /**
     * Вспомогательный метод для печати формы с идентификатором 113.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params
     * @return string HTML-код формы
     */
    private function displayPrintForm7(waOrder $order, $params = array())
    {
        $strict = true;
        $side = waRequest::get('side', ($order ? '' : 'print'), waRequest::TYPE_STRING);
        switch ($side) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f7p.gif', $image_info)) {
                    $params = $order->params;

                    $rate_id = ifset($params['shipping_rate_id']);
                    if (strpos($rate_id, 'bookpost') === false) {
                        $this->printOnImage($image, 'X', 1390, 160, 80);
                    } else {
                        $this->printOnImage($image, 'X', 1390, 320, 80);
                    }

                    if (waRequest::get('inventory')) {
                        $this->printOnImage($image, 'X', 2310, 310, 35);
                    }

                    switch (waRequest::get('notify_type', 'simple')) {
                        case 'paid':
                            $this->printOnImage($image, 'X', 2310, 460, 35);
                            break;
                        case 'simple':
                            $this->printOnImage($image, 'X', 2310, 385, 35);
                            break;
                    }

                    #Отправитель
                    $size = 60;
                    $name = waRequest::get('sender_name', $this->company_name.' '.$this->company_name2);
                    $names = $this->adjustSizes($name, array(45), $size);
                    $this->printOnImage($image, $names[0], 450, 1050 + (35 - $size), $size);

                    $size = 60;

                    $full_address = waRequest::get('sender_address', $this->address1.' '.$this->address2);
                    $address = $this->adjustSizes($full_address, array(47, 47, 47), $size);
                    $this->printOnImage($image, $address[0], 330, 1440 + (35 - $size), $size);
                    $this->printOnImage($image, $address[1], 330, 1560 + (35 - $size), $size);
                    $this->printOnImage($image, $address[2], 330, 1680 + (35 - $size), $size);

                    #SMS notice
                    $phone = preg_replace('@\D+@', '', $this->phone);
                    if (waRequest::get('shipping_sms') && preg_match('@^(7|8)(\d{3})(\d{7})$@', $phone, $matches)) {
                        $this->printOnImagePersign($image, $matches[2], 480, 1810, 103, 80);
                        $this->printOnImagePersign($image, $matches[3], 810, 1810, 103, 80);

                        $this->printOnImage($image, 'X', 385, 1930, 35);
                    }


                    $this->printOnImagePersign($image, waRequest::get('sender_zip', $this->zip), 1650, 1810, 103, 80);

                    #Получатель
                    $name = waRequest::get('shipping_name', $order->contact_name);
                    $size = 60;
                    $names = $this->adjustSizes($name, array(42), $size);
                    $this->printOnImage($image, $names[0], 2450, 1680 + (35 - $size), $size);

                    $full_address = waRequest::get('shipping_address', $this->buildAddress($order->shipping_address));

                    $size = 60;
                    $sizes = array(57, 57, 57);

                    $address = $this->adjustSizes($full_address, $sizes, $size);
                    $this->printOnImage($image, $address[0], 2300, 2040 + (35 - $size), $size);
                    $this->printOnImage($image, $address[1], 2300, 2160 + (35 - $size), $size);
                    $this->printOnImage($image, $address[2], 2300, 2280 + (35 - $size), $size);

                    #SMS notice
                    if (waRequest::get('arrival_notice') && ($phone = $order->getContactField('phone'))) {
                        if (preg_match('@^(\+?7|8)(\d{3})(\d{7})$@', $phone, $matches)) {
                            $this->printOnImagePersign($image, $matches[2], 2445, 2435, 103, 80);
                            $this->printOnImagePersign($image, $matches[3], 2775, 2435, 103, 80);

                            $this->printOnImage($image, 'X', 2355, 2555, 35);
                        }
                    }

                    $zip = waRequest::get('shipping_zip', $order->shipping_address['zip']);
                    $this->printOnImagePersign($image, $zip, 3615, 2435, 103, 80);
                    $order_price_d = self::floatval(waRequest::get('order_price_d', floor($order->total - $order->shipping)));
                    if ($order_price_d && waRequest::get('order_price_checked')) {
                        $this->printOnImage($image, 'X', 2310, 160, 35);
                        $order_price = waRequest::get('order_price', waCurrency::format('%.W', floor($order->total - $order->shipping), $order->currency));

                        $size = 60;
                        $prices = $this->adjustSizes(sprintf('%s (%s) руб.', $order_price_d, $order_price), array(47), $size);
                        $this->printOnImage($image, reset($prices), 2300, 1000 + (35 - $size), $size);
                    }

                    #
                    $order_amount_d = self::floatval(waRequest::get('order_amount_d', $order->total));
                    if ($order_amount_d && waRequest::get('order_amount_checked')) {
                        $this->printOnImage($image, 'X', 2310, 235, 35);
                        $order_amount = waRequest::get('order_amount', waCurrency::format('%.W', $order->total, $order->currency));

                        $order_amount_f = ($order_amount_d - floor($order_amount_d)) * 100;
                        $size = 60;
                        $prices = $this->adjustSizes(sprintf('%s (%s) руб. %02d коп.', floor($order_amount_d), $order_amount, $order_amount_f), array(47), $size);
                        $this->printOnImage($image, reset($prices), 2300, 1250 + (35 - $size), $size);
                    }

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            default:
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }

                $request = waRequest::request();
                $this->view()->assign('src', http_build_query(array_merge($request, array('side' => 'front'))));
                $this->view()->assign('order', $order);
                $this->view()->assign('shipping_name', $order->contact_lastname.' '.$order->contact_firstname.' '.$order->contact_middlename);
                $this->view()->assign('shipping_address', $this->buildAddress($order->shipping_address));
                $this->view()->assign('editable', waRequest::post() || waRequest::get('mass_print') ? false : true);
                $this->view()->assign('settings', $this->getSettings());
                break;
        }

        return $this->view()->fetch($this->path.'/templates/form7.html');
    }

    /**
     * Вспомогательный метод для печати формы с идентификатором 112.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params
     * @return string HTML-код формы
     */
    private function displayPrintForm112(waOrder $order, $params = array())
    {
        $strict = true;
        $request = waRequest::request();

        $order['rub'] = intval(waRequest::request('rub', round(floor($order->total))));
        $order['cop'] = min(99, max(0, intval(waRequest::request('cop', round($order->total * 100 - $order['rub'] * 100)))));

        switch ($side = waRequest::get('side', ($order ? (waRequest::get('mass_print') ? 'print' : '') : 'print'), waRequest::TYPE_STRING)) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f112ep_front.gif', $image_info)) {
                    $format = '%.W{n0} %.2{f0}';

                    if (waRequest::request('payment')) {
                        $this->printOnImage($image, 'X', 90, 750, 80);
                    }

                    if ($this->zip_distribution_center) {
                        $zip = $this->zip_distribution_center;
                    } else {
                        $zip = $this->zip;
                    }

                    $this->printOnImage($image, sprintf('%d', $order['rub']), 110, 680);
                    $this->printOnImage($image, sprintf('%02d', $order['cop']), 430, 680);
                    $this->printOnImage($image, waRequest::request('order_amount', waCurrency::format($format, $order->total, $order->currency)), 650, 620, 30);
                    $this->printOnImage($image, $this->company_name, 210, 875);
                    $this->printOnImage($image, $this->address1, 210, 957);
                    $this->printOnImage($image, $this->address2, 70, 1040);
                    $this->printOnImagePersign($image, $zip, 1965, 1020, 58.3, 50);

                    $this->printOnImagePersign($image, $this->inn, 227, 1330, 55.5, 45);

                    $this->printOnImagePersign($image, $this->bank_kor_number, 1207, 1330, 55.5, 45);
                    $this->printOnImage($image, $this->bank_name, 570, 1405);
                    $this->printOnImagePersign($image, $this->bank_account_number, 310, 1470, 55.5, 45);
                    $this->printOnImagePersign($image, $this->bik, 1815, 1470, 55.5, 45);


                    $this->printOnImage($image, waRequest::request('billing_name', $order->contact_name), 310, 1550);


                    $billing_address = $order->billing_address;
                    if (!$this->buildAddress($billing_address)) {
                        $billing_address = $order->shipping_address;
                    }

                    $full_address = waRequest::request('billing_address', $this->buildAddress($billing_address));

                    $size = 35;
                    $sizes = array(60, 65);
                    $address = $this->adjustSizes($full_address, $sizes, $size);
                    $this->printOnImagePersign($image, $billing_address['zip'], 1965, 1690, 58.3, 50);

                    $this->printOnImage($image, $address[0], 520, 1635 + 35 - $size, $size);
                    $this->printOnImage($image, $address[1], 70, 1720 + 35 - $size, $size);

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'print':
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', false);
                $this->view()->assign(
                    array(
                        'src_front' => http_build_query(array_merge($request, array('side' => 'front'))),
                        'src_back'  => http_build_query(array_merge($request, array('side' => 'back'))),
                    )
                );
                break;
            default:
                $this->view()->assign(
                    array(
                        'src_front' => http_build_query(array_merge($request, array('side' => 'front'))),
                        'src_back'  => http_build_query(array_merge($request, array('side' => 'back'))),
                    )
                );
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('order', $order);
                $billing_address = $this->buildAddress($order->billing_address);
                if (empty($billing_address)) {
                    $billing_address = $this->buildAddress($order->shipping_address);
                }
                $this->view()->assign('billing_address', $billing_address);
                $this->view()->assign('editable', waRequest::post() ? false : true);
                break;
        }

        return $this->view()->fetch($this->path.'/templates/form112.html');
    }

    /**
     * Кеширование экземпляра класса шаблонизатора (Smarty) для многократного использования.
     */
    private function view()
    {
        static $view;
        if (!$view) {
            $view = wa()->getView();
        }
        return $view;
    }

    private function buildAddress($address)
    {
        $address_chunks = array(
            $address['street'],
            $address['city'],
            $address['region_name'],
            ($address['country'] != 'rus') ? $address['country_name'] : '',
        );

        return implode(', ', array_filter(array_map('trim', $address_chunks), 'strlen'));
    }

    /**
     * Разбиение адреса получателя на подстроки длиной от 25 до 40 символов для удобного отображения на печатной форме.
     *
     * @param string $address Массив, содержащий информацию об адресе
     * @param int[] $sizes
     * @return array
     */
    private function splitAddress($address, $sizes = array())
    {
        $address_chunks = array_filter(preg_split('@\s+@', trim($address)), 'strlen');

        $address = array_fill_keys(array_keys($sizes), '');

        foreach ($address as $n => &$item) {
            $next = reset($address_chunks);
            $next_size = mb_strlen($next);
            while ($next_size && (mb_strlen($item) + $next_size + 1) <= $sizes[$n]) {
                $item .= ' '.array_shift($address_chunks);
                $next = reset($address_chunks);
                $next_size = mb_strlen($next);
            }
            unset($item);
        }

        return $address_chunks ? false : $address;
    }

    private function adjustSizes($full_address, $sizes, &$size)
    {
        if (empty($size)) {
            $size = 35;
        }
        do {
            $address = $this->splitAddress($full_address, $sizes);
            if ($address === false) {
                $k = $size > 10 ? 1.1 : 2;
                foreach ($sizes as &$_size) {
                    $_size = round($_size * $k);
                }
                unset($_size);
                $size = max(1, round($size / $k));
            }
        } while ($address === false);
        return $address;
    }

    /**
     * Вспомогательный метод для печати формы с идентификатором 116.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params
     * @return string HTML-код формы
     */
    private function displayPrintForm116(waOrder $order, $params = array())
    {
        $strict = true;
        $request = waRequest::request();
        switch ($side = waRequest::get('side', ($order ? (waRequest::get('mass_print') ? 'print' : '') : 'print'), waRequest::TYPE_STRING)) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f116.gif', $image_info)) {
                    #customer info
                    $shipping_name = waRequest::request('shipping_name', $order->contact_name);
                    $shipping_address = waRequest::request('shipping_address', $this->buildAddress($order->shipping_address));
                    $shipping_zip = waRequest::request('shipping_zip', $order->shipping_address['zip']);

                    $price = self::floatval(waRequest::request('order_price_d', $order->total));
                    $amount = self::floatval(waRequest::request('order_amount_d', $order->total));

                    $full_format = '%0i (%.W) руб. %.2 коп.';

                    if (!empty($price)) {
                        $blocks = array(
                            array(294, 940, 55,),
                        );
                        $this->printOnImageBlock($image, waCurrency::format($full_format, $price, $order->currency), $blocks, 45);
                    }
                    if (!empty($amount)) {
                        $blocks = array(
                            array(294, 1140, 55,),
                        );
                        $this->printOnImageBlock($image, waCurrency::format($full_format, $amount, $order->currency), $blocks, 45);
                    }

                    #customer
                    $this->printOnImage($image, $shipping_name, 600, 1350, 45);
                    $blocks = array(
                        array(600, 1610, 70,),
                        array(300, 1720, 60,),
                    );
                    $this->printOnImageBlock($image, $shipping_address, $blocks, 50);
                    $this->printOnImagePersign($image, $shipping_zip, 2330, 1680, 122, 80);

                    #company
                    $this->printOnImage($image, $this->company_name, 620, 1830, 45);
                    $blocks = array(
                        array(600, 1970, 70,),
                        array(300, 2100, 55,),
                    );
                    $this->printOnImageBlock($image, $this->address1.' '.$this->address2, $blocks, 50);
                    $this->printOnImagePersign($image, $this->zip, 2330, 2050, 122, 80);

                    #additional
                    if (!empty($price)) {
                        $this->printOnImage($image, waCurrency::format('%2', $price, $order->currency), 800, 3670, 50);
                    }
                    if (!empty($amount)) {
                        $this->printOnImage($image, waCurrency::format('%2', $amount, $order->currency), 2200, 3670, 50);
                    }

                    $this->printOnImage($image, $shipping_name, 620, 3850, 45);


                    $blocks = array(
                        array(620, 3950, 70,),
                        array(320, 4100, 55,),
                    );
                    $this->printOnImageBlock($image, $shipping_address, $blocks, 50);

                    $this->printOnImagePersign($image, $shipping_zip, 2280, 4050, 122, 80);

                    #document
                    $this->printOnImage($image, waRequest::request('document', $this->document), 800, 2390, 60);
                    $this->printOnImage($image, waRequest::request('document_series', $this->document_series), 1550, 2390, 60);
                    $this->printOnImage($image, waRequest::request('document_number', $this->document_number), 1950, 2390, 60);

                    $dd_mm = waRequest::request('document_issued_day', $this->document_issued_day);
                    $dd_mm .= '.';
                    $dd_mm .= waRequest::request('document_issued_month', $this->document_issued_month);
                    $this->printOnImage($image, $dd_mm, 2530, 2390, 60);
                    $this->printOnImage($image, waRequest::request('document_issued_year', $this->document_issued_year), 2840, 2390, 60);

                    $document_issued = waRequest::request('document_issued', $this->document_issued);
                    $blocks = array(
                        array(300, 2520, 80,),
                    );
                    $this->printOnImageBlock($image, $document_issued, $blocks, 50);

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'print':
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', false);
                $this->view()->assign('order', $order);
                $this->view()->assign('src_front', http_build_query(array_merge($request, array('side' => 'front'))));
                break;
            default:
                $this->view()->assign('src_front', http_build_query(array_merge($request, array('side' => 'front'))));

                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', waRequest::post() ? false : true);
                $this->view()->assign('order', $order);
                $this->view()->assign('address', $this->buildAddress($order->shipping_address));
                $this->view()->assign('settings', $this->getSettings());
                break;
        }
        return $this->view()->fetch($this->path.'/templates/form116.html');
    }

    /**
     * Вспомогательный метод для печати формы с идентификатором 116.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @param array $params
     * @return string HTML-код формы
     */
    private function displayPrintForm107(waOrder $order, $params = array())
    {
        $strict = true;
        $request = waRequest::request();
        $side = waRequest::get('side', ($order ? (waRequest::get('mass_print') ? 'print' : '') : 'print'), waRequest::TYPE_STRING);
        switch ($side) {
            case 'front':
                $offsets = array(0, 3480);
                $image_info = null;
                $page = waRequest::get('page', 0, waRequest::TYPE_INT);
                if ($image = $this->read('f107.gif', $image_info)) {
                    #company
                    $items = $order->items;
                    $post_items = waRequest::request('item', array());
                    foreach ($offsets as $offset) {
                        $blocks = array(
                            array(270 + $offset, 3030, 55,),
                            array(270 + $offset, 3135, 55,),
                        );
                        $this->printOnImageBlock($image, $this->company_name, $blocks, 50);

                        $total = 0;

                        for ($i = 0; $i < 14; $i++) {
                            if (isset($items[$i + $page * 14])) {
                                $item = $items[$i + $page * 14];
                                $edited_item = ifset($post_items[$i + $page * 14], array());

                                $y = 1010 + round(120.5 * $i);
                                $this->printOnImage($image, $page * 14 + $i + 1, 290 + $offset, $y, 50);
                                $item['quantity'] = intval(ifset($edited_item['quantity'], $item['quantity']));
                                $item['price'] = $item['price'] * $item['quantity'];
                                $item['price'] = self::floatval(ifset($edited_item['price'], $item['price']));
                                if (!empty($item['price'])) {
                                    $total += $item['price'];
                                    $price = waCurrency::format('%2', $item['price'], $order->currency);

                                    $this->printOnImage($image, $price, 2360 + $offset, $y, 50);
                                }

                                $this->printOnImage($image, $item['quantity'], 2010 + $offset, $y, 50);

                                $blocks = array(
                                    array(500 + $offset, $y, 42,),
                                );

                                $this->printOnImageBlock($image, ifset($edited_item['name'], $item['name']), $blocks, 50);

                            } else {
                                break;
                            }
                        }

                        $total = 0;

                        if ($i + $page * 14 >= count($items)) {
                            foreach ($items as $id => $item) {
                                $edited_item = ifset($post_items[$id], array());

                                $item['quantity'] = intval(ifset($edited_item['quantity'], $item['quantity']));
                                $item['price'] = $item['price'] * $item['quantity'];
                                $item['price'] = self::floatval(ifset($edited_item['price'], $item['price']));
                                if (!empty($item['price'])) {
                                    $total += $item['price'];

                                }
                            }
                            $this->printOnImage($image, $order->getTotalQuantity(), 2010 + $offset, 2720, 50);
                            $total = waCurrency::format('%2', $total, $order->currency);
                            $this->printOnImage($image, $total, 2360 + $offset, 2720, 50);
                        }
                    }

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'print':
                $pages = array();
                $count = ceil(count($order->items) / 14);
                for ($page = 0; $page < $count; $page++) {
                    $pages[] = http_build_query(array_merge($request, array('side' => 'front', 'page' => $page)));
                }
                $this->view()->assign('pages', $pages);

                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', false);
                $this->view()->assign('order', $order);
                break;
            default:
                $pages = array();
                $count = ceil(count($order->items) / 14);
                for ($page = 0; $page < $count; $page++) {
                    $pages[] = http_build_query(array_merge($request, array('side' => 'front', 'page' => $page)));
                }
                $this->view()->assign('pages', $pages);

                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', waRequest::post() ? false : true);
                $this->view()->assign('order', $order);
                $this->view()->assign('address', $this->buildAddress($order->shipping_address));
                $this->view()->assign('settings', $this->getSettings());
                break;
        }
        return $this->view()->fetch($this->path.'/templates/form107.html');
    }

    /**
     * Возвращает информацию о статусе отправления (HTML).
     *
     * @param string $tracking_id Необязательный идентификатор отправления, указанный пользователем
     * @return string
     * @see waShipping::tracking()
     * @example return _wp('Online shipment tracking: <a href="link">link</a>.');
     */
    public function tracking($tracking_id = null)
    {
        $template = 'Отслеживание отправления вручную: <a href="https://pochta.ru/tracking#%1$s" target="_blank">https://pochta.ru/tracking#%1$s</a>';
        if ($tracking_id) {
            $template = sprintf($template/*.' <b>%1$s</b>'*/, htmlentities($tracking_id, ENT_NOQUOTES, 'utf-8'));
            if (class_exists('SoapClient') && $this->api_login && $this->api_password) {
                $timeout = ini_get('default_socket_timeout');
                @ini_set('default_socket_timeout', 15);
                try {
                    $status = $this->trackingStatus($tracking_id);
                    if (!empty($status['message'])) {
                        $template = $status['message'];
                    }
                    if (!empty($status['error'])) {
                        $template .= $status['error'];
                    }
                } catch (SoapFault $ex) {
                    $message = array(
                        'tracking_id' => $tracking_id,
                        'code'        => $ex->getCode(),
                        'error'       => $ex->getMessage(),
                    );
                    waLog::log(var_export($message, true), 'wa-plugins/shipping/russianpost/soap.error.log');
                    $template .= "Произошла ошибка при обращении к API «Почты России»";
                } catch (Exception $ex) {
                    $template .= $ex->getMessage();
                }
                @ini_set('default_socket_timeout', $timeout);
            }
        } else {
            $template = sprintf($template, '');
        }
        return $template;
    }

    private function getTrackingData($tracking_id)
    {
        $valid = false;
        if (preg_match('@^\d{14}$@', $tracking_id)) {
            $valid = true;
        } elseif (preg_match('@^\w{2}\d{9}\w{2}$@', $tracking_id)) {
            $valid = true;
        }

        if (!$valid) {
            throw new waException('Неверный формат идентификатора отправления');
        }

        $options = array(
            'soap_version' => SOAP_1_2,
        );


        $params = array(
            'OperationHistoryRequest' => array(
                'Barcode'     => $tracking_id,
                'MessageType' => '0',
                'Language'    => 'RUS',
            ),
            'AuthorizationHeader'     => array(
                'login'    => $this->api_login,
                'password' => $this->api_password,
            ),
        );

        $wrappers = stream_get_wrappers();

        $protocol = parse_url($this->wsdl, PHP_URL_SCHEME);
        if (!in_array($protocol, $wrappers, true)) {
            $this->wsdl = preg_replace('@^https://@', 'http://', $this->wsdl);
            $protocol = parse_url($this->wsdl, PHP_URL_SCHEME);
        } else {
            $options['context'] = stream_context_create(array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ),
            ));
        }

        if (!in_array($protocol, $wrappers, true)) {
            $error = 'В системе не найдено адаптеров для '.$protocol;
        } else {
            $client = new SoapClient($this->wsdl, $options);
            $param = new SoapParam($params, 'OperationHistoryRequest');
            $result = $client->getOperationHistory($param);
            if (empty($result)) {
                $error = $client->getError();
            } elseif (!empty($result->AuthorizationFault)) {
                $error = (string)$result->AuthorizationFault;
            } elseif (!empty($result->OperationHistoryFault)) {
                $error = (string)$result->OperationHistoryFault;
            } elseif (!empty($result->LanguageFault)) {
                $error = (string)$result->LanguageFault;
            } else {
                $timestamp = time();
                if (!empty($result->OperationHistoryData->historyRecord)) {

                    //If there is only one step in the array
                    if (isset($result->OperationParameters->historyRecord->OperationParameters)) {
                        $history[0] = $result->OperationHistoryData->historyRecord;
                    } else {
                        $history = $result->OperationHistoryData->historyRecord;
                    }

                    $rows = array();
                    foreach ($history as $record) {
                        $row = array(
                            'operation' => (string)ifempty($record->OperationParameters->OperType->Name),
                            'date'      => (string)ifempty($record->OperationParameters->OperDate),
                            'post_zip'  => (string)ifempty($record->AddressParameters->OperationAddress->Index),
                            'post_name' => (string)ifempty($record->AddressParameters->OperationAddress->Description),
                            'type'      => (string)ifempty($record->OperationParameters->OperAttr->Name),
                            'weight'    => (int)ifempty($record->ItemParameters->Mass),
                            'payment'   => (int)ifempty($record->FinanceParameters->Payment),
                            'value'     => (int)ifempty($record->FinanceParameters->Value),
                            'zip'       => (string)ifempty($record->AddressParameters->DestinationAddress->Index),
                            'address'   => (string)ifempty($record->AddressParameters->DestinationAddress->Description),
                            'recipient' => (string)ifempty($record->UserParameters->Rcpn),
                        );
                        $rows[] = $row;
                    };
                }
            }
        }

        if (!empty($error)) {
            waLog::log($error, 'wa-plugins/shipping/russianpost/track.error.log');
        }

        return compact('rows', 'error', 'timestamp');
    }

    private function trackingStatus($tracking_id)
    {
        $keep_interval = 72;
        $refresh_interval = 3;

        $message = '';
        $error = '';
        $cache = new waSerializeCache('tracking.'.urlencode($tracking_id), $keep_interval * 3600, 'wa-plugins/shipping/russianpost');
        try {
            if (!($data = $cache->get())) {
                $data = $this->getTrackingData($tracking_id);
                if (!empty($data['timestamp'])) {
                    $cache->set($data);
                } else {
                    $error = htmlentities($data['error'], ENT_NOQUOTES, 'utf-8');
                }
            } elseif (($data['timestamp'] + $refresh_interval * 3600) < time()) {
                $new_data = $this->getTrackingData($tracking_id);
                if ($new_data) {
                    if (!empty($new_data['timestamp'])) {
                        $data = $new_data;
                        $cache->set($data);
                    } else {
                        $error = htmlentities($new_data['error'], ENT_NOQUOTES, 'utf-8');
                    }

                }
            }
        } catch (Exception $ex) {
            $error = htmlentities($ex->getMessage(), ENT_NOQUOTES, 'utf-8');
        }

        if (!empty($data['rows'])) {

            $generic = array(
                'address',
                'weight',
                'payment',
                'value',
                'recipient',
                'zip',
            );
            $generic = array_fill_keys($generic, '-');

            foreach ($data['rows'] as &$row) {
                if (!empty($row['date'])) {
                    $row['date'] = strtotime($row['date']);
                    $row['datetime'] = wa_date('humandatetime', $row['date']);
                    $row['date'] = wa_date('humandate', $row['date']);
                }
                if (!empty($row['payment'])) {
                    $row['payment'] = wa_currency($row['payment'] / 100, 'RUB');
                }
                if (!empty($row['value'])) {
                    $row['value'] = wa_currency($row['value'] / 100, 'RUB');
                }
                if (!empty($row['weight'])) {
                    $row['weight'] = sprintf('%0.2f', $row['weight'] / 1000);
                }
                foreach ($row as $field => &$cell) {
                    $cell = htmlentities($cell, ENT_QUOTES, 'utf-8');
                    unset($cell);
                }
                foreach ($generic as $field => &$value) {
                    if (!empty($row[$field])) {
                        $value = $row[$field];
                    }
                    unset($value);
                }
                unset($row);
            }

            if ($error) {
                $error = sprintf('<br/>При автоматическом обновлении истории отправления произошла ошибка: <span class="errormsg">%s</span>', $error);
            }

            $message = $this->getStatusHtml($tracking_id, $data, $generic, $error);

        } else {
            if ($error) {
                $error = sprintf('<br/>Ошибка обмена данными с сервисом «Почты России» для автоматического отслеживания отправления: <span class="errormsg">%s</span>', $error);
            }
        }
        return compact('message', 'error');
    }

    private function getStatusHtml($tracking_id, $data, $generic, &$error)
    {
        $status = '';
        if ($row = end($data['rows'])) {
            $status = <<<HTML
Текущий статус: <strong>{$row['operation']} {$row['datetime']}</strong>, место: {$row['post_zip']} {$row['post_name']}
HTML;
        }

        $updated = wa_date('humandatetime', $data['timestamp']);

        $table = <<<HTML

HTML;

        $table .= <<<HTML
<table class="zebra table" style="white-space: nowrap;">
<thead>
<tr>
    <th>Операция</th>
    <th>Дата</th>
    <th title="Место проведения операции">Место</th>
</tr>
</thead>
HTML;
        $table .= '<tbody>';

        foreach ($data['rows'] as $row) {
            $table .= <<<HTML
<tr>
    <td>{$row['operation']}<br/><span class="hint">{$row['type']}</span></td>
    <td>{$row['datetime']}</td>
    <td>{$row['post_name']}<br/><span class="hint">{$row['post_zip']}</span></td>
</tr>
HTML;
        }


        $table .= <<<HTML
</tbody></table>
HTML;

        $table_frontend = <<<HTML

HTML;

        $table_frontend .= <<<HTML
<table class="zebra table" style="white-space: nowrap;">
<thead>
<tr>
    <th class="align-right">Операция</th>
    <th class="align-right">Дата</th>
    <th class="align-right" title="Место проведения операции">Место</th>
</tr>
</thead>
HTML;
        $table_frontend .= '<tbody>';

        foreach ($data['rows'] as $row) {
            $table_frontend .= <<<HTML
<tr>
    <td class="align-right">{$row['operation']}<br/><span class="hint">{$row['type']}</span></td>
    <td class="align-right">{$row['datetime']}</td>
    <td class="align-right">{$row['post_name']}<br/><span class="hint">{$row['post_zip']}</span></td>
</tr>
HTML;
        }


        $table_frontend .= <<<HTML
</tbody></table>
HTML;


        $id = 'wa_plugins_shipping_russianpost_'.preg_replace('@([^a-z_0-9])@', '', $tracking_id);
        switch (wa()->getEnv()) {
            case 'backend':
                $html = <<<HTML
{$status} <a href="#" class="inline-link" id="{$id}_show" style="float: right; display: none">Подробнее...</a>

<script type="text/javascript">
(function () {
    $('#{$id}_dialog').hide();
    $('#{$id}_show').show();
    $('#{$id}_cancel').show();
    $('#{$id}_show').bind('click', function() {
        var dialog =  $('#{$id}_dialog');
        if (typeof dialog.waDialog == 'function') {
            if (dialog.length && dialog.parents('div').length) {
                $('#{$id}_dialog_').remove();
                dialog.appendTo('body');
                dialog.attr('id', dialog.attr('id')+'_');
            }
            $('#{$id}_dialog_').waDialog();
        } else {
            var a = $(this);
            if (dialog.length) {
                var table = dialog.find('.dialog-content-indent');
                a.replaceWith(table);
                dialog.remove();
            }

            $(this).hide();
        }
        return false;
    });
})();
</script>

<div class="dialog" id="{$id}_dialog">
    <div class="dialog-background">
    </div>
    <div class="dialog-window">
        <div class="dialog-content">
            <div class="dialog-content-indent fields">
                <h1>История отправления {$tracking_id}</h1>
                <div class="block fields">
                    <div class="field">
                        <div class="name">Адресовано:</div>
                        <div class="value">{$generic['zip']}, {$generic['address']}, {$generic['recipient']}</div>
                    </div>
                    <div class="field">
                        <div class="name">Объявленная ценность:</div>
                        <div class="value">{$generic['value']}</div>
                    </div>
                    <div class="field">
                        <div class="name">Наложенный платеж:</div>
                        <div class="value">{$generic['payment']}</div>
                    </div>
                    <div class="field">
                        <div class="name">Вес (кг):</div>
                        <div class="value">{$generic['weight']}</div>
                    </div>
                </div>

                {$table}
                <br/>
                <span class="hint">Дата запроса к сервису «Почты России» для отслеживания отправления: {$updated}</span>
                {$error}
            </div>
        </div>
        <div class="dialog-buttons">
            <div class="dialog-buttons-gradient">
                <a class="cancel button" id="{$id}_cancel" style="display: none" href="#">Закрыть</a>
            </div>
        </div>
    </div>
</div>
HTML;
                break;
            default:
                $html = <<<HTML
<span>{$status} <br/><a href="#" class="inline-link" id="{$id}_show" style="float: left">Показать всю историю отправления...</a><br/></span>
<div id="{$id}_full" style="display: none;">
Адресовано: {$generic['zip']}, {$generic['address']}, {$generic['recipient']}<br>
Объявленная ценность: {$generic['value']}<br>
Наложенный платеж: {$generic['payment']}<br>
Вес (кг): {$generic['weight']}<br>
{$table_frontend}
<span class="hint">Дата запроса к сервису «Почты России» для отслеживания отправления: {$updated}</span>
</div>


<script type="text/javascript">
(function () {
    $('#{$id}_show').bind('click', function() {
        var history =  $('#{$id}_full');
        var a = $(this).parent();
        if (history.length) {
            history.show();
            a.remove();
        }
        return false;
    });
})();
</script>
HTML;
                break;
        }
        $error = '';
        return $html;
    }

    /**
     * Возвращает ISO3-код валюты или массив кодов валют, которые поддерживает этот плагин.
     *
     * @return array|string
     * @see waShipping::allowedCurrency()
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * Возвращает обозначение единицы веса или массив единиц веса, которые поддерживает этот плагин.
     *
     * @return array|string
     * @see waShipping::allowedWeightUnit()
     */
    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function allowedLinearUnit()
    {
        return 'cm';
    }

    /**
     * Предварительная подготовка данных для сохранения настроек с помощью метода saveSettings() базового класса waSystemPlugin.
     *
     * @see waSystemPlugin::saveSettings()
     */
    public function saveSettings($settings = array())
    {
        $fields = array(
            'halfkilocost',
            'overhalfkilocost',
        );
        foreach ($fields as $field) {
            if (ifempty($settings[$field])) {
                foreach ($settings[$field] as & $value) {
                    if (strpos($value, ',') !== false) {
                        $value = str_replace(',', '.', $value);
                    }
                    $value = str_replace(',', '.', (double)$value);
                }
                unset($value);
            }
        }
        if ((ifset($settings['parcel'], 'none') == 'none') && (ifset($settings['bookpost'], 'none') == 'none')) {
            throw new waException('Выберите хотя бы один вид отправления: посылку или бандероль.');
        }
        return parent::saveSettings($settings);
    }

    private static function floatval($value)
    {
        return round(floatval(str_replace(array(',', ' '), array('.', ''), $value)), 2);
    }

    /**
     * Отображение указанного фрагмента текста на изображении печатной формы.
     *
     * @param resource $image Графический ресурс
     * @param string $text Текст
     * @param int $x Горизонтальная координата
     * @param int $y Вертикальная координата
     * @param int $font_size Размер шрифта
     */
    private function printOnImage(&$image, $text, $x, $y, $font_size = 35)
    {
        $y += $font_size;
        static $font_path = null;
        static $text_color = null;
        static $mode;
        static $convert = false;

        if (is_null($font_path)) {
            $font_path = $this->path.'/lib/config/data/arial.ttf';
            $font_path = (file_exists($font_path) && function_exists('imagettftext')) ? $font_path : false;
        }
        if (is_null($text_color)) {
            $text_color = ($this->color && false) ? imagecolorallocate($image, 32, 32, 96) : imagecolorallocate($image, 16, 16, 16);
        }

        if (empty($mode)) {
            if ($font_path) {
                $info = gd_info();
                if (!empty($info['JIS-mapped Japanese Font Support'])) {
                    //any2eucjp
                    $convert = true;
                }
                if (!empty($info['FreeType Support']) && version_compare(preg_replace('/[^0-9\.]/', '', $info['GD Version']), '2.0.1', '>=')) {
                    $mode = 'ftt';
                } else {
                    $mode = 'ttf';
                }
            } else {
                $mode = 'string';
            }
        }
        if ($convert) {
            $text = iconv('utf-8', 'EUC-JP', $text);
        }

        switch ($mode) {
            case 'ftt':
                imagefttext($image, $font_size, 0, $x, $y, $text_color, $font_path, $text);
                break;
            case 'ttf':
                imagettftext($image, $font_size, 0, $x, $y, $text_color, $font_path, $text);
                break;
            case 'string':
                imagestring($image, $font_size, $x, $y, $text, $text_color);
                break;
        }
    }

    /**
     * Посимвольное отображение указанного фрагмента текста на изображении печатной формы.
     *
     * @param resource $image Графический ресурс
     * @param string $text Текст
     * @param int $x Горизонтальная координата
     * @param int $y Вертикальная координата
     * @param int $cell_size Размер смещения вправо для отображения следующего символа
     * @param int $font_size Размер шрифта
     */
    private function printOnImagePersign(&$image, $text, $x, $y, $cell_size = 34, $font_size = 35)
    {
        $size = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $size; $i++) {
            $this->printOnImage($image, mb_substr($text, $i, 1, 'UTF-8'), $x, $y, $font_size);
            $x += $cell_size;
        }
    }

    private function printOnImageBlock(&$image, $string, $blocks, $size = 35)
    {
        $sizes = array();
        $strings = array();
        foreach ($blocks as $n => $block) {
            $sizes[] = end($block);
            if ($string === true) {
                $strings[$n] = str_repeat('1234 6789 ', max(round(end($block) / 10), 8));
            }
        }
        if (empty($strings)) {
            $strings = $this->adjustSizes($string, $sizes, $size);
        }
        foreach ($blocks as $n => $block) {
            $this->printOnImage($image, $strings[$n], $block[0], $block[1] + (35 - $size), $size);
        }
    }

    /**
     * Чтение содержимого графического файла.
     *
     * @param string $file Путь к файлу
     * @param array $info Массив информации об изображении
     * @return resource|bool В случае ошибки возвращает false
     */
    private function read($file, &$info)
    {
        if ($file) {
            $file = $this->path.'/lib/config/data/'.$file;
        }
        $info = @getimagesize($file);
        if (!$info) {
            return false;
        }
        switch ($info[2]) {
            case 1:
                // Create resource from gif image
                $srcIm = @imagecreatefromgif($file);
                break;
            case 2:
                // Create resource from jpg image
                $srcIm = @imagecreatefromjpeg($file);
                break;
            case 3:
                // Create resource from png image
                $srcIm = @imagecreatefrompng($file);
                break;
            case 5:
                // Create resource from psd image
                break;
            case 6:
                // Create resource from bmp image imagecreatefromwbmp
                $srcIm = @imagecreatefromwbmp($file);
                break;
            case 7:
                // Create resource from tiff image
                break;
            case 8:
                // Create resource from tiff image
                break;
            case 9:
                // Create resource from jpc image
                break;
            case 10:
                // Create resource from jp2 image
                break;
            default:
                break;
        }
        return empty($srcIm) ? false : $srcIm;
    }

    private function drawGrid(&$image, $image_info)
    {
        $width = $image_info[0];
        $height = $image_info[1];
        for ($x = 50; $x < $width; $x += 50) {
            for ($y = 50; $y < $height; $y += 1000) {
                if (($x % 1000) != 50) {
                    $this->printOnImage($image, $x / 10, $x, $y, 15);
                }
            }
        }

        for ($x = 50; $x < $width; $x += 1000) {
            for ($y = 50; $y < $height; $y += 50) {
                if (($y % 1000) != 50) {
                    $this->printOnImage($image, $y / 10, $x, $y, 15);
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isDifficult()
    {
        $difficult = include($this->path.'/lib/config/data/difficult.php');
        if (in_array($this->getAddress('zip'), $difficult)) {
            return true;
        } elseif (in_array(trim($this->getAddress('zip')), $difficult)) {
            return true;
        } else {
            return false;
        }
    }

    public function getTotalSize()
    {
        return parent::getTotalSize();
    }

    /**
     * Проверяет каждый товар на превышение размера
     *
     * @return bool
     */
    protected function isValidSize()
    {
        $is_valid = true;

        $max_side_length = $this->max_side_length;
        $max_volume = $this->max_volume;
        if (!empty($max_side_length) && !empty($max_volume)) {
            $items = $this->getItems();
            $total_size = $this->getTotalSize();

            if ($total_size === null) {
                foreach ($items as $item) {
                    $item_l = ifset($item, 'length', 0);
                    $item_w = ifset($item, 'width', 0);
                    $item_h = ifset($item, 'height', 0);

                    $item_volume = $item_l + $item_w + $item_h;

                    if ($item_volume > $max_volume
                        || $item_l > $max_side_length || $item_w > $max_side_length || $item_h > $max_side_length
                    ) {
                        $is_valid = false;
                        break;
                    }
                }
            } elseif (is_array($total_size)) {
                $total_volume = $total_size['height'] + $total_size['width'] + $total_size['length'];

                if ($total_volume > $max_volume
                    || $total_size['length'] > $max_side_length || $total_size['width'] > $max_side_length || $total_size['height'] > $max_side_length
                ) {
                    $is_valid = false;
                }
            } else {
                $is_valid = false;
            }
        }

        return $is_valid;
    }
}
