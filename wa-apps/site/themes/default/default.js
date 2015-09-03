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

    // custom LOGO position adjustment
    if ($('#logo').length)
    {
        $(window).load(function(){
        
            var _logo_height = $('#logo').height();
            var _logo_vertical_shift = Math.round((_logo_height-25)/2);
            
            $('#globalnav').css('padding-top', _logo_vertical_shift+'px');
            $('#logo').css('margin-top', '-'+_logo_vertical_shift+'px');
        
        });
    }

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
        
    // MAILER app email subscribe form
    $('#mailer-subscribe-form input.charset').val(document.charset || document.characterSet);
    $('#mailer-subscribe-form').submit(function() {
        var form = $(this);

        var email_input = form.find('input[name="email"]');
        var email_submit = form.find('input[type="submit"]');
        if (!email_input.val()) {
            email_input.addClass('error');
            return false;
        } else {
            email_input.removeClass('error');
        }
        
        email_submit.hide();
        email_input.after('<i class="icon16 loading"></i>');

        $('#mailer-subscribe-iframe').load(function() {
            $('#mailer-subscribe-form').hide();
            $('#mailer-subscribe-iframe').hide();
            $('#mailer-subscribe-thankyou').show();
        });
    });
    
    // STICKY CART for non-mobile
    if ( !(MatchMedia("only screen and (max-width: 760px)")) ) {
        $(window).scroll(function(){
           	if ( $(this).scrollTop() >= 55 && !$("#cart").hasClass( "fixed" ) && !$("#cart").hasClass( "empty" ) && !($(".cart-summary-page")).length ) {
           	    $("#cart").hide();
           	    
           	    $("#cart").addClass( "fixed" );       	    
           	    if ($('#cart-flyer').length)
           	    {
           	        var _width = $('#cart-flyer').width()+52;
           	        var _offset_right = $(window).width() - $('#cart-flyer').offset().left - _width + 1;
           	        
           	        $("#cart").css({ "right": _offset_right+"px", "width": _width+"px" });
           	    }
           	    
           	    $("#cart").slideToggle(200);
           	} else if ( $(this).scrollTop() < 50 && $("#cart").hasClass( "fixed" ) ) {
    	        $("#cart").removeClass( "fixed" );
        	    $("#cart").css({ "width": "auto" });	   
        	}
        });
    }
});
