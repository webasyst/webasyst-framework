<?php
/**
 * @property double $surcharge
 */
class emsruShipping extends waShipping
{
    public function calculate()
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
            $services = 'Для расчета стоимости доставки укажите страну, регион и город доставки.';
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
            } else {
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
        if (!$response && $hint) {
            throw new waException(sprintf('Ошибка расчета стоимости доставки (Empty response. Hint: %s)', $hint));
        }

        $json = json_decode($response, true);
        $result = array();
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
}
