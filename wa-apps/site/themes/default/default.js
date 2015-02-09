$(document).ready(function() {

    // custom LOGO position adjustment
    if ($('#logo').length)
    {
        $(window).load(function(){
        
            var _logo_height = $('#logo').height();
            var _logo_vertical_shift = Math.round((_logo_height-25)/2);
            
            $('#header-container').css('padding-top', _logo_vertical_shift+'px');
            $('#logo').css('margin-top', '-'+_logo_vertical_shift+'px');
        
        });
    }

    // SIDEBAR slide menu
    if ($('.slidemenu').length)
    {
        var _back_lbl = 'Back';
        if ( $('.slidemenu').attr('data-back-lbl') )
            _back_lbl = $('.slidemenu').attr('data-back-lbl');
            
        $('.slidemenu').waSlideMenu({
            slideSpeed          : 300,
            loadContainer       : '#page-content',
            backLinkContent     : _back_lbl,
            excludeUri          : ['/', '#'],
            loadOnlyLatest      : false,
            setTitle            : true,
            scrollToTopSpeed    : 200,
            backOnTop           : false,
            beforeLoad          : function(){ $('h1.category-name').append(' <i class="icon24 loading"></i>'); }
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
    if (!window.matchMedia("only screen and (max-width: 760px)").matches)
    {
        $(window).scroll(function(){
               
           	if ( $(this).scrollTop() >= 110 && !$("#cart").hasClass( "fixed" ) && !$("#cart").hasClass( "empty" ) && !($(".cart-summary-page")).length )
           	{
           	    $("#cart").hide();
           	    
           	    $("#cart").addClass( "fixed" );       	    
           	    if ($('#cart-flyer').length)
           	    {
           	        var _width = $('#cart-flyer').width()+50;
           	        var _offset_right = $(window).width() - $('#cart-flyer').offset().left - _width;
           	        
           	        $("#cart").css({ "right": _offset_right+"px", "width": _width+"px" });
           	    }
           	    
           	    $("#cart").slideToggle(200);
           	}
           	else if ( $(this).scrollTop() < 100 && $("#cart").hasClass( "fixed" ) )
        	{
    	        $("#cart").removeClass( "fixed" );
        	    $("#cart").css({ "width": "auto" });	   
        	}
           	
        });
    }

});
