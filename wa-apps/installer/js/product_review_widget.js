var InstallerProductReviewWidget = ( function($) {

    var InstallerProductReviewWidget = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // DOM product widget block elements
        that.$product_widget_block = that.$wrapper.find('.js-product-widget-block');
        that.$product_icon_block = that.$product_widget_block.find('.js-product-icon');
        that.$product_name_block = that.$product_widget_block.find('.js-product-name');
        that.$product_rate_control = that.$product_widget_block.find('.js-rates-list');
        that.$product_support_link = that.$product_widget_block.find('.js-product-support');

        // initial widget errors (for example invalid product id passed to widget)
        that.errors = options.errors || {};

        // EVENTS for outside world
        that.events = {
            init_widget_fail: 'wa_installer_product_review_widget_init_widget_fail',
            load_product_fail: 'wa_installer_product_review_widget_loading_product_fail'
        };

        // interface object from store side of side of product review widget for work with server side
        that.review_widget_io = {};

        // VAR
        that.product_id = options.product_id;
        that.store_review_core_url = options.store_review_core_url || '';
        that.store_auth_params = options.store_auth_params || {};
        that.installer_app_url = options.installer_app_url || '';

        /**
         * @type {Boolean}
         * View (modal or inline)
         * Take into account that actual view also depends on type of product
         * @see isModal()
         */
        that.is_modal = options.is_modal;

        /**
         * @type {Array} - array of array of 2 elements. First element is class for modal view, second element is class for inline view
         */
        that.view_classes = options.view_classes;

        that.is_debug = options.is_debug || false;
        that.has_access = options.has_access || false;  // has access to installer app
        that.message = '';

        // VAR that will be received from core js data
        that.locale = '';
        that.locale_def = options.locale_defs;

        // DATA that will be received from data controller, here all data need for UI of widget
        that.data = {};

        that.templates = options.templates;

        // INIT
        that.init();
    };

    InstallerProductReviewWidget.prototype.init = function() {
        var that = this;

        // IMPORTANT: check initial widget errors and if there are some then stops further logic
        if (!$.isEmptyObject(that.errors)) {
            that.processInitialWidgetErrors(that.errors);
            return;
        }
        
        that.initByCoreJsData().done(function () {
            that.loadReviewWidgetIO().done(function (data) {
                that.initProductWidgetBlock(data);
            });
        });
    };

    InstallerProductReviewWidget.prototype.isModal = function(product_type) {
        var that = this;

        // modal view allowed only for APP, otherwise modal is not supported
        if (product_type !== 'APP') {
            return false;
        } else {
            return that.is_modal;
        }
    };

    /**
     * Set modal or inline view dynamically - without changing inner state of current object
     * @param is_modal
     */
    InstallerProductReviewWidget.prototype.setUIView = function(is_modal) {
        var that = this;

        /**
         *
         * @param {String} selector
         * @param {String[]} off_classes - array of string
         * @param {String} on_class
         */
        var switchClass = function(selector, off_classes, on_class) {
            var $dom = that.$wrapper.find(selector);
            $dom.removeClass(off_classes.join(" "));
            $dom.addClass(on_class);
        };

        $.each(that.view_classes, function (index, classes) {
            var modal_class = classes[0],
                inline_class = classes[1],
                selector = '.' + classes.join(',.');
            switchClass(selector, classes, is_modal ? modal_class : inline_class);
        });
    };

    InstallerProductReviewWidget.prototype.initProductWidgetBlock = function() {
        var that = this,
            review_widget_io = that.review_widget_io,
            errors = review_widget_io.getErrors(),
            data = review_widget_io.getData(),  // here is data for widget from server (store) part
            has_access = that.has_access,
            review_widget_shown = sessionStorage.getItem('review_widget_shown');

        // IMPORTANT: check errors and if there are some then stops further logic
        if (!$.isEmptyObject(errors)) {
            that.processLoadingProductStoreInfoErrors(errors);
            return;
        }

        // is_modal depends on product type
        var is_modal = that.isModal(data.product_info.type);

        // Show only one product widget in modal mode
        if (is_modal) {
            if ($.isEmptyObject(errors) && data.review.id || (review_widget_shown && review_widget_shown !== data.product_id)) {
                that.processLoadingProductStoreInfoErrors({
                    not_shown: 'Product "' + data.product_id.toUpperCase() + '" is not shown'
                });
                return;
            }
        }

        // Set storage session in modal mode
        if (is_modal) {
            sessionStorage.setItem('review_widget_shown', data.product_id);
        }

        var $product_widget_block = that.$product_widget_block,
            $product_icon_block = that.$product_icon_block,
            $product_name_block = that.$product_name_block,
            $product_rate_control = that.$product_rate_control,
            $product_support_link = that.$product_support_link,
            rate = data.review.rate ? data.review.rate : null,
            before_rate = (rate || 0),
            installer_app_url = that.installer_app_url || '',
            product_icons = data.product_info.icon ? data.product_info.icon : '/wa-apps/installer/img/dummy-plugin.png',
            product_url = data.product_info.url ? data.product_info.url : '#',
            product_support_url = data.product_info.support;

            that.message = data.review.message;

        that.setUIView(is_modal);

        if (is_modal) {
            var dialog = $.waDialog({
                html: $product_widget_block,
                lock_body_scroll: false,
                onOpen: dataInsert,
                onClose: function () {
                    // send request only if has access to installer app
                    if (has_access) {
                        $.post(installer_app_url + '?module=reviews&action=closeWidget');
                    }

                    // Remove storage session in modal mode
                    sessionStorage.removeItem('review_widget_shown');
                }
            });

            // Remove storage session in modal mode
            setTimeout(function () {
                sessionStorage.removeItem('review_widget_shown');
            }, 5000)
        } else{
            dataInsert($product_widget_block, null);
            $product_widget_block.show();
        }

        function dataInsert($wrapper, dialog) {
            $product_icon_block.html('<img src="' + product_icons + '">');

            if (product_support_url && product_support_url !== 'mailto:') {

                $product_support_link.attr('href', product_support_url);

               var product_support = $product_support_link[0],
                   developer = product_support.hostname;

               if (product_support.protocol === 'mailto:') {
                    developer = product_support.href.split(':')[1];
               }

                $product_support_link.append('<span>' + developer + '</span>');

            } else {

                $product_support_link.parent().hide();

            }

            $product_name_block.empty().append(
                $('<a/>', {
                    'target': '_blank',
                    'href': product_url,
                    text: data.product_info.name
                })
            );

            switch (data.product_info.type) {
                case 'APP':
                    $product_name_block.prepend(that.locale_def.rate_app);
                    $product_icon_block.find('img').addClass('app');
                    break;
                case 'PLUGIN':
                    $product_name_block.prepend(that.locale_def.rate_plugin);
                    break;
                case 'THEME':
                    $product_name_block.prepend(that.locale_def.rate_theme);
                    break;
                case 'WIDGET':
                    $product_name_block.prepend(that.locale_def.rate_widget);
                    break;
            }

            var widget = initRateWidget({
                $wrapper: $product_rate_control,
                rate: rate,
                onSet: function(rate) {
                    if (data.reviewer_info) {
                        that.sendReview(rate).done(
                            function (res) {
                                if (res.status == 'ok') {
                                    if (dialog) {
                                        dialog.close();
                                    }
                                    widget.setRate(rate);
                                    before_rate = rate;
                                    initReviewDialog(rate, widget);
                                }

                                if (res.status == 'fail' && res.errors) {
                                    var errors = res.errors;
                                    widget.setRate(before_rate);
                                    if (!errors.length) {
                                        return false;
                                    }
                                    $wrapper.find('.errormsg').empty();
                                    $.each(errors, function (i, error) {

                                        if (!error.text) {
                                            return false;
                                        }

                                        var $error = $("<div />").addClass("i-error errormsg").text(error.text);
                                        $product_rate_control.after($error);

                                    });

                                }
                            }
                        );
                    }
                }
            });
        }

        function initReviewDialog(rate, widget) {
            var dialog = $.waDialog({
                    wrapper: $(that.templates["review_dialog"]),
                    onOpen: initRateDialogContent
                });

            function initRateDialogContent($wrapper, dialog) {
                // DOM

                var $user_name = $wrapper.find('.js-customer-center-user-name'),
                    $signup_user_info = $wrapper.find('.js-dialog-signup-user-info'),
                    $logout_link = $wrapper.find('.js-customer-center-logout-link'),
                    $content_title = $wrapper.find(".js-content-title"),
                    $comment_field = $wrapper.find(".js-comment-field"),
                    $errors_place = $wrapper.find('.js-errors-place'),
                    $button = $wrapper.find(".js-send-comment"),
                    $user = $wrapper.find(".js-comment-user");

                if (data.reviewer_info) {
                    $user.find('.user').text(data.reviewer_info.name);
                    var userpic = (data.reviewer_info.userpic_url) ? data.reviewer_info.userpic_url : '/wa-content/img/userpic20.jpg';
                    $user.find('.userpic20').css('background-image', 'url(' + userpic + ')');

                    // If user signed up his email is not empty
                    if (data.reviewer_info.email) {
                        $signup_user_info.show();
                        $user_name.text(data.reviewer_info.name + ' (' + data.reviewer_info.email + ')');
                    }
                }
                $logout_link.attr('href', that.logout_url);

                // CONST
                var is_edit = (data && (data.rate || data.review));

                // DYNAMIC VARS
                var is_locked = false;

                switch (data.product_info.type) {
                    case 'APP':
                        $content_title.append(' ' + that.locale_def.for_app);
                        break;
                    case 'PLUGIN':
                        $content_title.append(' ' + that.locale_def.for_plugin);
                        break;
                    case 'THEME':
                        $content_title.append(' ' + that.locale_def.for_theme);
                        break;
                    case 'WIDGET':
                        $content_title.append(' ' + that.locale_def.for_widget);
                        break;
                }

                $content_title.append(' '+ data.product_info.name);

                $comment_field.val(that.message);

                var widget_in = initRateWidget({
                    $wrapper: $wrapper.find(".js-rates-list"),
                    rate: rate
                    });

                $comment_field.on("keyup", function() {
                    var is_empty = !$.trim($comment_field.val()).length,
                        text = that.locale_def.button_default;

                    if (is_empty) {
                        text = (is_edit ? that.locale_def.button_edit_default : that.locale_def.button_default);
                        $user.hide();
                    } else {
                        text = (is_edit ? that.locale_def.button_edit_active : that.locale_def.button_active);
                        $user.show();
                    }

                    $button.text(text);
                });

                $comment_field.trigger("keyup");

                // EVENTS
                $button.on("click", function(e) {
                    e.preventDefault();

                    if (!is_locked) {
                        $button.prop("disabled", true);
                        is_locked = true;
                        $errors_place.html('');
                        var new_rate = widget_in.getRate();

                        that.sendReview(new_rate, $comment_field.val())
                            .done(function(res){

                                $button.prop("disabled", false);
                                is_locked = false;

                                if (res.status == 'ok') {
                                    $wrapper.find(".js-comment-section").html(that.templates["confirm"]);
                                    $button.remove();
                                    dialog.resize();
                                    widget.setRate(new_rate);
                                    before_rate = new_rate;
                                    that.message = $comment_field.val();
                                    is_success = true;
                                }

                                if (res.status == 'fail' && res.errors) {
                                    var errors = res.errors;

                                    if (!errors.length) {
                                        return false;
                                    }

                                    $.each(errors, function (i, error) {

                                        if (!error.text) {
                                            return false;
                                        }

                                        var $error = $("<div />").addClass("i-error").text(error.text);
                                        $errors_place.append($error);

                                    });

                                    $errors_place.show();
                                    dialog.resize();
                                }
                            });
                    }
                });
            }
        }

    };

    InstallerProductReviewWidget.prototype.initByCoreJsData = function () {
        var that = this,
            df = $.Deferred();

        // Register event about initiation of product review core js
        $(document).one('wa_product_review_core_init', function (e, data) {
            that.product_review_widget_url = data.product_review_widget_url;
            that.locale = data.locale;
            df.resolve();
        });

        // Download product review core js
        if (!that.store_review_core_url) {
            if (that.is_debug) {
                console.error('undefined url for product review core js');
            }
            df.reject();
        } else {
            $.getScript(that.store_review_core_url);
        }

        return df;
    };

    InstallerProductReviewWidget.prototype.sendReview = function(rate, text) {
        var that = this,
            installer_app_url = that.installer_app_url || '',
            has_access = that.has_access,
            review_widget_io = that.review_widget_io;
        var xhr = review_widget_io.sendReview(rate, text);
        xhr.done(function (r) {
            // if send review is ok
            if (r && r.status === 'ok') {
                // send "mark" request only if has access to installer app
                if (has_access) {
                    $.post(installer_app_url + '?module=reviews&action=markWhenReviewAdded')
                }
            }
        });
        return xhr;
    };

    InstallerProductReviewWidget.prototype.loadReviewWidgetIO = function () {
        var that = this,
            df = $.Deferred();

        // Register event about initiation of store side of product review widget
        var event_name = 'wa_store_installer_widget_init',
            event_ns = that.product_id,
            event_id = event_name + '.' + event_ns,
            $doc = $(document);

        $doc.on(event_id, function (e, review_widget_io) {
            // we can has several triggering of event because we has in page several widgets and we must react only on current product related event
            var data = review_widget_io.getData() || {};

            // if data is empty unbind event right away
            if ($.isEmptyObject(data)) {
                $doc.off(event_id);
                return;
            }

            // react only on current product, after react unbind current "own handler"
            if (data.product_id === that.product_id) {
                that.review_widget_io = review_widget_io;
                $doc.off(event_id);
                df.resolve();
            }
        });

        var url = that.buildSecureUrl(that.product_review_widget_url, that.store_auth_params);
        url += '&product_id=' + that.product_id;
        $.getScript(url);

        return df;
    };

    InstallerProductReviewWidget.prototype.processInitialWidgetErrors = function(errors) {
        var that = this,
            event_name = that.events.init_widget_fail;

        // So outside world could render render if they need it
        that.$wrapper.trigger(event_name, [ {
            product_id: that.product_id,
            errors: $.extend({}, errors || that.errors, true)
        } ]);

        // Developer console hint
        if (that.is_debug) {
            console.error(
                'Fail while init widget',
                errors
            );
            console.info('%c event "%s" thrown, feel free to listen it', 'color: blue;', event_name);
        }
    };
    
    InstallerProductReviewWidget.prototype.processLoadingProductStoreInfoErrors = function(errors) {
        var that = this,
            event_name = that.events.load_product_fail;

        // So outside world could render render if they need it
        that.$wrapper.trigger(event_name, [ {
            product_id: that.product_id,
            errors: $.extend({}, errors, true)
        } ]);

        // Developer console hint
        if (that.is_debug) {
            console.error(
                'Fail while load product info',
                errors
            );
            console.info('%c event "%s" thrown, feel free to listen it', 'color: blue;', event_name);
        }
    };

    InstallerProductReviewWidget.prototype.buildSecureUrl = function (url, auth_params) {
        var that = this,
            separator = (url.indexOf('?') === -1) ? '?' : '&';

        if (auth_params) {
            url += separator; // ? or & in url
            url += 'inst_id=' + (auth_params["inst_id"] || '');
            url += '&token_key=' + (auth_params["token"] || '');
            url += '&token_sign=' + (auth_params["sign"] || '');
            url += '&token_expire_datetime=' + (auth_params["remote_expire_datetime"] || '');
            url += '&locale=' + (auth_params["locale"] || '');
        }

        return url;
    };

    return InstallerProductReviewWidget;

    function initRateWidget(options) {
        var that = this;

        // DOM
        var $wrapper = options.$wrapper,
            $rates = $wrapper.find(".js-set-rate");

        // CONST
        var active_rate = options.rate;
        // EVENTS
        $wrapper.on("click", ".js-set-rate", function(event) {
            event.preventDefault();

            var $rate = $(this),
                rate = $rate.index() + 1;

            var save = true;
            if (typeof options["onSet"] === "function") {
                var callback = options["onSet"](rate);
                if (callback === false) {
                    save = false;
                }
            }

            setRate(rate, save);
        });


        $wrapper.on("mouseenter", ".js-set-rate", function(event) {
            event.preventDefault();
            var index = $(this).index() + 1;
            setRate(index, false);
        });

        $wrapper.on("mouseleave", function(event) {
            event.preventDefault();

            if (typeof options["onExit"] === "function") {
                options["onExit"](active_rate);
            }

            setRate(active_rate, false);
        });

        if (active_rate) {
            setRate(active_rate, false);
        }

        return {
            getRate: function() {
                return active_rate;
            },
            setRate: setRate
        };

        /**
         * @var {Number|Null} index
         * */
        function setRate(rate, save) {
            var active_class = "is-active";

            save = (typeof save === "boolean" ? save : true);

            $rates.each( function(i) {
                var $rate = $(this);

                if (i <= rate - 1) {
                    $rate.addClass(active_class);
                } else {
                    $rate.removeClass(active_class);
                }
            });

            if (save) {
                active_rate = rate;
                $(document).trigger('setRate', rate);
            }
        }
    }

})(jQuery);
