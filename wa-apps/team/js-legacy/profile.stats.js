// Team :: Profile :: Statistic Tab
var ProfileStatistic = ( function($) {

    var Graph = ( function($) {

        Graph = function(options) {
            var that = this;

            //
            that.app_id = options["app_id"];

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$hint = that.$wrapper.find(".t-hint-wrapper");
            that.node = that.$wrapper.find(".t-graph")[0];
            that.d3node = d3.select(that.node);
            //
            that.show_class = "is-shown";

            // DATA
            that.charts = options.data;
            that.data = getData(that.charts, that.app_id);
            that.group_by = options["group_by"];
            that.locales = options["locales"];

            // VARS
            that.margin = {
                top: 14,
                right: 10,
                bottom: 28,
                left: 34
            };
            that.area = getArea(that.node, that.margin);
            that.column_indent = 4;
            that.column_width = getColumnWidth(that.area.inner_width, that.column_indent, that.data[0].length);

            // DYNAMIC VARS
            that.svg = false;
            that.defs = false;
            that.x = false;
            that.y = false;
            that.xDomain = false;
            that.yDomain = false;

            // INIT
            that.initGraph();
        };

        Graph.prototype.initGraph = function() {
            var that = this,
                graphArea = that.area;

            that.initGraphCore();

            that.svg = that.d3node
                .append("svg")
                .attr("width", graphArea.outer_width)
                .attr("height", graphArea.outer_height);

            // that.defs = that.svg.append("defs");
            //
            that.renderBackground();
            // Render Graphs
            that.renderCharts();
            //
            that.renderAxis();
        };

        Graph.prototype.initGraphCore = function() {
            var that = this,
                data = that.data,
                graphArea = that.area;

            var x = that.x = d3.time.scale().range([0, graphArea.inner_width]);
            var y = that.y = d3.scale.linear().range([graphArea.inner_height, 0]);

            that.yDomain = getValueDomain();
            that.xDomain = getTimeDomain();

            x.domain(that.xDomain);
            y.domain(that.yDomain);

            function getValueDomain() {
                var min = d3.min(data, function(chart) {
                    return d3.min(chart, function(point) {
                        return point.value;
                    });
                });
                if (min > 0) {
                    min = 0;
                }
                var max = d3.max(data, function(chart) {
                    return d3.max(chart, function(point) {
                        return (point.value + point.y0);
                    });
                });

                return [min, max];
            }

            function getTimeDomain() {
                var min, max,
                    points_length = data[0].length,
                    first_point = data[0][0].date,
                    second_point = data[0][1].date,
                    last_point = data[0][points_length-1].date,
                    half_time_period = parseInt( ( second_point.getTime() - first_point.getTime() )/2 );

                min = new Date( first_point.getTime() - half_time_period );
                max = new Date( last_point.getTime() + half_time_period );

                return [min, max];
            }
        };

        Graph.prototype.renderAxis = function() {
            var that = this,
                x = that.x,
                y = that.y,
                svg = that.svg;

            var xAxis = d3.svg.axis()
                .scale(x)
                .orient("bottom")
                .ticks(10);

            var yAxis = d3.svg.axis()
                .scale(y)
                .innerTickSize(2)
                .orient("right")
                .tickValues( getValueTicks(6, that.yDomain) )
                .tickFormat(function(d) { return d + ""; });

            // Render Осей
            var axis = svg.append("g")
                .attr("class","axis");

            axis.append("g")
                .attr("transform","translate(" + 10 + "," + that.margin.top + ")")
                .attr("class","y")
                .call(yAxis);

            axis.append("g")
                .attr("class","x")
                .attr("transform","translate(" + that.margin.left + "," + (that.area.outer_height - that.margin.bottom ) + ")")
                .call(xAxis);
        };

        Graph.prototype.renderBackground = function() {
            var that= this,
                width = that.area.inner_width,
                height = that.area.inner_height,
                xTicks = 31,
                yTicks = 5,
                i;

            var background = that.svg.append("g")
                .attr("class", "background")
                .attr("transform", "translate(" + that.margin.left + "," + that.margin.top + ")");

            background.append("rect")
                .attr("width", width)
                .attr("height", height);

            for (i = 0; i <= yTicks; i++) {
                var yVal = 1 + (height - 2) / yTicks * i;
                background.append("line")
                    .attr("x1", 1)
                    .attr("x2", width)
                    .attr("y1", yVal)
                    .attr("y2", yVal)
                ;
            }
        };

        Graph.prototype.renderCharts = function() {
            var that = this,
                svg = that.svg,
                data = that.data;

            var wrapper = svg.selectAll(".t-graph-wrapper")
                .data(data);

            wrapper
                .enter()
                .append("g")
                .attr("class", "t-graph-wrapper")
                .attr("transform", "translate(" + that.margin.left + "," + that.margin.top + ")");

            var rect = wrapper.selectAll(".rect")
                .data( function(chart) {
                    return chart;
                });

            rect
                .enter()
                .append("rect")
                .attr("class", "rect")
                .style("fill", function(data,point_index,chart_index) {
                    var color = that.charts[chart_index].color;
                    return color ? color : false;
                })
                .on("mouseover", onOver)
                .on("mousemove", onMove)
                .on("mouseout", onOut);

            rect
                .transition()
                .duration(1000)
                .attr("x", function(d, i) {
                    return that.x( d.date ) - that.column_width/2;
                })
                .attr("y", function(d) {
                    return ( that.y(d.y0) + that.y(d.value) ) - that.area.inner_height;
                })
                .attr("height", function(d) {
                    return that.area.inner_height - that.y(d.value);
                })
                .attr("width", that.column_width);

            rect.exit().remove();
            wrapper.exit().remove();

            function onOver(d,i,j) {
                that.showHint(d3.event, this, d, that.charts[j]);
            }

            function onMove() {
                that.moveHint(this);
            }

            function onOut() {
                that.hideHint();
            }
        };

        Graph.prototype.update = function( app_id ) {
            var that = this;

            that.data = getData(that.charts, app_id);

            that.initGraphCore();

            var yAxis = d3.svg.axis()
                .scale(that.y)
                .innerTickSize(2)
                .orient("right")
                .tickValues( getValueTicks(6, that.yDomain) )
                .tickFormat(function(d) { return d + ""; });

            that.svg.selectAll(".axis .y")
                .call(yAxis);

            that.renderCharts();
        };

        Graph.prototype.showHint = function(event, node, point, chart) {
            var that = this,
                $point = $(node),
                point_height = Math.ceil( $point.attr("height") ),
                has_height = ( point_height > 0 );

            if (!has_height) {
                return false;
            }

            var date = point.date,
                $date = that.$hint.find(".t-date"),
                $app = that.$hint.find(".t-app"),
                $count = that.$hint.find(".t-value");

            var hint_text = getHintText(date, that.group_by);

            $date.text(hint_text );
            $app.text(chart.name);
            $count.text(point.value);

            var css = getHintPosition($point);

            that.$hint
                .css(css)
                .addClass(that.show_class);

            function getHintPosition($point) {
                var $window = $(window),
                    window_w = $window.width(),
                    window_h = $window.height(),
                    hint_w = that.$hint.outerWidth(),
                    hint_h = that.$hint.outerHeight(),
                    point_width = Math.ceil( $point.attr("width") ),
                    point_height = Math.ceil( $point.attr("height") ),
                    point_border_w = 2,
                    space = 10;

                var wrapperOffset = that.$wrapper.offset(),
                    pointOffset = $point.offset(),
                    hintOffset = {
                        left: pointOffset.left - wrapperOffset.left + point_width + space,
                        top: pointOffset.top - wrapperOffset.top + ( (point_height < hint_h) ? point_height - hint_h - point_border_w : point_border_w )
                    };

                if (window_w < hintOffset.left + hint_w) {
                    hintOffset.left = pointOffset.left - (hint_w + space);
                }

                return hintOffset;
            }

            function getHintText(date, group_by) {
                var month = parseInt( date.getMonth() ),
                    result;

                if (group_by == "months") {
                    var months = that.locales["months"];
                    if (months[month]) {
                        result = months[month];
                    } else {
                        months = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
                        result = months[month];
                    }
                } else {
                    var day = date.getDate();
                    day = ( day < 10 ) ? "0" + day : day;

                    month += 1;
                    month = ( month < 10 ) ? "0" + month : month;

                    result = day + "." + month + "." + date.getFullYear();

                    try {
                        result = $.datepicker.formatDate(that.locales.dateFormat, date);
                    } catch(e) {
                        result = day + "." + month + "." + date.getFullYear();
                    }
                }

                return result;
            }
        };

        Graph.prototype.moveHint = function( ) {
            var that = this;
        };

        Graph.prototype.hideHint = function( ) {
            var that = this;

            that.$hint
                .removeAttr("style")
                .removeClass(that.show_class);
        };

        return Graph;

        // Получаем размеры для графика
        function getArea(node, margin) {
            var width = node.offsetWidth,
                height = node.offsetHeight;

            return {
                outer_width: width,
                outer_height: height,
                inner_width: width - margin.left - margin.right,
                inner_height: height - margin.top - margin.bottom
            };
        }

        function getData(charts, app_id) {
            var chartsData = [];

            for (var i = 0; i < charts.length; i++) {
                var chart = charts[i].data,
                    chartData = [];

                for (var j = 0; j < chart.length ; j++) {
                    var point = chart[j],
                        point_value = ( !app_id || charts[i].id === app_id ) ? parseInt( point.value ) : 0;

                    chartData.push({
                        date: formatDate( point.date ),
                        value: point_value
                    });
                }

                chartsData.push(chartData);
            }

            var stack = d3.layout.stack()
                .offset("zero")
                .values( function(d) { return d; })
                .x(function(d) { return d.date; })
                .y(function(d) { return d.value; });

            return stack(chartsData);

            function formatDate(date_string) {
                var dateArray = date_string.split("-"),
                    year = parseInt(dateArray[0]),
                    month = parseInt(dateArray[1]) - 1,
                    day = parseInt(dateArray[2]);

                return new Date(year, month, day);
            }
        }

        function getColumnWidth(width, indent, length) {
            var result = null;

            length = length + 1;

            if (width && length) {
                var indent_space = indent * ( length - 1 );
                result = (width - indent_space)/length;
                if (result < 0) {
                    result = 0;
                }
            }

            return result;
        }

        function getValueTicks(length, domain) {
            var min = domain[0],
                max = ( domain[1] || 1 ),
                delta = (max - min) + 1,
                period = delta/(length - 1),
                result = [];

            for (var i = 0; i < length; i++) {
                var label = (delta > 10) ? Math.round( i * period ) : (parseInt(  i * period * 10 ) / 10 );
                result.push(label);
            }

            return result;
        }

    })(jQuery);

    //

    ProfileStatistic = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$graphWrapper = that.$wrapper.find("#t-graph-wrapper");
        that.$header = that.$wrapper.find(".t-header-wrapper");
        that.$filters = that.$header.find(".t-filters");
        that.graphData = options["graphData"];

        // VARS
        that.app_url = options["app_url"];
        that.app_id = options["app_id"];
        that.group_by = options["group_by"];
        that.timeframe = options["timeframe"];
        that.start_date = options["start_date"];
        that.end_date = options["end_date"];
        that.loading_class = "is-loading";
        that.contact_id = options["contact_id"];
        that.locales = options["locales"];

        // DYNAMIC VARS
        that.graph = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    ProfileStatistic.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        that.initGraph();
        //
        that.initDatePicker();
    };

    ProfileStatistic.prototype.bindEvents = function() {
        var that = this;

        that.$filters.on("click", ".dropdown .menu-v a", function(event) {
            event.preventDefault();
            that.setFilter( $(this) );
        });

        that.$filters.on("click", ".t-period-filter .menu-v a", function(event) {
            event.preventDefault();
            that.changePeriod( $(this) );
        });

        that.$filters.on("click", ".t-app-filter .menu-v a", function(event) {
            event.preventDefault();
            var app_id = $(this).data("app-id");
            that.changeApp( (app_id) ? app_id : false );
        });

        that.$filters.on("click", ".js-set-custom-period", function(event) {
            event.preventDefault();
            that.changeCustomPeriod( $(this).closest("form") );
        })
    };

    ProfileStatistic.prototype.initGraph = function() {
        var that = this;

        if (that.$graphWrapper.length && that.graphData && that.graphData.length) {
            that.graph = new Graph({
                $wrapper: that.$graphWrapper,
                data: prepareData(that.graphData),
                group_by: that.group_by,
                locales: that.locales,
                app_id: that.app_id
            });
        }

        function prepareData( data ) {
            var point_length = data[0].data.length;
            if (point_length === 1) {
                $.each(data, function(index) {
                    var point = data[index].data[0],
                        before_point = {
                            value: 0,
                            date: getLiftDate(point.date, false)
                        },
                        next_point = {
                            value: 0,
                            date: getLiftDate(point.date, true)
                        };

                    data[index].data = [before_point, point, next_point];
                });
            }

            return data;

            function getLiftDate(date_string, next) {
                var dateArray = date_string.split("-"),
                    year = parseInt(dateArray[0]),
                    month = parseInt(dateArray[1]) - 1,
                    day = parseInt(dateArray[2]);

                var one_day = 1000 * 60 * 60 * 24,
                    date = new Date( new Date(year, month, day).getTime() + (next ? one_day : -one_day) );

                var d_year = parseInt(date.getFullYear()),
                    d_month = parseInt(date.getMonth()) + 1,
                    d_day = parseInt(date.getDate());

                if (d_day < 10) {
                    d_day = "0" + d_day;
                }
                if (d_month < 10) {
                    d_month = "0" + d_month;
                }

                return [d_year, d_month, d_day].join("-");
            }
        }
    };

    ProfileStatistic.prototype.initDatePicker = function() {
        var that = this;
        var $datePickers = that.$wrapper.find(".js-datepicker");
        $datePickers.each( function() {
            var $input = $(this),
                $altField = $input.parent().find("input[type='hidden']");

            $input.datepicker({
                altField: $altField,
                altFormat: "yy-mm-dd"
            });
        });
    };

    ProfileStatistic.prototype.changeApp = function( app_id ) {
        var that = this;

        that.app_id = (app_id) ? app_id : false;

        that.graph.update( that.app_id );
    };

    ProfileStatistic.prototype.setFilter = function( $link ) {
        var that = this,
            $li = $link.closest("li"),
            $menu = $li.closest(".menu-v"),
            $dropdown = $menu.closest(".dropdown"),
            $selected = $dropdown.find(".t-selected-item"),
            selected_class = "selected";

        $menu.find("." + selected_class).removeClass(selected_class);
        $li.addClass(selected_class);

        $link.trigger("set");

        $menu.hide();
        setTimeout( function () {
            $menu.removeAttr("style");
        }, 500);

        $selected.html( $link.html() );
    };

    ProfileStatistic.prototype.changePeriod = function( $link ) {
        var that = this,
            $hidden = $link.closest(".t-period-filter").find(".t-hidden-part"),
            active_class = "js-show-period-form",
            is_period = !$link.hasClass(active_class),
            shown_class = "is-shown",
            loading_class = that.loading_class;

        if (is_period) {
            $hidden.removeClass(shown_class);

            var href = that.app_url + "?module=profile&action=stats", // &is_graph_data=true
                data = {
                    is_update: true,
                    id: that.contact_id
                };

            if ( $link.data("timeframe") && $link.data("groupby") ) {
                data.timeframe = that.timeframe = $link.data("timeframe");
                data.groupby = that.group_by = $link.data("groupby");
            }

            if (that.app_id) {
                data.app_id = that.app_id;
            }

            if (that.xhr) {
                that.xhr.abort();
                that.xhr = false;
            }

            that.$wrapper.addClass(loading_class);

            that.xhr = $.post(href, data, function( html ) {
                that.$wrapper.replaceWith( html );
            });
        } else {
            $hidden.addClass(shown_class);
        }
    };

    ProfileStatistic.prototype.changeCustomPeriod = function( $form ) {
        var that = this,
            href = that.app_url + "?module=profile&action=stats",
            data = $form.serializeArray();

        data.push({
            name: "is_update",
            value: true
        });
        data.push({
            name: "id",
            value: that.contact_id
        });

        data.push({
            name: "timeframe",
            value: "custom"
        });

        if (that.app_id) {
            data.push({
                name: "app_id",
                value: that.app_id
            });
        }

        prepareData(data);

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.$wrapper.addClass(that.loading_class);

        that.xhr = $.post(href, data, function( html ) {
            that.$wrapper.replaceWith( html );
        });


        function prepareData(data) {
            var sql_start, sql_end, groupby, groupby_index;

            $.each(data, function(index, item) {
                var name = item.name;
                if (name == "from") {
                    sql_start = item.value;
                }

                if (name == "to") {
                    sql_end = item.value;
                }

                if (name == "groupby") {
                    groupby_index = index;
                    groupby = item.value;
                }
            });

            if (groupby == "days" && groupby_index >= 0) {
                var change_groupby = setGroupBy(sql_start, sql_end);
                if (change_groupby) {
                    data[groupby_index].value = "months";
                }
            }

            return data;

            function setGroupBy(sql_start, sql_end) {
                var start = formatDate(sql_start),
                    end = formatDate(sql_end),
                    delta = Math.abs( end.getTime() - start.getTime() ),
                    day = 24 * 60 * 60 * 1000,
                    max_count = 92;

                return (delta > max_count * day);
            }

            function formatDate(date_string) {
                var dateArray = date_string.split("-"),
                    year = parseInt(dateArray[0]),
                    month = parseInt(dateArray[1]) - 1,
                    day = parseInt(dateArray[2]);

                return new Date(year, month, day);
            }
        }
    };

    ProfileStatistic.prototype.update = function() {
        var that = this;


    };

    return ProfileStatistic;

})(jQuery);