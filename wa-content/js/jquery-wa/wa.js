/**
 * @description autocomplete component
 * @example /webasyst/ui/component/autocomplete/
 * */
( function($) { "use strict";

    var plugin_name = "autocomplete";

    $.fn.waAutocomplete = function(plugin_options) {
        var return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this,
            is_loaded = false;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                var $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    load().then( function() {
                        var instance = $wrapper.autocomplete(plugin_options).autocomplete("instance");
                        $wrapper.data(plugin_name, instance);
                    });
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }

        /**
         * @return Promise
         * */
        function load() {
            var deferred = $.Deferred();

            if (is_loaded) {
                deferred.resolve();
            } else {
                $.getScript("https://code.jquery.com/ui/1.12.1/jquery-ui.js", function () {
                    $('<link/>', { href: "//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css", rel: 'stylesheet' }).appendTo('head');
                    is_loaded = true;
                    deferred.resolve();
                });
            }

            return deferred.promise();
        }
    };

})(jQuery);

/**
 * @description dialog component
 * @example /webasyst/ui/component/dialog/
 * */
( function($) {

    var class_names = {
        "wrapper": "dialog",
            "background": "dialog-background",
            "body": "dialog-body",
                "header": "dialog-header",
                "content": "dialog-content",
                "footer": "dialog-footer"
    };

    var locked_class = "is-locked";

    var Dialog = ( function($) {

        Dialog = function(options) {
            var that = this;

            that.$wrapper = options["$wrapper"];

            if (that.$wrapper && that.$wrapper.length) {
                // DOM
                that.$block = that.$wrapper.find( getSelector("body") );
                that.$body = $(document).find("body");
                that.$window = $(window);

                // CONST
                that.esc = (typeof options["esc"] === "boolean" ? options["esc"] : true);
                that.animate = (typeof options["animate"] === "boolean" ? options["animate"] : true);

                // VARS
                that.options = (options["options"] || {});
                that.position = (typeof options["position"] === "function" ? options["position"] : null);
                that.lock_body_scroll = (typeof options["lock_body_scroll"] === "boolean" ? options["lock_body_scroll"] : true);

                // DYNAMIC VARS
                that.is_visible = false;
                that.is_removed = false;

                // HELPERS
                that.onOpen = (typeof options["onOpen"] === "function" ? options["onOpen"] : null);
                that.onClose = (typeof options["onClose"] === "function" ? options["onClose"] : null);
                that.onResize = (typeof options["onResize"] === "function" ? options["onResize"] : null);
                that.onBgClick = (typeof options["onBgClick"] === "function" ? options["onBgClick"] : null);

                // INIT
                that.init();

            } else {
                log("Error: bad data for dialog");
            }
        };

        Dialog.prototype.init = function() {
            var that = this;
            // save link on dialog
            that.$wrapper.data("dialog", that);
            //
            that.render();

            // Delay binding close events so that dialog does not close immidiately
            // from the same click that opened it.
            setTimeout(function() {
                that.bindEvents();
            }, 0);
        };

        Dialog.prototype.bindEvents = function() {
            var that = this,
                $window = $(window),
                $document = $(document),
                $block = (that.$block) ? that.$block : that.$wrapper;

            $block.on("click", ".js-close-dialog, .js-dialog-close", function(event) {
                event.preventDefault();
                that.close();
            });

            that.$wrapper.on("dialog-close", function() {
                that.close();
            });

            // Click on background, default nothing
            that.$wrapper.on("click", getSelector("background"), function(event) {
                if (that.onBgClick) {
                    that.onBgClick(event, that.$wrapper, that);
                }
            });

            if (that.esc) {
                $document.on("keyup", escapeWatcher);
                function escapeWatcher(event) {
                    var is_exist = $.contains(document, that.$wrapper[0]);
                    if (is_exist) {
                        var escape_code = 27;
                        if (event.keyCode === escape_code) {
                            that.close();
                        }
                    } else {
                        if (that.is_removed) {
                            $document.off("keyup", escapeWatcher);
                        }
                    }
                }
            }

            $window.on("resize", onResize);
            function onResize() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.resize();
                } else {
                    if (that.is_removed) {
                        $window.off("resize", onResize);
                    }
                }
            }

            // refresh dialog position
            $document.on("refresh", resizeDialog);
            function resizeDialog() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.resize();
                } else {
                    if (that.is_removed) {
                        $document.off("resizeDialog", resizeDialog);
                    }
                }
            }
        };

        Dialog.prototype.render = function() {
            var that = this;

            try {
                that.show();
            } catch(e) {
                log("Error: " + e.message);
            }

            // trigger event on open
            if (that.onOpen) {
                that.onOpen(that.$wrapper, that);
            }
        };

        Dialog.prototype.setPosition = function() {
            var that = this,
                $window = that.$window,
                $block = (that.$block) ? that.$block : that.$wrapper,
                $content = $block.find( getSelector("content") );

            $content.css("height", "auto");

            var window_w = $window.width(),
                window_h = $window.height(),
                wrapper_w = $block.outerWidth(),
                wrapper_h = $block.outerHeight(),
                pad = 20,
                css;

            var getPosition = getDefaultPosition;

            if (that.position) {
                getPosition = that.position;
                pad = 0;
            }

            css = getPosition({
                width: wrapper_w,
                height: wrapper_h
            });

            if (css.left > 0) {
                if (css.left + wrapper_w > window_w) {
                    css.left = window_w - wrapper_w - pad;
                }
            }

            if (css.top > 0) {
                if (css.top + wrapper_h > window_h) {
                    css.top = window_h - wrapper_h - pad;
                }
            } else {
                css.top = pad;

                $content.hide();

                var block_h = $block.outerHeight(),
                    content_h = window_h - block_h - pad * 2;

                if (content_h > 0){
                    $content
                        .css("height", content_h + "px")
                        .addClass("is-long-content")
                        .show();
                }else{
                    $content
                        .css("height", "auto")
                        .addClass("is-long-content")
                        .show();
                }

            }

            $block.css(css);

            function getDefaultPosition(area) {
                return {
                    left: Math.floor( (window_w - area.width)/2 ),
                    top: Math.floor( (window_h - area.height)/2 )
                };
            }
        };

        Dialog.prototype.close = function() {
            var that = this,
                result = null;

            if (that.is_visible) {
                if (that.onClose) {
                    result = that.onClose(that);
                }

                if (result !== false) {
                    if (that.animate) {
                        that.animateDialog(false).then( function() {
                            that.$wrapper.remove();
                        });
                    } else {
                        that.$wrapper.remove();
                    }

                    if (that.lock_body_scroll) {
                        that.$body.removeClass(locked_class);
                    }

                    that.is_removed = true;
                }
            }

            return result;
        };

        Dialog.prototype.resize = function() {
            var that = this;

            that.setPosition();

            if (that.onResize) {
                that.onResize(that.$wrapper, that);
            }
        };

        Dialog.prototype.hide = function() {
            var that = this;

            if (that.animate) {
                that.animateDialog(false).then( function() {
                    that.$wrapper.detach();
                });
            } else {
                that.$wrapper.detach();
            }

            that.is_visible = false;

            if (that.lock_body_scroll) {
                that.$body.removeClass(locked_class);
            }
        };

        Dialog.prototype.show = function() {
            var that = this,
                is_exist = $.contains(document, that.$wrapper[0]);

            if (!is_exist) {
                that.$body.append(that.$wrapper);
            }

            that.$wrapper.show();
            that.is_visible = true;

            // set position
            that.setPosition();

            // enable animation
            if (that.animate) {
                that.animateDialog(true);
            }

            if (that.lock_body_scroll) {
                that.$body.addClass(locked_class);
            }
        };

        /**
         * @param {Boolean} animate
         * */
        Dialog.prototype.animateDialog = function(animate) {
            var that = this,
                deferred = $.Deferred(),
                time = 200;

            var shifted_class = "is-shifted",
                animate_class = "is-animated";

            if (animate) {
                that.$wrapper.addClass(shifted_class);
                that.$wrapper[0].offsetHeight;
                that.$wrapper
                    .addClass(animate_class)
                    .removeClass(shifted_class);

                setTimeout( function() {
                    deferred.resolve();
                }, time);

            } else {
                that.$wrapper.addClass(shifted_class);
                setTimeout( function() {
                    deferred.resolve();
                    that.$wrapper.removeClass(animate_class);
                }, time);
            }

            return deferred.promise();
        };

        return Dialog;

    })($);

    $.waDialog = function(plugin_options) {
        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        var options = $.extend(true, {}, plugin_options),
            result = false;

        options["$wrapper"] = getWrapper(options);

        if (options["$wrapper"]) {
            result = new Dialog(options);
        }

        return result;
    };

    function getWrapper(options) {
        var result;

        if (options["html"]) {
            result = $(options["html"]);

        } else if (options["$wrapper"]) {
            result = options["$wrapper"];

        } else {
            result = generateDialog(options["header"], options["content"], options["footer"]);
        }

        return result;

        function generateDialog($header, $content, $footer) {
            var result = false;

            var $wrapper = $("<div />").addClass( class_names["wrapper"] ),
                $bg = $("<div />").addClass( class_names["background"] ),
                $body = $("<div />").addClass( class_names["body"] ),
                $header_w = ( $header ? $("<div />").addClass( class_names["header"] ).append($header) : false ),
                $content_w = ( $content ? $("<div />").addClass( class_names["content"] ).append($content) : false ),
                $footer_w = ( $footer ? $("<div />").addClass( class_names["footer"] ).append($footer) : false );

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

    function getSelector(name) {
        return (class_names[name] ? "." + class_names[name] : null);
    }

    function log(data) {
        if (console && console.log) {
            console.log(data);
        }
    }

})(jQuery);

/**
 * @description drawer component
 * @example /webasyst/ui/component/drawer/
 * */
( function($) {

    var locked_class = "is-locked";

    var Drawer = ( function($) {

        Drawer = function(options) {
            var that = this;

            that.$wrapper = options["$wrapper"];
            if (that.$wrapper && that.$wrapper.length) {
                // DOM
                that.$block = that.$wrapper.find(".drawer-body");
                that.$window = $(window);
                that.$body = $(document).find("body");

                // VARS
                that.esc = (typeof options["esc"] === "boolean" ? options["esc"] : true);
                that.lock_body_scroll = (typeof options["lock_body_scroll"] === "boolean" ? options["lock_body_scroll"] : true);
                that.options = (options["options"] || false);
                that.direction = getDirection(options["direction"]);
                that.animation_time = 333;
                that.hide_class = "is-hide";

                // DYNAMIC VARS
                that.is_visible = false;
                that.is_locked = false;

                // HELPERS
                that.onOpen = (typeof options["onOpen"] === "function" ? options["onOpen"] : null);
                that.onClose = (typeof options["onClose"] === "function" ? options["onClose"] : null);
                that.onBgClick = (typeof options["onBgClick"] === "function" ? options["onBgClick"] : null);

                // INIT
                that.init();
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

        Drawer.prototype.init = function() {
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

            that.$wrapper.on("drawer-close", function(event) {
                event.preventDefault();
                that.close();
            });

            $block.on("click", ".js-close-drawer", function(event) {
                event.preventDefault();
                that.close();
            });

            // Click on background, default nothing
            if (that.onBgClick) {
                that.$wrapper.on("click", ".drawer-background", function(event) {
                    that.onBgClick(event, that.$wrapper, that);
                });
            }

            if (that.esc) {
                $document.on("keyup", keyupWatcher);
                function keyupWatcher(event) {
                    var is_exist = $.contains(document, that.$wrapper[0]);
                    if (is_exist) {
                        var escape_code = 27;
                        if (event.keyCode === escape_code) {
                            that.close();
                        }
                    } else {
                        $document.off("keyup", keyupWatcher);
                    }
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
            if (that.onOpen) {
                that.onOpen(that.$wrapper, that);
            }
        };

        Drawer.prototype.close = function() {
            var that = this,
                result = null;

            if (that.is_visible) {
                if (that.onClose) {
                    result = that.onClose(that);
                }

                if (result !== false) {
                    if (!that.is_locked) {
                        that.is_locked = true;

                        that.$wrapper.addClass(that.hide_class);
                        setTimeout( function() {
                            that.$wrapper.remove();
                            that.is_locked = false;

                            if (that.lock_body_scroll) {
                                that.$body.removeClass(locked_class);
                            }
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

                    if (that.lock_body_scroll) {
                        that.$body.removeClass(locked_class);
                    }
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
                if (that.lock_body_scroll) {
                    that.$body.addClass(locked_class);
                }

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

/**
 * @description dropdown component
 * @example /webasyst/ui/component/dropdown/
 * */
( function($) { "use strict";

    var Dropdown = ( function($) {

        Dropdown = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$button = that.$wrapper.find("> .dropdown-toggle");
            that.$menu = that.$wrapper.find("> .dropdown-body");

            // VARS
            that.on = {
                change: (typeof options["change"] === "function" ? options["change"] : function() {}),
                ready: (typeof options["ready"] === "function" ? options["ready"] : function() {}),
                open: (typeof options["open"] === "function" ? options["open"] : function() {}),
                close: (typeof options["close"] === "function" ? options["close"] : function() {})
            };
            that.options = {
                hover: (typeof options["hover"] === "boolean" ? options["hover"] : true),
                hide: (typeof options["hide"] === "boolean" ? options["hide"] : true),
                items: (options["items"] ? options["items"] : null),
                active_class: (options["active_class"] ? options["active_class"] : "selected"),
                update_title: (typeof options["update_title"] === "boolean" ? options["update_title"] : true)
            };

            // DYNAMIC VARS
            that.is_opened = false;
            that.$before = null;
            that.$active = null;

            // INIT
            that.initClass();
        };

        Dropdown.prototype.initClass = function() {
            var that = this,
                $document = $(document),
                $body = $("body");

            if (that.options.hover) {
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

            if (that.options.items) {
                that.initChange(that.options.items);
            }

            $body.on("keyup", keyWatcher);
            function keyWatcher(event) {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    var is_escape = (event.keyCode === 27);
                    if (that.is_opened && is_escape) {
                        event.stopPropagation();
                        that.hide();
                    }
                } else {
                    $body.off("keyup", keyWatcher);
                }
            }

            $document.on("click", clickWatcher);
            function clickWatcher(event) {
                var wrapper = that.$wrapper[0],
                    is_exist = $.contains(document, wrapper);

                if (is_exist) {
                    var is_target = (event.target === wrapper || $.contains(wrapper, event.target));
                    if (that.is_opened && !is_target) {
                        that.hide();
                    }
                } else {
                    $document.off("click", clickWatcher);
                }
            }

            that.$wrapper.data("dropdown", that);
            that.on.ready(that);
        };

        Dropdown.prototype.toggleMenu = function(open) {
            var that = this,
                active_class = "is-opened";

            that.is_opened = open;

            if (open) {
                that.$wrapper.addClass(active_class);
                that.on.open(that);

            } else {
                that.$wrapper.removeClass(active_class);
                that.on.close(that);
            }
        };

        Dropdown.prototype.initChange = function(selector) {
            var that = this,
                active_class = that.options.active_class;

            that.$active = that.$menu.find(selector + "." + active_class);

            that.$wrapper.on("click", selector, onChange);

            function onChange(event) {
                event.preventDefault();

                var $target = $(this);

                if (that.$active.length) {
                    that.$before = that.$active.removeClass(active_class);
                }

                that.$active = $target.addClass(active_class);

                if (that.options.update_title) {
                    that.setTitle($target.html());
                }

                if (that.options.hide) {
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

        /**
         * @param {String} name
         * @param {String} value
         * @return {Boolean} result
         * */
        Dropdown.prototype.setValue = function(name, value) {
            var that = this,
                result = false;

            if (that.options.items) {
                that.$menu.find(that.options.items).each( function() {
                    var $target = $(this),
                        target_value = "" + $target.data(name);

                    if (target_value) {
                        if (target_value === value) {
                            $target.trigger("click");
                            result = true;
                            return false;
                        }
                    }
                });
            }

            return result;
        };

        return Dropdown;

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

/**
 * @description progressbar component
 * @example /webasyst/ui/component/progressbar/
 * */
( function($) { "use strict";

    var Progressbar = ( function($) {

        Progressbar = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$bar_wrapper = null;
            that.$bar = null;
            that.$text = null;

            // VARS
            that.type = (options["type"] || "line");
            that.percentage = (options["percentage"] || 0);
            that.color = (options["color"] || false);
            that.stroke_w = (options["stroke-width"] || 4.8);
            that.display_text = isDisplayText(options["display_text"]);
            that.text_inside = (typeof options["text-inside"] === "boolean" ? options["text-inside"] : false);

            // DYNAMIC VARS

            // INIT
            that.initClass();

            function isDisplayText(show) {
                var result = true;

                if (typeof show === "boolean") {
                    result = show;
                }

                return result;
            }
        };

        Progressbar.prototype.initClass = function() {
            var that = this;

            that.render();

            that.set();
        };

        Progressbar.prototype.render = function() {
            var that = this;

            if (that.type === "line") {
                that.$wrapper.html("");
                that.$bar_wrapper = $("<div class=\"progressbar-line-wrapper\" />");
                that.$bar_outer = $("<div class=\"progressbar-outer\" />");
                that.$bar_inner = $("<div class=\"progressbar-inner\" />");
                that.$text = $("<div class=\"progressbar-text\" />");

                if (that.color) {
                    that.$bar_inner.css("background-color", that.color);
                }

                that.$bar_wrapper.addClass( that.text_inside ? "text-inside" : "text-outside" );

                that.$bar_inner.appendTo(that.$bar_outer);
                that.$bar_wrapper.append(that.$bar_outer).prependTo(that.$wrapper);
                that.$text.appendTo( that.text_inside ? that.$bar_inner : that.$bar_wrapper );

            } else if (that.type === "circle") {

                that.$bar_wrapper = $("<div class=\"progressbar-circle-wrapper\" />");
                that.$svg = $(document.createElementNS("http://www.w3.org/2000/svg", "svg"));
                that.$bar_outer = $(document.createElementNS('http://www.w3.org/2000/svg',"circle"));
                that.$bar_inner = $(document.createElementNS('http://www.w3.org/2000/svg',"path"));
                that.$text = $("<div class=\"progressbar-text\" />");

                that.$svg.append(that.$bar_outer).append(that.$bar_inner);
                that.$bar_wrapper
                    .append(that.$svg)
                    .append(that.$text)
                    .prependTo(that.$wrapper);
            }

            if (!that.display_text) {
                that.$text.hide();
            }
        };

        Progressbar.prototype.set = function(options) {
            var that = this;

            options = (typeof options === "object" ? options : {});
            var percentage = (parseFloat(options.percentage) >= 0 ? options.percentage : that.percentage);

            if (percentage === 0 || percentage > 0 && percentage <= 100) {
                // all good. percentage is number
            } else if (percentage > 100) {
                percentage = 100;
            } else if (percentage < 0) {
                percentage = 0;
            } else {
                return false;
            }

            that.percentage = percentage;

            var text = percentage + "%";
            if (options.text) { text = options.text; }
            that.$text.html(text);

            if (that.type === "line") {
                that.$bar_inner.width(percentage + "%");

            } else if (that.type === "circle") {
                var svg_w = that.$svg.width(),
                    stroke_w = that.stroke_w,
                    radius = svg_w/2 - stroke_w;

                var start_deg = 90,
                    end_deg = 360;

                if (percentage < 100) {
                    end_deg = start_deg - (3.6 * percentage);
                } else {
                    start_deg = 0;
                }

                that.$bar_outer
                    .attr("r", radius)
                    .attr("stroke-width", stroke_w);

                that.$bar_inner
                    .attr("d", getPathD(0, 0, radius, start_deg, end_deg))
                    .attr("stroke-width", stroke_w);
            }
        };

        return Progressbar;

        function getPathD(x, y, r, startAngle, endAngle) {
            startAngle = degToRad(startAngle);
            endAngle = degToRad(endAngle);

            if (startAngle > endAngle) {
                var s = startAngle;
                startAngle = endAngle;
                endAngle = s;
            }
            if (endAngle - startAngle > Math.PI * 2) {
                endAngle = Math.PI * 1.99999;
            }

            var largeArc = endAngle - startAngle <= Math.PI ? 0 : 1;

            return [
                "M",
                x + Math.cos(startAngle) * r, y - Math.sin(startAngle) * r,
                // x, y,
                "L", x + Math.cos(startAngle) * r, y - Math.sin(startAngle) * r,
                "A", r, r, 0, largeArc, 0, x + Math.cos(endAngle) * r, y - Math.sin(endAngle) * r,
                "L",
                x + Math.cos(endAngle) * r, y - Math.sin(endAngle) * r
                // , x, y
            ].join(" ");

            function degToRad(deg) { return deg/180 * Math.PI; }
        }

    })($);

    var plugin_name = "progressbar";

    $.fn.waProgressbar = function(plugin_options) {
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

                    $wrapper.data(plugin_name, new Progressbar(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description slider component
 * @example /webasyst/ui/component/slider/
 * */
( function($) { "use strict";

    var Touch = ( function() {

        Touch = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // VARS
            that.on = getEvents(options["on"]);
            that.selector = options["selector"];

            that.touch_min_length = (options["touch_min_length"] || 5);
            that.swipe_min_length = (options["swipe_min_length"] || 60);
            that.swipe_time_limit = (options["swipe_time_limit"] || 300);

            // DYNAMIC VARS

            // INIT

            that.initClass();
        };

        Touch.prototype.initClass = function() {
            var that = this;

            var touch_min_length = that.touch_min_length,
                touch_is_vertical,
                finger_place_x_start,
                finger_place_y_start,
                finger_place_x_end,
                finger_place_y_end,
                touch_delta_x,
                touch_delta_y,
                time_start,
                time_end,
                element;

            var result = {
                target: null,
                start: {
                    top: null,
                    left: null
                },
                end: {
                    top: null,
                    left: null
                },
                delta: {
                    x: null,
                    y: null
                },
                orientation: {
                    vertical: null,
                    x: null,
                    y: null
                },
                vertical: null,
                time: null
            };

            element = that.$wrapper[0];
            element.addEventListener("touchstart", onTouchStart, { passive: false });

            function onTouchStart(event) {
                finger_place_x_start = event.touches[0].clientX;
                finger_place_y_start = event.touches[0].clientY;
                finger_place_x_end = null;
                finger_place_y_end = null;
                touch_delta_x = null;
                touch_delta_y = null;
                touch_is_vertical = null;
                time_start = getTime();
                time_end = null;

                var target = element;

                if (that.selector) {
                    var is_selection = false;

                    var $selector = that.$wrapper.find(that.selector);
                    $selector.each( function() {
                        var is_target = (this === event.target || $.contains(this, event.target) );
                        if (is_target) {
                            target = this;
                            is_selection = true;
                        }
                    });

                    if (!is_selection) {
                        return false;
                    }
                }

                result = {
                    target: target,
                    start: {
                        top: finger_place_y_start,
                        left: finger_place_x_start
                    },
                    end: {
                        top: null,
                        left: null
                    },
                    delta: {
                        x: null,
                        y: null
                    },
                    orientation: {
                        vertical: null,
                        x: null,
                        y: null
                    },
                    time: null
                };

                var callback = that.on["start"],
                    response = callback(event, result);

                if (response === false) { return false; }

                element.addEventListener("touchmove", onTouchMove, { passive: false });
                element.addEventListener("touchend", onTouchEnd, { passive: false });

                // console.log("start", result );
            }

            function onTouchMove(event) {
                time_end = getTime();
                finger_place_x_end = event.touches[0].clientX;
                finger_place_y_end = event.touches[0].clientY;
                touch_delta_x = finger_place_x_end - finger_place_x_start;
                touch_delta_y = finger_place_y_end - finger_place_y_start;

                if (Math.abs(touch_delta_x) > touch_min_length || Math.abs(touch_delta_y) > touch_min_length) {
                    var is_vertical = (Math.abs(touch_delta_y) > Math.abs(touch_delta_x));

                    if (touch_is_vertical === null) {
                        touch_is_vertical = is_vertical;
                    }

                    if (!touch_is_vertical) {
                        event.preventDefault();
                    }
                }

                result.end = {
                    top: finger_place_y_end,
                    left: finger_place_x_end
                };

                result.delta = {
                    x: touch_delta_x,
                    y: touch_delta_y
                };

                if ( Math.abs(touch_delta_x) > touch_min_length ) {
                    result.orientation.x = ( touch_delta_x < 0 ? "left" : "right" );
                }

                if ( Math.abs(touch_delta_y) > touch_min_length ) {
                    result.orientation.y = ( touch_delta_y < 0 ? "top" : "bottom" );
                }

                result.time = (time_end - time_start);

                if (touch_is_vertical !== null) {
                    result.vertical = touch_is_vertical;
                }

                that.on["move"](event, result);

                // console.log("move", result);
            }

            function onTouchEnd(event) {
                // отключаем обработчики
                element.removeEventListener("touchmove", onTouchMove);
                element.removeEventListener("touchend", onTouchEnd);

                if (result.time <= that.swipe_time_limit) {
                    if (!touch_is_vertical && (result.delta.x > that.swipe_min_length || result.delta.y > that.swipe_min_length)) {
                        var callback = (result.orientation.x === "left") ? that.on["swipe_left"] : that.on["swipe_right"];
                        callback(result);
                    }
                }

                that.on["end"](event, result);

                // console.log("end", result);
            }
        };

        return Touch;

        function getEvents(on) {
            var result = {
                start: function() {},
                move: function() {},
                end: function() {},
                swipe_left: function() {},
                swipe_right: function() {}
            };

            if (on) {
                if (typeof on["start"] === "function") {
                    result["start"] = on["start"];
                }
                if (typeof on["move"] === "function") {
                    result["move"] = on["move"];
                }
                if (typeof on["end"] === "function") {
                    result["end"] = on["end"];
                }
                if (typeof on["swipe_left"] === "function") {
                    result["swipe_left"] = on["swipe_left"];
                }
                if (typeof on["swipe_right"] === "function") {
                    result["swipe_right"] = on["swipe_right"];
                }
            }

            return result;
        }

        function getTime() {
            var date = new Date();
            return date.getTime();
        }

    })(jQuery);

    var RangeSlider = ( function($) {

        RangeSlider = function(options) {
            var that = this;

            // DOM
            that.$wrapper = renderWrapper(options["$wrapper"]);
            that.$bar_wrapper = that.$wrapper.find(".slider-bar-wrapper");
            that.$bar = that.$bar_wrapper.find(".slider-bar");
            that.$point_left = that.$bar.find(".slider-point.left");
            that.$point_right = that.$bar.find(".slider-point.right");

            // DOM Fields
            that.$input_min = (options["$input_min"] || false);
            that.$input_max = (options["$input_max"] || false);

            // VARS
            that.hide = getHideOptions(options["hide"]);
            that.limit_range = getRange(options["limit"]);
            that.values_range = getRange(getValues(that, options), that.limit_range);

            // DYNAMIC DOM
            that.$point_active = false;

            // DYNAMIC VARS
            that.left = 0;
            that.right = 100;
            that.range_left = false;
            that.range_width = false;
            that.indent = 0;

            // EVENT
            that.onChange = ( typeof options["change"] === "function" ? options["change"] : function () {});
            that.onMove = ( typeof options["move"] === "function" ? options["move"] : function () {});

            // INIT
            that.initClass();

            function getHideOptions(option) {
                var result = {
                    min: false,
                    max: false
                };

                if (option) {
                    if (option.min) {
                        result.min = !!option.min;
                    }
                    if (option.max) {
                        result.max = !!option.max;
                    }
                }

                return result;
            }

            function getValues(that, options) {
                var result = {
                    min: null,
                    max: null
                };

                if (that.$input_min.length) {
                    var min_value = parseFloat(that.$input_min.val());
                    if (min_value >= 0) {
                        result.min = min_value;
                    }
                }

                if (that.$input_max.length) {
                    var max_value = parseFloat(that.$input_max.val());
                    if (max_value >= 0) {
                        result.max = max_value;
                    }
                }

                if (options.values) {
                    if (options.values.min) {
                        var min = parseFloat(options.values.min);
                        if (min) {
                            result.min = min;
                        }
                    }
                    if (options.values.max) {
                        var max = parseFloat(options.values.max);
                        if (max) {
                            result.max = max;
                        }
                    }
                }

                return result;
            }
        };

        RangeSlider.prototype.initClass = function() {
            var that = this;

            var move_class = "is-move",
                $document = $(document);

            that.update(true);

            // EVENTS

            // MOUSE
            if (!that.hide.min) {
                that.$point_left.on("mousedown", onMouseStart);
            } else {
                that.$point_left.hide();
            }
            if (!that.hide.max) {
                that.$point_right.on("mousedown", onMouseStart);
            } else {
                that.$point_right.hide();
            }

            // TOUCH
            var touch = new Touch({
                $wrapper: that.$wrapper,
                selector: ".slider-point",
                on: {
                    start: function(event, data) {
                        var $point = $(data.target);
                        onStart($point);
                    },
                    move: function(event, data) {
                        onMove(data.end.left);

                    },
                    end: function(event, data) {
                        onEnd();
                    }
                }
            });

            // CHANGE
            if (that.$input_min.length) {
                that.$input_min.on("change", function(event) {
                    if (event.originalEvent) {
                        var $input = $(this),
                            val = parseFloat( $input.val() );

                        val = ( val >= that.limit_range[0] ? val : that.limit_range[0]);

                        if (val >= that.values_range[1]) {
                            val = that.values_range[1];
                        }

                        that.values_range[0] = val;

                        $input.val(val);

                        that.update();
                    }
                });
            }

            if (that.$input_max.length) {
                that.$input_max.on("change", function(event) {
                    if (event.originalEvent) {
                        var $input = $(this),
                            val = parseFloat( $input.val() );

                        val = ( val <= that.limit_range[1] ? val : that.limit_range[1]);

                        if (val <= that.values_range[0]) {
                            val = that.values_range[0];
                        }

                        that.values_range[1] = val;

                        $input.val(val);

                        that.update();
                    }
                });
            }

            // RESET
            that.$wrapper.closest("form").on("reset", function() {
                that.setOffset([0, 100], true);
            });

            //

            function onMouseStart() {
                onStart($(this));

                // Add sub events
                $document.on("mousemove", onMouseMove);
                $document.on("mouseup", onMouseUp);
            }

            function onMouseMove(event) {
                var left = (event.pageX || event.clientX);
                onMove(left);
            }

            function onMouseUp() {
                $document.off("mousemove", onMouseMove);
                $document.off("mouseup", onMouseUp);
                onEnd();
            }

            //

            function onStart($point) {
                reset();

                that.$point_active = $point;
                that.range_left = that.$bar_wrapper.offset().left;
                that.range_width = that.$bar_wrapper.outerWidth();
            }

            function onMove(left) {
                var $point = that.$point_active;
                if ($point) {
                    // Add move Class
                    if (!$point.hasClass(move_class)) {
                        $point.addClass(move_class);
                    }
                    // Do moving
                    onMovePrepare(left, $point);
                }

                that.onMove(that.values_range, that);
                that.$wrapper.trigger("move", [that.values_range, that]);

                function onMovePrepare(left, $point) {
                    var is_left = ($point[0] === that.$point_left[0]),
                        delta, percent, min, max;

                    if (!that.hide.min && !that.hide.max) {
                        that.indent = (16*100)/that.$bar_wrapper.width();
                    }

                    //
                    delta = left - that.range_left;
                    if (delta < 0) {
                        delta = 0;
                    } else if (delta > that.range_width) {
                        delta = that.range_width;
                    }
                    //
                    percent = (delta/that.range_width) * 100;

                    // Min Max
                    if (is_left) {
                        min = 0;
                        max = that.right - that.indent;
                    } else {
                        min = that.left + that.indent;
                        max = 100;
                    }

                    if (percent < min) {
                        percent = min;
                    } else if (percent > max) {
                        percent = max;
                    }

                    // Set Range
                    if (is_left) {
                        that.setOffset([percent, that.right], true);
                    } else {
                        that.setOffset([that.left, percent], true);
                    }
                }
            }

            function onEnd() {
                reset();

                that.onChange(that.values_range, that);
                that.$wrapper.trigger("slider_change", [that.values_range, that]);
            }

            function reset() {
                if (that.$point_active) {
                    that.$point_active.removeClass(move_class);
                    that.$point_active = false;
                }
            }
        };

        RangeSlider.prototype.update = function(change_input) {
            var that = this;

            var left_o = parseFloat(that.getOffset(that.values_range[0])),
                right_o = parseFloat(that.getOffset(that.values_range[1])),
                left, right, min, max;

            left = (left_o >= 0 ? left_o : 0);
            right = (right_o >= 0 ? right_o : 0);

            min = Math.min(left, right) * 100;
            max = Math.max(left, right) * 100;

            that.setOffset([min, max], change_input);
        };

        RangeSlider.prototype.setValues = function(values_array) {
            var that = this;

            if (Array.isArray(values_array) && values_array.length) {
                var values_range = {
                    min: values_array[0],
                    max: values_array[1]
                };
                that.values_range = getRange(values_range, that.limit_range);

                that.update(true);
            }
        };

        RangeSlider.prototype.setOffset = function(offset_array, change_input) {
            var that = this,
                left, right;

            var offset_left = parseFloat(offset_array[0]),
                offset_right = parseFloat(offset_array[1]);

            offset_left = (offset_left >= 0 && offset_left <= 100) ? offset_left : 0;
            offset_right = (offset_right >= 0 && offset_right <= 100) ? offset_right : 100;

            left = Math.min(offset_left, offset_right);
            right = Math.max(offset_left, offset_right);

            // Set data
            that.left = left;
            that.right = right;

            var delta_value = that.limit_range[1] - that.limit_range[0],
                min_val = that.limit_range[0] + that.left * ( delta_value / 100 ),
                max_val = that.limit_range[0] + that.right * ( delta_value / 100 );

            if (change_input) {
                if (that.$input_min.length) {
                    that.$input_min.val( parseInt(min_val * 10)/10 ).trigger("change");
                }

                if (that.$input_min.length) {
                    that.$input_max.val( parseInt(max_val * 10)/10 ).trigger("change");
                }
            }

            that.values_range = [min_val, max_val];

            // Bar
            render(left, right);

            function render(left, right) {
                var indent = that.indent;
                if (right - left < indent) {
                    if (right === 100 || ((right + indent) > 100) ) {
                        left = right - indent;
                    } else {
                        right = left + indent;
                    }
                }

                that.$bar.css({
                    width: (right - left) + "%",
                    left: left + "%"
                });
            }
        };

        RangeSlider.prototype.getValue = function(offset) {
            var that = this,
                result = null;

            offset = parseFloat(offset);

            if (offset >= 0 && offset <= 1) {
                var value_delta = that.limit_range[1] - that.limit_range[0];
                result = that.limit_range[0] + offset * value_delta;
            }

            return result;
        };

        RangeSlider.prototype.getOffset = function(value) {
            var that = this,
                result = null;

            value = parseFloat(value);

            if (value >= that.limit_range[0] && value <= that.limit_range[1]) {
                var value_delta = that.limit_range[1] - that.limit_range[0];
                result = (value - that.limit_range[0])/value_delta;
            }

            return result;
        };

        return RangeSlider;

        function renderWrapper($wrapper) {
            var template =
                '<div class="slider-bar-wrapper">' +
                '<span class="slider-bar">' +
                '<span class="slider-point left"></span>' +
                '<span class="slider-point right"></span>' +
                '</span>' +
                '</div>';

            $wrapper.prepend(template);

            return $wrapper;
        }

        function getRange(range, limit_range) {
            var result = [0,1];

            if (range) {
                if (typeof range["min"] === "number") {
                    result[0] = range["min"];
                }

                if (typeof range["max"] === "number") {
                    result[1] = range["max"];
                } else {
                    result[1] = (limit_range ? limit_range[1] : result[0] + 1);
                }

                if (limit_range) {
                    if (result[0] < limit_range[0]) {
                        result[0] = limit_range[0];
                    }
                    if (result[1] > limit_range[1]) {
                        result[1] = limit_range[1];
                    }
                }

                if (result[0] > result[1]) {
                    var max = result[0];
                    result[0] = result[1];
                    result[1] = max;
                }

            } else if (limit_range) {
                result = [limit_range[0], limit_range[1]];
            }

            return result;
        }

    })(jQuery);

    var plugin_name = "slider";

    $.fn.waSlider = function(plugin_options) {
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

                    $wrapper.data(plugin_name, new RangeSlider(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description toggle component
 * @example /webasyst/ui/component/toggle/
 * */
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
            that.use_animation = ( typeof options["use_animation"] === "boolean" ? options["use_animation"] : true);

            // DYNAMIC VARS
            that.$before = null;
            that.$active = that.$wrapper.find("> *." + that.active_class);

            // INIT
            that.init();
        };

        Toggle.prototype.init = function() {
            var that = this,
                active_class = that.active_class;

            that.$wrapper.on("click", "> *", onClick);

            that.$wrapper.trigger("ready", that);

            that.on.ready(that);

            if (that.use_animation) {
                that.initAnimation();
            }

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

        Toggle.prototype.initAnimation = function() {
            var that = this;

            var is_ready = false;

            var observer = new MutationObserver(refresh);
            observer.observe(that.$wrapper[0],{
                childList: true,
                subtree: true
            });

            that.$wrapper.addClass("animate");
            that.$wrapper.on("change", refresh);

            var $wrapper = $("<div class=\"animation-block\" />");

            if (that.$active.length) { refresh(); }

            function refresh() {
                var area = getArea(that.$active);

                $wrapper.css(area);

                if (!is_ready) {
                    $wrapper.prependTo(that.$wrapper);
                    is_ready = true;
                }
            }

            function getArea() {
                var offset = that.$active.offset(),
                    wrapper_offset = that.$wrapper.offset();

                return {
                    top: offset.top - wrapper_offset.top,
                    left: offset.left - wrapper_offset.left,
                    width: that.$active.width(),
                    height: that.$active.height()
                };
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

/**
 * @description switch component
 * @example /webasyst/ui/component/switch/
 * */
( function($) { "use strict";

    var Switch = ( function($) {

        Switch = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$toggle = $("<span class=\"switch-toggle\" />").appendTo(that.$wrapper);
            that.$field = that.$wrapper.find("input:checkbox:first");

            // VARS
            that.on = {
                ready: (typeof options["ready"] === "function" ? options["ready"] : function() {}),
                change: (typeof options["change"] === "function" ? options["change"] : function() {})
            };

            // DYNAMIC VARS
            that.is_active = (that.$field.length ? that.$field.is(":checked") : false);
            that.is_active = (typeof options["active"] === "boolean" ? options["active"] : that.is_active);

            that.is_disabled = (that.$field.length ? that.$field.is(":disabled") : false);
            that.is_disabled = (typeof options["disabled"] === "boolean" ? options["disabled"] : that.is_disabled);

            // INIT
            that.init();
        };

        Switch.prototype.init = function() {
            var that = this;

            that.set(that.is_active, false);
            that.disable(that.is_disabled);

            that.$wrapper.data("switch", that).trigger("ready", [that]);
            that.on.ready(that);

            that.$wrapper.on("click", function(event) {
                event.preventDefault();
                if (!that.is_disabled) {
                    that.set(!that.is_active);
                }
            });
        };

        /**
         * @param {Boolean} active
         * @param {Boolean?} trigger_change
         * */
        Switch.prototype.set = function(active, trigger_change) {
            var that = this,
                active_class = "is-active";

            trigger_change = (typeof trigger_change === "boolean" ? trigger_change : true);

            if (active) {
                that.$wrapper.addClass(active_class);
            } else {
                that.$wrapper.removeClass(active_class);
            }

            if (that.$field.length) {
                that.$field.attr("checked", active);
            }

            if (trigger_change) {
                if (that.$field.length) {
                    that.$field.trigger("change", [active, that]);
                } else {
                    that.$wrapper.trigger("change", [active, that]);
                }

                that.on.change(active, that);
            }

            that.is_active = active;

            return that.is_active;
        };

        /**
         * @param {Boolean} disable
         * */
        Switch.prototype.disable = function(disable) {
            var that = this,
                disabled_class = "is-disabled";

            if (disable) {
                that.$wrapper.addClass(disabled_class);
            } else {
                that.$wrapper.removeClass(disabled_class);
            }

            that.is_disabled = disable;
        };

        return Switch;

    })($);

    var plugin_name = "switch";

    $.fn.waSwitch = function(plugin_options) {
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

                    $wrapper.data(plugin_name, new Switch(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

    $.waSwitch = function(options) {
        return new Switch(options);
    }

})(jQuery);

/**
 * @description tooltip component
 * @example /webasyst/ui/component/wa-tooltip/
 * */
(function ($) {
    "use strict";

    var Tooltip = (function ($) {

        Tooltip = function (options) {
            let that = this;

            // DOM
            that.$wrapper = options["$wrapper"][0];

            // VARS
            delete options["$wrapper"];
            that.options = options;
            that.tooltip_class = that.options.class || that.$wrapper.getAttribute('data-wa-tooltip-class') || false;
            that.is_click = that.options.trigger === 'click' || that.$wrapper.getAttribute('data-wa-tooltip-trigger') === 'click' || false;
            that.icon = that.options.icon || that.$wrapper.getAttribute('data-wa-tooltip-icon') || false;
            that.template = that.options.template || that.$wrapper.getAttribute('data-wa-tooltip-template') || false

            //
            that.options.arrow = false;
            if (that.icon) {
                that.options.allowHTML = true;
            }

            // INIT
            if (window.Popper && window.tippy) {
                that.init()
            } else {
                // DYNAMIC LOAD SOURCE
                (async () => {
                    await import('/wa-content/js/tippy/popper.min.js').then((async () => {
                        await import('/wa-content/js/tippy/wa.tooltip.js').then(() => that.init())
                    }))
                })()
            }
        }

        Tooltip.prototype.init = function () {
            let that = this;

            that.options.onCreate = function (tooltip) {
                that.setIcon(tooltip);
                that.setClass(tooltip);
            }

            that.setContent();
            that.misc();

            const tooltip = tippy(that.$wrapper, that.options);

            /* remove tooltip without text*/
            if (!tooltip.popper.innerText) {
                tooltip.destroy()
            }
        };

        Tooltip.prototype.setContent = function () {
            let that = this;
            if (that.template) {
                that.options.content = function () {
                    const $template = document.querySelector(that.template);

                    if ($template) {
                        return $template.innerHTML;
                    }
                };

                that.options.allowHTML = true;
            }
        };

        Tooltip.prototype.misc = function () {
            let that = this;
            /* Set cursor pointer if event trigger is Click */
            if (that.is_click) {
                that.$wrapper.style.cursor = 'pointer'
            }
        };

        Tooltip.prototype.setClass = function (tooltip) {
            let that = this;
            let $tooltip_content = tooltip.popper.querySelector('.wa-tooltip-content')
            if (that.tooltip_class) {
                that.tooltip_class.split(' ').forEach((_class) => {
                    $tooltip_content.classList.add(_class);
                })
                /* Remove tooltips arrow because we`re don`t know correct color it */
                tooltip.setProps({ arrow: false });
            }
        };

        Tooltip.prototype.setIcon = function (tooltip) {
            let that = this;
            if (that.icon) {
                tooltip.popper
                    .querySelector('.wa-tooltip-content')
                    .insertAdjacentHTML('afterBegin',`<i class="${that.icon}"></i>`);

                tooltip.setProps({ allowHTML: true });
            }
        };

        return Tooltip;

    })($);

    const plugin_name = "tooltip";

    $.fn.waTooltip = function(plugin_options) {
        let return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                const $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    let options = $.extend(true, plugin_options, { $wrapper });

                    $wrapper.data(plugin_name, new Tooltip(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description upload component
 * @example /webasyst/ui/component/upload/
 * */
(function ($) {
    "use strict";

    var Upload = (function ($) {

        Upload = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"][0];
            that.$file_input = that.$wrapper.querySelector('[type="file"]')
            that.$upload_wrapper = that.$wrapper.querySelector('.upload')

            // VARS
            that.is_uploadbox = (typeof options["is_uploadbox"] === "boolean" ? options["is_uploadbox"] : false)
            that.show_file_name = (typeof options["show_file_name"] === "boolean" ? options["show_file_name"] : true)

            // INIT
            that.initClass();
        };

        Upload.prototype.initClass = function () {
            let that = this;

            if (that.is_uploadbox) {
                that.$wrapper.classList.add('box', 'uploadbox')
            }

            that.bindEvents();
        };

        Upload.prototype.bindEvents = function () {
            let that = this;

            if(that.is_uploadbox) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    that.$wrapper.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    that.$wrapper.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    that.$wrapper.addEventListener(eventName, unhighlight, false);
                });

                that.$wrapper.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    let dt = e.dataTransfer,
                        files = dt.files;
                    handleFiles(files);
                }

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                function highlight() {
                    that.$wrapper.classList.add('highlighted');
                }

                function unhighlight() {
                    that.$wrapper.classList.remove('highlighted');
                }
            }
            function handleFiles(files = []) {
                if (!files.length) {
                    files = this.files;
                }
                files = [...files];
                if (that.show_file_name) {
                    files.forEach(getName);
                }
            }

            function getName(file) {
                let filename = file.name,
                    $span = that.$upload_wrapper.querySelector('.filename');

                if (!$span) {
                    $span = document.createElement("span");
                }

                $span.classList.add('filename', 'hint');
                $span.textContent = filename;
                that.$upload_wrapper.appendChild($span);
            }

            that.$file_input.addEventListener('change', handleFiles, false);
        }

        return Upload;

    })($);

    var plugin_name = "upload";

    $.fn.waUpload = function(plugin_options) {
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

                    $wrapper.data(plugin_name, new Upload(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description tabs component
 * @example /webasyst/ui/component/tabs/
 * */
( function($) { "use strict";

    var Tabs = ( function($) {

        Tabs = function(options) {
            let that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.arrows_side = options.arrows_side;

            // VARS

            // DYNAMIC VARS

            // INIT
            if (that.$wrapper.hasClass('overflow-dropdown') && !that.$wrapper.hasClass('overflow-arrows')) {
                that.initDropdown();
            }

            if (that.$wrapper.hasClass('overflow-arrows') && ! that.$wrapper.hasClass('overflow-dropdown')) {
                that.initArrows();
            }
        };

        Tabs.prototype.initDropdown = function() {
            let that = this,
                $window = $(window)

            that.dropdownMenu()
            that.makeDropdown()

            $window.on('resize', function() {
                that.makeDropdown()
            });
        };

        Tabs.prototype.makeDropdown = function() {
            let that = this,
                wrapper_width = that.$wrapper.width(),
                dropdown_width = that.$wrapper.find("li.dropdown:visible").width() || 0,
                width_sum = 0;

            that.$wrapper.find('>li:not(li.dropdown)').each(function(){
                width_sum += $(this).outerWidth(true);
                if (width_sum + dropdown_width > wrapper_width) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            })

            let hidden_lists = that.$wrapper.find('>li:not(li.dropdown):not(:visible)')
            if (hidden_lists.length > 0) {
                $("li.dropdown").show();
            } else {
                $("li.dropdown").hide();
            }

            that.$wrapper.find('.menu').html(hidden_lists.clone().show());
        };

        Tabs.prototype.dropdownMenu = function() {
            let dropdown = `<li class="dropdown">
                            <a class="dropdown-toggle without-arrow" href="javascript:void(0);">
                                <i class="fas fa-chevron-down fa-3x"></i>
                            </a>
                            <div class="dropdown-body right">
                                <ul class="menu"></ul>
                            </div>
                        </li>`;
            this.$wrapper.append(dropdown)
            this.$wrapper.find('.dropdown').waDropdown();
        };

        Tabs.prototype.initArrows = function() {
            let that = this
            this.$tabs = this.$wrapper
            this.tabs_wrapper_custom_class = that.$tabs.data('wrapper-class') || ''
            this.$tabs.wrap(`<div class="tabs-wrapper arrows js-tabs-wrapper ${this.tabs_wrapper_custom_class}"></div>`)
            this.$tabs_wrapper = that.$tabs.parent();
            this.scrollbar_width = this.$tabs[0].offsetHeight - this.$tabs[0].clientHeight
            this.$tabs.css('margin-bottom', `-${this.scrollbar_width}px`)

            // VARS

            // DYNAMIC VARS

            this.is_changed = false
            this.left = 0

            if (that.arrows_side !== 'none') {

                const setVars = function() {

                    that.wrapper_width = that.$tabs.outerWidth(true)
                    that.tabs_width = that.$tabs[0].scrollWidth

                    that.showArrows();
                    if (that.$arrowsWrapper.is(':visible') && that.arrows_side === 'right') {
                        that.tabs_width += that.$arrowsWrapper.outerWidth(true);
                    }

                    that.watcher();
                }

                that.createArrows();


                function sleep(ms) {
                    return new Promise(
                        resolve => setTimeout(resolve, ms)
                    );
                }

                async function delayedSetVars() {
                    await sleep(500);
                    setVars()
                }

                delayedSetVars()
            }
        }

        Tabs.prototype.createArrows = function() {
            let that = this,
                arrows_class

            if (that.arrows_side === 'right') {
                let $html = `<ul class="tabs-arrows-wrapper js-arrows-wrapper inlinebox space-8" style="display: none">
                            <li class="left js-arrow-left" title="&larr;">
                                <i class="fas fa-angle-left fa-3x"></i>
                            </li>
                            <li class="right js-arrow-right" title="&rarr;">
                                <i class="fas fa-angle-right fa-3x"></i>
                            </li>
                        </ul>`;
                $($html).appendTo(this.$tabs_wrapper)
                arrows_class = '.js-arrows-wrapper'
                this.$arrowsWrapper = this.$tabs_wrapper.find(".js-arrows-wrapper")
            } else if (that.arrows_side === 'both') {
                let $html_left = `<ul class="tabs-arrows-wrapper js-arrows-both-wrapper inlinebox space-8 left" style="display: none">
                                    <li class="left js-arrow-left" title="&larr;">
                                        <i class="fas fa-angle-left fa-3x"></i>
                                    </li>
                                </ul>`,
                    $html_right = `<ul class="tabs-arrows-wrapper js-arrows-both-wrapper inlinebox space-8 right" style="display: none">
                                    <li class="right js-arrow-right" title="&rarr;">
                                        <i class="fas fa-angle-right fa-3x"></i>
                                    </li>
                                </ul>`;
                $($html_left).prependTo(this.$tabs_wrapper)
                $($html_right).appendTo(this.$tabs_wrapper)
                arrows_class = '.js-arrows-both-wrapper'
                this.$arrowsWrapper = this.$tabs_wrapper.find(".js-arrows-both-wrapper.right")
            }

            this.$arrowLeft = this.$tabs_wrapper.find(`${arrows_class} .js-arrow-left`)
            this.$arrowRight = this.$tabs_wrapper.find(`${arrows_class} .js-arrow-right`)

            this.$arrowLeft.on("click", function () {
                that.move(false);
            })

            this.$arrowRight.on("click", function () {
                that.move(true);
            })
        }

        Tabs.prototype.watcher = function() {
            let that = this,
                $window = $(window);

            $window.on("resize", function () {
                let is_exist = $.contains(document, that.$tabs[0]);

                if (is_exist) {
                    that.update()
                } else {
                    $window.off("resize", that.watcher);
                }
            });
        }

        Tabs.prototype.update = function() {
            let that = this
             that.wrapper_width = that.$tabs.outerWidth(true)

            if (this.is_changed) {
                this.is_changed = false;
                this.left = 0;

                this.render(this.left);
            }

            this.showArrows();

        }

        Tabs.prototype.showArrows = function() {
            let is_enabled = this.tabs_width > this.wrapper_width,
                active_class = "is-active";

            if (is_enabled) {
                let is_left_corner = !(this.left > 0),
                    is_right_corner = (this.left + this.wrapper_width >= this.tabs_width),
                    is_center = (this.left + this.wrapper_width < this.tabs_width);

                this.$arrowsWrapper.show()

                if (is_left_corner) {
                    this.$arrowLeft.removeClass(active_class).parent('.js-arrows-both-wrapper').hide();
                    this.$arrowRight.addClass(active_class);
                } else if (is_right_corner) {
                    this.$arrowLeft.addClass(active_class).parent('.js-arrows-both-wrapper').show();
                    this.$arrowRight.removeClass(active_class).parent('.js-arrows-both-wrapper').hide();
                } else if (is_center) {
                    this.$arrowLeft.addClass(active_class).parent('.js-arrows-both-wrapper').show();
                    this.$arrowRight.addClass(active_class);
                }
            } else {
                if (this.$arrowsWrapper) {
                   // this.$arrowsWrapper.hide()
                }
            }
        }

        Tabs.prototype.move = function(right) {
            let lift = this.wrapper_width / 3,
                new_left = 0;

            if (right) {
                new_left = this.left + lift;
                if (new_left > this.tabs_width - this.wrapper_width) {
                    new_left = this.tabs_width - this.wrapper_width;
                }
            } else {
                new_left = this.left - lift;
                if (new_left < 0) {
                    new_left = 0;
                }
            }

            this.left = new_left;
            this.is_changed = true;

            this.render(new_left);
            this.showArrows();
        }

        Tabs.prototype.render = function(left) {
            let left_string = `-${left}px`;
            this.$tabs.find('li').css('transform', `translate(${left_string},0)`);
        }

        return Tabs;

    })($);

    const plugin_name = "tabs";

    $.fn.waTabs = function(plugin_options) {
        let return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                let $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    let options = $.extend(true, plugin_options, {
                        $wrapper: $wrapper
                    });

                    $wrapper.data(plugin_name, new Tabs(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description sidebar component
 * @example /webasyst/ui/component/sidebar/
 * */
( function($) { "use strict";

    var Sidebar = ( function($) {

        Sidebar = function(options) {
            let that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$toggler = that.$wrapper.find('.sidebar-mobile-toggle');
            that.$sidebar_content = that.$toggler.nextAll();

            // VARS
            that.is_open = options.is_open || false;
            //that.direction = options.direction || 'down';

            // DYNAMIC VARS

            // INIT
            if (that.is_open) {
                that.$sidebar_content.show();
            }

            that.toggleClick();
            that.insideClick();
        };

        Sidebar.prototype.toggleClick = function() {
            let that = this;
            that.$toggler.on("click touchstart", function(event) {
                event.preventDefault();
                that.toggleAction();
            });
        };

        Sidebar.prototype.insideClick = function() {
            let that = this;
            that.$sidebar_content.each(function (i, el) {
                if(that.$toggler.is(':visible')) {
                    $(el).on('click', 'a', function (){
                        that.toggleAction();
                    });
                }
            })
        };

        Sidebar.prototype.toggleAction = function() {
            let that = this;
            that.$sidebar_content.each(function (i, el) {
                $(el).slideToggle(400, function () {
                    let self = $(this);
                    if(self.is(':hidden')) {
                        self.css('display', '');
                    }
                });
            })
        };

        return Sidebar;

    })($);

    const plugin_name = "sidebar";

    $.fn.waShowSidebar = function(plugin_options) {
        let return_instance = ( typeof plugin_options === "string" && plugin_options === plugin_name),
            $items = this,
            result = this;

        plugin_options = ( typeof plugin_options === "object" ? plugin_options : {});

        if (return_instance) { result = getInstance(); } else { init(); }

        return result;

        function init() {
            $items.each( function(index, item) {
                let $wrapper = $(item);

                if (!$wrapper.data(plugin_name)) {
                    let options = $.extend(true, plugin_options, {
                        $wrapper: $wrapper
                    });

                    $wrapper.data(plugin_name, new Sidebar(options));
                }
            });
        }

        function getInstance() {
            return $items.first().data(plugin_name);
        }
    };

})(jQuery);

/**
 * @description loading component
 * @example /webasyst/ui/component/loading/
 * */
( function($) {

    var ready_class = "is-ready",
        abort_class = "is-aborted",
        done_class = "is-done";

    var Loading = ( function($) {

        Loading = function(options) {
            var that = this;

            // DOM
            that.$wrapper = $("<div />", { class: "wa-loading", id: "wa-loading" });
            that.$bar = $("<div />", { class: "bar" }).appendTo(that.$wrapper);
            that.top = (options["top"] || "4rem");
            that.hide_time = 200;

            // VARS
            that.timeout = 0;
            that.is_rendered = false;

            // INIT
            that.init();
        };

        Loading.prototype.init = function() {
            var that = this;

            that.$wrapper.css("top", that.top);
        };

        /**
         * @param {Number?} percent
         * */
        Loading.prototype.show = function(percent) {
            var that = this,
                $body = $("body");

            clearTimeout(that.timeout);
            that.$bar.css("transition", "");

            percent = (typeof percent === "number" ? percent : 0);

            that.set(percent);

            that.$wrapper.removeClass([abort_class, done_class].join(" "));
            that.$wrapper.appendTo($body);
            that.$wrapper[0].offsetHeight;
            that.$wrapper.addClass(ready_class);

            that.is_rendered = true;
        };

        /**
         * @param {Number} percent
         * */
        Loading.prototype.set = function(percent) {
            var that = this;

            percent = (typeof percent === "number" ? percent : 0);

            if (percent >= 0 ) {
                percent = (percent > 100 ? 100 : percent);
                that.$bar.width(percent + "%");
            }
        };

        Loading.prototype.abort = function() {
            var that = this;

            if (!that.is_rendered) { return false; }

            that.$wrapper.addClass(abort_class);

            that.hide();
        };

        Loading.prototype.done = function() {
            var that = this;

            if (!that.is_rendered) { return false; }

            that.$wrapper.addClass(done_class);

            that.set(100);

            that.hide();
        };

        Loading.prototype.animate = function(time, percent, close) {
            var that = this;

            time = (typeof time === "number" ? time : 4000);
            percent = (typeof percent === "number" ? percent : 100);
            close = (typeof close === "boolean" ? close : true);

            that.show();

            var style = "width " + time + "ms ease-out";
            that.$bar.css("transition", style);

            that.set(percent);

            clearTimeout(that.timeout);
            that.timeout = setTimeout( function() {
                that.$bar.css("transition", "");
                if (close) { that.done(); }
            }, time);
        };

        Loading.prototype.hide = function() {
            var that = this,
                fade_time = ( that.hide_time < 200 ? 0 : 200);

            that.$wrapper.removeClass(ready_class);

            clearTimeout(that.timeout);
            that.timeout = setTimeout( function() {
                that.$wrapper
                    .detach()
                    .removeClass([abort_class, done_class].join(" "));

                that.set(0);

                that.is_rendered = false;
            }, fade_time);
        };

        return Loading;

    })($);

    $.waLoading = function(options) {
        options = ( typeof options === "object" ? options : {});
        return new Loading(options);
    };

})(jQuery);

/**
 * @description jQuery Ajax Setup
 * */
( function($) {

    if (!window.wa_skip_ajax_setup) {

        $.ajaxSetup({ cache: false });

        $(document).ajaxError(function(e, xhr, settings, exception) {
            // Ignore 502 error in background process
            if (xhr.status === 502 && exception === 'abort' || (settings.url && settings.url.indexOf('background_process') >= 0) || (settings.data && settings.data.indexOf('background_process') >= 0)) {
                console && console.log && console.log('Notice: XHR failed on load: '+ settings.url);
                return true;
            }
            // Generic error page
            else if (xhr.status !== 200 && xhr.responseText) {
                if (!$.wa.errorHandler || $.wa.errorHandler(xhr)) {
                    // if (xhr.responseText.indexOf('Exception') !== -1) {
                        $.wa.notice({
                            title: "AJAX Error",
                            text: "<div>" + xhr.responseText + "</div>",
                            button_name: "Close"
                        });
                        return true;
                    // }

                    document.open("text/html");
                    document.write(xhr.responseText); // !!! throws an "Access denied" exception in IE9
                    document.close();
                    $(window).one('hashchange', function() {
                        window.location.reload();
                    });
                }
            }
            // Session timeout, show login page
            else if (xhr.getResponseHeader('wa-session-expired')) {
                window.location.reload();
            }
            // Show an exception in development mode
            else if (typeof xhr.responseText !== 'undefined' && xhr.responseText.indexOf('Exception') !== -1) {
                $.wa.notice({
                    title: "AJAX Error",
                    text: "<div>" + xhr.responseText + "</div>",
                    button_name: "Close"
                });
            }
        });
    }

    if (!window.wa_skip_csrf_prefilter) {
        $.ajaxPrefilter(function (settings, originalSettings, xhr) {
            if (settings.crossDomain || (settings.type||'').toUpperCase() !== 'POST') {
                return;
            }

            var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
            if (!matches || !matches[1]) {
                return;
            }

            var csrf = decodeURIComponent(matches[1]);
            if (!settings.data && settings.data !== 0) settings.data = '';

            if (typeof(settings.data) === 'string') {
                if (settings.data.indexOf('_csrf=') === -1) {
                    settings.data += (settings.data.length > 0 ? '&' : '') + '_csrf=' + csrf;
                    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                }
            } else if (typeof(settings.data) === 'object') {
                if (window.FormData && settings.data instanceof window.FormData) {
                    if (typeof settings.data.set === "function") {
                        settings.data.set('_csrf', csrf);
                    } else {
                        settings.data.append('_csrf', csrf);
                    }
                } else {
                    settings.data['_csrf'] = csrf;
                }
            }
        });
    }

})(jQuery);

/**
 * @description Array with useful features ( $.wa )
 * */
( function($) {

    function sourceLoader(sources, async) {
        async = (typeof async === "boolean" ? async : true);

        var deferred = $.Deferred();

        loader(sources).then( function() {
            deferred.resolve();
        }, function(bad_sources) {
            if (console && console.error) {
                console.error("Error loading resource", bad_sources);
            }
            deferred.reject(bad_sources);
        });

        return deferred.promise();

        function loader(sources) {
            var deferred = $.Deferred(),
                counter = sources.length;

            var bad_sources = [];

            if (async) {
                $.each(sources, function(i, source) {
                    loadSource(source);
                });

            } else {
                runner();
                function runner(i) {
                    i = (typeof i === "number" ? i : 1);
                    loadSource(sources[i - 1]).always( function() {
                        if (i < sources.length) {
                            runner(i + 1);
                        }
                    });
                }
            }

            return deferred.promise();

            function loadSource(source) {
                var result;

                switch (source.type) {
                    case "css":
                        result = loadCSS(source).then(onLoad, onError);
                        break;

                    case "js":
                        result = loadJS(source).then(onLoad, onError);
                        break;

                    default:
                        var deferred = $.Deferred();
                        deferred.reject();
                        result = deferred.promise();
                        counter -= 1;
                        break;
                }

                return result;
            }

            function loadCSS(source) {
                var deferred = $.Deferred(),
                    promise = deferred.promise();

                var $link = $("#" + source.id);
                if ($link.length) {
                    promise = $link.data("promise");

                } else {
                    $link = $("<link />", {
                        id: source.id,
                        rel: "stylesheet"
                    }).appendTo("head")
                        .data("promise", promise);

                    $link
                        .on("load", function() {
                            deferred.resolve(source);
                        }).on("error", function() {
                        deferred.reject(source);
                    });

                    $link.attr("href", source.uri);
                }

                return promise;
            }

            function loadJS(source) {
                var deferred = $.Deferred(),
                    promise = deferred.promise();

                var $script = $("#" + source.id);
                if ($script.length) {
                    promise = $script.data("promise");

                } else {
                    var script = document.createElement("script");
                    document.getElementsByTagName("head")[0].appendChild(script);

                    $script = $(script)
                        .attr("id", source.id)
                        .data("promise", promise);

                    $script
                        .on("load", function() {
                            deferred.resolve(source);
                        }).on("error", function() {
                        deferred.reject(source);
                    });

                    $script.attr("src", source.uri);
                }

                return promise;
            }

            function onLoad(source) {
                counter -= 1;
                watcher();
            }

            function onError(source) {
                bad_sources.push(source);
                counter -= 1;
                watcher();
            }

            function watcher() {
                if (counter === 0) {
                    if (!bad_sources.length) {
                        deferred.resolve();
                    } else {
                        deferred.reject(bad_sources);
                    }
                }
            }
        }
    }

    var SizeWatcher = ( function($) {

        SizeWatcher = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.cases = options["cases"];

            // INIT
            that.init();
        };

        SizeWatcher.prototype.init = function() {
            var that = this;

            var $wrapper = that.$wrapper,
                $windows = $(window);

            var width_type = null;

            setWidthClass();

            if (typeof ResizeObserver === "function") {
                var resizeObserver = new ResizeObserver(onSizeChange);
                resizeObserver.observe($wrapper[0]);
                function onSizeChange(entries) {
                    var is_exist = $.contains(document, $wrapper[0]);
                    if (is_exist) {
                        var entry = entries[0].contentRect;
                        setWidthClass(entry.width);
                    } else {
                        resizeObserver.unobserve($wrapper[0]);
                    }
                }
            } else {
                $windows.on("resize refresh", resizeWatcher);
                function resizeWatcher() {
                    var is_exist = $.contains(document, $wrapper[0]);
                    if (is_exist) {
                        setWidthClass();
                    } else {
                        $windows.off("resize refresh", resizeWatcher);
                    }
                }
            }

            function setWidthClass(width) {
                width = (typeof width !== "undefined" ? width : $wrapper.outerWidth());

                $.each(that.cases, function(i, item) {
                    var is_enabled = false;

                    if (item.min === null || width >= item.min) {
                        if (item.max === null || width <= item.max) {
                            is_enabled = true;
                        }
                    }

                    if (is_enabled) {
                        $wrapper.addClass(item.class_name);
                    } else {
                        $wrapper.removeClass(item.class_name);
                    }
                });
            }
        };

        return SizeWatcher;

    })($);

    $.wa = $.extend($.wa || {}, {
        title: {
            pattern: "%s",
            set: function(title_string) {
                if (title_string) {
                    var state = history.state;
                    if (state) {
                        state.title = title_string;
                    }
                    document.title = $.wa.title.pattern.replace("%s", title_string);
                }
            }
        },

        /**
         * @description Localization repository function
         * */
        translate: function(word, translation) {
            var result = word;
            var sources = $.wa.translate.sources;
            if (!sources) {
                $.wa.translate.sources = {};
                sources = $.wa.translate.sources;
            }

            if (translation) {
                sources[word] = translation;
                result = true;

            } else {

                if (typeof word === "object") {
                    $.each(word, function(_word, _translation) {
                        if (_translation) {
                            $.wa.translate(_word, _translation);
                        }
                    });
                }

                if (sources[word]) {
                    result = sources[word];
                }
            }

            return result;
        },

        confirm: function(options) {
            var deferred = $.Deferred();

            var header = ( options.title ? '<h2>' + options.title + '</h2>' : null );
            var text = ( options.text ? options.text : "Confirm text is required" );

            var success_button = "<button class=\"js-success-action button blue\">" + ( options.success_button_name ? options.success_button_name : $.wa.translate("Ok") ) + "</button>",
                cancel_button = "<button class=\"js-dialog-close button gray\">" + ( options.cancel_button_name ? options.cancel_button_name : $.wa.translate("Cancel") ) + "</button>";

            var footer = success_button + cancel_button;

            var is_success = false;

            $.waDialog({
                header: header,
                content: text,
                footer: footer,
                onOpen: function($wrapper, dialog) {
                    $wrapper.on("click", ".js-success-action", function(event) {
                        event.preventDefault();
                        is_success = true;
                        dialog.close();
                    });
                },
                onClose: function() {
                    if (is_success) {
                        if (typeof options.onSuccess === "function") {
                            options.onSuccess();
                        }
                        deferred.resolve();
                    } else {
                        if (typeof options.onCancel === "function") {
                            options.onCancel();
                        }
                        deferred.reject();
                    }
                }
            });

            return deferred.promise();
        },

        notice: function(options) {
            var deferred = $.Deferred();

            var header = ( options.title ? '<h2>' + options.title + '</h2>' : null );
            var text = ( options.text ? options.text : "Notice text is required" );
            var footer = "<button class=\"js-dialog-close button gray\">" + (options.button_name ? options.button_name : $.wa.translate("Done")) + "</button>";

            $.waDialog({
                header: header,
                content: text,
                footer: footer,
                onClose: function () {
                    if (typeof options.onClose === "function") {
                        options.onClose();
                    }
                    deferred.resolve();
                }
            });

            return deferred.promise();
        },

        escape: function(string) {
            return $("<div />").text(string).html();
        },

        sizeWatcher: function(options) {
            return new SizeWatcher(options);
        },

        /**
         * @param {Array} options
         * @param {Boolean?} async
         * @return {Promise}
         * @description loader for css/js sources
         *
         * options = [          // array
         *   {                  // source item
         *     id: source_id,   // needed to prevent reloading source, set as <script/link id="source_id">
         *     type: "css/js",  // type of source
         *     uri: ""          // source path for load
         *   },
         *   ...
         * ]
         * */
        loadSources: function(options, async) {
            return sourceLoader(options, async);
        },

        /**
         * Automatically set server-side timezone if "Auto" timezone setting
         * is saved in user profile.
         */
        determineTimezone: function(wa_url, callback) {
            var done = false;

            $.each(document.cookie.split(/;\s*/g), function(i, pair) {
                pair = pair.split('=', 2);
                if (pair[0] === 'tz') {
                    done = true;
                    if (callback) { callback(pair[1]); }
                    return false;
                }
            });

            if (done) { return; }

            $.wa.loadSources([{
                id: "wa-timezone-js",
                type: "js",
                uri: wa_url + "wa-content/js/jstz/jstz.min.js"
            }]).then(setTimezone);

            function setTimezone() {
                var timezone = window.jstz.determine().name();

                // Session cookie timezone
                document.cookie = "tz="+jstz.determine().name();

                // Expires in two weeks
                var expire = new Date();
                expire.setTime(expire.getTime() + 14*24*60*60*1000); // two weeks
                document.cookie = "oldtz="+timezone+"; expires="+expire.toUTCString();

                if (callback) { callback(timezone); }
            }
        }
    });


    document.addEventListener('DOMContentLoaded', function() {
        /* hide/show scrollbar */
        $('.sidebar, .sidebar-body, .content').on('hover', function () {
            let $element = $(this),
                element_class = 'hide-scrollbar';

            if($element.hasClass(element_class)){
                $element.removeClass(element_class)
            }else{
                $element.addClass(element_class)
            }
        })
    });
})(jQuery);

