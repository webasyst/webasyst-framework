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

    private function getBaseHTML($id, $script, $options)
    {
        $width = ifset($options['width'], '100%');
        $height = ifset($options['height'], '400px');

        $url = 'https://api-maps.yandex.ru/2.1/';
        $params = array(
            'lang' => wa()->getLocale(),
        );
        if ($key = $this->getSettings('apikey')) {
            $params['apikey'] = $key;
        }

        $url .= '?' . http_build_query($params);

        $html = <<<HTML
<div id="yandex-map-{$id}" class="map" style="width:{$width}; height: {$height}"></div>
<script type="text/javascript">
    $(function () {
        var init = function () {
            {$script}
        };
        if (!(window.ymaps)) {
            $.getScript('{$url}', init)
        } else {
            init();
        }
    })
</script>
HTML;
        return $html;
    }

    protected function getByAddress($address, $options = array())
    {
        $id = uniqid();
        $address = json_encode($address);
        $zoom = ifset($options['zoom'], 10);

        $script = <<<JS
ymaps.ready(function () {
    ymaps.geocode({$address}, {
        results: 1
    }).then(function (res) {
        var map = new ymaps.Map('yandex-map-{$id}', {
            center: [55.753994, 37.622093],
            zoom: {$zoom},
            controls: [
                'zoomControl',
                'fullscreenControl'
            ]
        });
        var firstGeoObject = res.geoObjects.get(0),
            coords = firstGeoObject.geometry.getCoordinates(),
            bounds = firstGeoObject.properties.get('boundedBy');
        map.geoObjects.add(firstGeoObject);
        map.setCenter(coords);
        /*
        map.setBounds(bounds, {
            checkZoomRange: true
        });
        */
    });
});
JS;
        return $this->getBaseHTML($id, $script, $options);
    }

    protected function getByLatLng($lat, $lng, $options = array())
    {
        $id = uniqid();
        $zoom = ifset($options['zoom'], 10);
        $center = json_encode(array($lat, $lng));

        $script = <<<JS
ymaps.ready(function () {
    var map = new ymaps.Map('yandex-map-{$id}', {
        center: {$center},
        zoom: {$zoom},
        controls: ['smallMapDefaultSet']
    });
});
JS;
        return $this->getBaseHTML($id, $script, $options);
    }

    public function getJs($html = true)
    {
        $url = 'https://api-maps.yandex.ru/2.1/';
        $params = array(
            'lang' => wa()->getLocale(),
        );
        if ($key = $this->getSettings('apikey')) {
            $params['apikey'] = $key;
        }

        $url .= '?' . http_build_query($params);
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
        $this->controls['apikey'] = array(
            'title'        => _ws('API key'),
            'description'  => _ws('Obtain an API key on the <a href="https://developer.tech.yandex.ru/" target="_blank">developer dashboard</a>. Select “JavaScript API, Geocoding API” option.'),
            'control_type' => waHtmlControl::INPUT,
        );
    }
}
