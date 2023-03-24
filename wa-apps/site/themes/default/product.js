function Product(form, options) {
    var that = this;

    that.is_dialog = ( options["is_dialog"] || false );
    that.images = ( options["images"] || [] );

    this.form = $(form);
    this.product_topbar = options.product_topbar;
    this.add2cart = this.form.find(".add2cart");
    this.add2cart_top = this.form.find(".add2cart.js-top");
    this.button = this.add2cart.find("[type=submit]");
    for (var k in options) {
        this[k] = options[k];
    }
    var self = this;
    // add to cart block: services
    this.form.find(".services input[type=checkbox]").click(function () {
        var obj = $('select[name="service_variant[' + $(this).val() + ']"]');
        if (obj.length) {
            if ($(this).is(':checked')) {
                obj.removeAttr('disabled');
            } else {
                obj.attr('disabled', 'disabled');
            }
        }
        self.cartButtonVisibility(true);
        self.updatePrice();
    });

    this.form.find(".services .service-variants").on('change', function () {
        self.cartButtonVisibility(true);
        self.updatePrice();
    });

    this.form.find('.inline-select a').click(function () {
        var d = $(this).closest('.inline-select');
        d.find('a.selected').removeClass('selected');
        $(this).addClass('selected');
        d.find('.sku-feature').val($(this).data('value')).change();
        d.find('.js-f-name').text($(this).text().trim());
        return false;
    });

    // init value
    this.form.find('.inline-select a.selected').trigger('click');

    this.form.find(".skus input[type=radio]").click(function () {
        var image_id = $(this).data('image-id');
        that.setImage(image_id);
        if ($(this).data('disabled')) {
            self.button.attr('disabled', 'disabled');
        } else {
            self.button.removeAttr('disabled');
        }
        var sku_id = $(this).val();
        self.updateSkuServices(sku_id);
        self.cartButtonVisibility(true);
        self.updatePrice();

        var sku = (that.skus[sku_id] ? that.skus[sku_id] : null);
        that.form.trigger("product_sku_changed", [sku_id, sku]);
    });
    var $initial_cb = this.form.find(".skus input[type=radio]:checked:not(:disabled)");
    if (!$initial_cb.length) {
        $initial_cb = this.form.find(".skus input[type=radio]:not(:disabled):first").prop('checked', true).click();
    }
    $initial_cb.click();

    this.form.find(".sku-feature").on("change", function () {
        var sku_id = null,
            key = "";

        self.form.find(".sku-feature").each(function () {
            key += $(this).data('feature-id') + ':' + $(this).val() + ';';
        });

        var sku = self.features[key];
        if (sku) {
            sku_id = sku.id;
            that.setImage(sku.image_id);
            self.updateSkuServices(sku.id);
            if (sku.available) {
                self.button.removeAttr('disabled');
            } else {
                self.form.find("div.stocks div").hide();
                self.form.find(".sku-no-stock").show();
                self.button.attr('disabled', 'disabled');
            }
            self.add2cart_top.find(".price").data('price', sku.price);
            self.updatePrice(sku.price, sku.compare_price);
        } else {
            self.form.find("div.stocks div").hide();
            self.form.find(".sku-no-stock").show();
            self.button.attr('disabled', 'disabled');
            self.add2cart_top.find(".compare-at-price").hide();
            self.add2cart_top.find(".price").empty();
        }
        self.cartButtonVisibility(true);

        that.form.trigger("product_sku_changed", [sku_id, sku]);
    });
    this.form.find(".sku-feature:first").change();

    if (!this.form.find(".skus input:radio:checked").length) {
        this.form.find(".skus input:radio:enabled:first").attr('checked', 'checked');
    }

    this.form.submit(function () {
        var f = $(this);
        f.find('.adding2cart').addClass('icon24 loading').show();

        $.post(f.attr('action') + '?html=1', f.serialize(), function (response) {
            f.find('.adding2cart').hide();
            if (response.status == 'ok') {
                var cart_total = $(".cart-total");
                var cart_div = f.closest('.cart');
                if( $(window).scrollTop() >= 110 )
                    $('#cart').addClass('fixed');

                cart_total.closest('#cart').removeClass('empty');

                self.cartButtonVisibility(false);
                if ( !( MatchMedia("only screen and (max-width: 760px)") ) ) {

                    // flying cart
                    var clone = $('<div class="cart"></div>').append(f.clone());
                    clone.appendTo('body');
                    clone.css({
                        'z-index': 100500,
                        background: cart_div.closest('.dialog').length ? '#fff' : cart_div.parent().css('background'),
                        top: cart_div.offset().top,
                        left: cart_div.offset().left,
                        width: cart_div.width() + 'px',
                        height: cart_div.height() + 'px',
                        position: 'absolute',
                        overflow: 'hidden'
                    }).css({'border':'2px solid #eee','padding':'20px','background':'#fff'}).animate({
                        top: cart_total.offset().top,
                        left: cart_total.offset().left,
                        width: '10px',
                        height: '10px',
                        opacity: 0.7
                    }, 600, function () {
                        $(this).remove();
                        cart_total.html(response.data.total);

                        var $addedText = $('<div class="cart-just-added"></div>').html( self.getEscapedText( self.add2cart.find('span.added2cart').text() ) );
                        $('#cart-content').append($addedText);
                        setTimeout( function() {
                            $addedText.remove();
                        }, 2000);

                        if ($('#cart').hasClass('fixed'))
                            $('.cart-to-checkout').slideDown(200);
                    });
                    if (cart_div.closest('.dialog').length) {
                        setTimeout(function () {
                            cart_div.closest('.dialog').hide().find('.cart').empty();
                        }, 0);

                    }

                } else {

                    // mobile: added to cart message
                    cart_total.html(response.data.total);
                }

                if (f.data('cart')) {
                    $("#page-content").load(location.href, function () {
                        $("#dialog").hide().find('.cart').empty();
                    });
                }
                if (response.data.error) {
                    alert(response.data.error);
                }
            } else if (response.status == 'fail') {
                alert(response.errors);
            }
        }, "json");

        return false;
    });

    this.compare_price = options["compare_price"];
}
Product.prototype.getEscapedText = function( bad_string ) {
    return $("<div>").text( bad_string ).html();
};

Product.prototype.currencyFormat = function (number, no_html) {
    // Format a number with grouped thousands
    //
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +	 bugfix by: Michael White (http://crestidg.com)

    var i, j, kw, kd, km;
    var decimals = this.currency.frac_digits;
    var dec_point = this.currency.decimal_point;
    var thousands_sep = this.currency.thousands_sep;

    // input sanitation & defaults
    if( isNaN(decimals = Math.abs(decimals)) ){
        decimals = 2;
    }
    if( dec_point == undefined ){
        dec_point = ",";
    }
    if( thousands_sep == undefined ){
        thousands_sep = ".";
    }

    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

    if( (j = i.length) > 3 ){
        j = j % 3;
    } else{
        j = 0;
    }

    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
    kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


    var number = km + kw + kd;
    var s = no_html ? this.currency.sign : this.currency.sign_html;
    if (!this.currency.sign_position) {
        return s + this.currency.sign_delim + number;
    } else {
        return number + this.currency.sign_delim + s;
    }
};


Product.prototype.serviceVariantHtml= function (id, name, price) {
    return $('<option data-price="' + price + '" value="' + id + '"></option>').text(name + ' (+' + this.currencyFormat(price, 1) + ')');
};

Product.prototype.updateSkuServices = function (sku_id) {
    this.form.find("div.stocks div").hide();
    this.product_topbar.find(".stocks div").hide();
    this.form.find(".sku-" + sku_id + "-stock").show();
    this.product_topbar.find(".sku-" + sku_id + "-stock").show();
    for (var service_id in this.services[sku_id]) {
        var variants = this.services[sku_id][service_id];
        if (variants === false) {
            this.form.find(".service-" + service_id).hide().find('input,select').attr('disabled', 'disabled').removeAttr('checked');
        } else {
            this.form.find(".service-" + service_id).show().find('input').removeAttr('disabled');
            if (typeof (variants) == 'string' || typeof (variants) === "number") {
                this.form.find(".service-" + service_id + ' .service-price').html(this.currencyFormat(variants));
                this.form.find(".service-" + service_id + ' input').data('price', variants);
            } else {
                var select = this.form.find(".service-" + service_id + ' .service-variants');
                var selected_variant_id = select.val();
                for (var variant_id in variants) {
                    var obj = select.find('option[value=' + variant_id + ']');
                    if (variants[variant_id] === false) {
                        obj.hide();
                        if (obj.attr('value') == selected_variant_id) {
                            selected_variant_id = false;
                        }
                    } else {
                        if (!selected_variant_id) {
                            selected_variant_id = variant_id;
                        }
                        obj.replaceWith(this.serviceVariantHtml(variant_id, variants[variant_id][0], variants[variant_id][1]));
                    }
                }
                this.form.find(".service-" + service_id + ' .service-variants').val(selected_variant_id);
            }
        }
    }
};
Product.prototype.updatePrice = function (price, compare_price) {
    var self = this;

    if (price === undefined) {
        var input_checked = this.form.find(".skus input:radio:checked");
        if (input_checked.length) {
            price = parseFloat(input_checked.data('price'));
            compare_price = parseFloat(input_checked.data('compare-price'));
        } else {
            price = parseFloat(this.add2cart_top.find(".price").data('price'));
            compare_price = this.compare_price;
        }
    }

    var service_price = 0;

    this.form.find(".services input:checked").each(function () {
        var s = $(this).val();
        if (self.form.find('.service-' + s + '  .service-variants').length) {
            service_price += parseFloat(self.form.find('.service-' + s + '  .service-variants :selected').data('price'));
        } else {
            service_price += parseFloat($(this).data('price'));
        }
    });

    this.add2cart_top.find(".price").html(this.currencyFormat(price + service_price));

    if (compare_price) {
        if (!this.add2cart_top.find(".compare-at-price").length) {
            this.add2cart_top.prepend('<span class="compare-at-price nowrap"></span>');
        }
        this.add2cart_top.find(".compare-at-price").html(this.currencyFormat(compare_price + service_price)).show();
    } else {
        this.add2cart_top.find(".compare-at-price").hide().html("");
    }

    this.compare_price = (compare_price ? compare_price : 0);
};

Product.prototype.cartButtonVisibility = function (visible) {
    //toggles "Add to cart" / "%s is now in your shopping cart" visibility status
    if (visible) {
        if (this.compare_price > 0) {
            this.add2cart.find('.compare-at-price').show();
        }
        this.add2cart.find('[type="submit"]').show();
        this.add2cart.find('.price').show();
        this.add2cart.find('.qty').show();
        this.add2cart.find('span.added2cart').hide();
    } else {
        if ( MatchMedia("only screen and (max-width: 760px)") ) {
            this.add2cart.find('.compare-at-price').hide();
            this.add2cart.find('[type="submit"]').hide();
            this.add2cart.find('.price').hide();
            this.add2cart.find('.qty').hide();
            this.add2cart.find('span.added2cart').show();
            if( $(window).scrollTop() >= 110 )
                $('#cart').addClass('fixed');
            $('#cart-content').append($('<div class="cart-just-added"></div>').html( this.getEscapedText( this.add2cart.find('span.added2cart').text() ) ));
            if ($('#cart').hasClass('fixed'))
                $('.cart-to-checkout').slideDown(200);
        }
    }
};

Product.prototype.setImage = function(image_id) {
    var that = this;

    if (that.is_dialog) {
        if (that.images) {
            image_id = (image_id ? image_id : "default");
            var image = that.images[image_id];
            if (image) {
                $("#product-image").attr("src", image.uri_200);
            }
        }
    } else {
        if (image_id) {
            $("#product-image-" + image_id).trigger("click");
        }
    }
};

$(function () {

    var $ = jQuery,
        $coreWrapper = $("#product-core-image"),
        $coreImages = $coreWrapper.find("a");

    if ($coreImages.length) {
        $(".swipebox").swipebox({
            useSVG : true,
            hideBarsDelay: false
        });

        $coreImages.on("click", function(e) {
            e.preventDefault();
            var images = [];
            if ($.swipebox.isOpen) {
                return;
            }
            if ($("#product-gallery a").length) {
                var k = $("#product-gallery div.selected").prevAll('.image').length;
                $('#product-gallery div.image').each(function () {
                    images.push({href: $(this).find('a').data('href')});
                });
                if (k) {
                    images = images.slice(k).concat(images.slice(0, k));
                }
            } else {
                images.push({href: $(this).attr('href')});
            }

            $.swipebox(images, {
                useSVG : true,
                hideBarsDelay: false,
                afterOpen: function() {
                    var $closeButton = $("#swipebox-close");
                    if ($closeButton.length) {
                        $closeButton.css('background-color', 'rgba(0,0,0,.95)')
                    }
                    var closeSwipe = function() {
                        if ($closeButton.length) {
                            $closeButton.trigger("click");
                        }
                        $(document).off("scroll", closeSwipe);
                    };

                    $(document).on("scroll", closeSwipe);
                }
            });
            return false;
        });
    }

    // product images
    $("#product-gallery a").not('#product-image-video').click(function () {
        var $small_image = $(this).find("img");


        $('#product-core-image').show();
        $('#video-container').hide();
        $(this).parent().addClass('selected').siblings().removeClass('selected');

        $("#product-image").addClass('blurred');
        $("#switching-image").show();

        var img = $(this).find('img');
        var size = $("#product-image").attr('src').replace(/^.*\/[^\/]+\.(.*)\.[^\.]*$/, '$1');
        var src = img.attr('src').replace(/^(.*\/[^\/]+\.)(.*)(\.[^\.]*)$/, '$1' + size + '$3');
        $('<img>').attr('src', src).load(function () {
            $("#product-image")
                .attr('src', src)
                .attr("title", $small_image.attr("title"))
                .attr("alt", $small_image.attr("alt"))
                .removeClass('blurred');

            $("#switching-image").hide();
        }).each(function() {
            //ensure image load is fired. Fixes opera loading bug
            if (this.complete) { $(this).trigger("load"); }
        });
        var size = $("#product-image").parent().attr('href').replace(/^.*\/[^\/]+\.(.*)\.[^\.]*$/, '$1');
        var href = img.attr('src').replace(/^(.*\/[^\/]+\.)(.*)(\.[^\.]*)$/, '$1' + size + '$3');
        $("#product-image").parent().attr('href', href);
        return false;
    });

    // product image video
    $('#product-image-video').click(function () {
        $('#product-core-image').hide();
        $('#video-container').show();
        $(this).parent().addClass('selected').siblings().removeClass('selected');
        return false;
    });

    // compare block
    $("a.compare-add").click(function () {
        var compare = $.cookie('shop_compare');
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
            if (compare.split(',').length > 1) {
                $("#compare-link").attr('href', url).show().find('span.count').html(compare.split(',').length);
            }
        }
        $.cookie('shop_compare', compare, { expires: 30, path: '/'});
        $(this).hide();
        $("a.compare-remove").show();
        return false;
    });
    $("a.compare-remove").click(function () {
        var compare = $.cookie('shop_compare');
        if (compare) {
            compare = compare.split(',');
        } else {
            compare = [];
        }
        var i = $.inArray($(this).data('product') + '', compare);
        if (i != -1) {
            compare.splice(i, 1)
        }
        $("#compare-link").hide();
        if (compare.length > 0) {
            $.cookie('shop_compare', compare.join(','), { expires: 30, path: '/'});
            var url = $("#compare-leash a").attr('href').replace(/compare\/.*$/, 'compare/' + compare.join(',') + '/');
            $('#compare-leash a').attr('href', url).show().find('strong').html(compare.length);
        } else {
            $('#compare-leash').hide();
            $.cookie('shop_compare', null, {path: '/'});
        }
        $(this).hide();
        $("a.compare-add").show();
        return false;
    });
});
