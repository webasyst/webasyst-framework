( function($) {

    var Drawer = ( function($) {

        Drawer = function(options) {
            var that = this;

            that.$wrapper = options["$wrapper"];
            if (that.$wrapper && that.$wrapper.length) {
                // DOM
                that.$block = that.$wrapper.find(".drawer-body");
                that.$body = $(window.top.document).find("body");
                that.$window = $(window.top);

                // VARS
                that.options = (options["options"] || false);
                that.direction = getDirection(options["direction"]);
                that.animation_time = 333;
                that.hide_class = "is-hide";

                // DYNAMIC VARS
                that.is_visible = false;
                that.is_locked = false;

                // HELPERS
                that.onBgClick = (options["onBgClick"] || false);
                that.onOpen = (options["onOpen"] || function() {});
                that.onClose = (options["onClose"] || function() {});

                // INIT
                that.initClass();
            } else {
                log("Error: bad data for drawer");
            }

            function getDirection(direction) {
                var result = "right",
                    direction_array = ["left", "right"];

                if (direction_array.indexOf(direction) !== -1) {
                    result = direction;
                }

                return result;
            }
        };

        Drawer.prototype.initClass = function() {
            var that = this;
            // save link on drawer
            that.$wrapper.data("drawer", that);
            //
            that.render();

            // Delay binding close events so that drawer does not close immidiately
            // from the same click that opened it.
            setTimeout( function() {
                that.bindEvents();
            }, 0);
        };

        Drawer.prototype.bindEvents = function() {
            var that = this,
                $document = $(document),
                $block = (that.$block) ? that.$block : that.$wrapper;

            that.$wrapper.on("close", close);

            // Click on background, default nothing
            that.$wrapper.on("click", ".drawer-background", function(event) {
                if (typeof that.onBgClick === "function") {
                    that.onBgClick(event);
                } else {
                    event.stopPropagation();
                }
            });

            $document.on("keyup", function(event) {
                var escape_code = 27;
                if (event.keyCode === escape_code) {
                    that.close();
                }
            });

            $block.on("click", ".js-close-drawer", close);

            //

            function close() {
                var result = that.close();
                if (result === true) {
                    $document.off("click", close);
                    $document.off("wa_before_load", close);
                }
            }
        };

        Drawer.prototype.render = function() {
            var that = this;

            var direction_class = (that.direction === "left" ? "left" : "right");
            that.$wrapper.addClass(direction_class).addClass(that.hide_class).show();

            try {
                that.show();
            } catch(e) {
                log("Error: " + e.message);
            }

            //
            that.onOpen(that.$wrapper, that);
        };

        Drawer.prototype.close = function() {
            var that = this,
                result = null;

            if (that.is_visible) {
                //
                result = that.onClose(that);
                //
                if (result !== false) {
                    if (!that.is_locked) {
                        that.is_locked = true;

                        that.$wrapper.addClass(that.hide_class);
                        setTimeout( function() {
                            that.$wrapper.remove();
                            that.is_locked = false;
                        }, that.animation_time);
                    }
                }
            }

            return result;
        };

        Drawer.prototype.hide = function() {
            var that = this;

            if (!that.is_locked) {
                that.is_locked = true;

                that.$wrapper.addClass(that.hide_class);
                setTimeout( function() {
                    $("<div />").append(that.$wrapper.hide());
                    that.is_visible = false;
                    that.is_locked = false;
                }, that.animation_time);
            }
        };

        Drawer.prototype.show = function() {
            var that = this,
                is_exist = $.contains(document, that.$wrapper[0]);

            if (!is_exist) {
                that.$body.append(that.$wrapper);
            }

            if (!that.is_locked) {
                that.is_locked = true;
                setTimeout( function() {
                    that.$wrapper.removeClass(that.hide_class);
                    that.is_locked = false;
                }, 100);
            }

            that.is_visible = true;
        };

        return Drawer;

    })($);

    $.waDrawer = function(plugin_options) {
        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        var options = $.extend(true, {}, plugin_options),
            result = false;

        options["$wrapper"] = getWrapper(options);

        if (options["$wrapper"]) {
            result = new Drawer(options);
        }

        return result;
    };

    function getWrapper(options) {
        var result = false;

        if (options["html"]) {
            result = $(options["html"]);

        } else if (options["wrapper"]) {
            result = options["wrapper"];

        } else {
            // result = generateDrawer(options["header"], options["content"], options["footer"]);
        }

        return result;

        function generateDrawer($header, $content, $footer) {
            var result = false;

            var wrapper_class = "drawer",
                bg_class = "drawer-background",
                block_class = "drawer-body",
                header_class = "drawer-header",
                content_class = "drawer-content",
                footer_class = "drawer-footer";

            var $wrapper = $("<div />").addClass(wrapper_class),
                $bg = $("<div />").addClass(bg_class),
                $body = $("<div />").addClass(block_class),
                $header_w = ( $header ? $("<div />").addClass(header_class).append($header) : false ),
                $content_w = ( $content ? $("<div />").addClass(content_class).append($content) : false ),
                $footer_w = ( $footer ? $("<div />").addClass(footer_class).append($footer) : false );

            if ($header_w || $content_w || $footer_w) {
                if ($header_w) {
                    $body.append($header_w)
                }
                if ($content_w) {
                    $body.append($content_w)
                }
                if ($footer_w) {
                    $body.append($footer_w)
                }
                result = $wrapper.append($bg).append($body);
            }

            return result;
        }
    }

    function log(data) {
        if (console && console.log) {
            console.log(data);
        }
    }

})(jQuery);
