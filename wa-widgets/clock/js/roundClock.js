/*
* Source: http://bl.ocks.org/tomgp/6475678
* */

var RoundClock;

( function($) {

    var radians = 0.0174532925;

    var getMargin = function(that, widget_size ) {
        var margin = 0;

        if (widget_size === "1x1") {
            margin = 25;

            if (that.show_town) {
                margin = 10;
            }
        }

        if (widget_size === "2x1") {
            margin = 25;

            if (that.show_town) {
                margin = 10;
            }
        }

        if (widget_size === "2x2") {
            margin = 50;

            if (that.show_town) {
                margin = 20;
            }
        }

        return margin;
    };

    var getNodeHeight = function(that, widget_size) {
        var height = 0,
            widget_height = parseInt(that.$widget.offsetHeight),
            $townName = $(that.$widget).find(".town-name-wrapper"),
            town_name_height = 0;

        if ($townName.length) {
            town_name_height = parseInt($townName.outerHeight());
        }

        height = (widget_height - town_name_height);

        return height;
    };

    var getOffset = function( offset, source ) {
        var result = 0,
            localTimeZone = -(new Date().getTimezoneOffset()/60),
            localOffset = localTimeZone * 60 * 60 * 1000;

        if (Math.abs(offset) >= 0) {
            if (source == "local") {
                result = 0;

            } else if (source == "server") {
                result += (offset - localOffset); // In miliseconds

            } else if ( Math.abs(offset) < 1000 ) {
                offset = offset * 60 * 60 * 1000;
                result += (offset - localOffset); // In miliseconds
            }
        }

        return result;
    };

    var getDate = function(that) {
        var timestamp = new Date().getTime(),
            offset = that.offset;

        return new Date(timestamp + offset);
    };

    RoundClock = function(options) {
        var that = this;

        // Widget
        that.offset = getOffset( options.offset, options.source );
        that.widget_id = options.widget_id;
        that.widget_app = options.widget_app;
        that.widget_name = options.widget_name;
        that.show_town = options.show_town;

        // DOM
        that.$widget = document.getElementById("widget-" + that.widget_id);
        that.$wrapper = d3.select("#round-clock-" + that.widget_id);

        // Vars
        that.margin = getMargin(that, options.size);
        that.node_width = parseInt(that.$widget.offsetWidth);
        that.node_height = getNodeHeight(that, options.size);
        that.min_side = ( Math.min.apply(Math, [that.node_width, that.node_height]) );
        that.width = that.min_side - ( that.margin * 2 );
        that.height = that.min_side - ( that.margin * 2 );
        that.clockRadius = ( Math.min.apply(Math, [that.width, that.height]) ) / 2;

        that.hourScale = d3.scale.linear().range([0, 330]).domain([0, 11]);
        that.hourHandLength = 2 * that.clockRadius / 3;
        that.hourLabelRadius = that.clockRadius - ( that.margin * 0.8 ) ;
        that.hourLabelYOffset = ( that.margin * 0.14 );
        that.hourTickStart = that.clockRadius;
        that.hourTickLength = -( that.min_side/20 );

        that.minuteScale = d3.scale.linear().range([0, 354]).domain([0, 59]);
        that.minuteHandLength = that.clockRadius;

        that.secondScale = that.minuteScale;
        that.secondHandLength = that.clockRadius - ( that.min_side / 20  );
        that.secondHandBalance = ( that.margin * 0.6 );
        that.secondTickStart = that.clockRadius;
        that.secondTickLength = -( that.min_side/30 );
        that.secondLabelRadius = that.clockRadius + ( that.margin * 0.32 );
        that.secondLabelYOffset = ( that.margin * 0.1 );

        that.handData = [
            {
                type: "hour",
                value: 0,
                length: -that.hourHandLength,
                scale: that.hourScale
            },
            {
                type: "minute",
                value: 0,
                length: -that.minuteHandLength,
                scale: that.minuteScale
            },
            {
                type:"second",
                value: 0,
                length: -that.secondHandLength,
                scale: that.secondScale,
                balance: that.secondHandBalance
            }
        ];

        // Functions
        that.initClock();
    };

    RoundClock.prototype.initClock = function() {
        var that = this;

        that.drawClock();

        that.initController();
    };

    RoundClock.prototype.drawClock = function() {
        var that = this;

        //draw them in the correct starting position
        that.updateData();

        var $wrapper = that.$wrapper;

        var svg = $wrapper.append("svg")
            .attr("width", that.min_side)
            .attr("height", that.min_side);

        var face = svg.append("g")
            .attr("class", "clock-face")
            .attr("transform", "translate(" + (that.clockRadius + that.margin) + ", " + (that.clockRadius + that.margin) + ")");

        //add marks for seconds
        face.selectAll(".second-tick")
            .data(d3.range(0, 60))
            .enter()
                .append("line")
                    .attr("class", "second-tick")
                    .attr("x1", 0)
                    .attr("x2", 0)
                    .attr("y1", that.secondTickStart)
                    .attr("y2", that.secondTickStart + that.secondTickLength)
                    .attr("transform", function(d){
                        return "rotate(" + that.secondScale(d) + ")";
                    });

        //and labels
        face.selectAll(".second-label")
            .data(d3.range(5, 61, 5))
            .enter()
                .append("text")
                    .attr("class", "second-label")
                    .attr("text-anchor", "middle")
                    .attr("x", function(d){
                        return that.secondLabelRadius*Math.sin(that.secondScale(d)*radians);
                    })
                    .attr("y", function(d){
                        return -that.secondLabelRadius*Math.cos(that.secondScale(d)*radians) + that.secondLabelYOffset;
                    })
                    .text(function(d){
                        return ( d / 5 );
                    });

        //... and hours
        face.selectAll(".hour-tick")
            .data(d3.range(0, 12))
            .enter()
                .append("line")
                    .attr("class", "hour-tick")
                    .attr("x1", 0)
                    .attr("x2", 0)
                    .attr("y1", that.hourTickStart)
                    .attr("y2", that.hourTickStart + that.hourTickLength)
                    .attr("transform", function(d){
                        return "rotate(" + that.hourScale(d) + ")";
                    });

        //face.selectAll(".hour-label")
        //    .data(d3.range(3, 13, 3))
        //    .enter()
        //        .append("text")
        //            .attr("class", "hour-label")
        //            .attr("text-anchor", "middle")
        //            .attr("x", function(d){
        //                return that.hourLabelRadius*Math.sin(that.hourScale(d)*radians);
        //            })
        //            .attr("y", function(d){
        //                return -that.hourLabelRadius*Math.cos(that.hourScale(d)*radians) + that.hourLabelYOffset;
        //            })
        //            .text(function(d){
        //                return d;
        //            });

        var hands = face.append("g").attr("class", "clock-hands");

        face.append("g").attr("class", "face-overlay")
            .append("circle")
                .attr("class", "hands-cover")
                .attr("x", 0)
                .attr("y", 0)
                .attr("r", that.clockRadius/20);

        hands.selectAll("line")
            .data(that.handData)
            .enter()
                .append("line")
                    .attr("class", function(d){
                        return d.type + "-hand";
                    })
                    .attr("x1", 0)
                    .attr("y1", function(d){
                        return d.balance ? d.balance : 0;
                    })
                    .attr("x2", 0)
                    .attr("y2", function(d){
                        return d.length;
                    })
                    .attr("transform", function(d){
                        return "rotate("+ d.scale(d.value) +")";
                    });
    };

    RoundClock.prototype.updateData = function() {
        var that = this,
            date = getDate(that);

        that.handData[0].value = (date.getHours() % 12) + date.getMinutes()/60 ;
        that.handData[1].value = date.getMinutes();
        that.handData[2].value = date.getSeconds();
    };

    RoundClock.prototype.moveHands = function() {
        var that = this,
            $wrapper = that.$wrapper;

        $wrapper.select(".clock-hands").selectAll("line")
            .data(that.handData)
            .transition()
            .attr("transform", function(d){
                return "rotate("+ d.scale(d.value) +")";
            });
    };

    RoundClock.prototype.refreshClock = function() {
        var that = this;

        that.updateData();
        //
        that.moveHands();
    };

    RoundClock.prototype.initController = function() {
        var that = this,
            controller_app,
            controller_widgets_array,
            is_controller_exist;

        // Init App
        if (typeof DashboardControllers[that.widget_app] === "undefined") {
            DashboardControllers[that.widget_app] = {};
        }
        controller_app = DashboardControllers[that.widget_app];

        // Init Widget in App
        is_controller_exist = (typeof controller_app[that.widget_name] !== "undefined");
        if (!is_controller_exist) {
            controller_app[that.widget_name] = {};
            controller_app[that.widget_name]['widget_list'] = [];
        }
        controller_widgets_array = controller_app[that.widget_name]['widget_list'];

        // Init widget unit
        controller_widgets_array.push(that.widget_id);
        //console.log(that.widget_id);

        // init Controller
        if (!is_controller_exist) {
            controller_app[that.widget_name]['controller'] = new ClockController(that.widget_app,that.widget_name);
        }
    };

})(jQuery);