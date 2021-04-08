( function($) { "use strict";

    var Toggle = ( function($) {

        Toggle = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // VARS
            that.on = {
                ready: (typeof options["ready"] === "function" ? options["ready"] : function() {}),
                change: (typeof options["change"] === "function" ? options["change"] : function() {})
            };
            that.active_class = (options["active_class"] || "selected");

            // DYNAMIC VARS
            that.$before = null;
            that.$active = that.$wrapper.find("> *." + that.active_class);

            // INIT
            that.initClass();
        };

        Toggle.prototype.initClass = function() {
            var that = this,
                active_class = that.active_class;

            that.$wrapper.on("click", "> *", onClick);

            that.$wrapper.trigger("ready", that);

            that.on.ready(that);

            //

            function onClick(event) {
                event.preventDefault();

                var $target = $(this),
                    is_active = $target.hasClass(active_class);

                if (is_active) { return false; }

                if (that.$active.length) {
                    that.$before = that.$active.removeClass(active_class);
                }

                that.$active = $target.addClass(active_class);

                that.$wrapper.trigger("change", [this, that]);
                that.on.change(event, this, that);
            }
        };

        return Toggle;

    })($);

    var plugin_name = "toggle";

    $.fn.waToggle = function(plugin_options) {
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

                    $wrapper.data(plugin_name, new Toggle(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);