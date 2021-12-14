var WASettingsDialog = ( function($) {

    WASettingsDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = $(options["html"]);
        that.$block = that.$wrapper.find(".s-dialog-block");

        // VARS
        that.position = ( options["position"] || false );
        that.userPosition = ( options["setPosition"] || false );
        that.options = ( options["options"] || false );
        that.remain_after_load = ( options["remain_after_load"] || false );
        that.$body = $(window.top.document).find("body");
        that.$window = $(window.top);

        // DYNAMIC VARS
        that.is_closed = false;
        that.is_visible = true;

        // HELPERS
        that.onBgClick = ( options["onBgClick"] || false );
        that.onOpen = ( options["onOpen"] || function() {} );
        that.onClose = ( options["onClose"] || function() {} );
        that.onConfirm = ( options["onConfirm"] || function() {} );
        that.onCancel = ( options["onCancel"] || function() {} );
        that.onResize = ( options["onResize"] || false );

        // INIT
        that.initClass();
    };

    WASettingsDialog.prototype.initClass = function() {
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

    WASettingsDialog.prototype.bindEvents = function() {
        var that = this,
            $document = $(document),
            $block = (that.$block) ? that.$block : that.$wrapper;

        that.$wrapper.on("close", close);

        if (!that.remain_after_load) {
            $document.on("wa_before_load", close);
        }

        // Click on background, default nothing
        that.$wrapper.on("click", ".s-dialog-background", function(event) {
            if (!that.onBgClick) {
                event.stopPropagation();
            } else {
                that.onBgClick(event);
            }
        });

        $document.on("keyup", function(event) {
            var escape_code = 27;
            if (event.keyCode === escape_code) {
                if (that.is_visible) {
                    that.close();
                }
            }
        });

        // for confirm, cancel event
        $block.on("click", ".js-cancel-dialog", cancel);

        // for confirm, confirm event
        $block.on("click", ".js-confirm-dialog", function(event) {
            event.stopPropagation();
            if (typeof that.onConfirm == "function") {
                var confirm_result = that.onConfirm(that);
                if (confirm_result !== false) {
                    that.close();
                }
            } else {
                log("Error: Confirm is not a function");
            }
        });

        $block.on("click", ".js-close-dialog", close);

        $(window).on("resize", onResize);

        // refresh dialog position
        $document.on("resizeDialog", resizeDialog);

        //

        function cancel() {
            if (typeof that.onCancel == "function") {
                that.onCancel(that);
            } else {
                log("Error: Cancel is not a function");
            }
        }

        function close() {
            if (!that.is_closed) {
                that.close();
            }
            $document.off("click", close);
            $document.off("wa_before_load", close);
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

    WASettingsDialog.prototype.render = function() {
        var that = this;

        try {
            that.$body.append(that.$wrapper);
        } catch(e) {
            log("Error: " + e.message);
        }

        //
        that.setPosition();
        //
        that.onOpen(that.$wrapper, that);
    };

    WASettingsDialog.prototype.setPosition = function() {
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

            var $content = $block.find(".s-dialog-content");

            $content.hide();

            var block_h = $block.outerHeight(),
                content_h = window_h - block_h - pad * 2;

            $content
                .height(content_h)
                .addClass("is-long-content")
                .show();

        }

        $block.css(css);

        function getDefaultPosition( area ) {
            // var scrollTop = $(window).scrollTop();

            return {
                left: parseInt( (window_w - area.width)/2 ),
                top: parseInt( (window_h - area.height)/2 ) // + scrollTop
            };
        }
    };

    WASettingsDialog.prototype.close = function() {
        var that = this;
        //
        that.is_closed = true;
        //
        that.onClose(that.$wrapper, that);
        //
        that.$wrapper.remove();
    };

    WASettingsDialog.prototype.resize = function() {
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

    WASettingsDialog.prototype.hide = function() {
        var that = this;

        that.$wrapper.hide();
        that.is_visible = false;
    };

    WASettingsDialog.prototype.show = function() {
        var that = this;

        that.$wrapper.show();
        that.is_visible = true;
    };

    return WASettingsDialog;

    function log(data) {
        if (console && console.log) {
            console.log(data);
        }
    }

})(jQuery);
