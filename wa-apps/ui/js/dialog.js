( function($) {

    var Dialog = ( function($) {

        Dialog = function(options) {
            var that = this;

            that.$wrapper = options["$wrapper"];
            if (that.$wrapper && that.$wrapper.length) {
                // DOM
                that.$block = that.$wrapper.find(".dialog-body");
                that.$body = $(window.top.document).find("body");
                that.$window = $(window.top);

                // VARS
                that.position = (options["position"] || false);
                that.userPosition = (options["setPosition"] || false);
                that.options = (options["options"] || false);

                // DYNAMIC VARS
                that.is_visible = false;

                // HELPERS
                that.onBgClick = (options["onBgClick"] || false);
                that.onOpen = (options["onOpen"] || function() {});
                that.onClose = (options["onClose"] || function() {});
                that.onResize = (options["onResize"] || false);

                // INIT
                that.initClass();
            } else {
                log("Error: bad data for dialog");
            }
        };

        Dialog.prototype.initClass = function() {
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
                $document = $(document),
                $block = (that.$block) ? that.$block : that.$wrapper;

            that.$wrapper.on("close", close);

            // Click on background, default nothing
            that.$wrapper.on("click", ".dialog-background", function(event) {
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

            $block.on("click", ".js-close-dialog", close);

            $(window).on("resize", onResize);

            // refresh dialog position
            $document.on("resizeDialog", resizeDialog);

            //

            function close() {
                var result = that.close();
                if (result === true) {
                    $document.off("click", close);
                    $document.off("wa_before_load", close);
                }
            }

            function onResize() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.resize();
                } else {
                    $(window).off("resize", onResize);
                }
            }

            function resizeDialog() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.resize();
                } else {
                    $(document).off("resizeDialog", resizeDialog);
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

            //
            that.setPosition();
            //
            that.onOpen(that.$wrapper, that);
        };

        Dialog.prototype.setPosition = function() {
            var that = this,
                $window = that.$window,
                window_w = $window.width(),
                window_h = $window.height(),
                $block = (that.$block) ? that.$block : that.$wrapper,
                wrapper_w = $block.outerWidth(),
                wrapper_h = $block.outerHeight(),
                pad = 20,
                css;

            if (that.position) {
                css = that.position;

            } else {
                var getPosition = (that.userPosition) ? that.userPosition : getDefaultPosition;
                css = getPosition({
                    width: wrapper_w,
                    height: wrapper_h
                });
            }

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

                var $content = $block.find(".dialog-content");

                $content.hide();

                var block_h = $block.outerHeight(),
                    content_h = window_h - block_h - pad * 2;

                $content
                    .height(content_h)
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
                }
            }

            return result;
        };

        Dialog.prototype.resize = function() {
            var that = this,
                animate_class = "is-animated",
                do_animate = true;

            if (do_animate) {
                that.$block.addClass(animate_class);
            }

            that.setPosition();

            if (that.onResize) {
                that.onResize(that.$wrapper, that);
            }
        };

        Dialog.prototype.hide = function() {
            var that = this;

            $("<div />").append(that.$wrapper.hide());
            that.is_visible = false;
        };

        Dialog.prototype.show = function() {
            var that = this,
                is_exist = $.contains(document, that.$wrapper[0]);

            if (!is_exist) {
                that.$body.append(that.$wrapper);
            }
            that.$wrapper.show();
            that.is_visible = true;
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

            var wrapper_class = "dialog",
                bg_class = "dialog-background",
                block_class = "dialog-body",
                header_class = "dialog-header",
                content_class = "dialog-content",
                footer_class = "dialog-footer";

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
