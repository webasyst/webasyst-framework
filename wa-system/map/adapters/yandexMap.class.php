<?php

class yandexMap extends waMapAdapter
{

    /**
     * ru_RU only
     *
     * @return array
     */
    public function getLocale()
    {
        return array(
            'ru_RU',
        );
    }

    public function getName()
    {
        return 'Яндекс.Карты';
    }

    protected function buildUrl()
    {
        $url = 'https://api-maps.yandex.ru/2.1/';
        $params = array(
            'lang' => wa()->getLocale(),
        );
        if ($key = $this->getSettings('apikey')) {
            $params['apikey'] = $key;
        }

        $url .= '?' . http_build_query($params);

        return $url;
    }

    /**
     * @param array|string $address
     * @param array $options
     *   string  $options['on_error'] [optional]         What to do on error
     *     - 'show' - show error as it right on map html block
     *     - 'function(e) { ... }' - anonymous js function
     *     - any other NOT EMPTY string that is javascript function name in global scope (for example, console.log)
     *     - <empty> - not handle map error
     *
     * @return string
     * @throws waException
     */
    protected function getByAddress($address, $options = array())
    {
        $options = is_array($options) ? $options : array();

        $id = uniqid();

        $template = waConfig::get('wa_path_system') . '/map/templates/yandex/map.html';

        $options['width'] = ifset($options['width'], '100%');
        $options['height'] = ifset($options['height'], '400px');
        $options['zoom'] = ifset($options['zoom'], 10);

        $this->typecastOnErrorOption($options);

        $template = $this->renderTemplate($template, array(
            'id' => $id,
            'address' => $address,
            'options' => $options,
            'url' => $this->buildUrl(),
            'type' => 'address'
        ));
        $static_link = $this->getStaticTemplate($address);

        return $template . $static_link;
    }

    protected function getStaticTemplate($address)
    {
        $params = array(
            'text' => $address,
            'z' => 10,
        );
        $url = '//yandex.ru/maps/?' . http_build_query($params);

        $link_text = _ws('Link to map');
        $yandex_id = 'yandex-static-map-link' . uniqid();

        return '<a href="'.$url.'" id="'.$yandex_id.'" target="_blank" style="display: none;"><i class="icon16 marker"></i>'. $link_text .'</a>';
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param array $options
     *   string  $options['on_error'] [optional]         What to do on error
     *     - 'show' - show error as it right on map html block
     *     - 'function(e) { ... }' - anonymous js function
     *     - any other NOT EMPTY string that is javascript function name in global scope (for example, console.log)
     *     - <empty> - not handle map error
     * @return string
     */
    protected function getByLatLng($lat, $lng, $options = array())
    {
        $options = is_array($options) ? $options : array();

        $id = uniqid();

        $template = waConfig::get('wa_path_system') . '/map/templates/yandex/map.html';

        $options['width'] = ifset($options['width'], '100%');
        $options['height'] = ifset($options['height'], '400px');
        $options['zoom'] = ifset($options['zoom'], 10);

        $this->typecastOnErrorOption($options);

        return $this->renderTemplate($template, array(
            'id' => $id,
            'center' => array($lat, $lng),
            'options' => $options,
            'url' => $this->buildUrl(),
            'type' => 'coords'
        ));
    }

    public function getJs($html = true)
    {
        $url = $this->buildUrl();

        if ($html) {
            return <<<HTML
<script type="text/javascript" src="{$url}"></script>
HTML;

        } else {
            return $url;
        }
    }

    public function geocode($address)
    {
        $data = array();
        if ($this->geocodingAllowed()) {
            if ($response = $this->sendGeoCodingRequest($address)) {
                $this->geocodingAllowed(true);
                if (!empty($response['metaDataProperty']['GeocoderResponseMetaData']['found'])) {
                    if ($response['metaDataProperty']['GeocoderResponseMetaData']['found'] == 1) {
                        $member = reset($response['featureMember']);
                        if (!empty($member['GeoObject']['Point']['pos'])) {
                            $data += self::parse($member['GeoObject']['Point']['pos']);
                        }
                    } else {
                        foreach ($response['featureMember'] as $member) {
                            $precision = ifset($member['GeoObject']['metaDataProperty']['GeocoderMetaData']['precision']);
                            if (!empty($member['GeoObject']['Point']) && ($precision == 'exact')) {
                                $data += self::parse($member['GeoObject']['Point']['pos']);
                                break;
                            }
                        }
                    }
                }
            } else {
                $this->geocodingAllowed(false);
            }
        }
        return $data;
    }

    private static function parse($point)
    {
        $data = array();
        if (strpos($point, ' ')) {
            $data = array(
                'lat' => '',
                'lng' => '',
            );
            list($data['lng'], $data['lat']) = explode(' ', $point, 2);
        }
        return $data;
    }

    protected function sendGeoCodingRequest($address)
    {
        $response = null;
        if ($address) {
            $address = preg_replace('@российская\s+федерация@ui', 'Россия', $address);
            /**
             * Geocoder doc
             * @link https://tech.yandex.ru/maps/doc/geocoder/desc/concepts/About-docpage/
             */
            $url = 'https://geocode-maps.yandex.ru/1.x/';

            /**
             * Params doc
             * @link https://tech.yandex.ru/maps/doc/geocoder/desc/concepts/input_params-docpage/
             */
            $params = array(
                'format'  => 'json',
                'geocode' => $address,
            );

            if ($key = $this->getSettings('apikey')) {
                $params['apikey'] = $key;
            }

            $options = array(
                'format'  => waNet::FORMAT_JSON,
                'timeout' => 9,
            );
            $net = new waNet($options);
            try {
                $result = $net->query($url, $params);
                $response = ifset($result['response']['GeoObjectCollection'], array());
            } catch (waException $ex) {
                waLog::log(get_class($this).": ".$ex->getMessage()."\n".$ex->getFullTraceAsString(), 'geocode.log');
                return array();
            }
        }
        return $response;
    }

    protected function initControls()
    {
        parent::initControls(); // TODO: Change the autogenerated stub
        $env = $this->getEnvironment();
        if ($env == waMapAdapter::FRONTEND_ENVIRONMENT) {
            $description = _ws('Obtain an API key for the <strong>free or paid version</strong> on the <a href="https://developer.tech.yandex.ru/" target="_blank">developer dashboard</a>. Select “<strong>JavaScript API, Geocoding API</strong>” option.');
        } else {
            $description = _ws('Obtain an API key for the <strong>paid version</strong> on the <a href="https://developer.tech.yandex.ru/" target="_blank">developer dashboard</a>. Select “<strong>JavaScript API, Geocoding API</strong>” option.')
             . '<br>'
             . _ws('If the free version’s API key is specified, only a link to the map will be displayed.');
        }
        $this->controls['apikey'] = array(
            'title'        => _ws('API key'),
            'description'  => $description,
            'control_type' => waHtmlControl::INPUT,
        );
    }

    /**
     * Inner helper
     * Find 'on_error' option in options array and convert value to array with keys
     *   - string 'type' - with possible values: 'show', '', 'callback'
     *   - string 'callback' - js callback or empty string (for 'show' and '' types)
     * @param array &$options
     *
     */
    private function typecastOnErrorOption(&$options)
    {
        $options = is_array($options) ? $options : array();

        $on_error_type = trim(ifset($options['on_error'], ''));

        if (preg_match('/^function\s*\(/', $on_error_type)) {
            $on_error_type = 'callback';
            $on_error_callback = $options['on_error'];
        } elseif ($on_error_type === 'show') {
            $on_error_type = 'show';
            $on_error_callback = '';
        } else if ($on_error_type) {
            $on_error_type = 'callback';
            $on_error_callback = $options['on_error'];
        } else {
            $on_error_type = '';
            $on_error_callback = '';
        }

        $options['on_error'] = array(
            'type' => $on_error_type,
            'callback' => $on_error_callback
        );
    }

    protected function renderTemplate($template, $assign = array())
    {
        if (!is_scalar($template) || !file_exists($template)) {
            return '';
        }
        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();
        $view->assign($assign);
        $html = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return $html;
    }
}
