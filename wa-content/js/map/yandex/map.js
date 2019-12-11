var waYandexMap = ( function($) {

    waYandexMap = function(params) {
        var that = this;

        // DOM
        that.$wrapper = params.$wrapper;
        that.$map = params.$map;

        // CONST

        // VARS
        that.url = params.url || '';
        that.type = params.type || '';          // query type of point: 'address', 'coords'
        that.address = params.address || '';    // address value if query type is 'address'
        that.center = params.center || [55.753994, 37.622093];      // center of the map
        that.locales = params.locales || '';

        // OPTIONS
        that.options = params.options || {};

        // INIT
        that.init();

    };

    waYandexMap.prototype.init = function() {
        var that = this;

        var onOk = function() {
            if (that.type === 'address') {
                that.initByAddress();
            } else if (that.type === 'coords') {
                that.initByCoords();
            }
        };

        var onError = function(e) {
            that.onMapError(e);
        };

        waYandexMap.loadMap(that.url, onOk, onError);
    };

    waYandexMap.prototype.onMapError = function(e) {
        var that = this,
            $wrapper = that.$wrapper,
            $map = that.$map,
            options = that.options;

        // get on error option for this instance
        var on_error = options.on_error || {};

        // here will be callback
        var callback;

        // define callback (callback factory)
        if (on_error.type === 'show') {
            callback = function (e, $wrapper) {
                var $error = $wrapper.find('.js-wa-yandex-map-error-block');

                e = e || {};

                var message = '';

                if (e.code) {
                    if (that.locales['error_' + e.code]) {
                        message = that.locales['error_' + e.code];
                    } else {
                        message = that.locales['error'];
                    }
                } else {
                    message = that.locales['error'];
                }

                if (e.message) {
                    message = message.replace('%s', e.message);
                } else {
                    message = message.replace('%s', 'Error');
                }

                $error.find('.js-text').addClass("align-center errormsg").html(message);
                $map.css({
                    width: "auto",
                    height: "auto"
                }).append($error);

                $error.show();
            };
        } else if (on_error.type === 'callback' && typeof on_error.callback === 'function') {
            callback = on_error.callback;
        } else {
            callback = function (e) {
                console.error(['Yandex map geocoding error', e]);
            };
        }

        // call callback
        callback(e, $wrapper);
    };

    waYandexMap.prototype.initByAddress = function () {
        var that = this,
            $map = that.$map,
            options = that.options || {};

        var onOK = function(res) {
            var map = new ymaps.Map($map.attr('id'), {
                center: that.center,
                zoom: options.zoom || 10,
                controls: [
                    'zoomControl',
                    'fullscreenControl'
                ]
            });

            var firstGeoObject = res.geoObjects.get(0),
                coords = firstGeoObject.geometry.getCoordinates();
            map.geoObjects.add(firstGeoObject);
            map.setCenter(coords);

            /*
            var bounds = firstGeoObject.properties.get('boundedBy');
            map.setBounds(bounds, {
                checkZoomRange: true
            });
            */
        };

        var onError = function(e) {
            that.onMapError(e);
        };

        ymaps.ready(function () {
            ymaps.geocode(that.address, {
                results: 1
            }).then(onOK, onError);
        });
    };

    waYandexMap.prototype.initByCoords = function () {
        var that = this,
            $map = that.$map,
            options = that.options;

        ymaps.ready(function () {
            new ymaps.Map($map.attr('id'), {
                center: that.center,
                zoom: options.zoom || 10,
                controls: ['smallMapDefaultSet']
            });
        });
    };

    // STATIC METHODS AND PROPERTIES

    // cache of errors of loading (apikey => error)
    waYandexMap.loadingErrors = {};

    // load map lib (ymaps)
    waYandexMap.loadMap = function(url, onOk, onError) {

        // we already has ymaps in memory - so all is ok
        if (window.ymaps) {
            onOk();
            return;
        }

        url = $.trim(url);

        var api_key = '';

        // extract apikey
        var res = url.match(/apikey=(.*?)(&|$)+/);
        if (res && res[1]) {
            api_key = res[1];
        }

        // we already now that for this apikey we has error, so just signal about it..
        if (waYandexMap.loadingErrors[api_key]) {
            // ..and no need go further
            onError(waYandexMap.loadingErrors[api_key]);
            return;
        }

        // otherwise we need to try to load ymaps
        // but we must handle loading error also

        // build error handler name
        var error_handler_name = "onError" + api_key.replace(/\-/g, '_');

        // dynamically create error handler - if for this url we has not yet handler
        if (!waYandexMap[error_handler_name]) {
            (function (api_key) {
                waYandexMap[error_handler_name] = function (error) {
                    waYandexMap.loadingErrors[api_key] = error;
                    onError(error);
                };
            })(api_key);    // here url must be in closure
        }

        // build ok handler name
        var ok_handler_name = "onOk" + api_key.replace(/\-/g, '_');

        if (!waYandexMap[ok_handler_name]) {
            waYandexMap[ok_handler_name] = function () {
                onOk();
            };
        }

        // load script (ymaps)
        $.getScript(url + "&onload=waYandexMap." + ok_handler_name + "&onerror=waYandexMap." + error_handler_name);
    };

    return waYandexMap;

})(jQuery);
