$(document).ready(function() {

    if ($('.slidemenu').length)
    {
        var _back_lbl = 'Back';
        if ( $('.slidemenu').attr('data-back-lbl') )
            _back_lbl = $('.slidemenu').attr('data-back-lbl');
            
        $('.slidemenu').waSlideMenu({
            slideSpeed          : 300,
            loadSelector        : '#page-content',
            backLinkContent     : _back_lbl,
            excludeUri          : ['/', '#'],
            loadOnlyLatest      : false,
            setTitle            : true,
            scrollToTopSpeed    : 200,
            backOnTop           : false
        });
    }
    
    // MOBILE slide-in menu
    $('.apps-toggle').click( function(){
        if (!$('.apps-negative').length) {
            $('body').prepend($('header .apps').clone().removeClass('apps').addClass('apps-negative'));
            $('.apps-negative').hide().slideToggle(200);
        } else {
            $('.apps-negative').slideToggle(200);
        }
    });
    
    // SIDEBAR HEADER click
    $('a.nav-sidebar-header').click(function(){
    
        // on devices without :hover event (tablets such as iPad) clicking on sidebar header link should show the sidebar
        var _sidebar_visible = $('.nav-sidebar-body').css('opacity');
        if ( !parseInt(_sidebar_visible) )
        {
            return false;
        }
    });
    
    // FOLLOW US toggle
    $('a#followus-leash-toggler').click(function(){

        $('#followus-leash-inner').slideToggle(200);
        $('a#followus-leash-toggler i.icon16.toggle-arrow').toggleClass('uarr');
        $('a#followus-leash-toggler i.icon16.toggle-arrow').toggleClass('darr');
        return false;

    });
    
    // FOLLOW US Mailer subscribe
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
            $('#mailer-subscribe-thankyou').show();
        });
    });
    
    // LOGO position adjustment
    if ($('#logo').length)
    {
        $(window).load(function(){
        
            var _logo_height = $('#logo').height();
            var _logo_vertical_shift = Math.round((_logo_height-25)/2);
            
            $('#header-container').css('padding-top', _logo_vertical_shift+'px');
            $('#logo').css('margin-top', '-'+_logo_vertical_shift+'px');
        
        });
    }

    // STICKY CART
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

});
