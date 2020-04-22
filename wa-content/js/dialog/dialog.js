( function($) {

    var class_names = {
        "wrapper": "wa-dialog",
            "background": "wa-dialog-background",
            "body": "wa-dialog-body",
                "header": "wa-dialog-header",
                "content": "wa-dialog-content",
                "footer": "wa-dialog-footer"
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
                that.animate = (typeof options["animate"] === "boolean" ? options["animate"] : true);
                that.esc = (typeof options["esc"] === "boolean" ? options["esc"] : true);

                // VARS
                that.userPosition = (options["setPosition"] || false);
                that.lock_body_scroll = (typeof options["lock_body_scroll"] === "boolean" ? options["lock_body_scroll"] : true);
                that.options = (options["options"] || false);

                // DYNAMIC VARS
                that.is_visible = false;
                that.is_removed = false;

                // HELPERS
                that.onBgClick = (options["onBgClick"] || false);
                that.onOpen = (options["onOpen"] || function() {});
                that.onClose = (options["onClose"] || function() {});
                that.onResize = (options["onResize"] || false);

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

            $block.on("click", ".js-close-dialog", function(event) {
                event.preventDefault();
                that.close();
            });

            that.$wrapper.on("close", function() {
                that.close();
            });

            // Click on background, default nothing
            that.$wrapper.on("click", getSelector("background"), function(event) {
                if (typeof that.onBgClick === "function") {
                    that.onBgClick(event);
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

            // set position
            that.setPosition();

            if (that.lock_body_scroll) {
                that.$body.addClass(locked_class);
            }

            // trigger event on open
            that.onOpen(that.$wrapper, that);

            // enable animation
            if (that.animate) {
                var animate_class = "is-animated";

                setTimeout( function() {
                    that.$block.addClass(animate_class);
                }, 4);
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

            var getPosition = (that.userPosition) ? that.userPosition : getDefaultPosition;
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

                $content
                    .css("height", content_h + "px")
                    .addClass("is-long-content")
                    .show();

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
                //
                result = that.onClose(that);
                //
                if (result !== false) {
                    that.$wrapper.remove();

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

            $("<div />").append(that.$wrapper.hide());
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

            if (that.lock_body_scroll) {
                that.$body.addClass(locked_class);
            }
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
            if (options.debug_output && (!result.$block || !result.$block.length)) {
                // In case html does not have required structure, show content - useful for debugging
                options.content = options.html;
                options.html = null;
                options["$wrapper"] = getWrapper(options);
                result = new Dialog(options);
            }
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
