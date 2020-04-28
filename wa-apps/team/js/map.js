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
        if (that.provider && ['google', 'yandex', 'disabled'].indexOf(that.provider) < 0) {
            console.error('Not supported map provider: %s'.replace('%s', that.provider));
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
            default:
                return;
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

        TeamMap.google_error_html = TeamMap.google_error_html || '';
        setTimeout(function () {
            if (that.$map.find('.gm-err-container').length) {
                var $err = that.$map.find('.gm-err-container');
                $err.find('.gm-err-message').last().after('<div class="gm-err-message">' + $.team.locales.map_check_your_key + '</div>');
                TeamMap.google_error_html = that.$map.find('.gm-err-container').parent().html();
            } else if (!that.$map.find('.gm-style').length) {
                if (TeamMap.google_error_html) {
                    that.$map.children().html(TeamMap.google_error_html);
                } else {
                    var html = '<div class="gm-err-container"><div class="gm-err-content"><div class="gm-err-icon"><img src="https://maps.gstatic.com/mapfiles/api-3/images/icon_error.png" draggable="false" style="user-select: none;"></div><div class="gm-err-title">:title:</div><div class="gm-err-message">:message1:</div><div class="gm-err-message">:message2:</div></div></div>';
                    html = html.replace(':title:', $.team.locales.map_error_title);
                    html = html.replace(':message1:', $.team.locales.map_error_message);
                    html = html.replace(':message2:', $.team.locales.map_check_your_key);
                    that.$map.children().html(html);
                }
            }
        }, 5000);
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
            default:
                if (fail) {
                    fail();
                } else if (success) {
                    success();
                }
                break;
        }
    };

    TeamMap.prototype.googleGeocode = function (query, success, fail) {
        var geocoder = new google.maps.Geocoder();
        var was_res = false,
            too_late = false;
        geocoder.geocode( { 'address': query }, function(results, status) {
            was_res = true;
            if (too_late) {
                return;
            }
            if (status == google.maps.GeocoderStatus.OK) {
                var latLng = results[0].geometry.location;
                success(latLng.lat(), latLng.lng());
            } else {
                fail && fail();
            }
        });
        setTimeout(function () {
            if (!was_res) {
                too_late = true;
                fail && fail(true);
            }
        }, 5000);
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
        }, function (err) {
            fail(err);
        });
    };

    return TeamMap;

})(jQuery);
