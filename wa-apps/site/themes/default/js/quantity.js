( function($, waTheme) {

    var validate = function(type, value) {
        value = (typeof value === "string" ? value : "" + value);

        var result = value;

        switch (type) {
            case "float":
                var float_value = parseFloat(value);
                if (float_value >= 0) {
                    result = float_value.toFixed(3) * 1;
                }
                break;

            case "number":
                var white_list = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", ".", ","],
                    letters_array = [],
                    divider_exist = false;

                $.each(value.split(""), function(i, letter) {
                    if (letter === "." || letter === ",") {
                        letter = ".";
                        if (!divider_exist) {
                            divider_exist = true;
                            letters_array.push(letter);
                        }
                    } else {
                        if (white_list.indexOf(letter) >= 0) {
                            letters_array.push(letter);
                        }
                    }
                });

                result = letters_array.join("");
                break;

            case "integer":
                var white_list = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"],
                    letters_array = [];

                $.each(value.split(""), function(i, letter) {
                    if (white_list.indexOf(letter) >= 0) {
                        letters_array.push(letter);
                    }
                });

                result = letters_array.join("");
                break;

            default:
                break;
        }

        return result;
    };

    var Quantity = ( function($) {

        Quantity = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$field = that.$wrapper.find(".js-quantity-field");
            that.$min = that.$wrapper.find(".js-decrease");
            that.$max = that.$wrapper.find(".js-increase");
            that.$min_desc = that.$wrapper.find(".js-min-description");
            that.$max_desc = that.$wrapper.find(".js-max-description");

            // CONST
            that.locales = options["locales"];
            that.denominator = (typeof options["denominator"] !== "undefined" ? validate("float", options["denominator"]) : 1);
            that.denominator = (that.denominator > 0 ? that.denominator : 1);
            that.min = (typeof options["min"] !== "undefined" ? validate("float", options["min"]) : that.denominator);
            that.min = (that.min > 0 ? that.min : 1);
            that.max = (typeof options["max"] !== "undefined" ? validate("float", options["max"]) : 0);
            that.max = (that.max >= 0 ? that.max : 0);
            that.step = (typeof options["step"] !== "undefined" ? validate("float", options["step"]) : 1);
            that.step = (that.step > 0 ? that.step : 1);
            that.stock_unit = options["stock_unit"];

            // DYNAMIC VARS
            that.value = getValue();

            // INIT
            that.init();

            function getValue() {
                var value = that.$field.val();

                if (parseFloat(value) > 0) {
                    //
                } else {
                    that.$field.val(that.min);
                    value = that.min;
                }

                return that.validate(value);
            }
        };

        Quantity.prototype.init = function() {
            var that = this;

            that.$field.data("controller", that);
            that.$wrapper.data("controller", that);
            that.$wrapper.on("click", ".js-increase", function(event) {
                event.preventDefault();
                that.set(that.value + that.step);
            });

            that.$wrapper.on("click", ".js-decrease", function(event) {
                event.preventDefault();
                that.set(that.value - that.step);
            });

            that.$field.on("input", function() {
                var value = that.$field.val(),
                    new_value = (typeof value === "string" ? value : "" + value);

                new_value = validateNumber(new_value);
                if (new_value !== value) {
                    that.$field.val(new_value);
                }
            });

            that.$field.on("change", function() {
                that.set(that.$field.val());
            });

            //

            that.setDescription();

            //

            function validateNumber(value) {
                var white_list = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "."],
                    letters_array = [],
                    divider_exist = false;

                value = value.replace(/,/g, ".");

                $.each(value.split(""), function(i, letter) {
                    if (letter === ".") {
                        if (!divider_exist) {
                            divider_exist = true;
                            letters_array.push(letter);
                        }
                    } else {
                        if (white_list.indexOf(letter) >= 0) {
                            letters_array.push(letter);
                        }
                    }
                });

                return letters_array.join("");
            }
        };

        /**
         * @param {string|number} value
         * @param {object?} options
         * */
        Quantity.prototype.set = function(value, options) {
            options = (typeof options !== "undefined" ? options : {});

            var that = this;

            value = that.validate(value);
            that.$field.val(value);
            that.value = value;

            that.setDescription();

            that.$wrapper.trigger("quantity.changed", [that.value, that]);
        };

        Quantity.prototype.validate = function(value) {
            var that = this,
                result = value;

            value = (typeof value !== "number" ? parseFloat(value) : value);
            value = parseFloat(value.toFixed(3));

            // левая граница
            if (!(value > 0)) { value = that.min; }

            if (value > 0 && value < that.min) { value = that.min; }

            // центр
            var steps_count = Math.floor(value/that.denominator);
            var x1 = (that.denominator * steps_count).toFixed(3) * 1;
            if (x1 !== value) {
                value = that.denominator * (steps_count + 1);
            }

            // правая граница
            if (that.max && value > that.max) { value = that.max; }

            return validate("float", value);
        };

        Quantity.prototype.update = function(options) {
            var that = this;

            var min = (typeof options["min"] !== "undefined" ? validate("float", options["min"]) : that.min),
                max = (typeof options["max"] !== "undefined" ? (parseFloat(options["max"]) >= 0 ? validate("float", options["max"]) : 0) : that.max),
                step = (typeof options["step"] !== "undefined" ? validate("float", options["step"]) : that.step),
                value = (typeof options["value"] !== "undefined" ? that.validate(options["value"]) : that.value);

            if (min > 0 && min !== that.min) { that.min = min; }
            if (max >= 0 && max !== that.max) { that.max = max; }
            if (step > 0 && step !== that.step) { that.step = step; }
            if (value > 0 && value !== that.value) { that.value = value; }

            that.set(that.value);
        };

        Quantity.prototype.setDescription = function(debug) {
            var that = this;

            var show_step = (that.step && that.step > 0 && that.step !== 1),
                near_limit_class = "is-near-limit",
                locked_class = "is-locked";

            toggleDescription(that.$min, that.$min_desc, that.min, that.locales["min"]);
            toggleDescription(that.$max, that.$max_desc, that.max, that.locales["max"]);

            function toggleDescription($button, $desc, limit, locale) {
                var description = null,
                    limit_string = locale.replace("%s", (limit ? `<span class="s-step">${limit} ${that.stock_unit}</span>` : ""));

                $button
                    .removeClass(locked_class)
                    .removeClass(near_limit_class);

                if (that.value === limit) {
                    $button.addClass(locked_class);
                    description = limit_string;
                } else {
                    if (show_step) {
                        if (limit > 0 && validate("float", Math.abs(that.value - limit)) < that.step) {
                            $button.addClass(near_limit_class);
                            description = limit_string;
                        } else {
                            description = `${that.step} ${that.stock_unit}`;
                        }
                    }
                }

                if (description) {
                    $desc.html(description).show();
                } else {
                    $desc.hide().html("");
                }
            }
        };

        return Quantity;

    })($);

    waTheme.init.shop.Quantity = Quantity;

})(jQuery, window.waTheme);