var ElectronicClock;

( function() {

    var initClock = function( that ) {
        // Set Time on Start
        setTime(that);

        that.initController();
    };

    var getClockData = function(that) {
        var date = getDate(that),
            seconds = date.getSeconds(),
            minutes = date.getMinutes(),
            hours = date.getHours(),
            day = date.getDay(),
            month = date.getMonth(),
            number = date.getDate(),
            day_name = that.localization.day[day],
            month_name = that.localization.month[month];

        if (hours < 10) {
            hours = "0" + hours;
        }

        if (minutes < 10) {
            minutes = "0" + minutes;
        }

        if (seconds < 10) {
            seconds = "0" + seconds;
        }

        if ( (hours == "00") && (minutes == "00") && (seconds == "00") ) {
            that.changeDate = true;
        }

        var divider_class = ( (seconds % 2) > 0 ) ? "step-1" : "";
        var clockDivider = "<span class=\"divider " + divider_class + "\">:</span>";

        if (that.format == "12") {

            if (hours > 11) {
                that.time = ( hours - 12 ) + "" + clockDivider + "" + minutes + "<span class=\"time-format\">pm</span>";
            } else {
                that.time = hours + "" + clockDivider + "" + minutes + "<span class=\"time-format\">am</span>";
            }

        } else {
            that.time = hours + "" + clockDivider + "" + minutes; // + ":" + seconds;
        }
        that.day = day_name;
        that.date = number + " " + month_name;

    };

    var setTime = function(that) {

        // Prepare Data for Render
        getClockData(that);

        that.$timeWrapper.html(that.time);

        if (that.changeDate) {
            that.$dayWrapper.html(that.day);
            that.$dateWrapper.html(that.date);
            that.changeDate = false;
        }
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

    ElectronicClock = function(options) {
        var that = this;

        // Widget
        that.offset = getOffset( options.offset, options.source );
        that.format = options.format;
        that.widget_id = options.widget_id;
        that.widget_app = options.widget_app;
        that.widget_name = options.widget_name;
        that.localization = options.localization;

        // Dynamic vars
        that.time = false;
        that.day = false;
        that.date = false;
        that.changeDate = false;

        // DOM
        that.$widget = DashboardWidgets[that.widget_id].$widget;
        that.$timeWrapper = that.$widget.find(".time-wrapper");
        that.$dayWrapper = that.$widget.find(".day-wrapper");
        that.$dateWrapper = that.$widget.find(".date-wrapper");

        // Init
        initClock(that);
    };

    ElectronicClock.prototype.refreshClock = function() {
        var that = this;

        setTime(that);
    };

    ElectronicClock.prototype.initController = function() {
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

})();