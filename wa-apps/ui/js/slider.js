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