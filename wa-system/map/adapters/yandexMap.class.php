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

        $html = <<<HTML
<div id="yandex-map-{$id}" class="map" style="width:{$width}; height: {$height}"></div>
<script type="text/javascript">
    $(function () {
        var init = function () {
            {$script}
        }
        if (!(window.ymaps)) {
            $.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU', init)
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

        $script = <<<HTML
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
HTML;
        return $this->getBaseHTML($id, $script, $options);
    }

    protected function getByLatLng($lat, $lng, $options = array())
    {
        $id = uniqid();
        $zoom = ifset($options['zoom'], 10);
        $center = json_encode(array($lat, $lng));

        $script = <<<HTML
ymaps.ready(function () {
    var map = new ymaps.Map('yandex-map-{$id}', {
        center: {$center},
        zoom: {$zoom},
        controls: ['smallMapDefaultSet']
    });
});
HTML;
        return $this->getBaseHTML($id, $script, $options);
    }
}
