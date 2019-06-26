( function($) {
    var is_touch_enabled = ("ontouchstart" in window);

    $(document).ready( function () {

        if (is_touch_enabled) {
            $("html").addClass("is-touch-enabled");

            $(".app-navigation").on("click", ".menu-h.dropdown .collapsible", function(event) {
                var has_menu = $(event.target).parent().hasClass("collapsible");
                if (has_menu) {
                    event.preventDefault();
                    event.stopPropagation();

                    var $li = $(this);

                    var active_class = "is-opened";

                    // clear all
                    $li.closest(".menu-h.dropdown")
                        .find(".collapsible").removeClass(active_class);

                    // mark parents
                    $li.parents(".collapsible").addClass(active_class);

                    // mark children
                    if ($li.hasClass(active_class)) {
                        $li.removeClass(active_class);
                    } else {
                        $li.addClass(active_class);
                    }
                }
            });
        }

    });

})(jQuery);
