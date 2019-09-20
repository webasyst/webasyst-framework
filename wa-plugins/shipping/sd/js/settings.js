var systemShippingSDPluginSettings = (function ($) {

    systemShippingSDPluginSettings = function (options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.regions_url = options['regions_url'];
        that.current_currency = options['current_currency'];
        that.current_weight = options['current_weight'];
        that.current_length = options['current_length'];
        that.namespace = options['namespace'];
        that.date_format = options['date_format'];
        that.extra_dates = [];

        // TEMPLATES
        that.new_date_html = options.templates.new_date_html;
        that.new_datetime_html = options.templates.new_datetime_html;
        that.error_date_html = options.templates.error_date_html;
        that.new_photo_html = options.templates.new_photo_html;

        // INIT
        that.initClass();
    };

    systemShippingSDPluginSettings.prototype.initClass = function () {
        var that = this;

        that.initGetRegions();
        that.initCurrency();
        that.initWeightUnit();
        that.initLengthUnit();
        that.initWorkTime();
        that.initAddDay('workdays');
        that.initAddDay('weekend');
        that.validateDate();
        that.initImages();
        that.initSubmit();
        that.initDatepicker();
        that.preSaveEvent();
    };

    systemShippingSDPluginSettings.prototype.initCurrency = function () {
        var that = this,
            $currencies_select = that.$wrapper.find('.js-sd-select-currency'),
            $price_input = that.$wrapper.find('.js-sd-currency'),
            currency_span = $('<span>', {'class': 'js-sd-currency-unit'}).text(' ' + that.current_currency);

        //Set after load
        $price_input.after(currency_span);

        //Set if change
        $currencies_select.on('change', function () {
            var $self = $(this),
                $currency_span = that.$wrapper.find('.js-sd-currency-unit'),
                unit_text = $self.find('option:selected').text();

            $currency_span.text(unit_text);
        })

    };

    systemShippingSDPluginSettings.prototype.initWeightUnit = function () {
        var that = this,
            $weight_select = that.$wrapper.find('.js-sd-select-weight'),
            $weight_input = that.$wrapper.find('.js-sd-weight'),
            weight_span = $('<span>', {'class': 'js-sd-weight-unit'}).text(' ' + that.current_weight);

        //Set after load
        $weight_input.after(weight_span);

        //Set if change
        $weight_select.on('change', function () {
            var $self = $(this),
                $weight_span = that.$wrapper.find('.js-sd-weight-unit'),
                unit_text = $self.find('option:selected').text();

            $weight_span.text(' ' + unit_text);
        })
    };

    systemShippingSDPluginSettings.prototype.initLengthUnit = function () {
        var that = this,
            $length_select = that.$wrapper.find('.js-sd-select-length'),
            $length_input = that.$wrapper.find('.js-sd-length'),
            length_span = $('<span>', {'class': 'js-sd-length-unit'}).text(' ' + that.current_length);

        //Set after load
        $length_input.after(length_span);

        //Set if change
        $length_select.on('change', function () {
            var $self = $(this),
                $length_span = that.$wrapper.find('.js-sd-length-unit'),
                unit_text = $self.find('option:selected').text();

            $length_span.text(' ' + unit_text);
        })
    };

    systemShippingSDPluginSettings.prototype.initGetRegions = function () {
        var that = this,
            $countries = that.$wrapper.find('.js-sd-select-country'),
            $regions_wrapper = that.$wrapper.find('.js-sd-regions'),
            $regions_msg_wrapper = that.$wrapper.find('.js-sd-regions-msg'),
            $errormsg =  that.$wrapper.find('.js-sd-country-errormsg'),
            $region = that.$wrapper.find('.js-sd-select-region'),
            $loader = that.$wrapper.find('.js-sd-country-loader');

        $countries.on('change', function () {
            var $self = $(this),
                country_code = $self.val(),
                post_data = {country: country_code},
                $empty_option = $('<option/>').attr({'value': ''}).prop('selected', true);

            //reset regions
            $region.empty();
            //set empty option
            $region.append($empty_option);

            $regions_wrapper.hide();
            $regions_msg_wrapper.hide();
            $errormsg.hide();

            if (country_code) {
                $loader.show();

                $.post(that.regions_url, post_data, function (response) {
                    if (response.data && response.data.oOrder && response.data.oOrder.length && response.data.options) {
                        var order = response.data.oOrder,
                            options = response.data.options;

                        order.forEach(function (item) {
                            if (item !== '' && options[item]) {
                                var $option = $('<option/>').attr({'value': item}).text(options[item]);
                                $region.append($option);
                            }
                        });
                        $regions_wrapper.show();
                    } else {
                        $regions_msg_wrapper.show();
                    }
                });

                $loader.hide();
            }
        });
    };

    systemShippingSDPluginSettings.prototype.initWorkTime = function () {
        var that = this,
            $worktime_toggle = that.$wrapper.find('.js-sd-worktime-toggle');

        //Disable time input
        $worktime_toggle.on('change', function () {
                var self = this,
                    $self = $(self),
                    $day_wrapper = $self.closest('tr'),
                    $fields = $day_wrapper.find('input[type=time]');

                if (self.checked) {
                    $fields.prop('required', true);
                } else {
                    $fields.prop('required', false);
                }
            }
        );

        //Set Min Max time
        that.$wrapper.on('change', '.js-sd-time-start', function () {
            var $self = $(this),
                time = $self.val(),
                $day_wrapper = $self.closest('tr'),
                $time_end = $day_wrapper.find('.js-sd-time-end, .js-sd-end-process');
            $time_end.attr('min', time);
        });

        that.$wrapper.on('change', '.js-sd-time-end', function () {
            var $self = $(this),
                time = $self.val(),
                $day_wrapper = $self.closest('tr'),
                $time = $day_wrapper.find('.js-sd-time-start, .js-sd-end-process');
            $time.attr('max', time);
        });

        that.$wrapper.on('change', '.js-sd-end-process', function () {
            var $self = $(this),
                time = $self.val(),
                $day_wrapper = $self.closest('tr'),
                $time = $day_wrapper.find('.js-sd-time-end');
            $time.attr('min', time);
        });

        that.updateMinMaxTime();
    };

    /**
     * Validate all date field and show error message
     */
    systemShippingSDPluginSettings.prototype.validateDate = function () {
        var that = this,
            $wrapper = that.$wrapper;

        $wrapper.on('change', '.js-sd-date', function () {
            var $self = $(this),
                date = $self.val().trim(),
                $table_wrapper = $self.closest('.js-sd-worktime-wrapper'),
                $error_date_msg = $table_wrapper.find('.js-sd-error-date-msg');

            $self.removeClass('js-sd-error-date error');
            $error_date_msg.remove();

            //dd.mm.yyyy or mm/dd/yyyy
            if (!date.match(/^((([0-9]|[0-2][0-9]|3[0-1])\.([0-9]|0[0-9]|1[0-2])\.\d{4}$)|(([0-9]|0[0-9]|1[0-2])\/([0-9]|[0-2][0-9]|3[0-1])\/\d{4}$))/ui)) {
                $self.addClass('js-sd-error-date error');
            }

            var $error_date = $table_wrapper.find('.js-sd-error-date');

            if ($error_date.length) {
                $table_wrapper.prepend(that.error_date_html)
            }
        })
    };

    systemShippingSDPluginSettings.prototype.initAddDay = function (type) {
        var that = this,
            $table = that.$wrapper.find('.js-sd-' + type),
            $tbody = $table.find('tbody'),
            template = that.new_datetime_html;
        if (type === 'weekend') {
            template = that.new_date_html;
        }

        //add new date)
        $table.on('click', '.js-sd-add-date', function () {
            $tbody.append(template);
            that.updateMinMaxTime();

            setNames($tbody, type);

            //add new date to ignore
            that.Datepicker();
        });

        //Delete date
        $table.on('click', '.js-sd-delete-date', function (e) {
            e.preventDefault();
            var $self = $(this),
                $tr = $self.closest('tr');

            $tr.remove();
            setNames($tbody, type);

            //delete date from ignore
            that.Datepicker();
        });

        /**
         * Reset field name attribute
         * @param $tbody
         * @param type
         */
        function setNames($tbody, type) {
            $tbody.find('tr').each(function (i, tr) {
                var $tr = $(tr);

                $tr.find('input[data-name]').each(function () {
                    var $self = $(this),
                        name = $self.data('name');
                    $self.attr('name', that.namespace + '[' + type + ']' + '[' + i + ']' + '[' + name + ']');
                })
            });
        }
    };
    systemShippingSDPluginSettings.prototype.initImages = function () {
        var that = this,
            image = new FormData,
            $photo_input = that.$wrapper.find('.js-upload-image'),
            $photo_wrapper = that.$wrapper.find('.js-sd-images');

        //Load image
        $photo_input.on('change', function () {
            image.append('file', $photo_input.prop('files')[0]);
            $.ajax({
                url: "?module=pages&action=uploadimage",
                type: 'POST',
                data: image,
                cache: false,
                contentType: false,
                processData: false
            }).done(function (response) {
                $photo_input.prop('files', null);
                $photo_input.val(null);

                if (response.errors) {

                } else if (response.data) {
                    var template = that.new_photo_html;
                    template = template.replace(/%s/g, response.data);
                    $photo_wrapper.append(template);
                    setNameAttr();
                }
            })
        });

        //delete image
        $photo_wrapper.on('click', '.js-sd-image-delete', function (e) {
            e.preventDefault();
            var $self = $(this),
                $link_wrapper = $self.closest('li');

            $link_wrapper.remove();
            setNameAttr();
        });

        function setNameAttr() {
            $photo_wrapper.find('li').each(function (i, li) {
                var $li = $(li);

                $li.find('input').each(function () {
                    var $self = $(this);

                    $self.attr('name', that.namespace + '[photos]' + '[' + i + ']' + '[uri]');
                })
            });
        }
    };

    /**
     * Init trigger to show error messages
     */
    systemShippingSDPluginSettings.prototype.initSubmit = function () {
        var that = this,
            $form = that.$wrapper.closest('form');

        $form.on('submit', function () {
            var $date = that.$wrapper.find('.js-sd-date'),
                $time = that.$wrapper.find('.js-sd-time-start, .js-sd-time-end');

            $date.trigger('change');
            $time.trigger('change');
        });
    };

    systemShippingSDPluginSettings.prototype.preSaveEvent = function () {
        var that = this,
            $form = that.$wrapper.closest('form'),
            $errormsg =  that.$wrapper.find('.js-sd-country-errormsg'),
            $country_selector =  that.$wrapper.find('.js-sd-select-country');

        $form.on('shop_save_shipping', function ($event) {
            $errormsg.hide();

            if (!$country_selector.val()) {
                $errormsg.show();
            }

            return !!$country_selector.val()
        });
    };

    systemShippingSDPluginSettings.prototype.initDatepicker = function () {
        var that = this;

        //first load event
        that.Datepicker();
        $('#ui-datepicker-div').hide();

        that.$wrapper.on('change', '.js-datepicker', function () {
            that.Datepicker();
        });

    };

    systemShippingSDPluginSettings.prototype.Datepicker = function () {
        var that = this,
            $pickers = that.$wrapper.find('.js-datepicker');

        $pickers.datepicker(
            {
                dateFormat: that.date_format,
                beforeShowDay: function (date) {
                    var string = $.datepicker.formatDate(that.date_format, date);
                    return [that.extra_dates.indexOf(string) === -1]
                },
                create: parseDates()
            }
        );

        function parseDates() {
            that.extra_dates = [];
            that.$wrapper.find('.js-datepicker').each(function () {
                that.extra_dates.push($(this).val());
            });
        }

    };

    systemShippingSDPluginSettings.prototype.updateMinMaxTime = function () {
        var that = this;
        that.$wrapper.find('input[type=time]').trigger('change')
    };

    return systemShippingSDPluginSettings;

})(jQuery);