<?php

/**
 * Плагин расчета доставки Почтой России.
 *
 * @see http://www.russianpost.ru/rp/servise/ru/home/postuslug/bookpostandparcel/parcelltariff
 *
 * @property $region
 * @property $halfkilocost
 * @property $currency
 * @property $overhalfkilocost
 * @property $caution
 * @property $max_weight
 * @property $complex_calculation_weight
 * @property $commission
 *
 * @property string $company_name
 * @property string $address1
 * @property string $address2
 * @property string $zip
 * @property string $inn
 * @property string $bank_kor_number
 * @property string $bank_name
 * @property string $bank_account_number
 * @property string $bik
 * @property string $color
 */
class russianpostShipping extends waShipping
{

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
            ->registerControl('RegionRatesControl');
        parent::initControls();
    }

    /**
     * Формирует HTML-код пользовательского элемента управления с идентификатором 'WeightCosts'.
     *
     * @see waHtmlControl::getControl()
     * @param string $name Идентификатор элемента управления, указанный в файле настроек
     * @param array $params Параметры элемента управления, указанные в файле настроек
     * @return string HTML-код элемента управления
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
            $params['value'] = floatval(isset($costs[$zone]) ? $costs[$zone] : 0.0);
            $params['title'] = "Пояс {$zone}";
            $control .= waHtmlControl::getControl(waHtmlControl::INPUT, $zone, $params);
        }
        $control .= "</table>";

        return $control;
    }

    /**
     * Формирует HTML-код пользовательского элемента управления с идентификатором 'RegionRatesControl'.
     *
     * @see waHtmlControl::getControl()
     * @param string $name Идентификатор элемента управления
     * @param array $params Параметры элемента управления
     * @return string
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
            $control .= htmlentities('Распределите регионы по тарифным поясам Почты России', ENT_QUOTES, 'utf-8');
            $control .= "</th>";
            $control .= "<th>Только авиа</th>";
            $control .= "</th></tr></thead><tbody>";

            $params['control_wrapper'] = '<tr><td>%s</td><td>&rarr;</td><td>%s</td><td>%s</td></tr>';
            $params['title_wrapper'] = '%s';
            $params['description_wrapper'] = '%s';
            $params['options'] = array();
            $params['options'][0] = _wp('*** пояс не выбран ***');
            for ($region = 1; $region <= 5; $region++) {
                $params['options'][$region] = sprintf(_wp('Пояс %d'), $region);
            }
            $avia_params = $params;
            $avia_params['control_wrapper'] = '%2$s';
            $avia_params['description_wrapper'] = false;
            $avia_params['title_wrapper'] = false;

            foreach ($regions as $region) {
                $name = 'zone';
                $id = $region['code'];
                if (empty($params['value'][$id])) {
                    $params['value'][$id] = array();
                }
                $params['value'][$id] = array_merge(array($name => 0, 'avia_only' => false), $params['value'][$id]);
                $region_params = $params;

                waHtmlControl::addNamespace($region_params, $id);
                $avia_params = array(
                    'namespace'           => $region_params['namespace'],
                    'control_wrapper'     => '%2$s',
                    'description_wrapper' => false,
                    'title_wrapper'       => false,
                    'value'               => $params['value'][$id]['avia_only'],
                );
                $region_params['value'] = max(0, min(5, $params['value'][$id][$name]));

                $region_params['description'] = waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'avia_only', $avia_params);
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
     * @example <pre>return array(
     *   'country' => 'rus', // или array('rus', 'ukr')
     *   'region'  => '77',
     *   // или array('77', '50', '69', '40');
     *   // либо не указывайте элемент 'region', если вам не нужно ограничивать доставку отдельными регионами указанной страны
     * );</pre>
     * @return array Верните пустой array(), если необходимо разрешить доставку по любым адресам без ограничений
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
     * @see waShipping::requestedAddressFields()
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
     * );</pre>
     * @return array|bool Верните false, если плагин не длолжен запрашивать адрес доставки;
     * верните пустой array(), если все поля адреса должны запрашиваться у покупателя
     */
    public function requestedAddressFields()
    {
        return array(
            'zip'     => array(),
            'country' => array('hidden' => true, 'value' => 'rus', 'cost' => true),
            'region'  => array('cost' => true),
            'city'    => array(),
            'street'  => array(),
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

        $rate['ground'] = $halfkilocost[$zone] + $overhalfkilocost[$zone] * ceil((($weight < 0.5 ? 0.5 : $weight) - 0.5) / 0.5);

        $rate['air'] = $rate['ground'] + $this->getSettings('air');

        if ($this->getSettings('caution') || ($weight > $this->complex_calculation_weight)) {

            $rate['ground'] *= 1.3;
            $rate['air'] *= 1.3;
        }

        $rate['ground'] += $price * ($this->commission / 100);
        $rate['air'] += $price * ($this->commission / 100);
        return $rate;
    }

    /**
     * Основной метод расчета стоимости доставки.
     * Возвращает массив предварительно рассчитанной стоимости и сроков доступных вариантов доставки,
     * либо сообщение об ошибке для отображения покупателю,
     * либо false, если способ доставки в текущих условиях не дложен быть доступен.
     *
     *
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
     * @return mixed
     */
    protected function calculate()
    {
        $weight = $this->getTotalWeight();
        if ($weight > $this->max_weight) {
            $services = sprintf("Вес отправления (%0.2f) превышает максимально допустимый (%0.2f)", $weight, $this->max_weight);
        } else {
            $region_id = $this->getAddress('region');
            if ($region_id) {
                if (!empty($this->region[$region_id]) && !empty($this->region[$region_id]['zone'])) {
                    $services = array();

                    $delivery_date = waDateTime::format('humandate', strtotime('+1 week')).' — '.waDateTime::format('humandate', strtotime('+2 week'));

                    $rate = $this->getZoneRates($weight, $this->getTotalPrice(), $this->region[$region_id]['zone']);
                    if (empty($this->region[$region_id]['avia_only'])) {
                        $services['ground'] = array(
                            'name'         => 'Наземный транспорт',
                            'id'           => 'ground',
                            'est_delivery' => $delivery_date,
                            'rate'         => $rate['ground'],
                            'currency'     => 'RUB',
                        );
                    }
                    $services['avia'] = array(
                        'name'         => 'Авиа',
                        'id'           => 'avia',
                        'est_delivery' => $delivery_date,
                        'rate'         => $rate['air'],
                        'currency'     => 'RUB',
                    );
                } else {
                    $services = false;
                }
            } else {
                $services = array(
                    array('rate' => null, 'comment' => 'Для расчета стоимости доставки укажите регион доставки')
                );
            }
        }
        return $services;
    }

    /**
     * Возвращает массив с информацией о печатных формах, формируемых плагином.
     *
     * @example return <pre>array(
     *    'form_id' => array(
     *        'name' => _wp('Printform name'),
     *        'description' => _wp('Printform description'),
     *    ),
     * );</pre>
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @return array
     */
    public function getPrintForms(waOrder $order = null)
    {
        return extension_loaded('gd') ? array(
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
     * @throws waException
     * @return string HTML-код формы
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

        switch ($side = waRequest::get('side', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
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
                    $this->printOnImage($image, $this->address1, 915, 910);
                    $this->printOnImage($image, $this->address2, 824, 975);
                    $this->printOnImagePersign($image, $this->zip, 1985, 1065, 34, 35);
                    $this->printOnImagePersign($image, $this->inn, 920, 1135, 34, 35);
                    $this->printOnImagePersign($image, $this->bank_kor_number, 1510, 1135, 34, 35);
                    $this->printOnImage($image, $this->bank_name, 1160, 1194);
                    $this->printOnImagePersign($image, $this->bank_account_number, 1018, 1250, 34, 35);
                    $this->printOnImagePersign($image, $this->bik, 1885, 1250, 34, 35);

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
                $this->view()->assign('editable', waRequest::post() ? false : true);
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

        switch ($side = waRequest::get('side', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f112ep_front.gif', $image_info)) {
                    $format = '%.W{n0} %.2{f0}';

                    $this->printOnImage($image, sprintf('%d', $order['rub']), 110, 680);
                    $this->printOnImage($image, sprintf('%02d', $order['cop']), 430, 680);
                    $this->printOnImage($image, waRequest::request('order_amount', waCurrency::format($format, $order->total, $order->currency)), 650, 620, 30);
                    $this->printOnImage($image, $this->company_name, 210, 875);
                    $this->printOnImage($image, $this->address1, 210, 957);
                    $this->printOnImage($image, $this->address2, 70, 1040);
                    $this->printOnImagePersign($image, $this->zip, 1965, 1020, 58.3, 50);

                    $this->printOnImagePersign($image, $this->inn, 227, 1330, 55.5, 45);

                    $this->printOnImagePersign($image, $this->bank_kor_number, 1207, 1330, 55.5, 45);
                    $this->printOnImage($image, $this->bank_name, 570, 1405);
                    $this->printOnImagePersign($image, $this->bank_account_number, 310, 1470, 55.5, 45);
                    $this->printOnImagePersign($image, $this->bik, 1815, 1470, 55.5, 45);

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'print':
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', waRequest::post() ? false : true);
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

    /**
     * Разбиение адреса получателя на подстроки длиной от 25 до 40 символов для удобного отображения на печатной форме.
     *
     * @param waOrder $order Объект, содержащий информацию о заказе
     * @return array
     */
    private function splitAddress(waOrder $order)
    {
        $address_chunks = array(
            $order->shipping_address['street'],
            $order->shipping_address['city'],
            $order->shipping_address['region_name'],
            ($order->shipping_address['country'] != 'rus') ? $order->shipping_address['country_name'] : '',
        );
        $address_chunks = array_filter($address_chunks, 'strlen');
        $address = array(implode(', ', $address_chunks), '');
        if (preg_match('/^(.{25,40})[,\s]+(.+)$/u', $address[0], $matches)) {

            array_shift($matches);
            $matches[0] = rtrim($matches[0], ', ');
            $address = $matches;
        }
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
        switch ($side = waRequest::get('side', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
            case 'front':
                $image_info = null;
                if ($image = $this->read('f116_front.gif', $image_info)) {
                    $address = $this->splitAddress($order);
                    $this->printOnImage($image, waRequest::request('order_amount', $order->total), 294, 845, 24);
                    $this->printOnImage($image, waRequest::request('order_price', $order->total), 294, 747, 24);
                    //customer
                    $this->printOnImage($image, waRequest::request('shipping_name', $order->contact_name), 390, 915);
                    $this->printOnImage($image, waRequest::request('shipping_address_1', $address[0]), 390, 975);
                    $this->printOnImage($image, waRequest::request('shipping_address_2', $address[1]), 300, 1040);
                    $this->printOnImagePersign($image, waRequest::request('shipping_zip', $order->shipping_address['zip']), 860, 1105, 55, 35);

                    //company
                    $this->printOnImage($image, $this->company_name, 420, 1170);
                    $this->printOnImage($image, $this->address1, 400, 1237);
                    $this->printOnImage($image, $this->address2, 300, 1304);
                    $this->printOnImagePersign($image, $this->zip, 1230, 1304, 55, 35);

                    //additional
                    $this->printOnImage($image, waRequest::request('order_price_d', waCurrency::format('%2', $order->total, $order->currency)), 590, 2003);
                    $this->printOnImage($image, waRequest::request('order_amount_d', waCurrency::format('%2', $order->total, $order->currency)), 1280, 2003);

                    $this->printOnImage($image, waRequest::request('shipping_name', $order->contact_name), 390, 2085);

                    $this->printOnImage($image, waRequest::request('shipping_address_1', $address[0]), 390, 2170);
                    $this->printOnImage($image, waRequest::request('shipping_address_2', $address[1]), 300, 2260);

                    $this->printOnImagePersign($image, waRequest::request('shipping_zip', $order->shipping_address['zip']), 1230, 2260, 55, 35);

                    header("Content-type: image/gif");
                    imagegif($image);
                    exit;
                }
                break;
            case 'back':
                $image_info = null;

                if ($image = $this->read('f116_back.gif', $image_info)) {
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
                $this->view()->assign('editable', waRequest::post() ? false : true);
                $this->view()->assign('order', $order);
                $this->view()->assign('address', $this->splitAddress($order));
                break;
        }
        return $this->view()->fetch($this->path.'/templates/form116.html');
    }

    /**
     * Возвращает информацию о статусе отправления (HTML).
     *
     * @see waShipping::tracking()
     * @example return _wp('Online shipment tracking: <a href="link">link</a>.');
     * @param string $tracking_id Необязательный идентификатор отправления, указанный пользователем
     * @return string
     */
    public function tracking($tracking_id = null)
    {
        $template = 'Отслеживание отправления: <a href="https://pochta.ru/tracking" target="_blank">https://pochta.ru/tracking</a>';
        if($tracking_id){
            $template .= sprintf(' <b>%s</b>',htmlentities($tracking_id,ENT_NOQUOTES,'utf-8'));
        }
        return $template;
    }

    /**
     * Возвращает ISO3-код валюты или массив кодов валют, которые поддерживает этот плагин.
     *
     * @see waShipping::allowedCurrency()
     * @return array|string
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * Возвращает обозначение единицы веса или массив единиц веса, которые поддерживает этот плагин.
     *
     * @see waShipping::allowedWeightUnit()
     * @return array|string
     */
    public function allowedWeightUnit()
    {
        return 'kg';
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
        return parent::saveSettings($settings);
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
            $text_color = ($this->COLOR && false) ? ImageColorAllocate($image, 32, 32, 96) : ImageColorAllocate($image, 16, 16, 16);
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
                // Create recource from gif image
                $srcIm = @imagecreatefromgif($file);
                break;
            case 2:
                // Create recource from jpg image
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
                // Create recource from bmp image imagecreatefromwbmp
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
}
