( function($) {

    var waTheme = {
        // VARS
        site_url: "",
        app_id: "",
        app_url: "",
        locale: "",
        is_touch_enabled: ("ontouchstart" in window),
        is_frame: isFrame(),

        //
        apps: {},

        // DOM
        layout: {},

        // FUNCTIONS
        init: {}, // constructor storage

        addFonts: function(fonts) {
            var content = [];

            $.each(fonts, function(i, font) {  addFont(font); });

            function addFont(font) {
                var name = font.name,
                    font_uri = font.uri;

                content.push("@import url('" + font_uri + "');");
            }

            render( content.join("\n") );

            function render(content) {
                var style = document.createElement("style");
                style.rel = "stylesheet";
                document.head.appendChild(style);
                style.textContent = content;
            }
        },

        // Методы для работы с ценой (форматирование в валюте за единицу измерения и т.д.)

        currencies: {
            "default": {
                code : null,
                fraction_divider: ".",
                fraction_size   : 2,
                group_divider   : " ",
                group_size      : 3,
                pattern_html    : "<span class=\"price\">%s</span>",
                pattern_text    : "%s",
                pattern_unit    : "%s/%unit",
                rounding        : 0.01
            }
        },

        /**
         * @param {object} currency_data
         * @return undefined
         * */
        addCurrency: function(currency_data) {
            var self = this;

            if (currency_data) {
                self.currencies[currency_data["code"]] = currency_data;
            }
        },

        /**
         * @param {string|number} price
         * @param {object?} options
         * @return {string}
         * */
        formatPrice: function(price, options) {
            var self = this,
                result = price;

            // Опции
            options = (typeof options !== "undefined" ? options : {});
            options.unit = (typeof options.unit === "string" ? options.unit : null);
            options.html = (typeof options.html === "boolean" ? options.html : true);
            options.currency = (typeof options.currency === "string" ? options.currency : "default");

            // Валидация валюты
            if (!options.currency || !self.currencies[options.currency]) {
                console.error("ERROR: Currency is not exist");
                return result;
            }

            var format = self.currencies[options.currency];

            try {
                price = parseFloat(price);
                if (price >= 0) {
                    price = price.toFixed(format.fraction_size);

                    var price_floor = Math.floor(price),
                        price_string = getGroupedString("" + price_floor, format.group_size, format.group_divider),
                        fraction_string = getFractionString(price - price_floor);

                    result = (options.html ? format.pattern_html : format.pattern_text)
                        .replace("%s", price_string + fraction_string );

                    if (options.unit) {
                        var unit = (options.html ? '<span class="unit">'+options.unit+'</span>' : options.unit);
                        result = format.pattern_unit
                            .replace("%s", result)
                            .replace("%unit", unit);
                    }
                }
            } catch(e) {
                if (console && console.log) {
                    console.log(e.message, price);
                }
            }

            if (options.html) {
                result = '<span class="price-wrapper">'+result+'</span>';
            }

            return result;

            function getGroupedString(string, size, divider) {
                var result = "";

                if (!(size && string && divider)) {
                    return string;
                }

                var string_array = string.split("").reverse();

                var groups = [];
                var group = [];

                for (var i = 0; i < string_array.length; i++) {
                    var letter = string_array[i],
                        is_first = (i === 0),
                        is_last = (i === string_array.length - 1),
                        delta = (i % size);

                    if (delta === 0 && !is_first) {
                        groups.unshift(group);
                        group = [];
                    }

                    group.unshift(letter);

                    if (is_last) {
                        groups.unshift(group);
                    }
                }

                for (i = 0; i < groups.length; i++) {
                    var is_last_group = (i === groups.length - 1),
                        _group = groups[i].join("");

                    result += _group + ( is_last_group ? "" : divider );
                }

                return result;
            }

            function getFractionString(number) {
                var result = "";

                if (number > 0) {
                    number = number.toFixed(format.fraction_size + 1);
                    number = Math.round(number * Math.pow(10, format.fraction_size))/Math.pow(10, format.fraction_size);
                    var string = number.toFixed(format.fraction_size);
                    result = string.replace("0.", format.fraction_divider);
                }

                return result;
            }
        }
    };

    addApp(["site", "shop", "hub", "blog", "photos", "helpdesk"]);

    window.waTheme = (window.waTheme ? $.extend(waTheme, window.waTheme) : waTheme);

    /**
     * @return {Boolean}
     * */
    function isFrame() {
        var result = false;

        try {
            result = (window.self !== window.top);
        } catch(e) {}

        return result;
    }

    /**
     * @param {Array|String} app
     * */
    function addApp(app) {
        var that = waTheme,
            type = typeof app;

        switch (type) {
            case "object":
                $.each(app, function(index, app_name) {
                    addApp(app_name);
                });
                break;
            case "string":
                add(app);
                break;
            default:
                break;
        }

        /**
         * @param {String} app_name
         * */
        function add(app_name) {
            if (!app_name.length) {return false;}

            that.apps[app_name] = {};
            that.init[app_name] = {};
        }
    }

})(jQuery);