<?php

/**
 * @property-read string $region Регион
 * @property-read string $city Город
 * @property-read double $surcharge Надбавка (%)
 * @property-read string $company_name Получатель наложенного платежа (магазин)
 * @property-read string $address1 Адрес получателя наложенного платежа (магазина), строка 1
 * @property-read string $address2 Адрес получателя наложенного платежа (магазина), строка 2
 * @property-read string $zip Индекс получателя наложенного платежа (магазина)
 * @property-read string $inn ИНН получателя наложенного платежа (магазина)
 * @property-read string $bank_kor_number Кор. счет получателя наложенного платежа (магазина)
 * @property-read string $bank_name Наименование банка получателя наложенного платежа (магазина)
 * @property-read string $bank_account_number Расчетный счет получателя наложенного платежа (магазина)
 * @property-read string $bik БИК получателя наложенного платежа (магазина)
 */
class emsruShipping extends waShipping
{

    protected function calculate()
    {
        $params = array();
        $params['weight'] = max(0.1, $this->getTotalWeight());

        if ($params['weight'] > 31.5) { /* hardcoded */
            return 'Вес отправления превышает максимально допустимый (31,5 кг).';
        } elseif (empty($params['weight'])) {
            return 'Вес отправления не задан.';
        }
        $incomplete = false;
        switch ($country_iso3 = $this->getAddress('country')) {
            case 'rus':
                $address = array_merge(array('country' => 'rus'), $this->getSettings());
                $params['from'] = $this->findTo($address);
                $params['to'] = $this->findTo($this->getAddress());
                if (empty($params['to'])) {
                    $address = $this->getAddress();
                    $incomplete = empty($address['city']) && empty($address['region']);
                }
                break;
            default: /* International shipping*/
                $country_model = new waCountryModel();
                if ($country = $country_model->get($country_iso3)) {
                    $params['to'] = mb_strtoupper($country['iso2letter']);
                } else {
                    $params['to'] = false;
                }
                $params['type'] = 'att';
                $incomplete = true;

                break;
        }
        $services = array();
        if (!empty($params['to'])) {
            if (!empty($params['from']) || !empty($params['type'])) {
                if ($result = $this->request('ems.calculate', $params)) {

                    $est_delivery = '';
                    $time = array(
                        'min' => sprintf('+%d day', ifset($result['term']['min'], 7)),
                        'max' => sprintf('+%d day', ifset($result['term']['max'], 14)),
                    );
                    $est_delivery .= waDateTime::format('humandate', strtotime($time['min']));
                    if ($time['min'] != $time['max']) {
                        $est_delivery .= ' — ';
                        $est_delivery .= waDateTime::format('humandate', strtotime($time['max']));
                    }
                    $rate = doubleval(ifset($result['price'], 0));
                    if (doubleval($this->surcharge) > 0) {
                        $rate += $this->getTotalPrice() * doubleval($this->surcharge) / 100.0;
                    }
                    $services['main'] = array(
                        'rate'         => $rate,
                        'currency'     => 'RUB',
                        'est_delivery' => $est_delivery,
                    );

                } else {
                    $services = 'Ошибка расчета стоимости доставки в указанные город и регион.';
                }
            } else {
                $services = 'Стоимость доставки не может быть рассчитана, так как в настройках способа доставки «EMS Почта России» не указан адрес отправителя.';
            }
        } elseif ($incomplete) {
            $services = array(
                array('rate' => null, 'comment' => 'Для расчета стоимости доставки укажите страну, регион и город доставки.')
            );
        } else {
            $services = 'Ошибка расчета стоимости доставки в указанные город и регион.';
        }
        return $services;

    }

    public function saveSettings($settings = array())
    {
        $address = array_merge(array('country' => 'rus'), $settings);
        if (!$this->findTo($address)) {
            throw new waException('Указанный адрес пункта отправления не найден в списке поддерживаемых API службы «EMS Почта России».');
        }
        if (isset($settings['surcharge'])) {
            if (strpos($settings['surcharge'], ',')) {
                $settings['surcharge'] = str_replace(',', '.', $settings['surcharge']);
            }
            $settings['surcharge'] = max(0, doubleval($settings['surcharge']));
        }
        return parent::saveSettings($settings);
    }

    public function getSettingsHTML($params = array())
    {
        $model = new waRegionModel();
        if (!isset($params['options'])) {
            $params['options'] = array();
        }
        $params['options']['region'] = array();
        foreach ($model->getByCountry('rus') as $region) {
            $params['options']['region'][$region['code']] = $region['name'];
        }
        return parent::getSettingsHTML($params);
    }

    private function findTo($address)
    {
        $city = mb_strtoupper(ifset($address['city']));
        $pattern = '/(КРАЙ|РАЙОН|(АВТОНОМНАЯ )?ОБЛАСТЬ|РЕСПУБЛИКА|(АВТОНОМНЫЙ )?ОКРУГ)/u';

        $cache = new waSerializeCache(__CLASS__, 86400, 'webasyst');
        if (!($map = $cache->get())) {
            $map = array(
                'city'   => array(),
                'region' => array(),
            );

            $result = $this->request('ems.get.locations', array('type' => 'russia'));
            foreach (ifempty($result['locations'], array()) as $location) {
                switch ($location['type']) {
                    case 'cities':
                        $map['city'][$location['name']] = $location['value'];
                        break;
                    case 'regions':
                        $name = trim(preg_replace($pattern, '', $location['name']));
                        $map['region'][$name] = $location['value'];
                        break;
                }
            }
            if ($map) {
                $cache->set($map);
            }
        }

        $region = trim(mb_strtoupper(ifset($address['region'])));
        $region_name = trim(mb_strtoupper(ifset($address['region_name'])));
        $to = null;
        if ($city && !empty($map['city'][$city])) {
            $to = $map['city'][$city];
        } else {
            if ($region_name && !empty($map['city'][$region_name])) {
                $to = $map['city'][$region_name];
            } elseif ($region && !empty($map['city'][$region])) {
                $to = $map['city'][$region];
            } else {
                $model = new waRegionModel();
                if ($region_name) {
                    $region_name = trim(preg_replace($pattern, '', mb_strtoupper($region_name)));
                    $to = ifset($map['region'][$region_name]);
                } elseif ($region && ($region = $model->get(ifset($address['country']), $region))) {
                    $region = trim(preg_replace($pattern, '', mb_strtoupper($region['name'])));
                    $to = ifset($map['city'][$region], ifset($map['region'][$region]));
                }
            }
        }
        return $to;
    }

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function requestedAddressFields()
    {
        return array(
            'zip'     => array(),
            'country' => array('cost' => true),
            'region'  => array('cost' => true),
            'city'    => array('cost' => true),
            'street'  => array(),
        );
    }

    public function allowedAddress()
    {
        $cache = new waSerializeCache(__CLASS__.__FUNCTION__, 86400, 'webasyst');
        if (!($addresses = $cache->get())) {
            $addresses = array();

            /* countries */
            $countries = $this->request('ems.get.locations', array('type' => 'countries'));
            $country_model = new waCountryModel();
            $map = $country_model->getAll('iso2letter');
            $address = array(
                'country' => array(),
            );
            foreach ($countries['locations'] as $country) {
                if ((ifset($country['type']) == 'countries') && ($value = strtolower(ifset($country['value']))) && isset($map[$value])) {
                    $address['country'][] = $map[$value]['iso3letter'];
                }
            }
            $addresses[] = $address;

            /* regions */
            $region_model = new waRegionModel();
            $address = array(
                'country' => 'rus',
                'region'  => array(),
            );
            $map = $region_model->getByCountry('rus');
            foreach ($map as $region) {

                if ($this->findTo(array('country' => 'rus', 'region_name' => $region['name']))) {
                    $address['region'][] = $region['code'];
                }
            }
            $addresses[] = $address;
            $cache->set($addresses);
        }
        return $addresses;
    }

    private function request($method, $params = array())
    {
        $timeout = 15;
        $methods = array(
            /* Возвращает список городов, регионов или стран из которых и в которые возможна доставка.*/
            'ems.get.locations'  => array(
                'params' => array(
                    /* тип запрашиваемых местоположений*/
                    'type' => array(
                        "cities",
                        "regions",
                        "countries",
                        "russia",
                    ),
                ),
                'result' => array(
                    'locations' => array(
                        array(
                            'value', /* "city--abakan"*/
                            'name', /*"Абакан"*/
                            'type', /*"cities"*/
                        ),
                    ),
                ),
            ),
            /*Возвращает максимальный возможный вес одного отправления*/
            'ems.get.max.weight' => array(
                'params' => array(),
                'result' => array(
                    'max_weight' => ':double',
                ),
            ),
            'ems.calculate'      => array(
                'params' => array(
                    'from'   => ':string', /* (обязательный, кроме международной доставки) — пункт отправления*/
                    'to'     => ':string', /*(обязательный) —пункт назначения отправления*/
                    'weight' => ':double', /*(обязательный) — вес отправления*/
                    /*(обязательный для международной доставки) — тип международного отправления*/
                    'type'   => array(
                        "doc", /*документы (до 2-ч килограм)*/
                        "att", /*товарные вложения*/
                    )
                ),
                'result' => array(
                    'price' => ':double', /* стоимость отправления */
                    'term'  => array(
                        'min', /* минимальный срок доставки */
                        'max', /* максимальный срок доставки*/

                    ),
                ),
            ),
        );
        $hint = '';
        if (!isset($methods[$method])) {
            throw new waException(sprintf("Ошибка расчета стоимости доставки (Invalid REST API method %s)", $method));
        }
        $url = 'http://emspost.ru/api/rest/?method='.$method;
        foreach ($params as $key => $value) {
            if ($param = ifset($methods[$method]['params'][$key])) {
                if (is_array($param)) {
                    if (!in_array($value, $param)) {
                        throw new waException(sprintf("Ошибка расчета стоимости доставки (Invalid REST API param %s)", $key));
                    }
                } else {

                }
                $url .= sprintf("&%s=%s", urlencode($key), urlencode($value));
            }
        }
        $url .= '&plain=true';

        if (extension_loaded('curl') && function_exists('curl_init')) {
            $curl_error = null;
            if (!($ch = curl_init())) {
                $curl_error = 'curl init error';
            }
            if (curl_errno($ch) != 0) {
                $curl_error = 'curl init error: '.curl_errno($ch);
            }
            if (!$curl_error) {
                @curl_setopt($ch, CURLOPT_URL, $url);
                @curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                $response = @curl_exec($ch);
                if (curl_errno($ch) != 0) {
                    $curl_error = 'curl error: '.curl_errno($ch);
                }
                curl_close($ch);
            }
            if ($curl_error) {
                throw new waException($curl_error);
            }
        } else {
            $hint .= " PHP extension curl are not loaded;";
            if (!ini_get('allow_url_fopen')) {
                $hint .= " PHP ini option 'allow_url_fopen' are disabled;";
            } else {
                $old_timeout = @ini_set('default_socket_timeout', $timeout);
                $response = @file_get_contents($url);
                @ini_set('default_socket_timeout', $old_timeout);
            }
        }
        if (empty($response) && $hint) {
            throw new waException(sprintf('Ошибка расчета стоимости доставки (Empty response. Hint: %s)', $hint));
        }

        $json = json_decode($response, true);
        if ($rsp = ifset($json['rsp'])) {
            switch ($rsp['stat']) {
                case 'ok':
                    $result = $rsp;
                    unset($result['stat']);
                    break;
                case 'fail':
                    throw new waException(sprintf("REST API error #%d: %s", ifset($rsp['err']['code'], 0), ifset($rsp['err']['msg'], 'unkown error')));
                    break;
                default:
                    throw new waException('Ошибка расчета стоимости доставки (Invalid response)');
                    break;
            }
        } else {
            throw new waException('Ошибка расчета стоимости доставки (Invalid response)');
        }
        return $result;
    }

    public function tracking($tracking_id = null)
    {
        $url = "http://www.emspost.ru/ru/tracking/?id=".urlencode($tracking_id);
        return 'Отслеживание отправления: <a href="'.$url.'" target="_blank">'.$url.'</a>';
    }

    /**
     * Возвращает массив с информацией о печатных формах, формируемых плагином.
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
                } else {
                    throw new waException('Image not found', 404);
                }

                break;
            case 'print':
                if (!$strict && !$order) {
                    $this->view()->assign('action', 'preview');
                }
                $this->view()->assign('editable', waRequest::post() ? false : true);
                break;
            default:
                $this->view()->assign('src_front', http_build_query(array_merge($request, array('side' => 'front'))));
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
            $text_color = ImageColorAllocate($image, 16, 16, 16);
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
}
