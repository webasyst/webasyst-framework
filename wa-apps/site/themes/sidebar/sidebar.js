$(document).ready(function () {

    var fixed_nav_offset_static = $('#fixed-nav-sidebar').offset().top;
    var fixed_nav_offset_current = 0;
    
    // STICKY FIXED NAV for non-mobile
    if (!window.matchMedia("only screen and (max-width: 1024px)").matches)
    {
        $(window).scroll(function(){
        
            //make app sidebar navigation fixed if it fits viewport
            if ( $('#fixed-nav-sidebar').height() < $(window).height() )
            {
                $("#fixed-nav-sidebar").addClass("fixed");
                fixed_nav_offset_current = Math.max(fixed_nav_offset_static - $(this).scrollTop(), 0);
            	$("#fixed-nav-sidebar").css("top",fixed_nav_offset_current);
            }

        });
    }

    $('#tablet-toggle-sidebar').click(function(){
    
        $('#sidebar').toggleClass('visible');
        $('#main').toggleClass('sidebar-visible');
        
    });

});
