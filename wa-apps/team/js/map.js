// Team :: Map
var TeamMap = (function ($) {

    var TeamMap = function ($map, provider, options) {
        var that = this;

        options = options || {};

        // DOM
        if (!$map) {
            throw Error('DOM element is required');
        }
        that.$map = $($map);

        // VARS
        that.provider = provider;
        if (['google', 'yandex'].indexOf(that.provider) < 0) {
            throw Error('Not supported map provider: %s'.replace('%s', that.provider));
        }

        that.map_info = null;

        that.initClass();
    };

    TeamMap.prototype.initClass = function() {
        var that = this;
    };

    TeamMap.prototype.render = function (lat, lng) {
        var that = this;
        switch (that.provider) {
            case 'google':
                return that.googleRender(lat, lng);
            case 'yandex':
                return that.yandexRender(lat, lng);
        }
    };

    TeamMap.prototype.googleRender = function (lat, lng) {
        var that = this;
        that.map_info = that.map_info || {};
        var latLng = new google.maps.LatLng(lat, lng);
        if (!that.map_info.map) {
            var options = {
                zoom: 12,
                center: latLng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            that.map_info.map = new google.maps.Map(that.$map.get(0), options);
        }
        if (that.map_info.marker) {
            that.map_info.marker.setMap(null);
        }
        that.map_info.marker = new google.maps.Marker({
            position: latLng,
            map: that.map_info.map
        });
        that.map_info.map.setCenter(latLng);
    };

    TeamMap.prototype.yandexRender = function (lat, lng) {
        var that = this;
        that.map_info = that.map_info || {};
        var coords = [ lat, lng ];
        if (!that.map_info.map) {
            var options = {
                zoom: 12,
                center: coords,
                controls: [
                    'zoomControl',
                    'fullscreenControl'
                ]
            };
            that.map_info.map = new ymaps.Map(that.$map.get(0), options);
        }
        if (that.map_info.marker) {
            that.map_info.map.geoObjects.remove(that.map_info.marker);
        }
        that.map_info.marker = new ymaps.Placemark(coords);
        that.map_info.map.geoObjects.add(that.map_info.marker);
        that.map_info.map.setCenter(coords);
    };

    TeamMap.prototype.geocode = function (query, success, fail) {
        var that = this;
        switch (that.provider) {
            case 'google':
                return that.googleGeocode(query, success, fail);
            case 'yandex':
                return that.yandexGeocode(query, success, fail);
        }
    };

    TeamMap.prototype.googleGeocode = function (query, success, fail) {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': query }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                var latLng = results[0].geometry.location;
                success(latLng.lat(), latLng.lng());
            } else {
                fail();
            }
        });
    };

    TeamMap.prototype.yandexGeocode = function (query, success, fail) {
        ymaps.geocode(query, {
            results: 1
        }).then(function (res) {
            var found = 1;
            if (res.metaData && res.metaData.geocoder && ('found' in res.metaData.geocoder)) {
                found = res.metaData.geocoder.found;
            }
            if (found <= 0) {
                fail();
                return;
            }
            var firstGeoObject = res.geoObjects.get(0);
            var coords = firstGeoObject.geometry.getCoordinates();
            success(coords[0], coords[1]);
        });
    };

    return TeamMap;

})(jQuery);