var InstallerStoreReview = ( function($) {

    InstallerStoreReview = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$document = $(document);
        that.$reviews_list = that.$wrapper.find('.js-reviews-list');

        // VARS
        that.templates = options.templates;
        that.review_template = that.templates.product_review;

        that.app_url = options.app_url;

        that.events = {
            // Outgoing events
            out: {
                review_submit: 'wa_product_review_submit'
            },
            // Incoming events
            in: {
                review_core_init: 'wa_product_review_core_init',
                review_init: 'wa_product_review_init',
                review_response: 'wa_product_review_submit_response'
            }
        };

        that.login_url = null;
        that.logout_url = null;
        that.url_param = '?section=rated';

        that.locale = options.locale;

        // DYNAMIC VARS
        that.is_allowed = false;
        that.tokens_is_reissued = false;
        that.locale_code = null;
        that.store_review_core_url = options.store_review_core_url;
        that.store_review_core_params = options.store_review_core_params;

        // INIT
        that.initClass();
    };

    InstallerStoreReview.prototype.initFilter = function() {
        var that = this,
            $i_product_review_block = that.$reviews_list.find('.i-product-review-block'),
            $i_reviews_filter = that.$wrapper.find('.js-reviews-filter'),
            $i_reviews_filter_button = $i_reviews_filter.find('button'),
            location = window.location;

        $i_reviews_filter_button.on('click', function () {
            var btn = $(this);
            btn.toggleClass('active').attr('disabled','disabled').siblings().removeClass('active').removeAttr('disabled');
            $i_product_review_block.toggleClass('hidden');
            if (btn.hasClass('js-rated-button') && location.search !== that.url_param) {
                history.pushState(null,null,location.href + that.url_param);
            }else{
                history.pushState(null,null,location.origin + location.pathname)
            }
        })
    };

    InstallerStoreReview.prototype.sortRated = function() {
        var that = this;

        that.$reviews_list.find('.i-product-review-block.rated').sort(function (a, b) {
            return parseInt($(a).data('review-time'), 10) > parseInt($(b).data('review-time'), 10) ? -1: 1;
        }).appendTo(that.$reviews_list);
    };

    InstallerStoreReview.prototype.initClass = function() {
        var that = this;

        that.locale_code = (that.store_review_core_params.locale || '');

        //
        that.initEventListener();
        //
        that.initReviewCore();
    };

    InstallerStoreReview.prototype.initReviewCore = function() {
        var that = this,
            script = document.createElement("script"),
            url = that.buildCoreUrlWithParams(that.store_review_core_url);

        document.getElementsByTagName("head")[0].appendChild(script);

        $(script).attr('id', 'wa-store-review-core').attr('src', url);
    };

    InstallerStoreReview.prototype.sendEvent = function(event, data) {
        var that = this;

        that.$document.trigger(event, data);
    };

    InstallerStoreReview.prototype.initEventListener = function () {
        var that = this;

        that.$document.on(that.events.in.review_core_init, function (e, data) {
            that.initCore(data);
        });

        that.$document.on(that.events.in.review_init, function (e, data) {
            that.initRate(data);
        });
    };

    InstallerStoreReview.prototype.initCore = function(data) {
        var that = this,
            $script = that.$document.find('#wa-store-review-core');

        $script.remove();

        if (!data.review_core_url && !that.store_review_core_url) {
            return false;
        }

        if (data.locale) {
            that.locale_code = data.locale;
        }

        if (data.review_core_url) {
            that.store_review_core_url = data.review_core_url;
        }

        if (data.login_url) {
            that.login_url = data.login_url;
        }

        if (data.logout_url) {
            that.logout_url = data.logout_url;
        }

        var script = document.createElement("script"),
            url = that.buildCoreUrlWithParams(that.store_review_core_url);

        document.getElementsByTagName("head")[0].appendChild(script);
        $(script).attr('id', 'wa-store-review-core').attr('src', url);

    };

    InstallerStoreReview.prototype.initRate = function (data) {
        var that = this,
            $i_reviews_loader = that.$wrapper.find('.js-loading-wrapper'),
            user_reviews = data.user_reviews;

        // Save init data
        that.is_allowed = data.is_allowed;

        // If tokens are outdated, they must be reissued once.
        if (!that.is_allowed) {
            if (!that.tokens_is_reissued) {
                that.tokens_is_reissued = true;
                that.reInitTokens();
            }
            return false;
        }

        $.each(user_reviews, function(i, data) {

            var $wrapper = $(that.review_template).clone(),
                product_reviewer = data.product.reviewer,
                product_id = data.product.id;

            that.$reviews_list.append($wrapper);

            data.user_data = product_reviewer;
            data.product_id = product_id;
            that.initProductReview($wrapper, data);

        });

        $i_reviews_loader.remove();

        that.initFilter();

        that.sortRated();

    };

    InstallerStoreReview.prototype.initProductReview = function ($wrapper, data) {
        var that = this,
            rate = data.rate ? data.rate : null,
            before_rate = (rate || 0),
            $product_title = $wrapper.find('.js-product-review-title'),
            $product_review_icon = $wrapper.find('.js-product-review-icon'),
            $product_review_desc = $wrapper.find('.js-product-review-desc'),
            $rates_list = $wrapper.find('.js-rates-list'),
            product_icons = data.product.icons || ['/wa-apps/installer/img/dummy-plugin.png'],
            product_url = data.product.url || '#',
            product_support_url = data.product.support,
            product_name = data.product.name || '',
            product_id = data.product.id || 0,
            is_rated = data.is_rated || false,
            img_class = '',
            url_get = window.location.search;

        $wrapper.data({
            'id': product_id,
            'message': data.message
         });

        if (product_support_url && product_support_url !== 'mailto:') {

            var $product_support = $('<a/>', {
                    'class': 'i-product-review-support js-product-review-support',
                    'target': '_blank',
                    'href': product_support_url
                }),
                product_support = $product_support[0],
                developer = product_support.hostname;

            if (product_support.protocol === 'mailto:') {
                developer = product_support.href.split(':')[1];
            }

            $rates_list.after($product_support.empty().html(that.locale.support + '<span>' + developer + '</span>'));

        } else {

            $rates_list.addClass('empty-support');

        }

        if (product_url) {

            $product_title.empty().attr('href', that.app_url + 'store/' + product_url).text(product_name);

        }

        if (product_icons) {

            var icon_src = product_icons[0];

            if (product_icons['96x96']) {
                icon_src = product_icons['96x96'];
            }

            if (data.product.type !== 'APP') {
                img_class = 'class="border"';
            }else{
                img_class = 'class="size-limit"';
            }

            $product_review_icon.empty().append('<img ' + img_class + ' src="' + icon_src + '" alt="">');
        }

        if (data.id) {

            var review_date = data.create_datetime_human || '',
                review_time = new Date(data.create_datetime).getTime(),
                reviewer_name = data.reviewer.name || '',
                userpic_url = data.reviewer.userpic_url || '/wa-content/img/userpic20.jpg',
                message = data.message;

            $wrapper.data('review-time', review_time);

            if (!is_rated) {

                $wrapper.addClass('rated hidden');

            }

            that.sortRated();

            var $product_review_user = $('<span/>').addClass('user')
                    .text(reviewer_name),
                $product_review_change = $('<span/>').addClass('review_change')
                    .html('<a href="#">' + that.locale.button_edit_active + '</a>'),
                $product_review_date = $('<span/>').addClass('review_date')
                    .text(review_date),
                $product_reviewer_userpic = $('<i/>').addClass('userpic20 icon16')
                    .css('background-image', 'url(' + userpic_url + ')'),
                $product_review = '',
                $product_reviewer_info = '';

            if (message) {
                $product_review = $('<div/>').addClass('i-product-review js-product-review')
                    .text(message);
                $product_reviewer_info = $('<div/>')
                    .addClass('i-product-reviewer-info js-product-reviewer-info')
                    .append($product_reviewer_userpic, $product_review_user, $product_review_date, $product_review_change);
            }

            if (product_support_url) {
                $wrapper.find('.js-product-review-support').remove();
                $product_title.after($product_support.empty().html(that.locale.support + ':<span>' + developer + '</span>'));
            }

            $product_review_desc.find('div').filter(':not(.js-rates-list)').remove();
            $product_review_desc.append($product_reviewer_info, $product_review);

            $product_review_change.on('click', function () {
                initReviewDialog(data.rate, $wrapper);
            })
        }

        if (url_get === that.url_param && !is_rated) {
            $wrapper.toggleClass('hidden');
        }

        var widget = initRateWidget({
            $wrapper: $rates_list,
            rate: rate,
            onSet: function (rate) {
                if (that.is_allowed) {

                    if (!data.is_rated) {
                        $wrapper.data('is-rate-click', 1);
                        data.rate = rate;
                        that.sendEvent(that.events.out.review_submit, data);
                        initReviewDialog(rate, $wrapper);
                    }
                }
            }
        });

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
                }
            }
        }

        function initReviewDialog(rate, product_block) {

            var is_success = false,
                dialog = $.waDialog({
                    wrapper: $(that.templates["review_dialog"]),
                    onOpen: initRateDialogContent,
                    onClose: function() {
                        if (!is_success) {
                            widget.setRate(before_rate, true);
                        }
                    }
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
                    $user = $wrapper.find(".js-comment-user"),
                    $rates_list = $wrapper.find(".js-rates-list");

                if (data.user_data) {
                    $user.find('.user').text(data.user_data.name);
                    var userpic = (data.user_data.userpic_url) ? data.user_data.userpic_url : '/wa-content/img/userpic20.jpg';
                    $user.find('.userpic20').css('background-image', 'url(' + userpic + ')');

                    // If user signed up his email is not empty
                    if (data.user_data.email) {
                        $signup_user_info.show();
                        $user_name.text(data.user_data.name + ' (' + data.user_data.email + ')');
                    }
                }
                $logout_link.attr('href', that.logout_url);

                // CONST
                var is_edit = (data && (data.rate || data.message));

                // DYNAMIC VARS
                var is_locked = false;

                var product_name = data.product.name;

                switch (data.product.type) {
                    case 'APP':
                        $content_title.append(' '+ that.locale.for_app + ' ' + product_name);
                        break;
                    case 'PLUGIN':
                        $content_title.append(' '+ that.locale.for_plugin + ' ' + product_name);
                        break;
                    case 'THEME':
                        $content_title.append(' '+ that.locale.for_theme + ' ' + product_name);
                        break;
                    case 'WIDGET':
                        $content_title.append(' '+ that.locale.for_widget + ' ' + product_name);
                        break;
                }

                var product_block_message = product_block.data('message');
                if (product_block_message) {
                    $comment_field.val(product_block_message);
                }

                var widget = initRateWidget({
                    $wrapper: $rates_list,
                    rate: rate,
                    onSet: function() {
                        $rates_list.find(".gray").remove();
                    }
                });

                $comment_field.on("keyup", function() {
                    var is_empty = !$.trim($comment_field.val()).length,
                        text = that.locale["button_default"];

                    if (is_empty) {
                        text = (is_edit ? that.locale["button_edit_default"] : that.locale["button_default"]);
                        $user.hide();
                    } else {
                        text = (is_edit ? that.locale["button_edit_active"] : that.locale["button_active"]);
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
                        data.text = $comment_field.val();
                        data.rate = widget.getRate();
                        product_block.data('is-rate-click', 0);
                        that.sendEvent(that.events.out.review_submit, data);
                    }
                });

                // Handle response from Store server
                that.$document.on(that.events.in.review_response, function (e, res) {
                    $button.prop("disabled", false);
                    is_locked = false;

                    if (res.status == 'ok') {

                        var product_review = res.data.product_review,
                            product_block_id = product_block.data('id'),
                            is_rate_click = product_block.data('is-rate-click'),
                            reviewed_product_id = res.data.product_review.product_id;

                        if (is_rate_click) {
                            $wrapper.find('.js-rates-list').append('<span class="gray"> &ndash; ' + that.locale.rate_added + '</span>');
                        }else{
                            $wrapper.find(".i-comment-section").html(that.templates["confirm"]);
                            $button.remove();
                            dialog.resize();
                        }

                        if (product_block_id == reviewed_product_id) {

                            if (!product_block.hasClass("rated")) {
                                product_block.addClass('rated hidden');
                            }

                            product_review.is_rated = true;
                            product_review.user_data = product_review.product.reviewer;
                            product_block.data('message', product_review.message);
                            that.initProductReview(product_block, product_review);
                        }

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
        }
    };

    InstallerStoreReview.prototype.once = function (fn, context) {
        var result,
            that = this;

        return function() {
            if(fn) {
                result = fn.apply(context || this, arguments);
                fn = null;
            }
            return result;
        };
    };

    InstallerStoreReview.prototype.reInitTokens = function () {
        var that = this,
            href = that.app_url + "?module=store&action=newToken";

        $.get(href, function (res) {
            if (res.status == 'ok' && res.data) {
                that.store_review_core_params = res.data;
                that.initCore({});
            }
        });
    };

    InstallerStoreReview.prototype.buildCoreUrlWithParams = function (url) {
        var that = this,
            separator = (url.indexOf('?') === -1) ? '?' : '&';

        if (that.store_review_core_params) {
            url += separator; // ? or & in url
            url += 'inst_id=' + (that.store_review_core_params["inst_id"] || '');
            url += '&token_key=' + (that.store_review_core_params["token"] || '');
            url += '&token_sign=' + (that.store_review_core_params["sign"] || '');
            url += '&token_expire_datetime=' + (that.store_review_core_params["remote_expire_datetime"] || '');
            url += '&locale=' + (that.locale_code || '');
        }

        return url;
    };

    return InstallerStoreReview;

})(jQuery);