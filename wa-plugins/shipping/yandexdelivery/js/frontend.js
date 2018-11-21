/**
 * @typedef {object} shippingYandexDeliveryPickup
 * @property {string}  id
 * @property {float} lat
 * @property {float} lng
 * @property {string} title
 * @property {string} description Full address
 * @property {string} comment
 * @property {string} payment
 * @property {string} schedule_html
 * @property {float} rate
 *
 * @property {string} input_id
 */

/**
 * @typedef {object} shippingYandexDeliveryCourier
 * @property {Array.string} intervals
 * @property {string} placeholder
 * @property {number} offset
 */
/**
 * @typedef {object} shippingYandexDeliveryGeocode
 * @property {string} description
 * @property {string} title
 * @property {string} value
 */

/**
 * @typedef {object} shippingYandexDeliveryResponseData
 * @property {Array.<shippingYandexDeliveryResponseService>}  services
 * @property {Array.<shippingYandexDeliveryGeocode>}  options
 */

/**
 * @typedef {object} shippingYandexDeliveryResponseService
 * @property {string} type
 * @property {Array.<shippingYandexDeliveryPickup>}  pickup
 * @property {Array.<shippingYandexDeliveryCourier>}  courier
 */


/**
 * @typedef {object} shippingYandexDeliveryResponseError
 * @property {string} error
 * @property {object} data
 */

/**
 * @typedef {object} shippingYandexDeliveryResponse
 * @property {string} status
 * @property {shippingYandexDeliveryResponseData} data
 * @property {shippingYandexDeliveryResponseError} error
 */

/**
 * @typedef {object} shippingYandexDeliveryId
 * @property {string} map
 * @property {string} filter
 */

/**
 *
 * @param {string} key
 * @param {shippingYandexDeliveryId} id
 * @param {string} url
 * @returns {ShippingYandexdelivery}
 * @constructor
 */
function ShippingYandexdelivery(key, id, url) {
    this.options = {
        "hide_group_option": true,
        "hide_non_pickup_group_option": true
    };
    var instance = this;
    this.url = url;
    this.key = key;

    this.initialized = false;
    this.container = null;
    this.control_wrapper = null;
    this.form = null;
    this.address = null;

    this.fixUI = function () {
        this.control_wrapper.find("> .wa-name, > .name").hide();
        this.control_wrapper.find("> .wa-value, > .value").css("margin", "0");

        this.form.show();
    };
    this.hide = function () {
        this.rate.hide(this.form);
        this.courier.hide();
        this.pickup.hide();
    };
    this.reset = function () {
        this.courier.reset();
        this.pickup.reset();
    };

    this.loader = {
        loader_container: null,
        init: function (control) {
            if (control) {
                this.loader_container = control.find('.yandexdelivery-loading-section');
            }
        },
        show: function () {
            if (this.loader_container) {
                this.loader_container.show();
            }
        },
        hide: function () {
            if (this.loader_container) {
                this.loader_container.hide();
            }
        }
    };

    this.rate = {
        id: null,
        rate_select: null,
        geo_id_select: null,
        filter_select: null,
        load: function () {
            instance.loader.show();
            var rate = this.rate_select;
            var city = instance.address.find(':input[name$="\[address\.shipping]\[city]"]').val();
            var geo_id = ('' + this.geo_id_select.val()).replace(/\W.+$/, '');
            var self = this;
            $.ajax({
                "type": 'POST',
                "url": instance.url,
                "data": {
                    "city": city
                },
                /**
                 *
                 * @param {shippingYandexDeliveryResponse} response
                 */
                "success": function (response) {
                    if (response.status === 'ok') {
                        if (response.data) {
                            instance.loader.hide();
                            var data;
                            var services = response.data.services;
                            for (var id in services) {
                                if (services.hasOwnProperty(id)) {
                                    data = services[id];
                                    switch (data.type) {
                                        case 'post': /* nothing to show? */
                                            break;
                                        case 'todoor': /* show courier delivery intervals & date */
                                            rate.find('option[value="' + id + '"]').data('courier', data.courier || []);
                                            break;
                                        case 'pickup': /* show pickup variants */
                                            rate.find('option[value="' + id + '"]').data('pickup', data.pickup || []);
                                            break;
                                    }
                                }
                            }

                            self.geo_id_select.find('>option').each(function () {
                                $(this).remove();
                            });

                            var options = response.data.options;
                            var option;
                            var $option;
                            var count = 0;
                            var selected = null;
                            for (var _geo_id in options) {
                                if (options.hasOwnProperty(_geo_id)) {
                                    option = options[_geo_id];
                                    $option = $('<option></option>');
                                    $option.val(option.value);
                                    $option.text(option.description);
                                    $option.attr('title', option.title);
                                    $option.data(option.data || []);
                                    self.geo_id_select.append($option);
                                    ++count;
                                    if (_geo_id === geo_id) {
                                        selected = option.value;
                                    }
                                }
                            }
                            self.geo_id_select.val(selected ? selected : null);
                            if (count > 1) {
                                self.geo_id_select.parents('.wa-field:first').show();
                                self.geo_id_select.attr('disabled', null);

                            } else {
                                self.geo_id_select.parents('.wa-field:first').hide();
                                self.geo_id_select.attr('disabled', true);
                            }
                            //add address options into suggest

                            self.filter_select.off('change.yandexdelivery').on('change.yandexdelivery', function () {
                                /**
                                 * @this HTMLSelectElement
                                 */
                                self.filter(this.value);
                            }).change();

                        } else {
                            console.error('Empty response');
                        }
                    } else {
                        console.error('Invalid response', response);
                    }
                },
                dataType: 'json'
            });
        },
        reset: function () {
            if (!this.filter_select.data('moved')) {
                instance.container.find(':input[name$="\.filter\]"]').each(function () {
                    var $this = $(this);
                    if ($this.data('moved')) {
                        $this.remove();
                    }
                });
                this.filter_select.data('moved', true);
                this.filter_select.insertBefore(this.rate_select);
            }
            this.filter_select.find('option').each(function () {
                var $this = $(this);
                if (this.value.match(/\./)) {
                    $this.remove();
                } else {
                    $this.attr('disabled', null);
                    $this.show();
                    $this.data(this.value, []);
                }
            });
        },
        init: function (form, select) {
            this.rate_select = select;
            if (!this.filter_select) {
                instance.container.find(':input[name$="\.filter\]"]').each(function () {
                    var $this = $(this);
                    if ($this.data('moved')) {
                        $this.remove();
                    }
                });
                this.filter_select = form.find(':input[name$="\.filter\]"]:first');
            }
            if (!this.geo_id_select) {
                this.geo_id_select = form.find(':input[name$="\[geo_id_to\]"]:first');
            }
            this.geo_id_select.parents('.wa-field:first').hide();
            /**
             * @this ShippingYandexdelivery.rate
             */

            this.reset();

            var self = this;

            /**
             * @var {ShippingYandexdelivery.rate} self
             */

            this.rate_select.off('change.yandexdelivery').on('change.yandexdelivery', function () {
                self.change(this);
            });
            this.geo_id_select.off('change.yandexdelivery').on('change.yandexdelivery', function () {
                self.suggest(this);
            });


            this.filter_select.off('change.yandexdelivery');

            this.filter_select.find('option').each(function () {
                var $this = $(this);
                var exists = self.rate_select.find('option[value^="' + this.value + '\."]');
                var services = [];
                exists.each(function (index, element) {
                    var id = self.getId(element.value);
                    var $service = $(this);

                    if (services.indexOf(id) === -1) {
                        services.push(id);
                        var $option = $('<option></option>');
                        $option.attr('value', id);
                        $option.text(this.text.replace(/^([^:]+):.+(\([^)]+\))$/, '$1 $2'));
                        $option.insertAfter($this);
                    }

                    $service.data('full-name', this.text);
                    this.text = this.text.replace(/^([^:]+):\s+/, '').replace(/\([^)]+\)$/, '');
                    $service.data('name', this.text);
                });
                if (services.length) {
                    $this.attr('disabled', null);
                    if ((services.length === 1)
                        || instance.options.hide_group_option
                        || ((this.value !== 'pickup') && instance.options.hide_non_pickup_group_option)
                    ) {
                        $this.hide();
                    }
                } else {
                    $this.attr('disabled', true);
                }
            });

            var value = this.rate_select.val();
            if (!value) {
                value = this.rate_select.find('option:first').attr('value');
                this.rate_select.val(value);
            }
            if (value) {
                var exists = this.filter_select.find('option[value^="' + value.replace(/:.+$/, '') + '"]');
                if (exists.length) {
                    this.filter_select.val(exists.val());
                }
            }

            this.load(value);
        },

        /**
         *
         * @param {HTMLOptionElement} select
         */
        change: function (select) {
            var type = select.value.replace(/\..+$/, '');
            switch (type) {
                case 'courier':
                    instance.hide();
                    instance.courier.load($(select).data('courier'));
                    break;
                case 'pickup':
                    var pickup_id = select.value.replace(/^.*\.([^.]+)$/, '$1');
                    instance.pickup.change();
                    instance.pickup.select(pickup_id);
                    break;
            }
        },

        /**
         *
         * @param {HTMLOptionElement} select
         */
        suggest: function (select) {
            var city = select.value.replace(/,.+$/, '').replace(/^\d+\W\s*[^\(]+\(/, '').replace(/^[\wа-яА-Я]\.?\s+/ui, '');
            var $city = instance.address.find(':input[name$="\[address\.shipping]\[city]"]');
            $city.val(city).change();
        },

        select: function (type, id) {
            var value = this.rate_select.find('option[value^="' + type + '\."][value$="\.' + id + '"]').attr('value');
            this.rate_select.val(value).trigger('change');
        },

        getId: function (value) {
            return value.replace(/^([^.]+)\.([^.]+)(\.[^.]+)?$/, '$1.$2');
        },
        filter: function (type) {
            var regexp = new RegExp('^' + type.replace(/\./, '\.') + '(\.|$)');
            var change = !regexp.test(this.rate_select.val());
            var self = this;
            var full = !(this.getId(type)).match(/\./);
            type = type.replace(/\..+$/, '');
            var pickups = [];
            var courier = null;
            var count = 0;
            this.rate_select.find('option').each(function () {
                var $this = $(this);
                if (regexp.test(this.value)) {
                    ++count;
                    if (change) {
                        self.rate_select.val(this.value).trigger('change');
                        change = false;
                    }

                    $this.text($this.data(full ? 'full-name' : 'name'));
                    $this.show();

                    switch (type) {
                        case 'courier':
                        case 'todoor':
                            courier = $this.data('courier');
                            break;
                        case 'pickup':
                            var pickup = $this.data('pickup') || {};
                            pickup.rate = $this.data('rate');
                            pickups.push(pickup);
                            break;
                    }
                } else {
                    $this.hide();
                }
            });
            instance.hide();

            if ((false && (count === 1)) || ((type === 'pickup') && instance.pickup.map.id)) {
                this.rate_select.hide();
            } else {
                this.rate_select.show();
            }
            this.filter_select.show();
            switch (type) {
                case 'courier':
                case 'todoor':
                    if (!full || true) {
                        instance.courier.load(courier);
                    }
                    break;
                case 'pickup':
                    var pickup_id = this.rate_select.val().replace(/^(.+):/, '').replace(/^.+\./, '');
                    instance.pickup.load(pickups, pickup_id);
                    break;
            }
        },
        hide: function (form) {
            if (!this.filter_select && form) {
                this.filter_select = form.find(':input[name$="\.filter\]"]:first');
            }
            if (this.filter_select) {
                this.filter_select.hide();
            }
        }
    };

    this.courier = {
        courier_container: null,
        date: null,
        date_formatted: null,
        interval: null,
        init: function (form) {
            this.date = form.find(':input[name$="\[desired_delivery\.date_str\]"]');
            this.date_formatted = form.find(':input[name$="\[desired_delivery\.date\]"]');
            this.interval = form.find(':input[name$="\[desired_delivery\.interval\]"]');

            this.courier_container = this.date.parents('.wa-field, .field');
            this.courier_container.hide();
            this.courier_container.find(':input').attr('disabled', true);
        },
        reset: function () {
            if (this.courier_container) {
                this.courier_container.find('option:not(:first)').remove();
                this.interval.val('');
                this.date.val('');
                this.date_formatted.val('');
                this.courier_container.slideUp();
                this.courier_container.find(':input').attr('disabled', true);
            }
        },
        load: function (courier) {
            this.courier_container.find(':input').attr('disabled', null);

            courier.available_days = [];
            var value = this.interval.val();
            var first_value = null;

            this.interval.find('option:not(:first)').remove();

            for (var interval in courier.intervals) {
                if (courier.intervals.hasOwnProperty(interval)) {
                    courier.available_days = courier.available_days.concat(courier.intervals[interval]);
                    if (first_value === null) {
                        first_value = interval;
                    } else {
                        first_value = false;
                    }
                    this.interval.append($("<option></option>")
                        .attr("value", interval)
                        .data('days', courier.intervals[interval])
                        .text(interval));
                }
            }

            this.interval.val(value ? value : first_value);


            this.date.datepicker('option', 'minDate', courier.offset);
            this.date.data('available_days', courier.available_days);
            this.date.attr('placeholder', courier.placeholder);
            this.courier_container.slideDown();
        },
        hide: function () {
            if (this.courier_container) {
                this.courier_container.slideUp();
            }
        }
    };

    this.pickup = {
        radio_id: null,
        radio: null,
        pickup_container: null,
        pickup_list: null,
        pickup_scroll: false,

        pickup_section: null,

        /**
         * @property {Array.<shippingYandexDeliveryPickup>} pickups
         */
        pickups: {},
        /**
         *
         * @param form
         */
        init: function (form) {
            if (form) {
                this.pickup_container = form.find('.yandexdelivery-map-section');
                this.pickup_container.hide();

                this.radio = form.find(':input[name$="\.pickup_id\]"]:first').parents('.js-yandexdelivery-variant:first');
                this.radio_id = this.radio.find(':input').attr('id');
                this.radio.hide();

                this.pickup_section = form.find('.yandexdelivery-pickup-section');
                this.pickup_list = form.find('.yandexdelivery-aside:first ul');


                var self = this;
                this.pickup_list.off('change.yandexdelivery').on('change.yandexdelivery', ':input[name$="\.pickup_id\]"]', function (event) {
                    var input = $(this);
                    self.pickup_list.find('input.js-yandexdelivery-button:visible').hide();
                    input.parents('li:first').find(':input').show();
                    var id = parseInt(input.data('id'));
                    if (event.originalEvent) {
                        self.map.select(id);
                    }
                });


                /* map click handler */
                $('html').off('click.yandexdelivery_' + self.radio_id).on('click.yandexdelivery_' + self.radio_id, '.js-yandexdelivery-button-' + self.radio_id, function () {
                    var button = $(this);
                    var id = button.data('id');
                    instance.rate.select('pickup', id);
                    self.show(id);
                });

                this.pickup_container.off('click.yandexdelivery').on('click.yandexdelivery', '.js-yandexdelivery-button', function () {
                    var button = $(this);
                    var id = button.data('id');
                    instance.rate.select('pickup', id);
                    self.show(id);
                });


                this.pickup_section.off('click.yandexdelivery').on('click.yandexdelivery', '.js-yandexdelivery-change', function () {
                    self.change();
                    return false;
                })
            }
        },

        reset: function () {
            if (this.pickup_container) {
                this.pickup_container.find('.js-yandexdelivery-variant:not(:first)').remove();
            }
            this.map.reset();
        },
        hide: function () {
            if (this.pickup_container) {
                this.pickup_container.hide();
            }
            if (this.pickup_section) {
                this.pickup_section.hide();
            }
        },

        /**
         *
         * @param {Array.<shippingYandexDeliveryPickup>} pickups
         * @param {Number} pickup_id
         */
        load: function (pickups, pickup_id) {

            /**
             * @this ShippingYandexdelivery.pickup
             */

            this.reset();
            if (this.pickup_container) {
                this.pickup_container.slideDown();
                this.pickups = {};
                var center = pickups.length ? [pickups[0].lat, pickups[0].lng] : null;
                var balloons = [];
                for (var i = 0; i < pickups.length; i++) {
                    balloons.push(this.add(pickups[i], pickups[i]['id'] === pickup_id));
                }
                this.map.init(center);
                this.map.show(balloons);
                if (pickup_id) {
                    this.preselect(pickup_id)
                }
            }
        },

        preselect: function (pickup_id) {
            if (!this.select(pickup_id)) {
                var self = this;
                setTimeout(function () {
                    self.preselect(pickup_id);
                }, 500);
            }
        },

        /**
         *
         * @param {shippingYandexDeliveryPickup} pickup
         * @param {boolean} checked
         * @returns {{type: string, id, geometry: {type: string, coordinates: [*,*]}, properties: {balloonContent: string, balloonContentHeader, balloonContentFooter: string, clusterCaption: string, hintContent}}}
         */
        add: function (pickup, checked) {
            pickup.input_id = this.radio_id + pickup.id.replace(/\./, '_');

            this.pickups[pickup.id] = pickup;

            var self = this;
            setTimeout(function () {
                if (self.radio) {
                    var variant = self.radio.clone(false);

                    var input = variant.find(':input[type="radio"]');
                    input.attr('checked', checked ? true : null);

                    input.val('#' + pickup.id + ' ' + pickup.title);
                    input.attr('id', pickup.input_id);
                    input.data('id', pickup.id);

                    var label = variant.find('label');
                    label.attr('for', pickup.input_id);
                    label.text(pickup.title);

                    variant.find(':input').data('id', pickup.id);

                    variant.find('.hint').text(pickup.description);
                    variant.show();

                    self.pickup_list.append(variant);
                }
            }, 10);

            var payment = (typeof pickup.payment === 'object') ? $.map(pickup.payment, function (p) {
                return p;
            }).join(", ") : pickup.payment;

            return {
                "type": "Feature",
                "id": pickup.id,
                "geometry": {
                    "type": "Point",
                    "coordinates": [pickup.lat, pickup.lng]
                },
                "properties": {
                    "balloonContent": ((pickup.description && (pickup.title !== pickup.description)) ? ('<div class="line title-line">' + pickup.description + '</div>') : '') +
                    (pickup.rate || payment ? (
                            '<div class="line payments-line">'
                            + (pickup.rate || '')
                            + ' '
                            + payment
                            + '</div>'
                        ) : ''
                    ) +
                    (pickup.comment ? ('<div class="line hint hint-line">' + pickup.comment.replace(/[\r\n]+/g, '<br/>') + '</div>') : '') +
                    (pickup.schedule_html ? ('<div class="line yandexdelivery-list">' + pickup.schedule_html + '</div>') : ''),
                    "balloonContentHeader": pickup.title,
                    "balloonContentFooter": '<div class="line actions">' +
                    '<div class="t-layout">' +
                    '<div class="t-column middle">' +
                    '<input class="js-yandexdelivery-button js-yandexdelivery-button-' + this.radio_id + '" type="button" value="Выбрать" data-id="' + pickup.id + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                    "clusterCaption": pickup.title,
                    "hintContent": pickup.title
                }
            };
        },
        select: function (id) {
            id = parseInt(id);
            if (this.pickup_list && (id > 0)) {
                var selector = ':input[name$="\.pickup_id\]"][value^="\#' + id + ' "]';
                var radio = this.pickup_list.find(selector);

                if (radio.length === 1) {
                    radio.attr('checked', true);
                    radio.change();
                    var self = this;
                    var container = radio.parents('li:first');
                    setTimeout(function () {
                        self.map.select(id);
                    }, 10);

                    if (this.pickup_scroll) {
                        setTimeout(function () {
                            var offset = self.pickup_list.scrollTop() + container.position().top;
                            self.pickup_list.parent().scrollTop(offset);
                        }, 1000);
                    }
                }

                return radio.length === 1;
            }
        },
        show: function (id) {
            this.map.exit();
            this.select(id);

            this.pickup_container.slideUp();

            var pickup = this.pickups[id];
            /**
             * @var {shippingYandexDeliveryPickup} pickup
             */
            if (pickup) {
                this.pickup_section.find('.yandexdelivery-pickup-header').text(pickup.title);

                this.pickup_section.find('.line.js-yandexdelivery-address').text((pickup.description !== pickup.title) ? pickup.description.replace(/[\r\n]+/g, '<br/>') : '');
                var payment = (typeof pickup.payment === 'object') ? $.map(pickup.payment, function (p) {
                    return p;
                }).join(", ") : pickup.payment;
                this.pickup_section.find('.line.js-yandexdelivery-payment').html(payment);
                this.pickup_section.find('.line.js-yandexdelivery-schedule').html(pickup.schedule_html);
                this.pickup_section.find('.line.hint').html(pickup.comment.replace(/[\r\n]+/g, '<br/>'));
            }
            this.pickup_section.slideDown();
        },
        change: function () {
            if (this.pickup_container && !this.pickup_container.is(':visible')) {
                this.pickup_section.slideUp();
                this.pickup_container.show();
            }

            if (this.pickup_list) {
                var self = this;
                setTimeout(function () {
                    self.pickup_list.change();
                }, 800);
            }
        },

        map: {
            id: null,
            map_container: null,
            /**
             * @property {ymaps} map
             */
            map: null,
            loading: false,
            manager: null,
            show: function (pickups, delay) {
                var self = this;
                if (this.map) {
                    this.map_container.show();
                    this.manager.add({
                        "type": "FeatureCollection",
                        "features": pickups
                    });
                    setTimeout(function () {
                        self.map.setBounds(self.manager.getBounds(), {checkZoomRange: true});
                    }, delay ? 1500 : 500);


                } else if (this.id) {
                    setTimeout(function () {
                        self.show(pickups, true);
                    }, 1000);
                }
            },
            select: function (id) {
                if (this.id) {
                    var self = this;
                    if (this.manager !== null) {
                        var pickup = this.manager.objects.getById(id);

                        if (pickup) {
                            if (!this.manager.objects.balloon.isOpen(id)) {
                                if (this.manager.objects.balloon.isOpen()) {
                                    this.manager.objects.balloon.close();
                                }
                            }

                            this.map.panTo(pickup.geometry.coordinates.map(parseFloat, '10')).then(function () {
                                self.manager.objects.balloon.open(id);
                            });
                        }
                    } else {
                        setTimeout(function () {
                            self.select(id);
                        }, 1000);
                    }
                }
            },
            exit: function () {
                if (this.map) {
                    var control = this.map.controls.get('fullscreenControl');
                    if (control.state.get('fullscreen')) {
                        control.exitFullscreen();
                    }
                }
            },
            init: function (center) {
                if (this.id) {
                    if ((typeof(ymaps) !== 'undefined') && (typeof(ymaps.Map) !== 'undefined')) {

                        if (!this.map) {
                            this.map_container = $('#' + this.id);
                            this.map_container.find('i.icon16.loading:first').remove();
                            this.map = new ymaps.Map(this.id, {
                                center: center || [55.76, 37.64],
                                zoom: 7
                            });

                            this.manager = new ymaps.ObjectManager({
                                clusterize: true,
                                gridSize: 32
                            });
                            this.map.geoObjects.add(this.manager);
                        }

                    } else {
                        var self = this;
                        if (!this.loading) {
                            this.loading = true;
                            $.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU', function () {
                                self.init();
                            });
                        } else {

                            setTimeout(function () {
                                self.init();
                            }, 500);
                        }
                    }
                }
            },
            reset: function () {
                if (this.manager) {
                    this.manager.removeAll();
                }
            }
        }
    };

    this.watch = function () {
        var rate = $('select[name="rate_id\[' + this.key + '\]"]');
        var self = this;
        if (rate.length) {
            if (!rate.data('initialized')) {
                rate.data('initialized', true);
                this.hide();
                this.initialized = true;
                setTimeout(function () {
                    rate = $(':input[name="rate_id\[' + self.key + '\]"]');
                    self.rate.init(self.form, rate);
                }, 500);
            }
        } else {
            this.hide();
        }
        setTimeout(function () {
            self.watch();
        }, 1500);
    };


    this.initialized = false;
    this.pickup.map.map = null;
    this.pickup.map.manager = null;
    this.pickup.map.id = id.map;
    this.rate.id = id.filter;

    var self = this;

    this.container = $('.shipping-' + self.key + ':first');
    this.form = $('.shipping-' + self.key + ':first .wa-form:not(.wa-address):first');
    this.address = $('.shipping-' + self.key + ':first .wa-form.wa-address:first');
    this.container = this.form.length ? this.form.parents('form:first') : null;
    if (this.form.length && this.container) {

        this.form.hide();

        if (this.pickup.map.id) {
            this.control_wrapper = this.form.find('#' + this.pickup.map.id).parents('.wa-field:first');
            this.fixUI();
            this.pickup.init(this.control_wrapper);
            this.loader.init(this.control_wrapper);
        }

        var input = this.container.find(':input[name="shipping_id"]');

        this.form.find(':input[name$="\[geo_id_to\]"]:first').parents('.wa-field:first').hide();

        this.courier.init(this.form);
        input.on('change.yandexdelivery', function () {
            if ((this.value.replace(/\..+$/, '') === self.key) && this.checked) {
                if (!self.initialized) {
                    instance.watch();
                }
            }
        }).change();

        input.on("change.yandexdelivery", 'select[name="rate_id[' + this.key + ']"]', function () {
            setTimeout(function () {
                var context = $(".shipping-" + self.key + ":first");
                $(':input[name="shipping_id"]', context).change();
            }, 200)
        });
    }

    return this;
}
