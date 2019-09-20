var systemShippingBoxberryPluginSettings = (function ($) {

    systemShippingBoxberryPluginSettings = function (options) {
        var that = this;
        // DOM
        that.$wrapper = options["$wrapper"];

        // VAR
        that.points_for_parcel = options["points_for_parcel"];
        that.saved_token = options["saved_token"];

        that.init();
    };

    systemShippingBoxberryPluginSettings.prototype.init = function () {
        var that = this;

        that.initPointsForParcelAutocomplete();
        that.initPreSaveEvent();
        that.initChangeToken();
    };

    systemShippingBoxberryPluginSettings.prototype.initChangeToken = function () {
        var that = this,
            $token = that.$wrapper.find('.js-boxberry-token');

        $token.on('change', function () {
            var $self = $(this),
                $autocomplete = that.$wrapper.find('.js-boxberry-targetstart-autocomplete'),
                $errormsg = that.$wrapper.find('.s-js-boxberry-targetstart-autocomplete'),
                $message = that.$wrapper.find('.s-js-boxberry-targetstart-start-message'),
                $points_wrapper = that.$wrapper.find('.js-boxberry-parcel-points-wrapper'),
                $points_list = that.$wrapper.find('.js-boxberry-parcel-points-list');

            if (!that.saved_token || $self.val() !== that.saved_token) {
                $autocomplete.val('').hide();
                $message.show();
                $points_list.html('');
                $points_wrapper.hide();
                $errormsg.hide();
            } else {
                $autocomplete.show();
                $message.hide();
            }
        });
        $token.trigger('change');
    };

    systemShippingBoxberryPluginSettings.prototype.initPreSaveEvent = function () {
        var that = this,
            $form = that.$wrapper.closest('form'),
            $errormsg = that.$wrapper.find('.s-js-boxberry-targetstart-autocomplete');

        // Before saving, check whether the parcel point is selected
        $form.on('shop_save_shipping', function ($event) {
            var $points_list = that.$wrapper.find('.js-boxberry-parcel-points-list'),
                token = that.$wrapper.find('.js-boxberry-token').val(),
                result = true;

            if (token === that.saved_token && !$points_list.val()) {
                $errormsg.show();
                result = false;
            } else {
                $errormsg.hide();
            }

            return result;
        });
    };

    systemShippingBoxberryPluginSettings.prototype.initPointsForParcelAutocomplete = function () {
        var that = this,
            $input = that.$wrapper.find('.js-boxberry-targetstart-autocomplete'),
            $points_list = that.$wrapper.find('.js-boxberry-parcel-points-list'),
            $points_wrapper = that.$wrapper.find('.js-boxberry-parcel-points-wrapper'),
            $errormsg = that.$wrapper.find('.s-js-boxberry-targetstart-autocomplete');

        $input.on('change', function () {
            var $self = $(this);

            if (!$self.val()) {
                $points_list.html('');
                $points_wrapper.hide();
            }
        });

        $input.autocomplete({
            source: function (request, response) {
                var term = request['term'].toLowerCase(),
                    result = [];

                // Hide the error of the previous search
                $errormsg.hide();

                $.each(that.points_for_parcel, function (city) {
                    var city_lower = city.toLowerCase();

                    // We are looking for the city which includes the term
                    if (city_lower.indexOf(term) !== -1) {
                        result.push(city);
                    }
                });

                // If no cities are found, show an error
                if (!result.length) {
                    $points_list.html('');
                    $points_wrapper.hide();
                    $errormsg.css('display', 'block');
                }

                response(result);
            },
            minLength: 3,
            delay: 300,
            select: function (event, ui) {
                var $self = $(this),
                    selected_city = ui.item.value,
                    points = that.points_for_parcel[selected_city];

                // Show the list of points for the city
                $points_list.html('');
                $points_wrapper.show();

                $.each(points, function (i, point_data) {
                    var option = $('<option value=""></option>');
                    option.val(point_data.code);
                    option.text(point_data.name);

                    $points_list.append(option);
                });

                $self.val(selected_city);
                return false;
            },
            focus: function () {
                return false;
            }
        });
    };


    return systemShippingBoxberryPluginSettings;

})(jQuery);