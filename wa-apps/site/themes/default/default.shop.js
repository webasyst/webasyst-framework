$(document).ready(function () {

    // countdown
    // was reworked
    // if ($.fn.countdowntimer) {
    //     $('.js-promo-countdown').each(function () {
    //         var $this = $(this).html('');
    //         var id = ($this.attr('id') || 'js-promo-countdown' + ('' + Math.random()).slice(2));
    //         $this.attr('id', id);
    //         var start = $this.data('start').replace(/-/g, '/');
    //         var end = $this.data('end').replace(/-/g, '/');
    //         $this.countdowntimer({
    //             startDate: start,
    //             dateAndTime: end,
    //             size: 'lg'
    //         });
    //     });
    // }

    //SLIDERS
    // inited in templated home.slider.html
    // $('.homepage-bxslider').bxSlider( { auto : $('.homepage-bxslider li').length > 1, pause : 7000, autoHover : true, pager: $('.homepage-bxslider li').length > 1 });
    // $('.homepage-bxslider').css('height','auto');

    $('.related-bxslider').bxSlider( { minSlides: 1, maxSlides: 7, slideWidth: 146, slideMargin: 20, infiniteLoop: true, pager: false });
    $('.onsale-bxslider').bxSlider( { minSlides: 1, maxSlides: 7, slideWidth: 146, slideMargin: 20, infiniteLoop: true, pager: false });

    // SIDEBAR HEADER click (smartphones only)
    if ( (!!('ontouchstart' in window)) && MatchMedia("only screen and (max-width: 760px)") ) {
        $('.nav-sidebar-body').css('opacity',1);
        $('.nav-sidebar-body').hide();
        $('a.nav-sidebar-header').click(function(){
            {
                $('.nav-sidebar-body').slideToggle(200);
                return false;
            }
        });
    }

    //CART dialog for multi-SKU products
    $('.dialog').on('click', 'a.dialog-close', function () {
        $(this).closest('.dialog').hide().find('.cart').empty();
        return false;
    });
    $(document).keyup(function(e) {
        if (e.keyCode == 27) {
            $(".dialog:visible").hide().find('.cart').empty();
        }
    });

    // COMPARE
    $(".container").on('click', '.product-list a.compare', function () {
        var compare = $.cookie('shop_compare');
        $.cookie('shop_compare', compare, { expires: 30, path: '/'});

        if (!$(this).find('svg').hasClass('active')) {
            if (compare) {
                compare += ',' + $(this).data('product');
            } else {
                compare = '' + $(this).data('product');
            }
            if (compare.split(',').length > 0) {
                if (!$('#compare-leash').is(":visible")) {
                    $('#compare-leash').css('height', 0).show().animate({height: 39},function(){
                        var _blinked = 0;
                        setInterval(function(){
                            if (_blinked < 4)
                                $("#compare-leash a").toggleClass("just-added");
                            _blinked++;
                         },500);
                    });
                }
                var url = $("#compare-leash a").attr('href').replace(/compare\/.*$/, 'compare/' + compare + '/');
                $('#compare-leash a').attr('href', url).show().find('strong').html(compare.split(',').length);
            } else {
                $('#compare-leash').hide();
            }
            $.cookie('shop_compare', compare, { expires: 30, path: '/'});
        } else {
            if (compare) {
                compare = compare.split(',');
            } else {
                compare = [];
            }
            var i = $.inArray($(this).data('product') + '', compare);
            if (i != -1) {
                compare.splice(i, 1)
            }
            if (compare.length > 0) {
                $.cookie('shop_compare', compare.join(','), { expires: 30, path: '/'});
                var url = $("#compare-leash a").attr('href').replace(/compare\/.*$/, 'compare/' + compare.join(',') + '/');
                $('#compare-leash a').attr('href', url).show().find('strong').html(compare.length);
            } else {
                $('#compare-leash').hide();
                $.cookie('shop_compare', null, {path: '/'});
            }
        }
        $(this).find('svg').toggleClass('active');
        return false;
    });

    //ADD TO CART
    $(".container").on('submit', '.product-list form.addtocart', function () {
        var f = $(this);
        f.find('.adding2cart').addClass('icon16 loading').show();
        if (f.data('url')) {
            var d = $('#dialog');
            var c = d.find('.cart');
            c.load(f.data('url'), function () {
                f.find('.adding2cart').hide();
                c.prepend('<a href="#" class="dialog-close">&times;</a>');
                d.show();
            });
            return false;
        }
        $.post(f.attr('action') + '?html=1', f.serialize(), function (response) {
            f.find('.adding2cart').hide();

            if (response.status == 'ok') {

                var cart_total = $(".cart-total");
                cart_total.closest('#cart').removeClass('empty');
                if( $(window).scrollTop() >= 55 )
                    $('#cart').addClass('fixed');

                if ( MatchMedia("only screen and (max-width: 760px)") ) {

                    // mobile: show "added to cart" message
                    f.find('[type="submit"]').hide();
                    f.find('.price').hide();
                    f.find('span.added2cart').show();
                    cart_total.html(response.data.total);
                    $('#cart-content').append($('<div class="cart-just-added"></div>').html(f.find('span.added2cart').text()));
                    if ($('#cart').hasClass('fixed'))
                        $('.cart-to-checkout').hide();
                } else {

                    // flying cart
                    var origin = f.closest('li');
                    var block = $('<div></div>').append(origin.html());
                    block.css({
                        'z-index': 100500,
                        background: '#fff',
                        top: origin.offset().top,
                        left: origin.offset().left,
                        width: origin.width()+'px',
                        height: origin.height()+'px',
                        position: 'absolute',
                        overflow: 'hidden'
                    }).appendTo('body').css({'border':'2px solid #eee','padding':'20px','background':'#fff'}).animate({
                        top: cart_total.offset().top,
                        left: cart_total.offset().left,
                        width: '10px',
                        height: '10px',
                        opacity: 0.7
                    }, 700, function() {
                        $(this).remove();
                        cart_total.html(response.data.total);

                        var $addedText = $('<div class="cart-just-added"></div>').html(f.find('span.added2cart').text());
                        $('#cart-content').append($addedText);
                        setTimeout( function() {
                            $addedText.remove();
                        }, 2000);

                        if ($('#cart').hasClass('fixed'))
                            $('.cart-to-checkout').show();
                    });
                }
            } else if (response.status == 'fail') {
                alert(response.errors);
            }

        }, "json");
        return false;
    });


    //PRODUCT FILTERING
    var f = function () {

        var ajax_form_callback = function (f) {
            var fields = getFields(f);
            var params = [];

            $.each(fields, function(i, field) {
                if (field.name && field.value) {
                    params.push(field.name + '=' + field.value);
                }
            });

            var url = location.pathname + ( params.length ? "?" + params.join('&') : "");

            $(window).lazyLoad && $(window).lazyLoad('sleep');
            $('#product-list').html('<img src="' + f.data('loading') + '">');
            $.get(url, function(html) {
                var tmp = $('<div></div>').html(html);
                $('#product-list').html(tmp.find('#product-list').html());
                if (!!(history.pushState && history.state !== undefined)) {
                    window.history.pushState({}, '', url);
                }
                $(window).lazyLoad && $(window).lazyLoad('reload');
            });

            function getFields($form) {
                var result = [];

                var form_array = $form.serializeArray();

                $.each(form_array, function(i, field) {
                    var full_name = field.name,
                        search_string = "[unit]";

                    var is_unit = (full_name.substr(-(search_string.length)) === search_string);
                    if (is_unit) {
                        var param_name = full_name.substr(0, full_name.length - search_string.length);
                        var is_param_set = checkParam(param_name);
                        if (is_param_set) {
                            result.push(field);
                        }

                    } else {
                        result.push(field);
                    }
                });

                return result;

                function checkParam(param_name) {
                    var result = false;

                    $.each(form_array, function(i, field) {
                        if (field.name === param_name +"[min]" || field.name === param_name +"[max]") {
                            if (field.value.length) {
                                result = true;
                                return true;
                            }
                        }
                    });

                    return result;
                }
            }
        };

        $('.filters.ajax form input').change(function () {
            ajax_form_callback($(this).closest('form'));
        });
        $('.filters.ajax form').submit(function () {
            ajax_form_callback($(this));
            return false;
        });

        $('.filters .slider').each(function () {
            if (!$(this).find('.filter-slider').length) {
                $(this).append('<div class="filter-slider"></div>');
            } else {
                return;
            }
            var min = $(this).find('.min');
            var max = $(this).find('.max');
            var min_value = parseFloat(min.attr('placeholder'));
            var max_value = parseFloat(max.attr('placeholder'));
            var step = 1;
            var slider = $(this).find('.filter-slider');
            if (slider.data('step')) {
                step = parseFloat(slider.data('step'));
            } else {
                var diff = max_value - min_value;
                if (Math.round(min_value) !== min_value || Math.round(max_value) !== max_value) {
                    var tail_length = 0;
                    try {
                        if (min_value > 0) {
                            var min_tail = (min_value + "").split(".")[1];
                            if (min_tail && min_tail.length) {
                                tail_length = min_tail.length;
                            }
                        }

                        if (max_value > 0) {
                            var max_tail = (max_value + "").split(".")[1];
                            if (max_tail && max_tail.length && max_tail.length > tail_length) {
                                tail_length = max_tail.length;
                            }
                        }
                    } catch(error) {
                        (console && console.log(error.message));
                    }

                    if (tail_length > 0) {
                        step = 1 / Math.pow(10, tail_length);
                    }

                //     step = diff / 10;
                //     var tmp = 0;
                //     while (step < 1) {
                //         step *= 10;
                //         tmp += 1;
                //     }
                //     step = Math.pow(10, -tmp);
                //     tmp = Math.round(100000 * Math.abs(Math.round(min_value) - min_value)) / 100000;
                //     if (tmp && tmp < step) {
                //         step = tmp;
                //     }
                //     tmp = Math.round(100000 * Math.abs(Math.round(max_value) - max_value)) / 100000;
                //     if (tmp && tmp < step) {
                //         step = tmp;
                //     }
                }
            }
            slider.slider({
                range: true,
                min: parseFloat(min.attr('placeholder')),
                max: parseFloat(max.attr('placeholder')),
                step: step,
                values: [parseFloat(min.val().length ? min.val() : min.attr('placeholder')),
                    parseFloat(max.val().length ? max.val() : max.attr('placeholder'))],
                slide: function( event, ui ) {
                    var v = ui.values[0] == $(this).slider('option', 'min') ? '' : ui.values[0];
                    min.val(v);
                    v = ui.values[1] == $(this).slider('option', 'max') ? '' : ui.values[1];
                    max.val(v);
                },
                stop: function (event, ui) {
                    min.change();
                }
            });
            min.add(max).change(function () {
                var v_min =  min.val() === '' ? slider.slider('option', 'min') : parseFloat(min.val());
                var v_max = max.val() === '' ? slider.slider('option', 'max') : parseFloat(max.val());
                if (v_max >= v_min) {
                    slider.slider('option', 'values', [v_min, v_max]);
                }
            });
        });

        window.addEventListener('popstate', function(event) {
            location.reload();
        });
    };
    f();

    //SLIDEMENU sidebar navigation
    $('.slidemenu')
        .on('afterLoadDone.waSlideMenu', function () {
            f();
            $(window).lazyLoad && $(window).lazyLoad('reload');
        })
        .on('onLatestClick.waSlideMenu', function () {
            if ( (!!('ontouchstart' in window)) && ( MatchMedia("only screen and (max-width: 760px)") ) ) {
                $('.nav-sidebar-body').slideUp(200);
            }
        });

    //LAZYLOADING
    if ($.fn.lazyLoad) {
        var paging = $('.lazyloading-paging');
        if (!paging.length) {
            return;
        }

        var times = parseInt(paging.data('times'), 10);
        var link_text = paging.data('linkText') || 'Load more';
        var loading_str = paging.data('loading-str') || 'Loading...';

        // check need to initialize lazy-loading
        var current = paging.find('li.selected');
        if (current.children('a').text() != '1') {
            return;
        }
        paging.hide();
        var win = $(window);

        // prevent previous launched lazy-loading
        win.lazyLoad('stop');

        // check need to initialize lazy-loading
        var next = current.next();
        if (next.length) {
            win.lazyLoad({
                container: '#product-list .product-list',
                load: function () {
                    win.lazyLoad('sleep');

                    var paging = $('.lazyloading-paging').hide();

                    // determine actual current and next item for getting actual url
                    var current = paging.find('li.selected');
                    var next = current.next();
                    var url = next.find('a').attr('href');
                    if (!url) {
                        win.lazyLoad('stop');
                        return;
                    }

                    var product_list = $('#product-list .product-list');
                    var loading = paging.parent().find('.loading').parent();
                    if (!loading.length) {
                        loading = $('<div><i class="icon16 loading"></i>'+loading_str+'</div>').insertBefore(paging);
                    }

                    loading.show();
                    $.get(url, function (html) {
                        var tmp = $('<div></div>').html(html);

                        product_list.append(tmp.find('#product-list .product-list').children());
                        var tmp_paging = tmp.find('.lazyloading-paging').hide();
                        paging.replaceWith(tmp_paging);
                        paging = tmp_paging;

                        times -= 1;

                        // check need to stop lazy-loading
                        var current = paging.find('li.selected');
                        var next = current.next();
                        if (next.length) {
                            if (!isNaN(times) && times <= 0) {
                                win.lazyLoad('sleep');
                                if (!$('.lazyloading-load-more').length) {
                                    $('<a href="#" class="lazyloading-load-more">' + link_text + '</a>').insertAfter(paging)
                                        .click(function () {
                                            loading.show();
                                            times = 1;      // one more time
                                            win.lazyLoad('wake');
                                            win.lazyLoad('force');
                                            return false;
                                        });
                                }
                            } else {
                                win.lazyLoad('wake');
                            }
                        } else {
                            win.lazyLoad('stop');
                            $('.lazyloading-load-more').hide();
                        }

                        loading.hide();
                        tmp.remove();
                    });
                }
            });
        }
    }

});

// Show Filters for Mobile
( function($) {

    var storage = {
        shownClass: "is-shown"
    };

    var bindEvents = function() {
        $("#filters-toggle-link").on("click", function() {
            toggleFilters( $(this) );
        });
    };

    var toggleFilters = function($link) {
        var $filters = $link.closest(".filters"),
            activeClass = storage.shownClass,
            show_text = $link.data("show-text"),
            hide_text = $link.data("hide-text"),
            is_active = $filters.hasClass(activeClass);

        if (is_active) {
            $filters.removeClass(activeClass);
            $link.text(show_text);
        } else {
            $filters.addClass(activeClass);
            $link.text(hide_text);
        }
    };

    $(document).ready( function() {
        bindEvents();
    });

})(jQuery);

// Shop :: Promo CountDown
var CountDown = ( function($) {

    CountDown = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.start = options["start"];
        that.end = options["end"];
        that.format = "%days%:%hours%:%minutes%:%seconds%";

        // DYNAMIC VARS
        that.period = that.getPeriod();
        that.time_period = null;
        that.timer = 0;

        // INIT
        that.run();
    };

    CountDown.prototype.getPeriod = function() {
        var that = this,
            start_date = new Date( that.start ),
            end_date = new Date( that.end );

        return (end_date > start_date) ? (end_date - start_date) : 0;
    };

    CountDown.prototype.getData = function() {
        var that = this,
            period = that.period;

        var second = 1000,
            minute = second * 60,
            hour = minute * 60,
            day = hour * 24,
            residue;

        var days = Math.floor(period/day);
        residue = ( period - days * day );

        var hours = Math.floor(residue/hour);
        residue = ( residue - hours * hour );

        var minutes = Math.floor(residue/minute);
        residue = ( residue - minutes * minute );

        var seconds = Math.floor(residue/second);

        return {
            days: days,
            hours: hours,
            minutes: minutes,
            seconds: seconds
        }
    };

    CountDown.prototype.getTime = function() {
        var that = this,
            data = that.getData(),
            result = that.format;

        return result
            .replace("%days%", (data.days < 10) ? "0" + data.days : data.days)
            .replace("%hours%", (data.hours < 10) ? "0" + data.hours : data.hours)
            .replace("%minutes%", (data.minutes < 10) ? "0" + data.minutes : data.minutes)
            .replace("%seconds%", (data.seconds < 10) ? "0" + data.seconds : data.seconds);
    };

    CountDown.prototype.run = function() {
        var that = this,
            timer = 1000;

        if (that.period > 0) {
            var time = that.getTime();

            that.$wrapper.html(time);

            that.period -= timer;

            if (that.period > 0) {
                that.timer = setTimeout( function () {
                    that.run();
                }, timer);
            }

        } else {
            that.destroy();
        }
    };

    CountDown.prototype.destroy = function() {
        var that = this;

        that.$wrapper.remove();
    };

    return CountDown;

})(jQuery);
