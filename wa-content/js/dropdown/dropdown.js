( function($) { "use strict";

    var Dropdown = ( function($) {

        Dropdown = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$button = that.$wrapper.find("> .wa-dropdown-toggle");
            that.$menu = that.$wrapper.find("> .wa-dropdown-area");

            // VARS
            that.on = {
                change: (typeof options["change"] === "function" ? options["change"] : function() {}),
                ready: (typeof options["ready"] === "function" ? options["ready"] : function() {})
            };
            that.data = getData(that.$wrapper, options);

            // DYNAMIC VARS
            that.is_opened = false;
            that.$before = null;
            that.$active = null;

            // INIT
            that.initClass();
        };

        Dropdown.prototype.initClass = function() {
            var that = this;

            var is_touch_enabled = ("ontouchstart" in window);
            if (is_touch_enabled) {
                initTapSupport();
            }

            if (that.data.hover) {
                that.$button.on("mouseenter", function() {
                    that.toggleMenu(true);
                });

                that.$wrapper.on("mouseleave", function() {
                    that.toggleMenu(false);
                });
            }

            that.$button.on("click", function(event) {
                event.preventDefault();
                that.toggleMenu(!that.is_opened);
            });

            if (that.data.change_selector) {
                that.initChange(that.data.change_selector);
            }

            $(document).on("click", clickWatcher);

            $(document).on("keyup", keyWatcher);

            that.on.ready(that);

            function keyWatcher(event) {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    var is_escape = (event.keyCode === 27);
                    if (that.is_opened && is_escape) {
                        that.hide();
                    }
                } else {
                    $(document).off("click", keyWatcher);
                }
            }

            function clickWatcher(event) {
                var wrapper = that.$wrapper[0],
                    is_exist = $.contains(document, wrapper);

                if (is_exist) {
                    var is_target = (event.target === wrapper || $.contains(wrapper, event.target));
                    if (that.is_opened && !is_target) {
                        that.hide();
                    }
                } else {
                    $(document).off("click", clickWatcher);
                }
            }

            function initTapSupport() {
                var button = that.$button[0];

                var time_start = 0,
                    time_delta = 300;

                button.addEventListener("touchstart", function() {
                    var date = new Date();
                    time_start = date.getTime();
                }, false);

                button.addEventListener("touchend", function(event) {
                    var date = new Date(),
                        time_end = date.getTime();

                    if (time_start) {
                        if (time_delta >= time_end - time_start) {
                            event.preventDefault();
                            that.$button.trigger("click");
                        }
                    }
                }, false);
            }
        };

        Dropdown.prototype.toggleMenu = function(open) {
            var that = this,
                active_class = "is-opened";

            if (open) {
                that.$wrapper
                    .addClass(active_class)
                    .trigger("open", that);

            } else {
                that.$wrapper
                    .removeClass(active_class)
                    .trigger("close", that);

            }

            that.is_opened = open;
        };

        Dropdown.prototype.initChange = function(selector) {
            var that = this,
                change_class = that.data.change_class;

            that.$active = that.$menu.find(selector + "." + change_class);

            that.$wrapper.on("click", selector, onChange);

            function onChange(event) {
                event.preventDefault();

                var $target = $(this);

                if (that.$active.length) {
                    that.$before = that.$active.removeClass(change_class);
                }

                that.$active = $target.addClass(change_class);

                if (that.data.change_title) {
                    that.setTitle($target.html());
                }

                if (that.data.change_hide) {
                    that.hide();
                }

                that.$wrapper.trigger("change", [$target[0], that]);
                that.on.change(event, this, that);
            }
        };

        Dropdown.prototype.hide = function() {
            var that = this;

            that.toggleMenu(false);
        };

        Dropdown.prototype.setTitle = function(html) {
            var that = this;

            that.$button.html( html );
        };

        return Dropdown;

        function getData($wrapper, options) {
            var result = {
                hover: true,
                change_selector: "",
                change_class: "selected",
                change_title: true,
                change_hide: true
            };

            var hover = ( typeof options["hover"] !== "undefined" ? options["hover"] : $wrapper.data("hover") );
            if (hover === false) { result.hover = false; }

            result.change_selector = (options["change_selector"] || $wrapper.data("change-selector") || "");
            result.change_class = (options["change_class"] || $wrapper.data("change-class") || "selected");

            var change_title = ( typeof options["change_title"] !== "undefined" ? options["change_title"] : $wrapper.data("change-title") );
            if (change_title === false) { result.change_title = false; }

            var hide = ( typeof options["change_hide"] !== "undefined" ? options["change_hide"] : $wrapper.data("change-hide") );
            if (hide === false) { result.change_hide = false; }

            return result;
        }

    })($);

    var plugin_name = "dropdown";

    $.fn.waDropdown = function(plugin_options) {
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
                        index: index,
                        $wrapper: $wrapper
                    });

                    $wrapper.data(plugin_name, new Dropdown(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);