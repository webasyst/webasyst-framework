( function($) {

    var Sidebar = ( function($) {

        Sidebar = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // CONST

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        Sidebar.prototype.init = function() {
            var that = this;

            that.initToggles();

            that.initStickyLinks();
        };

        /**
         * @description Intermediate sidebar fixation option
         * */
        Sidebar.prototype.initFixer = function() {
            var that = this;

            var StickyBlock = ( function($) {

                StickyBlock = function(options) {
                    var that = this;

                    // DOM
                    that.$window = $(window);
                    that.$wrapper = options["$wrapper"];
                    that.$outer_wrapper = options["$outer_wrapper"];

                    // CONST
                    that.debug = options["debug"];
                    that.indent = { top: 0 };
                    that.scroll_class = "with-custom-scroll";

                    // VARS
                    that.$clone = null;
                    that.offset = that.getOffset();
                    that.type = null;

                    // INIT
                    that.init();
                };

                StickyBlock.prototype.init = function() {
                    var that = this;

                    that.$clone = renderClone(that.$wrapper);
                    function renderClone($wrapper) {
                        var $clone = $("<div />");
                        $wrapper.before($clone);
                        return $clone.hide();
                    }

                    // INIT

                    that.onScroll();

                    // EVENTS

                    var timeout = 0;
                    that.$window.on("resize", function() {
                        clearTimeout(timeout);
                        timeout = setTimeout( function() {
                            that.reset();
                            that.onScroll();
                        }, 50);
                    });

                    $(document).on("refresh", refreshWatcher);
                    function refreshWatcher() {
                        var is_exist = $.contains(document, that.$wrapper[0]);
                        if (is_exist) {
                            that.reset();
                            that.onScroll();
                        } else {
                            $(document).off("refresh", refreshWatcher);
                        }
                    }

                    that.$window.on("scroll", watcher);
                    function watcher() {
                        var is_exist = $.contains(that.$window[0].document, that.$wrapper[0]);
                        if (is_exist) {
                            that.onScroll();
                        } else {
                            that.$window.off("scroll", watcher);
                        }
                    }

                    var move_is_locked = false;
                    that.$wrapper.on("mousewheel", function(event) {
                        var direction = null,
                            is_ie = false;

                        if (event.originalEvent.deltaY) {
                            direction = event.originalEvent.deltaY > 0 ? 1 : -1;
                        } else if (event.originalEvent.wheelDelta) {
                            direction = event.originalEvent.wheelDelta > 0 ? -1 : 1;
                            is_ie = true;
                        }

                        if (direction) {
                            var step = 100 * direction;

                            if (!move_is_locked) {
                                move_is_locked = true;

                                onMove(step, function() {
                                    event.preventDefault();
                                });

                                if (is_ie) {
                                    setTimeout( function() {
                                        move_is_locked = false;
                                    }, 200);
                                } else {
                                    move_is_locked = false;
                                }
                            } else {
                                event.preventDefault();
                            }
                        }
                    });

                    // MouseWheel for FF
                    that.$wrapper[0].addEventListener("DOMMouseScroll", function(event) {
                        var step = 100 * (event.detail > 0 ? 1 : -1);
                        onMove(step, function() {
                            event.preventDefault();
                        });
                    }, false);

                    // Touch

                    var is_touch_enabled = false,
                        touch_start_y = null;

                    that.$wrapper[0].addEventListener("touchstart", function(event) {
                        is_touch_enabled = true;
                        touch_start_y = event.touches[0].clientY;
                    }, false);

                    that.$wrapper[0].addEventListener("touchmove", function(event) {
                        if (is_touch_enabled && touch_start_y) {
                            var touch_move_y = event.touches[0].clientY,
                                delta = touch_start_y - touch_move_y,
                                speed = 5;

                            onMove(delta * speed, function() {
                                event.preventDefault();
                            });

                            touch_start_y = touch_move_y;
                        }
                    }, false);

                    that.$wrapper[0].addEventListener("touchend", function(event) {
                        is_touch_enabled = false;
                        touch_start_y = null;
                    }, false);

                    function onMove(step, stopEvent) {
                        if (that.$wrapper.hasClass(that.scroll_class)) {
                            stopEvent();
                            scrollContent(step);
                        }
                    }

                    function scrollContent(step) {
                        var scroll_height = that.$wrapper.outerHeight(),
                            scroll_top = that.$wrapper.data("scroll_top"),
                            block_h = that.$window.height(),
                            lift = 0;

                        scroll_top = ( parseInt(scroll_top) >= 0 ? parseInt(scroll_top) : 0 );

                        if (step > 0) {
                            lift = (scroll_top + step <= scroll_height - block_h ? scroll_top + step : scroll_height - block_h);
                        } else {
                            lift = (scroll_top - Math.abs(step) >= 0 ? scroll_top - Math.abs(step) : 0);
                        }

                        var style = "translate(0, " + lift * -1 + "px)";

                        that.$wrapper.css({
                            "-webkit-transform": style,
                            "-moz-transform": style,
                            "-o-transform": style,
                            "-ms-transform": style,
                            "transform": style
                        });

                        that.$wrapper.data("scroll_top", lift);
                    }
                };

                StickyBlock.prototype.onScroll = function() {
                    var that = this;

                    var scroll_top = that.$window.scrollTop();

                    // DOM
                    var $window = that.$window;

                    // SIZE
                    var display_w = $window.width(),
                        display_h = $window.height(),
                        offset = that.offset,
                        outer_wrapper_h = that.$outer_wrapper.outerHeight();

                    //
                    var disable_scroll = (outer_wrapper_h <= that.offset.height);

                    if (disable_scroll) {
                        that.fixTo("default");

                        // если сколлтоп меньше чем начало блока
                    } else if (scroll_top <= offset.top - that.indent.top) {
                        // ничего не делаем
                        that.fixTo("default");

                        // доскролили до начала блока
                    } else {
                        // блок с фиксированием вмещается в пространство контейнера
                        if (scroll_top + offset.height + that.indent.top < offset.top + outer_wrapper_h) {
                            that.fixTo("fix_top");

                            // Высота сайдбара больше экрана
                            if (offset.height > display_h) {
                                that.$wrapper.addClass(that.scroll_class);
                            }

                        } else {
                            that.fixTo("indent_top", {
                                top: outer_wrapper_h - offset.height
                            });
                        }
                    }

                };

                /**
                 * @param {String} type
                 * @param {Object?} options
                 * */
                StickyBlock.prototype.fixTo = function(type, options) {
                    var that = this;

                    type = (type ? type : null);

                    var indent_top_class = "is-fixed-top",
                        fix_top_class = "is-indent-top";

                    that.$wrapper
                        .removeClass(that.scroll_class)
                        .removeClass(indent_top_class)
                        .removeClass(fix_top_class);

                    switch (type) {
                        case "fix_top":
                            if (that.type !== type) {
                                fixTop();
                                that.type = type;
                                that.log(type);
                            }

                            break;
                        case "indent_top":
                            if (that.type !== type) {
                                indentTop(options);
                                that.type = type;
                                that.log(type);
                            }
                            break;
                        default:
                            if (that.type !== type) {
                                reset();
                                that.type = type;
                                that.log(type);
                            }
                            break;
                    }

                    function reset() {
                        that.$wrapper.removeAttr("style");
                        that.$clone.hide().height(0);

                        that.$wrapper[0].scrollTop = 0;
                    }

                    function fixTop() {
                        that.$wrapper.removeAttr("style");

                        that.$wrapper
                            .css({
                                position: "fixed",
                                top: that.indent.top,
                                left: that.offset.left,
                                width: that.offset.width
                            });

                        that.$clone.css({
                            height: that.$wrapper.outerHeight()
                        }).show();
                    }

                    function indentTop(options) {
                        that.$wrapper.removeAttr("style");

                        that.$wrapper
                            .css({
                                position: "absolute",
                                top: options.top,
                                left: 0,
                                width: that.offset.width
                            });

                        that.$clone.css({
                            height: that.$wrapper.outerHeight()
                        }).show();

                        that.$wrapper[0].scrollTop = 0;
                    }
                };

                StickyBlock.prototype.reset = function() {
                    var that = this;

                    that.fixTo("default");

                    that.offset = that.getOffset();
                };

                StickyBlock.prototype.getOffset = function() {
                    var that = this;

                    var offset = that.$wrapper.offset();

                    return {
                        top: offset.top,
                        left: offset.left,
                        width: that.$wrapper.outerWidth(),
                        height: that.$wrapper.outerHeight()
                    };
                };

                /**
                 * @param {String} string
                 * */
                StickyBlock.prototype.log = function(string) {
                    var that = this;

                    if (that.debug) {
                        console.log(string);
                    }
                };

                return StickyBlock;

            })(jQuery);

            new StickyBlock({
                $wrapper: that.$wrapper,
                $outer_wrapper: that.$wrapper.closest(".i-app-wrapper"),
                debug: false
            });
        };

        Sidebar.prototype.initStickyLinks = function() {
            var that = this;

            var StickyBlock = ( function($) {

                StickyBlock = function(options) {
                    var that = this;

                    // DOM
                    that.$window = $(window);
                    that.$wrapper = options["$wrapper"];

                    // CONST
                    that.fixed_class = "is-bottom-fixed";

                    // VARS
                    that.$clone = null;
                    that.offset = that.getOffset();

                    // INIT
                    that.init();
                };

                StickyBlock.prototype.init = function() {
                    var that = this;

                    that.$clone = renderClone(that.$wrapper);
                    function renderClone($wrapper) {
                        var $clone = $("<div />");
                        $wrapper.before($clone);
                        return $clone.hide();
                    }

                    // INIT

                    that.render();

                    // EVENTS

                    var timeout = 0;
                    that.$window.on("resize", function() {
                        clearTimeout(timeout);
                        timeout = setTimeout( function() {
                            that.reset();
                            that.render();
                        }, 50);
                    });

                    $(document).on("refresh", refreshWatcher);
                    function refreshWatcher() {
                        var is_exist = $.contains(document, that.$wrapper[0]);
                        if (is_exist) {
                            that.reset();
                            that.render();
                        } else {
                            $(document).off("refresh", refreshWatcher);
                        }
                    }
                };

                StickyBlock.prototype.render = function() {
                    var that = this;

                    that.$wrapper
                        .removeAttr("style")
                        .addClass(that.fixed_class)
                        .css({
                            left: that.offset.left,
                            width: that.offset.width
                        });

                    that.$clone.css({
                        height: that.$wrapper.outerHeight()
                    }).show();
                };

                StickyBlock.prototype.reset = function() {
                    var that = this;

                    that.$wrapper
                        .removeClass(that.fixed_class)
                        .removeAttr("style");
                    that.$clone.hide().height(0);
                    that.offset = that.getOffset();
                };

                StickyBlock.prototype.getOffset = function() {
                    var that = this;

                    var offset = that.$wrapper.offset();

                    return {
                        top: offset.top,
                        left: offset.left,
                        width: that.$wrapper.outerWidth(),
                        height: that.$wrapper.outerHeight()
                    };
                };

                return StickyBlock;

            })(jQuery);

            var $wrapper = that.$wrapper.find(".js-sticky-links-wrapper");
            if ($wrapper.length) {
                new StickyBlock({
                    $wrapper: $wrapper
                });
            }
        };

        Sidebar.prototype.initToggles = function() {
            var that = this;

            // DOM
            var $window = $(window),
                $filter_params = that.$wrapper.find(".js-sidebar-filters-params");

            renderToggles();

            // Sidebar spoiler
            that.$wrapper.on("click", ".js-sidebar-spoiler", function(event) {
                event.preventDefault();

                var $spoiler = $(this),
                    $section = $spoiler.closest(".js-sidebar-spoiler-wrapper"),
                    is_open = !$section.hasClass("is-hidden"),
                    id = $section.data("id");

                if (id) { saveToggle(id, is_open); }

                toggleBlock($section, !is_open).then(callback);

                function callback() {
                    $(document).trigger("refresh");
                }
            });

            function toggleBlock($section, show) {
                var deferred = $.Deferred();

                var hidden_class = "is-hidden";

                if (show) {
                    $section.removeClass(hidden_class);
                } else {
                    $section.addClass(hidden_class);
                }

                deferred.resolve();

                return deferred.promise();
            }

            function saveToggle(id, value) {
                var storage = getStorage();

                if (!storage[id]) { storage[id] = {}; }
                storage[id].hidden = value;

                setStorage(storage);
            }

            function renderToggles() {
                var storage = getStorage();

                that.$wrapper.find(".js-sidebar-spoiler-wrapper").each( function() {
                    var $section = $(this),
                        id = $section.data("id");

                    var black_list = {
                        // "apps-category": {
                        //     hidden: true
                        // }
                    };

                    $section.removeClass("is-invisible");

                    var storage_section = (storage[id] || black_list[id]);
                    if (storage_section && storage_section.hidden) {
                        toggleBlock($section, false);
                    }
                });
            }

            function getStorage() {
                var storage_name = "installer-store-sidebar-toggles",
                    result = {};

                var storage = localStorage.getItem(storage_name);
                if (storage) { result = JSON.parse(storage); }

                return result;
            }

            function setStorage(storage) {
                var storage_name = "installer-store-sidebar-toggles",
                    string = JSON.stringify(storage);

                localStorage.setItem(storage_name, string);
            }
        };

        Sidebar.prototype.reload = function() {
            var that = this,
                load_class = "is-loading",
                url = getUrl();

            that.$wrapper.addClass(load_class);

            $.get(url)
                .always( function() {
                    that.$wrapper.removeClass(load_class);
                }).done( function(html) {
                    that.$wrapper.replaceWith(html);
                });

            function getUrl() {
                var current_href = location.href,
                    separator = (current_href.indexOf('?') === -1) ? '?' : '&';

                return current_href + separator + 'reload_sidebar=1';
            }
        };

        return Sidebar;

    })(jQuery);

    window.waInstaller = {
        sidebar: null,
        init: {
            Sidebar: Sidebar
        }
    };

})(jQuery);