$.ajaxSetup({ cache: false });

// Show Menu on Hover
( function($) {

    var enter, leave;

    var storage = {
        activeClass: "submenu-is-shown",
        activeShadowClass: "is-shadow-shown",
        showTime: 200,
        $last_li: false
    };

    var bindEvents = function() {
        var $selector = $(".flyout-nav > li"),
            links = $selector.find("> a");

        $selector.on("mouseenter", function() {
            showSubMenu( $(this) );
        });

        $selector.on("mouseleave", function() {
            hideSubMenu( $(this) );
        });

        links.on("click", function() {
            onClick( $(this).closest("li") );
        });

        links.each( function() {
            var link = this,
                $li = $(link).closest("li"),
                has_sub_menu = ( $li.find(".flyout").length );

            if (has_sub_menu) {
                link.addEventListener("touchstart", function(event) {
                    onTouchStart(event, $li );
                }, false);
            }
        });

        $("body").get(0).addEventListener("touchstart", function(event) {
            onBodyClick(event, $(this));
        }, false);

    };

    var onBodyClick = function(event) {
        var activeBodyClass = storage.activeShadowClass,
            is_click_on_shadow = ( $(event.target).hasClass(activeBodyClass) );

        if (is_click_on_shadow) {
            var $active_li = $(".flyout-nav > li." + storage.activeClass).first();

            if ($active_li.length) {
                hideSubMenu( $active_li );
            }
        }
    };

    var onClick = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass);

        if (is_active) {
            var href = $li.find("> a").attr("href");
            if ( href && (href !== "javascript:void(0);") ) {
                hideSubMenu( $li );
            }

        } else {
            showSubMenu( $li );
        }
    };

    var onTouchStart = function(event, $li) {
        event.preventDefault();

        var is_active = $li.hasClass(storage.activeClass);

        if (is_active) {
            hideSubMenu( $li );
        } else {
            var $last_li = $(".flyout-nav > li." +storage.activeClass);
            if ($last_li.length) {
                storage.$last_li = $last_li;
            }
            showSubMenu( $li );
        }
    };

    var showSubMenu = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass),
            has_sub_menu = ( $li.find(".flyout").length );

        if (is_active) {
            clearTimeout( leave );

        } else {
            if (has_sub_menu) {

                enter = setTimeout( function() {

                    if (storage.$last_li && storage.$last_li.length) {
                        clearTimeout( leave );
                        storage.$last_li.removeClass(storage.activeClass);
                    }

                    $li.addClass(storage.activeClass);
                    toggleMainOrnament(true);
                }, storage.showTime);
            }
        }
    };

    var hideSubMenu = function( $li ) {
        var is_active = $li.hasClass(storage.activeClass);

        if (!is_active) {
            clearTimeout( enter );

        } else {
            storage.$last_li = $li;

            leave = setTimeout(function () {
                $li.removeClass(storage.activeClass);
                toggleMainOrnament(false);
            }, storage.showTime * 2);
        }
    };

    var toggleMainOrnament = function($toggle) {
        var $body = $("body"),
            activeClass = storage.activeShadowClass;

        if ($toggle) {
            $body.addClass(activeClass);
        } else {
            $body.removeClass(activeClass);
        }
    };

    $(document).ready( function() {
        bindEvents();
    });

})(jQuery);

var MatchMedia = function( media_query ) {
    var matchMedia = window.matchMedia,
        is_supported = (typeof matchMedia === "function");
    if (is_supported && media_query) {
        return matchMedia(media_query).matches
    } else {
        return false;
    }
};

$(document).ready(function() {

    // MOBILE nav slide-out menu
    $('#mobile-nav-toggle').click( function(){
        if (!$('.nav-negative').length) {
            $('body').prepend($('header .apps').clone().removeClass('apps').addClass('nav-negative'));
            $('body').prepend($('header .auth').clone().addClass('nav-negative'));
            $('body').prepend($('header .search').clone().addClass('nav-negative'));
            $('body').prepend($('header .offline').clone().addClass('nav-negative'));
            $('.nav-negative').hide().slideToggle(200);
        } else {
            $('.nav-negative').slideToggle(200);
        }
        $("html, body").animate({ scrollTop: 0 }, 200);
        return false;
    });

    // STICKY CART for non-mobile
    var $clone = null;

    $(window).scroll(function(){
        var is_mobile_case = MatchMedia("only screen and (max-width: 760px)");
        if (!is_mobile_case) {
            var scroll_top = $(this).scrollTop();
            var $cart = $("#cart"),
                $flyer = $('#cart-flyer');

            if ( scroll_top >= 55 && !$cart.hasClass( "fixed" ) && !$cart.hasClass( "empty" ) && !($(".cart-summary-page")).length ) {
                $cart.hide();

                if (!$clone) {
                    $clone = $("<div />").css({
                        width: $cart.outerWidth(),
                        height: "1rem"
                    });
                    $cart.before($clone);
                }

                $cart.addClass( "fixed" );

                if ($flyer.length) {
                    var _width = $flyer.width()+52;
                    var _offset_right = $(window).width() - $flyer.offset().left - _width + 1;

                    $("#cart").css({ "right": _offset_right+"px", "width": _width+"px" });
                }

                $cart.show();
                // $cart.slideToggle(200);
            } else if ( scroll_top < 50 && $("#cart").hasClass( "fixed" ) ) {

                $cart.removeClass( "fixed" );
                $cart.css({ "width": "auto" });

                if ($clone) { $clone.remove(); $clone = null; }
            }
        }
    });
});

// MAILER app email subscribe form
var SubscribeSection = ( function($) {

    SubscribeSection = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find("form");
        that.$emailField = that.$wrapper.find(".js-email-field");
        that.$submitButton = that.$wrapper.find(".js-submit-button");

        // VARS
        that.request_uri = options["request_uri"];
        that.locales = options["locales"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    SubscribeSection.prototype.initClass = function() {
        var that = this;

        if (that.request_uri.substr(0,4) === "http") {
            that.request_uri = that.request_uri.replace("http:", "").replace("https:", "");
        }

        var $invisible_captcha = that.$form.find(".wa-invisible-recaptcha");
        if (!$invisible_captcha.length) {
            that.initView();
        }

        that.initSubmit();
    };

    SubscribeSection.prototype.initView = function() {
        var that = this;

        that.$emailField.on("focus", function() {
            toggleView(true);
        });

        $(document).on("click", watcher);

        function watcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                var is_target = $.contains(that.$wrapper[0], event.target);
                if (!is_target) {
                    toggleView(false);
                }
            } else {
                $(document).off("click", watcher);
            }
        }

        function toggleView(show) {
            var active_class = "is-extended";
            if (show) {
                that.$wrapper.addClass(active_class);
            } else {
                var email_value = that.$emailField.val();
                if (!email_value.length) {
                    that.$wrapper.removeClass(active_class);
                } else {

                }
            }
        }
    };

    SubscribeSection.prototype.initSubmit = function() {
        var that = this,
            $form = that.$form,
            $errorsPlace = that.$wrapper.find(".js-errors-place"),
            is_locked = false;

        $form.on("submit", onSubmit);

        function onSubmit(event) {
            event.preventDefault();

            var formData = getData();

            if (formData.errors.length) {
                renderErrors(formData.errors);
            } else {
                request(formData.data);
            }
        }

        /**
         * @return {Object}
         * */
        function getData() {
            var result = {
                    data: [],
                    errors: []
                },
                data = $form.serializeArray();

            $.each(data, function(index, item) {
                if (item.value) {
                    result.data.push(item);
                } else {
                    result.errors.push({
                        name: item.name
                    });
                }
            });

            return result;
        }

        /**
         * @param {Array} data
         * */
        function request(data) {
            if (!is_locked) {
                is_locked = true;

                var href = that.request_uri;

                $.post(href, data, "jsonp")
                    .always( function() {
                        is_locked = false;
                    })
                    .done( function(response) {
                        if (response.status === "ok") {
                            renderSuccess();

                        } else if (response.errors) {
                            var errors = formatErrors(response.errors);
                            renderErrors(errors);
                        }
                    });
            }

            /**
             * @param {Object} errors
             * @result {Array}
             * */
            function formatErrors(errors) {
                var result = [];

                $.each(errors, function(text, item) {
                    var name = item[0];

                    if (name === "subscriber[email]") { name = "email"; }

                    result.push({
                        name: name,
                        value: text
                    });
                });

                return result;
            }
        }

        /**
         * @param {Array} errors
         * */
        function renderErrors(errors) {
            var error_class = "error";

            if (!errors || !errors[0]) {
                errors = [];
            }

            $.each(errors, function(index, item) {
                var name = item.name,
                    text = item.value;

                var $field = that.$wrapper.find("[name=\"" + name + "\"]"),
                    $text = $("<span class='c-error' />").addClass("error");

                if ($field.length && !$field.hasClass(error_class)) {
                    if (text) {
                        $field.parent().append($text.text(text));
                    }

                    $field
                        .addClass(error_class)
                        .one("focus click change", function() {
                            $field.removeClass(error_class);
                            $text.remove();
                        });
                } else {
                    $errorsPlace.append($text);

                    $form.one("submit", function() {
                        $text.remove();
                    });
                }
            });
        }

        function renderSuccess() {
            var $text = that.$wrapper.find(".js-success-message");
            $form.hide();
            $text.show();
        }
    };

    return SubscribeSection;

})(jQuery);