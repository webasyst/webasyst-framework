( function($) { "use strict";

    var class_names = {
        "wrapper": "wa-tooltip",
        "icon": "wa-tooltip-icon",
        "text": "wa-tooltip-text"
    };

    var Tooltip = ( function($) {

        Tooltip = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$toggle = that.$wrapper.find( getSelector("icon") );
            if (!that.$toggle.length) { that.$toggle = that.$wrapper; }
            that.$hint = that.$wrapper.find( getSelector("text") );

            // VARS
            that.hover_enabled = isHoverEnabled(options["hover"]);
            that.hover_close_delay = ( options["hover_close_delay"] || 1000 );
            that.on = {
                open: (typeof options["open"] === "function" ? options["open"] : function() {}),
                close: (typeof options["close"] === "function" ? options["close"] : function() {}),
                focus: (typeof options["focus"] === "function" ? options["focus"] : function() {}),
                blur: (typeof options["blur"] === "function" ? options["blur"] : function() {})
            };

            // DYNAMIC VARS
            that.is_opened = false;

            // INIT
            that.initClass();

            function isHoverEnabled(option) {
                var result = false;

                if (typeof option === "boolean") {
                    result = option;
                }

                return result;
            }
        };

        Tooltip.prototype.initClass = function() {
            var that = this,
                hover_class = "is-hover",
                disable_hover_class = "is-hover-disabled";

            var close_timer = 0;

            that.$wrapper.addClass(disable_hover_class);

            //

            if (that.hover_enabled) {
                that.$wrapper.on("mouseenter", function(event) {
                    event.preventDefault();
                    clearTimeout(close_timer);
                    that.$wrapper.addClass(hover_class);
                    that.on.focus(that);
                });

                that.$wrapper.on("mouseleave", function(event) {
                    event.preventDefault();
                    close_timer = setTimeout( function() {
                        that.$wrapper.removeClass(hover_class);
                        that.on.blur(that);
                    }, that.hover_close_delay);
                });
            }

            that.$toggle.on("click", function(event) {
                event.preventDefault();

                var $hint = that.$hint,
                    is_hint = false;

                if ($hint.length) {
                    ($hint[0] === event.target || $.contains($hint[0], event.target));
                }

                if (!is_hint) { toggle(); }
            });

            $(document).on("click", clickWatcher);
            function clickWatcher(event) {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    var is_target = (that.$wrapper[0] === event.target || $.contains(that.$wrapper[0], event.target));
                    if (!is_target && that.is_opened) {
                        toggle(false);
                    }
                } else {
                    $(document).off("click", clickWatcher);
                }
            }

            function toggle(show) {
                var open_class = "is-opened";

                show = (typeof show === "boolean" ? show : !that.is_opened);

                that.is_opened = show;

                if (show) {
                    that.$wrapper.addClass(open_class);
                    that.on.open(that);

                } else {
                    that.$wrapper.removeClass(open_class);
                    that.on.close(that);
                }
            }
        };

        return Tooltip;

    })($);

    $.waTooltip = function(options) {
        return new Tooltip(options);
    };

    function getSelector(name) {
        return (class_names[name] ? "." + class_names[name] : null);
    }

    var plugin_name = "tooltip";

    $.fn.waTooltip = function(plugin_options) {
        var return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                var $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    var options = $.extend(true, plugin_options, {
                        $wrapper: $wrapper
                    });

                    $wrapper.data(plugin_name, new Tooltip(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);