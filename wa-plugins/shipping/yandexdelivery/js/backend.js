/**
 *
 * @param {string} key
 * @param {string} id
 * @returns {ShippingYandexdeliveryBackend}
 * @constructor
 */
function ShippingYandexdeliveryBackend(key, id) {
    var instance = this;
    this.key = key;
    this.$filter = $('#' + id);

    this.$scope = this.$filter.parents('div.fields');

    this.$geo_id = this.$scope.find(':input[name$="\[geo_id_to\]"]');
    this.$geo_id_field = this.$geo_id.parents('div.field');
    this.$form = this.$scope.parents('form');

    this.$shipping_id = this.$form.find(':input[name="shipping_id"]');
    this.$interval = this.$scope.find(':input[name$="\[desired_delivery\.interval\]"]');
    this.$field = this.$interval.parents('div.field');
    this.$date = this.$scope.find(':input[name$="\[desired_delivery\.date_str\]"]');

    this.datepicker = function (input, courier, forced) {
        if (typeof (input.datepicker) !== 'function') {
            setTimeout(function () {
                instance.datepicker(input, courier);
            }, 100);
        } else {
            input.data('available_days', courier.available_days);
            input.datepicker('option', 'minDate', courier.offset);
            input.datepicker('refresh');
            if (!forced) {
                setTimeout(function () {
                    instance.datepicker(input, courier, true);
                }, 200);
            }

            input.datepicker('hide');
            input.datepicker('widget').hide();
        }
    };

    this.courier = function ($option) {

        var courier = $option.data('courier'),
            selected_interval = $option.data('desired-delivery-selected-interval');

        this.$field.find(':input').attr('disabled', null);

        this.$interval.find('>option').remove();

        courier.available_days = [];

        for (var interval in courier.intervals) {
            if (courier.intervals.hasOwnProperty(interval)) {
                courier.available_days = courier.available_days.concat(courier.intervals[interval]);
                $new_option = $("<option></option>")
                                .attr("value", interval)
                                .data('days', courier.intervals[interval])
                                .text(interval);
                if (interval == selected_interval) {
                    $new_option.attr('selected', 'selected');
                }

                this.$interval.append($new_option);
            }
        }

        this.$interval.prepend($("<option></option>")
            .attr("value", null)
            .data('days', courier.available_days)
            .text(''));

        var interval_value = this.$interval.data('value');
        if (interval_value !== undefined) {
            this.$interval.val(interval_value);
        }

        this.$date.attr('placeholder', courier.placeholder);
        this.datepicker(this.$date, courier);

        this.$field.slideDown();
    };


    if (this.$geo_id.find('>option').length > 1) {
        this.$geo_id_field.show();
        this.$geo_id_field.attr('disabled', null);
    } else {
        this.$geo_id_field.hide();
        this.$geo_id_field.attr('disabled', true);
    }

    this.change = function (select) {
        try {
            var value = select.val();
            if (value.replace(/\W.*$/, '') === this.key) {
                var $option = select.find(':selected');
                var type = $option.data('type');
                switch (type) {
                    case 'todoor':
                    case '"todoor"':
                        this.courier($option);
                        break;
                    default:
                        this.$field.find(':input').attr('disabled', true);
                        this.$field.slideUp();
                        break;
                }
            }

        } catch (e) {
            console.error(e.message, e);
        }
    };


    if (this.$shipping_id.length) {
        setTimeout(function () {
            instance.change(instance.$shipping_id);
        }, 10);

        this.$shipping_id.change(function () {
            instance.change($(this));
        });

    }

    return this;
}
