waCourierShippingPluginSettings = ( function($) {

    'use strict';

    var waCourierShippingPluginSettings = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$additional_address_fields = that.$wrapper.find('.js-additional-address-fields');
        that.$additional_address_fields_errors_place = $('.js-additional-address-fields-errors');

        // VARS
        that.locale_strings = options["locale_strings"] || {};
        that.namespace = options["namespace"] || '';
        that.xhr_url = options["xhr_url"] || "";

        // CONST
        that.js_validate = options["js_validate"] || false;

        // INIT
        that.init();
    };

    waCourierShippingPluginSettings.prototype.init = function () {

        var that = this;

        that.initAdditionalFields();
        that.initOther();
        that.initOnSubmit();

    };

    waCourierShippingPluginSettings.prototype.initOther = function() {
        var that = this,
            $wrapper = that.$wrapper;


        $wrapper.find(':input[name$="\[map\]"]').change(function (event) {
            var $scope = $(this).parents('div.field');
            if (!event.originalEvent) {
                $scope.find('div.js-map-adapter-settings').hide();
                if (this.checked) {
                    $scope.find('div.js-map-adapter-settings[data-adapter-id="' + this.value + '"]').show();
                }
            } else {
                $scope.find('div.js-map-adapter-settings').slideUp();
                if (this.checked) {
                    $scope.find('div.js-map-adapter-settings[data-adapter-id="' + this.value + '"]').slideDown();
                }
            }
        });
        $wrapper.find(':input[name$="\[map\]"]:checked').trigger('change');

        $wrapper.on('click', '.add-rate', function () {
            var el = $(this);
            var table = el.parents('table:first');
            var last = table.find('tr.rate:last');
            var clone = last.clone();

            clone.find('input').each(function () {
                var input = $(this);

                // increase index inside input name
                var name = input.attr('name');
                input.attr('name', name.replace(/\[rate]\[(\d+)]/, function (str, p1) {
                    return '[rate][' + (parseInt(p1, 10) + 1) + ']';
                }));

                input.val(0);
            });

            last.after(clone);

            return false;
        });

        $wrapper.on('click', '.delete-rate', function () {
            var el = $(this);
            var table = el.parents('table:first');
            if (table.find('tr.rate').length > 1) {
                el.parents('tr:first').remove();
            } else {
                el.parents('tr:first').find('input').val(0);
            }
            return false;
        });

        $wrapper.find('input[name$="[rate_by]"]').change(function () {
            $wrapper.find('.rate-by').children().hide().filter('.' + this.value).show();
        });

        $wrapper.find('select[name$="[currency]"]').change(function () {
            $wrapper.find('span.currency').text(this.value);
        });

        $wrapper.find('select[name$="[weight_dimension]"]').change(function () {
            $wrapper.find('.rate-by .weight.dimension').text(
                $('option:selected', this).attr('data-value')
            );
        });

        $wrapper.find(':input[name*="\[rate_zone\]"]').on('change keyup', function () {
            var name = this.name.replace(/^.+\[rate_zone]\[(.+)]$/, '$1');
            var checkbox = $wrapper.find('input[name$="\[contact_fields\]\[' + name + '\]"]');
            var value = this.value;
            /**
             * @var HTMLInputElement checkbox
             */
            checkbox.attr('disabled', !!value);
            if (value) {
                checkbox.attr('checked', true);
            }
        });


        (function () {

            var name = that.namespace + '[rate_zone][region]';
            var target = $wrapper.find('div.region');
            var loader = $wrapper.find('.region .loading');
            var old_val = target.find('select, input').val();

            $wrapper.find('select[name$="[country]"]').change(function () {
                loader.show();
                $.post(that.xhr_url, {
                    country: this.value
                }, function (r) {
                    if (
                        r.data
                        && r.data.options
                        && r.data.oOrder
                        && r.data.oOrder.length
                    ) {
                        var select = $(
                            "<select name='" + name + "'>" +
                            "<option value=''></option>" +
                            "</select>"
                        );
                        var o, selected = false;
                        for (var i = 0; i < r.data.oOrder.length; i++) {
                            o = $('<option></option>').attr(
                                'value', r.data.oOrder[i]
                            ).text(
                                r.data.options[r.data.oOrder[i]]
                            ).attr(
                                'disabled', r.data.oOrder[i] === ''
                            );
                            if (!selected && old_val === r.data.oOrder[i]) {
                                o.attr('selected', true);
                                selected = true;
                            }
                            select.append(o);
                        }
                        target.html(select);
                    } else {
                        target.html("<input name='" + name + "' value='0' type='hidden'>");
                    }
                    loader.hide();
                }, 'json');
            });

            $wrapper.on('change', 'select[name="' + name + '"]', function () {
                old_val = this.value;
            });

            var rate_name = that.namespace + '[rate]';
            $wrapper.on('change keyup', 'input[name^="' + rate_name + '"][name$="[limit]"]', function () {
                this.value = this.value.replace(',', '.').replace(/[^\d\\.]+/, '');
                if (parseFloat(this.value) < 0) {
                    this.value = 0;
                }
                var td = $(this).parents('tr').find('td:first');
                if (parseFloat(this.value) == 0) {
                    td.text('≥');
                } else {
                    td.text('>');
                }
            });
        })();

        var $form = $wrapper.parents('form');
        $form.find('input[name$="\[delivery_time\]"]').change(function (event) {
            var $input = $form.find('input[name$="\[exact_delivery_time\]"]').parents('div.field');
            if (this.checked) {
                if (!event.originalEvent) {
                    if (this.value === 'exact_delivery_time') {
                        $input.show();
                    } else {
                        $input.hide();
                    }
                } else {
                    if (this.value === 'exact_delivery_time') {
                        $input.slideDown();
                    } else {
                        $input.slideUp();
                    }
                }
            }
        }).change();
    };

    /**
     * Validate settings
     * @returns {boolean} TRUE if is all valid
     */
    waCourierShippingPluginSettings.prototype.validate = function() {
        var that = this,
            js_validate = that.js_validate;

        if (!js_validate) {
            return true;
        }

        return that.validateAdditionalAddressFields();
    };

    waCourierShippingPluginSettings.prototype.initOnSubmit = function() {
        var that = this,
            $wrapper = that.$wrapper,
            $form = $wrapper.parents('form');
        $form.submit(function (e) {
            if (!that.validate()) {
                e.stopPropagation();
                e.preventDefault();
            }
        });
    };

    waCourierShippingPluginSettings.prototype.initAdditionalFields = function () {
        var that = this,
            $additional_address_fields = that.$additional_address_fields;

        $additional_address_fields.on('change', ':checkbox', function () {

            that.clearAdditionalAddressFieldsErrors();

            var $checkbox = $(this),
                field_id = $checkbox.data('field-id'),
                $select = $additional_address_fields.find('select[data-field-id="' + field_id + '"]');
            if ($checkbox.is(':checked')) {
                $select.removeAttr('disabled').closest('.js-select-wrapper').show();
            } else {
                $select.attr('disabled', true).closest('.js-select-wrapper').hide();
            }
        });

        $additional_address_fields.on('change', 'select', function () {
            that.clearAdditionalAddressFieldsErrors();
        });
    };

    waCourierShippingPluginSettings.prototype.clearAdditionalAddressFieldsErrors = function() {
        var that = this,
            $additional_address_fields = that.$additional_address_fields,
            $errors_place = that.$additional_address_fields_errors_place;
        $additional_address_fields.find('.error').removeClass('error');
        $errors_place.html('').hide();
    };

    /**
     * Validate additional settings fields settings
     * @returns {boolean} TRUE if is valid
     */
    waCourierShippingPluginSettings.prototype.validateAdditionalAddressFields = function () {
        var that = this,
            $additional_address_fields = that.$additional_address_fields,
            $errors_place = that.$additional_address_fields_errors_place,
            locale_strings = that.locale_strings || {};

        that.clearAdditionalAddressFieldsErrors();

        var assigned_map = {},
            is_error = false;

        $additional_address_fields.find(':checkbox').filter(':checked').each(function () {
            var $checkbox = $(this),
                field_id = $checkbox.data('field-id'),
                $select = $additional_address_fields.find('select[data-field-id="' + field_id + '"]'),
                val = $select.val();

            if (assigned_map[val]) {
                $select.addClass('error');
                $additional_address_fields.find('select[data-field-id="' + assigned_map[val] + '"]').addClass('error');
                is_error = true;
            }
            assigned_map[val] = field_id;
        });

        if (is_error) {
            $errors_place.html(locale_strings.additional_address_field_assign_error || '');
            $errors_place.show();
        }

        return !is_error;
    };

    return waCourierShippingPluginSettings;

})(jQuery);
