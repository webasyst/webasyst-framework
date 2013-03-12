$(document).ready(function () {

    // scroll-dependent animations
    $(window).scroll(function() {    
      	if ( $(this).scrollTop()>=35 ) {
            if (!$("#cart").hasClass('empty')) {
              	$("#cart").addClass( "fixed" );
            }
    	}
    	else if ( $(this).scrollTop()<30 ) {
    		$("#cart").removeClass( "fixed" );
    	}    
    });
  
});
