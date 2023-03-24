( function($) {

    var ProductPage = ( function($) {

        ProductPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // CONST
            that.skus_features_html = options["skus_features_html"];

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        ProductPage.prototype.init = function() {
            var that = this;

            that.initFeaturesSection();
        };

        ProductPage.prototype.initFeaturesSection = function() {
            var that = this;

            var $section = that.$wrapper.find(".js-features-section");

            /**
             * @description this event will happen in the product.cart.html template when the user changes sku
             * */
            that.$wrapper.on("product_sku_changed", function(event, sku_id) {
                var html = (that.skus_features_html[sku_id] ? that.skus_features_html[sku_id] : "");
                $section.html(html);
                html ? $section.show() : $section.hide();
                updateURL(sku_id);
            });

            function updateURL(sku_id) {
                var key_name = "sku";
                var search_object = stringToObject(window.location.search.substring(1));

                if (sku_id) {
                    search_object[key_name] = sku_id;
                } else {
                    delete search_object[key_name];
                }

                var search_string = objectToString(search_object);
                var new_URL = location.origin + location.pathname + search_string + location.hash;

                if (typeof history.replaceState === "function") {
                    history.replaceState(null, document.title, new_URL);
                }

                function stringToObject(string) {
                    var result = {};

                    string = string.split("&");

                    $.each(string, function(i, value) {
                        if (value) {
                            var pair = value.split("=");
                            result[ decodeURIComponent( pair[0] ) ] = decodeURIComponent( pair[1] ? pair[1] : "" );
                        }
                    });

                    return result;
                }

                function objectToString(object) {
                    var result = "",
                        array = [];

                    $.each(object, function(key, value) {
                        array.push(encodeURIComponent(key) + "=" + encodeURIComponent(value));
                    });

                    if (array.length) {
                        result = "?" + array.join("&");
                    }

                    return result;
                }
            }
        };

        return ProductPage;

    })($);

    window.initProductPage = function(options) {
        return new ProductPage(options);
    };

})(jQuery);