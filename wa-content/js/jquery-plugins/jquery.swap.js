(function ($) {
    "use strict";
    $.fn.swap = function (to_index_or_target, $siblings) {
        var $el = $(this);
        var index;

        if (isNaN(parseInt(to_index_or_target))) {
            index = $(to_index_or_target).index();
        } else {
            index = to_index_or_target;
        }

        if (!$siblings) {
            $siblings = $el.siblings();
        }

        var length = $siblings.length;
        if (length === index) {
            $el.insertAfter($siblings.get(length - 1));
        } else {
            $el.insertBefore($siblings.get(index));
        }
    };
})(jQuery);
