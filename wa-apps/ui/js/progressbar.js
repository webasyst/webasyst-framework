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
                    end_deg = start_deg;

                if (percentage < 100) {
                    end_deg = start_deg - (3.6 * percentage);
                } else {
                    start_deg = 0;
                    end_deg = 360;
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
                x + Math.cos(endAngle) * r, y - Math.sin(endAngle) * r,
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