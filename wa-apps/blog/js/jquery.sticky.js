(function($) { "use strict";

    /**
     * jQuery plugin: makes element stick to top or bottom of the page when not in view.
     * When would be normally in view, stays as usual in its place.
     */

    $.fn.sticky = function(options) {
        var $self = this;
        var o = $.extend({
            fixed_css: { bottom: 0 },
            fixed_class: 'sticky-fixed',
            isStaticVisible: defaultIsStaticVisible,
            getClone: defaultGetClone,
            showFixed: function(e) {
                e.element.css({
                    'min-height': e.element.height()
                });
                e.fixed_clone.empty().append(e.element.children());
            },
            hideFixed: function(e) {
                e.element.css({
                    'min-height': 0
                });
                e.fixed_clone.children().appendTo(e.element);
            },
            updateFixed: function(e) {
                e.fixed_clone.css({
                    'min-width': e.element.width()
                });
            }
        }, options || {});

        // Prepare data for each element we're about to initialize
        var elements = $self.map(function() {
            var $e = $(this);
            return {
                element: $e,
                fixed_clone: o.getClone.call($e, $e, o),
                is_fixed: false
            };
        }).get();

        $(window).on('resize scroll', ensurePosition);
        ensurePosition();
        return this;

        function ensurePosition() {
            if (!$self.closest('body').length) {
                $(window).off('resize scroll', ensurePosition);
                return;
            }

            $.each(elements, function(i, e) {
                if (o.isStaticVisible.call(e.element, e, o)) {
                    if (e.is_fixed) {
                        e.is_fixed = false;
                        e.fixed_clone.hide();
                        o.hideFixed && o.hideFixed.call(e.fixed_clone, e, o); // !!! use events instead?
                    }
                } else {
                    if (!e.is_fixed) {
                        e.is_fixed = true;
                        e.fixed_clone.show();
                        o.showFixed && o.showFixed.call(e.fixed_clone, e, o); // !!! use events instead?
                    }
                    o.updateFixed && o.updateFixed.call(e.fixed_clone, e, o); // !!! use events instead?
                }
            });
        }
    };

    function defaultIsStaticVisible(e, o) {

        var $window = $(window);
        var window_borders = {
            top: $window.scrollTop(),
            right: $window.scrollLeft() + $window.width(),
            bottom: $window.scrollTop() + $window.height(),
            left: $window.scrollLeft()
        };

        var element_borders = e.element.offset();
        element_borders.right = element_borders.left + e.element.outerWidth();
        element_borders.bottom = element_borders.top + e.element.outerHeight();

        return  window_borders.top <= element_borders.top &&
                window_borders.left <= element_borders.left &&
                window_borders.right >= element_borders.right &&
                window_borders.bottom >= element_borders.bottom;
    }

    function defaultGetClone($e, o) {
        return $e.clone().addClass(o.fixed_class || 'sticky-fixed').css($.extend(
            { position: 'fixed', display: 'none' },
            o.fixed_css || {}
        )).removeAttr('id').insertAfter($e);
    }

})(jQuery);