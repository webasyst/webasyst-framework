( function($) {

    $(document).ready( function() {
        init();
    });

    function init() {
        var $nav_list = $("#niftybox .app-navigation > .menu-h.dropdown");
        if (!$nav_list.length) { return false; }

        var is_mobile = false;

        $nav_list.on("touchstart", ".collapsible > a", function() {
            is_mobile = true;
        });

        $nav_list.on("touchend", ".collapsible > a", function() {
            setTimeout( function() {
                is_mobile = false;
            }, 100);
        });

        $nav_list.on("click", ".collapsible > a", function() {
            if (is_mobile) {
                event.preventDefault();
            }
        });
    }

})(jQuery);