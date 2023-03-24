( function($) { "use strict";

    var ShopOrderPage = ( function($) {

        ShopOrderPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // VARS
            that.urls = options["urls"];

            // DYNAMIC VARS

            // INIT
            that.initClass();
        };

        ShopOrderPage.prototype.initClass = function() {
            var that = this;

            that.initUI();

            that.initThemeCart();

            that.$wrapper.on("click", ".js-clear-cart", function() {
                var wa_order_cart = getCartController(that.$wrapper);
                if (wa_order_cart) {
                    wa_order_cart.clear({
                        confirm: true
                    }).then( function() {
                        location.reload();
                    });
                } else {
                    alert("Error");
                }
            });

            that.$wrapper.on("click", "#wa-step-auth-section", function() {
                that.$wrapper.find('.wa-step-section').addClass('not-blurred');
            });
        };

        ShopOrderPage.prototype.initUI = function() {
            var that = this;

            initUI();

            that.$wrapper
                .on("wa_order_cart_reloaded", function() {
                    initUI();
                })
                .on("wa_order_form_reloaded", function() {
                    initUI();
                })
                .on("wa_order_form_changed", function() {
                    initUI();
                });

            $(document).on("wa_auth_form_loaded", function(event, data) {
                var $wrapper = $("#" + data.form_wrapper_id);
                if ($wrapper.length) {
                    initUI($wrapper);
                }
            });

            $(document).on("wa_order_dialog_open", function(event, $wrapper) {
                initUI($wrapper);
            });

            function initUI($wrapper) {
                $wrapper = ($wrapper ? $wrapper : that.$wrapper);

                initCheckbox($wrapper);
                initStyledSelect($wrapper);
                initRadio($wrapper);
            }

            function initCheckbox($wrapper) {
                $wrapper.find("input[type=\"checkbox\"]").each( function() {
                    var $input = $(this),
                        is_rendered = $input.data("is_rendered");

                    if (!is_rendered) {
                        render($(this));
                        $input.data("is_rendered", true);
                    }
                });

                function render($input) {
                    var $wrapper = $("<span class=\"s-checkbox\" />").insertBefore($input).prepend($input);
                    var $span = "<span><i class=\"s-icon\"><svg><use xlink:href=\"%icon_uri%\"></use></svg></i></span>".replace("%icon_uri%", that.urls["checkbox-icon"]);

                    $wrapper.append($span);

                    return $wrapper;
                }
            }

            function initRadio($wrapper) {
                $wrapper.find("input[type=\"radio\"]").each( function() {
                    var $input = $(this),
                        is_rendered = $input.data("is_rendered");

                    if (!is_rendered) {
                        render($(this));
                        $input.data("is_rendered", true);
                    }
                });

                function render($input) {
                    var $wrapper = $("<span class=\"s-radio\" />").insertBefore($input).prepend($input);
                    var span = "<span />";

                    $wrapper.append(span);

                    return $wrapper;
                }
            }

            function initStyledSelect($wrapper) {
                $wrapper.find("select").each( function() {
                    var $select = $(this),
                        is_rendered = $select.data("is_rendered");

                    if (!is_rendered) {
                        $("<div class=\"s-styled-select\"><span class=\"s-icon\"></span></div>").insertBefore($select).prepend($select);

                        $select.on("change", setActive);

                        setActive();

                        $select.data("is_rendered", true);
                    }

                    function setActive() {
                        var active_index = $select[0].selectedIndex,
                            active_class = "selected";

                        $select.find("option").each( function(index) {
                            var $option = $(this);
                            if (index === active_index) {
                                $option.addClass(active_class);
                            } else {
                                $option.removeClass(active_class);
                            }
                        })
                    }
                });
            }
        };

        ShopOrderPage.prototype.initThemeCart = function() {
            var that = this;

            var $cart = $("#cart");

            // Header :: Cart
            var Cart = (function($) {

                Cart = function(options) {
                    var that = this;

                    // DOM
                    that.$wrapper = options["$wrapper"];
                    that.$price = options["$price"];

                    // VARS

                    // DYNAMIC VARS

                    // INIT
                    that.initClass();
                };

                Cart.prototype.initClass = function() {
                    var that = this;

                };

                /**
                 * @param {Object} data
                 * */
                Cart.prototype.update = function(data) {
                    var that = this;

                    if (data.formatted_price) {
                        that.$price.html(data.formatted_price);
                        toggle(data.price > 0);
                    }

                    function toggle(show) {
                        var empty_class = "empty";

                        if (show) {
                            that.$wrapper.removeClass(empty_class);
                        } else {
                            that.$wrapper.addClass(empty_class);
                        }
                    }
                };

                return Cart;

            })($);

            var cart = new Cart({
                $wrapper: $cart,
                $price: $cart.find(".cart-total")
            });

            that.$wrapper.on("wa_order_cart_changed", function(event, api) {
                var wa_order_cart = getCartController(that.$wrapper);

                cart.update({
                    price: api.cart.total,
                    formatted_price: wa_order_cart.formatPrice(api.cart.total)
                });
            });
        };

        return ShopOrderPage;

        function getCartController($wrapper) {
            var result = null;

            var $cart = $wrapper.find("#js-order-cart");

            if ($cart.length && $cart.data("controller")) {
                result = $cart.data("controller");

            } else {
                throw new Error("Can't find cart controller");
            }

            return result;
        }

    })(jQuery);

    window.ShopOrderPage = ShopOrderPage;

})(jQuery);
