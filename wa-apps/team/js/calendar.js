// Pages

// Team :: Calendar Page
var CalendarPage = ( function($) {

    // Team :: Date Filter
    var DateFilter = ( function($) {

        DateFilter = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$monthSelect = that.$wrapper.find(".month");
            that.$yearSelect = that.$wrapper.find(".year");

            // VARS
            that.filters_href = options["filters_href"];

            // DYNAMIC VARS
            that.month = parseInt( that.$monthSelect.val() );
            that.year = parseInt( that.$yearSelect.val() );

            // INIT
            that.initClass();
        };

        DateFilter.prototype.initClass = function() {
            var that = this;

            that.$monthSelect.on("change", function() {
                var month = $(this).val();
                if (month) {
                    that.month = parseInt(month);
                }
                that.useFilter();
                return false;
            });

            that.$yearSelect.on("change", function() {
                var year = $(this).val();
                if (year) {
                    that.year = parseInt(year);
                }
                that.useFilter();
                return false;
            });

            that.$wrapper.on("click", ".t-arrow", function() {
                var $arrow = $(this);
                if ($arrow.hasClass("left")) {
                    that.changeMonth( false );
                }
                if ($arrow.hasClass("right")) {
                    that.changeMonth( true );
                }
                that.useFilter();
                return false;
            });

        };

        DateFilter.prototype.changeMonth = function( next ) {
            var that = this;

            if (next) {
                if (that.month >= 12) {
                    that.month = 1;
                    that.year++;
                } else {
                    that.month++;
                }
            } else {
                if (that.month <= 1) {
                    that.month = 12;
                    that.year--;
                } else {
                    that.month--;
                }
            }
        };

        DateFilter.prototype.useFilter = function() {
            var that = this,
                start_value = that.year + "-" + ( (that.month > 9) ? that.month : "0" + that.month) + "-01",
                start_string = "start=" + start_value,
                content_uri;

            var location = that.filters_href.split("?"),
                pathname = location[0],
                search = ( location[1] || "" ),
                searchArray = (search.indexOf("&") >= 0) ? search.split("&") : [search],
                start_index = false;

            if (searchArray.length) {
                $.each(searchArray, function(index, item) {
                    var name = item.split("=")[0];
                    if (name == "start") {
                        start_index = index;
                    }
                });
            }

            // Add "month" get param
            if (start_index || start_index === 0) {
                searchArray[start_index] = start_string;
            } else {
                searchArray.push(start_string);
            }

            content_uri = pathname + "?" + searchArray.join("&");

            $.team.content.load(content_uri);
        };

        return DateFilter;

    })(jQuery);

    CalendarPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$dateFilter = options["$dateFilter"];


        // VARS
        that.filters_href = options["filters_href"];
        that.user_id = options["user_id"];
        that.local_storage = new $.store();

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    CalendarPage.prototype.initClass = function() {
        var that = this;

        // Init Date Filter
        new DateFilter({
            $wrapper: that.$dateFilter,
            filters_href: that.filters_href
        });

        //
        that.initInfoBlock();
    };

    CalendarPage.prototype.initInfoBlock = function () {
        var that = this,
            $info_block = that.$wrapper.find(".t-info-notice-wrapper"),
            storage = that.local_storage,
            key = "team/calendar_info_warn_block_hide";

        if (storage.get(key)) {
            $info_block.hide();
        } else {
            $info_block.show();
        }

        $info_block.find(".t-info-notice-toggle").on("click", function () {
            storage.set(key, 1);
            $info_block.hide();
        });
    };

    return CalendarPage;

})(jQuery);

// Team :: Calendar
var TeamCalendar = ( function($) {

    TeamCalendar = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$rows = that.$wrapper.find(".t-week-row");

        // VARS
        that.days_count = 7;
        that.weeks_count = that.$rows.length;
        that.selected_class = "is-selected";
        that.active_class = "is-active";
        that.locales = options["locales"];
        that.is_profile = options["is_profile"];

        //
        that.event_view_uri = "?module=schedule&action=eventView";
        that.event_edit_uri = "?module=schedule&action=eventEdit";
        that.day_uri = "?module=schedule&action=day";
        that.user_id = options["user_id"];
        that.has_right_to_change = options["has_right_to_change"];
        that.selected_user_id = options["selected_user_id"];
        that.selected_calendar_id = options["selected_calendar_id"];
        that.period_start = options["period_start"];
        that.period_end = options["period_end"];
        that.daysArray = getDays( that.$rows );     // days matrix

        // DYNAMIC VARS
        that.wrapper_o = that.$wrapper.offset();
        that.wrapper_w = that.$wrapper.width();
        that.wrapper_h = that.$wrapper.height();
        that.day_w = 0;
        that.day_h = 0;
        that.dialogs = [];
        that.xhr = false;

        // SELECTION
        that.start = false;         // {week: int, day: int}
        that.end = false;           // {week: int, day: int}
        that.$selectedDays = [];    // arrays of selected $days
        that.is_locked = false;     // lock for move

        // INIT
        that.bindEvents();
        //
        that.initHoverOnDay();
        //
        that.initMove();
    };

    TeamCalendar.prototype.bindEvents = function() {
        var that = this,
            $document = $(document),
            can_add_event = that.has_right_to_change;

        if (can_add_event) {
            // Block out the selection by clicking on the event
            that.$wrapper.find(".show-full-days-events, .t-event-wrapper").on("mousedown", function(event) {
                event.stopPropagation();
                that.clearSelection();
            });

            // Remove selection when you click outside the block
            $document.on("mousedown", function() {
                that.clearSelection();
            });

            // Selection block on mouse down/move
            that.$wrapper.on("mousedown", function(event) {
                event.stopPropagation();
                var dialog_was_exist = that.closeDialogs();
                if (!dialog_was_exist) {
                    // Start
                    that.onMouseDown(event);

                    // Add nexts events
                    that.$wrapper.on("mousemove", move);
                    $document.on("mouseup", mouseUp);
                }
                return false;
            });
        }

        // Show full days events
        that.$wrapper.on("click", ".show-full-days-events", function(event) {
            event.preventDefault();
            var dialog_was_exist = that.closeDialogs();
            if (!dialog_was_exist) {
                that.showFullDayEvents( $(this) );
            }
        });

        // Show event details
        that.$wrapper.on("click", ".t-event-block.js-view-event", function(event) {
            event.preventDefault();
            var dialog_was_exist = that.closeDialogs();
            if (!dialog_was_exist) {
                that.showEventDetails( $(this).closest(".t-event-wrapper") );
            }
        });

        $(document).on("keydown", keydownWatcher);

        // FUNCTIONS

        function keydownWatcher(event) {
            var is_exist = $.contains(document, that.$wrapper[0]);
            if (is_exist) {
                var key = event.keyCode;
                if (key === 27) {
                    that.deselectDays();
                }
            } else {
                $(document).off("keydown", keydownWatcher);
            }
        }

        function move(event) {
            if (!that.is_locked) {
                that.is_locked = true;
                that.onMouseMove(event);

                setTimeout( function () {
                    that.is_locked = false;
                }, 10);
            }
            return false;
        }

        function mouseUp() {
            that.onMouseUp();
            that.$wrapper.off("mousemove", move);
            $document.off("mouseup", mouseUp);
            return false;
        }
    };

    TeamCalendar.prototype.showFullDayEvents = function( $link ) {
        var that = this,
            events_id = $link.data("events-id").split(","),
            date = $link.data("date"),
            data = {
                date: date,
                id: events_id
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post( that.day_uri, data, function(html) {
            var $linkW = $link.closest(".t-action-wrapper"),
                $row = $linkW.closest(".t-week-row"),
                cell_o = {
                    top: $row.offset().top,
                    left: $linkW.offset().left,
                    width: $linkW.outerWidth(),
                    height: $row.outerHeight()
                };

            var dialog = new TeamDialog({
                html: html,
                setPosition: setPosition
            });

            that.addDialog(dialog);

            function setPosition(area) {
                var wrapper_w = area.width,
                    wrapper_h = area.height,
                    delta_w = ( (wrapper_w - cell_o.width)/2 ),
                    delta_h = ( (wrapper_h - cell_o.height)/2 );

                var top = cell_o.top - ( (delta_h > 0) ? delta_h : 0 ),
                    left = cell_o.left - ( (delta_w > 0) ? delta_w : 0 );

                var right_space = ( $(window).width() - (left + wrapper_w) );
                if (right_space < 0) {
                    var padding_r = 10;
                    left -= Math.abs(right_space) + padding_r;
                }

                return {
                    top: top,
                    left: left
                }
            }
        });
    };

    TeamCalendar.prototype.showEventDetails = function( $event ) {
        var that = this,
            event_id = $event.data("id"),
            data = {
                "data[id]": event_id
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post( that.event_view_uri, data, function(html) {
            var dialog = new TeamDialog({
                html: html
            });
            that.addDialog(dialog);
        });
    };

    TeamCalendar.prototype.createEvent = function() {
        var that = this,
            $startDay = that.daysArray[that.start.week - 1][that.start.day - 1],
            $endDay = that.daysArray[that.end.week - 1][that.end.day - 1],
            start_date = $startDay.data("date"),
            end_date = $endDay.data("date"),
            data = {
                "data[start]": false,
                "data[end]": false,
                "data[contact_id]": ( that.selected_user_id || that.user_id )
            };

        var current_date = new Date(),
            current_hour = current_date.getHours();

        data["data[start]"] = start_date.replace("00:00:00", ( (current_hour >= 23) ? "23:59:59" : ( current_hour + 1 ) + ":00:00"  ) );
        data["data[end]"] = end_date.replace("00:00:00", ( (current_hour >= 22) ? "23:59:59" : ( current_hour + 2 ) + ":00:00"  ) );

        if (that.selected_calendar_id) {
            data["data[calendar_id]"] = that.selected_calendar_id;
        }

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post( that.event_edit_uri, data, function(response) {
            var dialog = new TeamDialog({
                html: response
            });
            that.addDialog(dialog);
        });

    };

    TeamCalendar.prototype.onMouseDown = function(event) {
        var that = this;
        //
        that.start = that.getDayPosition( event );
        that.end = that.start;
        //
        that.markDays();
    };

    TeamCalendar.prototype.onMouseMove = function(event) {
        var that = this,
            newPosition = that.getDayPosition( event );

        if ( !(that.end && that.end.week == newPosition.week && that.end.day == newPosition.day) ) {
            that.end = newPosition;
            that.markDays();
        }
    };

    TeamCalendar.prototype.initHoverOnDay = function(event) {
        var that = this,
            $wrapper = that.$wrapper,
            $hoverDay = false,
            active_class = "is-highlighted";

        $wrapper
            .on("mousemove", onMove)
            .on("mouseleave", onLeave);

        function onMove(event) {
            var day = that.getDayPosition(event),
                $day = getDay( day, that.daysArray );

            var $target = $(event.target);
            if ( $target.hasClass("t-event-block") || $target.closest(".t-event-block").length ) {
                clear();
                return false;
            }

            if (that.start) {
                clear();
                return false;
            }

            if (!$day) {
                clear();
                return false;
            } else if ($day.hasClass(active_class)) {
                return false;
            }

            if ($hoverDay) {
                clear();
            }

            markDay($day);
        }

        function onLeave() {
            clear();
        }

        function markDay( $day ) {
            $day.addClass(active_class);
            $hoverDay = $day;
        }

        function clear() {
            if ($hoverDay) {
                $hoverDay.removeClass(active_class);
            }
            $hoverDay = false;
        }

        function getDay(day, array ) {
            var result = false;

            if (array[day.week - 1] && array[day.week - 1][day.day - 1]) {
                result = array[day.week - 1][day.day - 1];
            }

            return result;
        }
    };

    TeamCalendar.prototype.onMouseUp = function() {
        var that = this;

        if ( that.start.week > that.end.week || ( that.start.week == that.end.week && that.start.day > that.end.day ) ) {
            var end = {
                    week: that.start.week,
                    day: that.start.day
                };

            that.start = {
                week: that.end.week,
                day: that.end.day
            };
            that.end = end;
        }

        that.createEvent();

        that.start = false;
        that.end = false;
    };

    TeamCalendar.prototype.initMove = function() {
        var schedule = this,
            $wrapper = schedule.$wrapper,
            $events = $wrapper.find(".js-move-event"),
            $event = false,
            is_locked = false,
            xhr;

        $events.draggable({
            helper: "clone",
            appendTo: "body",
            cursor: "move",
            delay: 200,
            cursorAt: {
                top: 11,
                left: 16
            },
            start: function(event, ui) {
                var $_event = $(ui.helper.context),
                    $clone = ui.helper;

                $event = $_event;

                var size = parseInt( $event.closest(".t-column").attr("colspan") ),
                    hint = ( $_event.data("move-hint") || false );

                if (hint) {
                    $clone.find(".t-event-block").append("<span class='t-day-count'>(" + hint + ")</span>");
                }

                $clone.addClass("is-clone").css({
                    minWidth: parseInt( $_event.width() / size ) + "px",
                    height: $_event.height() + "px"
                });
            },
            drag: function(event, ui) {
                if (!is_locked) {
                    is_locked = true;
                    setTimeout( function() {
                        is_locked = false;
                    }, 100);
                    onMove(event);
                }
            },
            stop: function(event, ui) {
                schedule.clearSelection();
                $event = false;
            }
        });

        $wrapper.droppable({
            drop: function(event) {
                onDrop(event)
            }
        });

        function onDrop(event) {
            var day = schedule.getDayPosition(event),
                $day = schedule.daysArray[day.week - 1][day.day - 1];

            if (!$day) {
                if (console && console.log) {
                    console.log("error: date is not exist");
                }
                return false;
            }

            var href = $.team.app_url + "?module=schedule&action=eventMove",
                data = {
                    id: parseInt( $event.data("id") ),
                    start: $day.data("date").replace(" 00:00:00", "")
                };

            if (xhr) {
                xhr.abort();
            }

            xhr = $.post(href, data, function(response) {
                if (response.status == "ok") {
                    schedule.reload();
                }
            }, "json");
        }

        function onMove(event) {
            var wrapper_o = $wrapper.offset(),
                x = [wrapper_o.left, wrapper_o.left + $wrapper.outerWidth()],
                y = [wrapper_o.top, wrapper_o.top + $wrapper.outerHeight()];

            // clear
            schedule.clearSelection();
            //
            if (event.pageX <= x[0] || event.pageX >= x[1]) {
                return false;
            }
            if (event.pageY <= y[0] || event.pageY >= y[1]) {
                return false;
            }

            // marking
            var size = parseInt( ($event.data("day-count") || $event.closest(".t-column").attr("colspan") ) );
            schedule.start = schedule.getDayPosition(event);
            schedule.end = getEnd(schedule.daysArray, schedule.start, size);
            schedule.markDays();

            function getEnd(daysArray, start, size) {
                var result = {
                        week: start.week,
                        day: start.day
                    },
                    days_after_start =  (7 - start.day);

                size = size - 1; // today = start

                if (size <= days_after_start) {
                    result.day = result.day + size;
                } else {
                    result.week = result.week + 1;
                    result.day = (size - days_after_start);

                    if (result.week > daysArray.length) {
                        result = {
                            week: daysArray.length,
                            day: 7
                        }
                    }
                }

                return result;
            }
        }
    };

    TeamCalendar.prototype.markDays = function() {
        var that = this;

        //
        that.deselectDays();

        //
        var start = $.extend({}, that.start),
            end = $.extend({}, that.end);

        // Swap data in motion to the left
        if (start.week > end.week) {
            start = $.extend({}, that.end);
            end = $.extend({}, that.start);
        }

        var week_start = start.week,
            week_end = end.week;

        for ( var week_index = week_start; week_index <= week_end; week_index++ ) {
            var day_start, day_end;

            // If the movement within one week
            if (week_start == week_end) {
                day_start = start.day;
                day_end = end.day;

                // Swap data in motion to the left
                if (day_start > day_end) {
                    day_start = end.day;
                    day_end = start.day;
                }

                // If this initial week
            } else if (week_index == week_start) {
                day_start = start.day;
                day_end = that.days_count;

                // If this is the final week
            } else if (week_index == week_end) {
                day_start = 1;
                day_end = end.day;

                // If this intermediate week
            } else {
                day_start = 1;
                day_end = that.days_count;
            }

            // Day render
            for ( var day_index = day_start; day_index <= day_end; day_index++ ) {
                render(week_index, day_index);
            }
        }

        function render(week_index, day_index) {
            if ( !(week_index > 0 && day_index > 0) ) {
                return false;
            }

            var $day = that.daysArray[week_index - 1][day_index - 1];
            if ($day.length) {
                $day.addClass(that.selected_class);
                that.$selectedDays.push($day);
            } else {
                console.log("Error: Day isn't exist");
                return false;
            }
        }
    };

    TeamCalendar.prototype.getDayPosition = function( event ) {
        var that = this;

        that.wrapper_o = that.$wrapper.offset();
        that.wrapper_w = that.$wrapper.width();
        that.wrapper_h = that.$wrapper.height();
        that.day_w = that.wrapper_w/that.days_count;
        that.day_h = that.wrapper_h/that.weeks_count;

        var lift_offset = {
            top: (event.pageY - that.wrapper_o.top),
            left: (event.pageX - that.wrapper_o.left)
        };

        var week = parseInt(lift_offset.top/that.day_h) + ( (lift_offset.top % that.day_h > 0 ) ? 1 : 0 );
        var day = parseInt(lift_offset.left/that.day_w) + ( (lift_offset.left % that.day_w > 0 ) ? 1 : 0 );

        return {
            week: week,
            day: day
        }
    };

    TeamCalendar.prototype.deselectDays = function() {
        var that = this;

        if (that.$selectedDays.length) {
            $.each(that.$selectedDays, function() {
                $(this).removeClass(that.selected_class);
            });
            that.$selectedDays = [];
        }
    };

    TeamCalendar.prototype.clearSelection = function() {
        var that = this;
        that.deselectDays();
        that.start = false;
        that.end = false;
    };

    TeamCalendar.prototype.reload = function() {
        var that = this,
            href = "?module=schedule&action=inc",
            data = {};

        that.closeDialogs();

        if (that.selected_user_id) {
            data.user = that.selected_user_id;
        }

        if (that.selected_calendar_id) {
            data.calendar = that.selected_calendar_id;
        }

        if (that.period_start) {
            data.start = that.period_start;
        }

        if (that.period_end) {
            data.end = that.period_end;
        }

        if (that.is_profile) {
            data.period = 1;
        }

        $.get(href, data, function(html) {
            $.team.calendar = false;
            that.$wrapper.closest(".t-calendar-wrapper").replaceWith(html);
        });
    };

    TeamCalendar.prototype.addDialog = function( dialog ) {
        var that = this;
        that.dialogs.push(dialog);
    };

    TeamCalendar.prototype.closeDialogs = function() {
        var that = this,
            result = false;

        // Prev Dialog
        if (that.dialogs.length) {

            $.each(that.dialogs, function(index, dialog) {
                if ( $.contains(document, dialog.$wrapper[0]) ) {
                    dialog.close();
                    result = true;
                }
            });

            // or $.each()splice
            that.dialogs = [];
        }

        // DatePicker
        var $datePicker = $("#ui-datepicker-div");
        if ($datePicker.length) {
            $(document).trigger("mousedown")
        }

        return result;
    };

    return TeamCalendar;

    function getDays( $rows ) {
        var result = [];

        $rows.each( function() {
            var $row = $(this),
                row_result = [];

            $row.find(".t-day-ornament").each( function() {
                row_result.push( $(this) );
            });

            result.push(row_result);

        });

        return result;
    }

})(jQuery);

// Dialogs

// Team :: Calendar :: Day Dialog
var DayEventsDialog = ( function($) {

    DayEventsDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.event_view_uri = "?module=schedule&action=eventView";

        // DYNAMIC VARS
        that.dialog = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    DayEventsDialog.prototype.initClass = function() {
        var that = this;

        // Show event details
        that.$wrapper.on("click", ".t-event-block.js-view-event", function(event) {
            event.preventDefault();
            that.showEventDetails( $(this).closest(".t-event-wrapper") );
        });
    };

    DayEventsDialog.prototype.showEventDetails = function( $event ) {
        var that = this,
            event_id = $event.data("id"),
            data = {
                "data[id]": event_id
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post( that.event_view_uri, data, function(response) {
            that.dialog = new TeamDialog({
                html: response
            });
        });
    };

    return DayEventsDialog;

})(jQuery);

// Team :: Calendar :: Event Edit
var EventEditDialog = ( function($) {

    EventEditDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find("form");
        that.$statusToggle = that.$wrapper.find(".t-status-toggle");
        that.$calendarToggle = that.$wrapper.find(".t-calendar-toggle");
        that.$typeToggle = that.$wrapper.find(".t-type-toggle");
        that.$userToggle = that.$wrapper.find(".t-user-toggle");
        that.$startAltField = that.$wrapper.find("input[name='data[start_alt]']");
        that.$endAltField = that.$wrapper.find("input[name='data[end_alt]']");
        that.$summaryField = that.$form.find("input[name='data[summary]']");

        // VARS
        that.teamDialog = that.$wrapper.data("teamDialog");
        that.active_class = "is-active";
        that.extended_class = "is-extended";
        that.selected_class = "selected";
        that.has_error_class = "error";
        that.locales = options["locales"];
        that.event_id = options["event_id"];
        that.calendars = getCalendarsArray( that.$calendarToggle );
        that.users = getUsersArray( that.$userToggle );

        // DYNAMIC VARS
        that.$activeStatusToggle = ( that.$statusToggle.find("." + that.active_class) || false );
        that.is_status = options["is_status"];
        that.is_locked = false;
        that.is_changed = false;
        that.user_id = options["user_id"];
        that.calendar_id = options["calendar_id"];
        that.summary = options["summary"];
        that.summary_type = options["summary_type"];
        that.start_date_locale = false;
        that.end_date_locale = false;

        // INIT
        that.initClass();
    };

    EventEditDialog.prototype.initClass = function() {
        var that = this;
        //
        that.initDatePicker();
        //
        that.initTimePicker();
        //
        that.bindEvents();
        //
        that.setStatusLocale();

        if (that.is_status) {
            //
            that.generateStatusTypes();
            //
            that.generatePreview();
        }
    };

    EventEditDialog.prototype.bindEvents = function() {
        var that = this;

        that.$form.on("submit", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.save( that.$form );
            }
            return false;
        });

        that.$form.on("click", ".js-delete-event", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.showDeleteConfirm();
            }
        });

        that.$statusToggle.on("click", ".t-toggle-button", function() {
            that.changeStatus( $(this) );
            return false;
        });

        that.$calendarToggle.on("click", ".menu-v a", function(event) {
            event.preventDefault();
            that.changeCalendar( $(this) );
        });

        that.$startAltField.on("change", function() {
            that.setStatusLocale();
            that.generateStatusTypes();
            that.generatePreview();
        });

        that.$endAltField.on("change", function() {
            that.setStatusLocale();
            that.generateStatusTypes();
            that.generatePreview();
        });

        that.$summaryField.on("change", function() {
            that.summary = $(this).val();
            that.summary_type = "custom";
        });

        that.$form.on("change", "input[name='data[status_summary]']", function() {
            that.summary = $(this).val();
            that.$summaryField.val( that.summary );
        });

        that.$form.on("change", "textarea[name=\"data[description]\"]", function() {
            var value = $(this).val();
            that.$form.find("textarea[name=\"data[status_description]\"]").val(value);
        });

        that.$form.on("change", "textarea[name=\"data[status_description]\"]", function() {
            var value = $(this).val();
            that.$form.find("textarea[name=\"data[description]\"]").val(value);
        });

        that.$form.on("summaryChange", function() {
            that.$summaryField.val( that.summary );
        });

        if (that.$userToggle.length) {
            that.$userToggle.on("click", ".menu-v a", function(event) {
                event.preventDefault();
                that.changeUser( $(this) );
            });
        }

        that.$form.on("change", ".js-extended-date", function() {
            that.dateToggle( $(this) );
            return false;
        });

        // Remove errors hints
        var $fields = that.$form.find("input");
        $fields.on("mousedown", function() {
            var $field = $(this),
                has_error = $field.hasClass( that.has_error_class );

            if (has_error) {
                $field
                    .removeClass(that.has_error_class)
                    .closest(".value")
                        .find(".t-error").remove();
            }
        });
    };

    EventEditDialog.prototype.initDatePicker = function() {
        var that = this;

        var $datePickers = that.$wrapper.find(".js-datepicker");
        $datePickers.each( function() {
            var $input = $(this),
                $altField = $input.closest(".t-date-wrapper").find("input[type='hidden']");

            $input.datepicker({
                altField: $altField,
                altFormat: "yy-mm-dd",
                changeMonth : true,
                changeYear : true,
                shortYearCutoff: 2,
                showOtherMonths: true,
                selectOtherMonths: true,
                stepMonths: 2,
                numberOfMonths: 2,

                beforeShow: function(input, ui) {
                    ui.dpDiv.on("click", function(event) {
                        event.stopPropagation();
                    });
                    $(input).on("click", function(event) {
                        var is_date_picker_opened = isDatePickerOpened();
                        if (is_date_picker_opened) {
                            event.stopPropagation();
                            closeDatePicker();
                            $(this).blur();
                        }
                    });
                }
            });
        });

        function isDatePickerOpened() {
            var result = false,
                $datePicker =  $("#ui-datepicker-div");

            if ( $datePicker.length && !( $datePicker.css("display") == "none") ) {
                result = $datePicker;
            }

            return result;
        }

        function closeDatePicker() {
            $(document).trigger("mousedown");
        }
    };

    EventEditDialog.prototype.initTimePicker = function() {
        var that = this,
            $timePickers = that.$wrapper.find(".js-timepicker");

        $timePickers.each( function() {
            var $input = $(this),
                is_rendered = false;

            $input.timepicker();

            $input.on("showTimepicker", function() {
                var $timepicker = $input.data("timepicker-list");

                if (!is_rendered) {
                    $timepicker.on("click", function (event) {
                        event.stopPropagation();
                    });
                    is_rendered = true;
                }

                var top = ( parseInt($input.offset().top) + parseInt($input.outerHeight()) - parseInt($(window).scrollTop()) );

                $timepicker.css({
                    "position": "fixed",
                    "top": top
                })
            });
        });
    };

    EventEditDialog.prototype.showDeleteConfirm = function() {
        var that = this,
            href = "?module=schedule&action=eventDeleteConfirm",
            data = {
                id: that.event_id
            };

        that.is_locked = true;

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.get(href, data, function(html) {
            new TeamDialog({
                html: html
            });

            that.is_locked = false;

            that.close();
        });
    };

    EventEditDialog.prototype.changeStatus = function( $toggle ) {
        var that = this,
            is_active = $toggle.hasClass(that.active_class);

        if (!is_active) {
            var status = $toggle.data("status-id");

            if (that.$activeStatusToggle) {
                that.$activeStatusToggle.removeClass(that.active_class);
            }

            // Marking
            $toggle.addClass(that.active_class);
            that.$activeStatusToggle = $toggle;

            that.$form.find('input[name="data[is_status]"]')
                .val(status)
                .trigger("change");

            // Show/Hide status fields
            var is_status = (status == 1),
                event_class = "is-event",
                status_class = "is-status";

            that.is_status = is_status;

            if (!is_status) {
                that.$wrapper.removeClass(status_class).addClass(event_class);

            } else {
                that.$wrapper.removeClass(event_class).addClass(status_class);

                // For status is_allday always true
                var $input = that.$form.find(".js-extended-date"),
                    is_all_day = ( $input.attr("checked") == "checked" );

                if (!is_all_day) {
                    $input.click();
                }
            }

            // Generate preview
            that.generateStatusTypes();
            //
            that.generatePreview();

            // Resize
            that.teamDialog.resize();
        }
    };

    EventEditDialog.prototype.changeCalendar = function( $link ) {
        var that = this,
            $li = $link.closest("li"),
            calendar_id = $li.data("calendar-id"),
            is_active = $li.hasClass(that.selected_class);

        if (!is_active && !that.is_locked) {
            that.is_locked = true;
            //
            that.calendar_id = calendar_id;
            // unset selected
            that.$calendarToggle.find("." + that.selected_class).removeClass(that.selected_class);
            // set selection
            that.$calendarToggle.find(".t-selected-item").html( $link.html() );
            // set data
            that.$form.find('input[name="data[calendar_id]"]')
                .val(calendar_id)
                .trigger("change");

            // render
            var $menu = that.$calendarToggle.find(".menu-v");
            $menu.hide();

            setTimeout( function() {
                $menu.removeAttr("style");
                that.is_locked = false;
            }, 500);

            //
            that.generateStatusTypes();
            //
            that.generatePreview();
        }
    };

    EventEditDialog.prototype.changeUser = function( $link ) {
        var that = this,
            $li = $link.closest("li"),
            user_id = $li.data("user-id"),
            is_active = $li.hasClass(that.selected_class);

        if (!is_active && !that.is_locked) {
            that.is_locked = true;
            //
            that.user_id = user_id;
            // unset selected
            that.$userToggle.find("." + that.selected_class).removeClass(that.selected_class);
            // set selection
            that.$userToggle.find(".t-selected-item").html( $link.html() );
            // set data
            that.$form.find('input[name="data[contact_id]"]')
                .val(user_id)
                .trigger("change");
            // render
            var $menu = that.$userToggle.find(".menu-v");
            $menu.hide();

            setTimeout( function() {
                $menu.removeAttr("style");
                that.is_locked = false;
            }, 200);

            that.generatePreview();
        }
    };

    EventEditDialog.prototype.save = function( $form ) {
        var that = this,
            data = prepareData( $form.serializeArray() ),
            href = "?module=schedule&action=eventSave";

        that.is_locked = true;

        if (data) {
            $.post(href, data, function(response) {

                if (response.status == "ok") {
                    if (response.data && response.data.message) {
                        alert(response.data.message);
                    }
                    that.reloadCalendar();
                } else if (response.errors) {
                    var redirect = null;
                    $.each(response.errors, function (i, error) {
                        if (error[0] === 'redirect') {
                            redirect = error[1];
                        }
                    });
                    redirect && (location.href = redirect);
                }

                that.close();

                that.is_locked = false;
            }, "json");
        } else {
            that.is_locked = false;
        }

        function prepareData(data) {
            var result = {},
                errors = [];

            $.each(data, function(index, item) {
                result[item.name] = item.value;
            });

            if (!result["data[summary_type]"]) {
                result["data[summary_type]"] = "custom";
            }

            var is_status = that.is_status;
            if (is_status) {
                result["data[description]"] = ( result["data[status_description]"] ) ? result["data[status_description]"] : "";
                delete result["data[status_description]"];

                result["data[summary]"] = that.summary;
                delete result["data[status_summary]"];
            }

            if (!$.trim(result["data[summary]"]).length) {
                if (is_status) {
                    errors.push({
                        field: "data[status_summary]",
                        locale: "empty"
                    });
                } else {
                    errors.push({
                        field: "data[summary]",
                        locale: "empty"
                    });
                }
            }

            if (!result["data[start]"].match(/^(\d){4}-(\d){2}-(\d){2}$/)) {
                errors.push({
                    field: "data[end]",
                    locale: "date"
                });
            }

            if (!result["data[end]"].match(/^(\d){4}-(\d){2}-(\d){2}$/)) {
                errors.push({
                    field: "data[end]",
                    locale: "date"
                });
            }

            var startDate = getDate( result["data[start]"] );
            var endDate = getDate( result["data[end]"] );
            if (startDate > endDate) {
                errors.push({
                    field: "data[start_alt]",
                    locale: "period"
                });
                errors.push({
                    field: "data[end_alt]",
                    locale: "period"
                });
            }

            if (errors.length) {
                showErrors(errors);
                return false;
            }

            if (!result["data[start_time]"].length) {
                result["data[start_time]"] = "00:00"
            }

            if (!result["data[end_time]"].length) {
                result["data[end_time]"] = "00:00"
            }

            result["data[start]"] = result["data[start]"] + " " + result["data[start_time]"];
            result["data[end]"] = result["data[end]"] + " " + result["data[end_time]"];
            result["data[is_allday]"] = ( result["data[is_allday]"] ) ? 1 : 0;

            delete result["data[start_time]"];
            delete result["data[end_time]"];
            delete result["data[start_alt]"];
            delete result["data[end_alt]"];

            return result;

            function getDate( string ) {
                var dateArray = string.split("-"),
                    year = parseInt(dateArray[0]),
                    month = parseInt(dateArray[1]),
                    day = parseInt(dateArray[2]);

                if (year > 0 && month > 0 && day > 0) {
                    return new Date(year, (month - 1), day);
                } else {
                    return false;
                }
            }

            function showErrors( errors ) {
                // Remove old errors
                that.$form.find(".t-error").remove();

                // Display new errors
                $.each(errors, function(index, item) {
                    var $field = that.$form.find("[name='" + item.field + "']");
                    if ($field.length) {
                        $field
                            .addClass(that.has_error_class)
                            .after('<span class="t-error">' + that.locales[item.locale] + '</span>')
                    }
                });
            }
        }
    };

    EventEditDialog.prototype.reloadCalendar = function() {
        var that = this;

        // $.team.content.reload();
        $.team.calendar.reload();
    };

    EventEditDialog.prototype.dateToggle = function( $toggle ) {
        var that = this,
            is_active = ( $toggle.attr("checked") == "checked" ),
            $dateFields = that.$form.find(".t-date-wrapper");

        if (!is_active) {
            $dateFields.addClass(that.extended_class);
        } else {
            $dateFields.removeClass(that.extended_class);
        }
    };

    EventEditDialog.prototype.generatePreview = function() {
        var that = this,
            $preview = that.$wrapper.find(".t-preview-block");

        var calendar = that.calendars[that.calendar_id],
            bg_color = calendar["bg_color"],
            font_color = calendar["font_color"];

        var user = that.users[that.user_id],
            $userIcon = user.$icon.clone(),
            user_name = user.name;

        var $type = $("<span class='t-type'>" + ( that.summary ? that.summary : that.locales.empty_type ) + "</span>").css({
            "background": bg_color,
            "color": font_color
        });

        $preview.html("")
            .append($userIcon)
            .append("<span class='t-user-name'>" + user_name + "</span>")
            .append( $type );
    };

    EventEditDialog.prototype.generateStatusTypes = function() {
        var that = this,
            $typeToggle = that.$typeToggle;

        // render
        var $wrapper = renderList();

        // events
        setBinds( $wrapper );

        // mark selected items
        setActive( $wrapper );

        function renderList() {
            var $templateItems = $typeToggle.find(".is-template li"),
                $text = $templateItems.eq(0),
                $field = $templateItems.eq(1);

            var $list = $("<ul class='menu-v'></ul>"),
                calendar = that.calendars[that.calendar_id];

            if (calendar.default_status) {
                // 2
                var $li2 = $text.clone();
                $li2.find(".t-type").text(calendar.default_status);
                $li2.find("input:radio").val("default");
                $list.append($li2);
            }

            // 1
            var $li1 = $text.clone();
            $li1.find(".t-type").text(calendar.name);
            $li1.find("input:radio").val("calendar");
            $list.append($li1);

            // 3
            var $li3 = $text.clone();
            $li3.find(".t-type").text( ( calendar.default_status ? calendar.default_status : calendar.name ) + " " + that.locales.till + " " + that.end_date_locale);
            $li3.find("input:radio").val("till");
            $list.append($li3);

            // 5
            var $li5 = $text.clone();
            $li5.find(".t-type").text( ( calendar.default_status ? calendar.default_status : calendar.name ) + " " + that.locales.from + " " + that.start_date_locale + " " + that.locales.till + " " + that.end_date_locale);
            $li5.find("input:radio").val("interval");
            $list.append($li5);

            // 4
            var $li4 = $field.clone();
            $li4.find("input:radio").val("custom");
            if (that.summary) {
                $li4.find("input:text").val( that.summary );
            }
            $list.append( $li4 );

            // final
            $typeToggle.find(".value").html("").append( $list );

            return $list;
        }

        function setBinds( $wrapper ) {

            $wrapper.on("change", "input:radio", function() {
                var $input = $(this),
                    is_checked = ( $input.attr("checked") == "checked" ),
                    $text = $input.closest("li").find(".t-type"),
                    $textInput = $input.closest("li").find("input:text");

                if (is_checked) {
                    if ($text.length) {
                        that.summary = $text.text();
                    } else if ($textInput.length) {
                        that.summary = $textInput.val();
                    } else {
                        return false;
                    }
                    that.summary_type = $input.val();
                    that.$form.trigger("summaryChange");
                    //
                    that.generatePreview();
                }
            });

            $wrapper.on("focus set", "input:text", function() {
                var $input = $(this);

                // set active
                var $radio = $input.closest("li").find("input:radio");
                if ($radio.attr("checked") != "checked") {
                    $radio.attr("checked", "checked").trigger("change");
                }

                if ( $input.hasClass( that.has_error_class ) ) {
                    $input
                        .removeClass(that.has_error_class)
                        .closest(".value")
                        .find(".t-error").remove();
                }
            });

            $wrapper.on("focus change keyup", "input:text", function() {
                var $input = $(this),
                    value = $input.val();
                that.summary = $("<div />").html( $input.val() ).text();

                if (value.length) {
                    that.summary_type = "custom";
                    that.summary = value;
                }

                that.generatePreview();
            });

        }

        function setActive( $wrapper ) {
            // case 1
            if (!that.summary_type && that.summary) {
                that.summary_type = "custom";
            }

            // case 2
            if (that.summary_type) {
                var $active = $wrapper.find("input:radio[value='" + that.summary_type + "']");

                // case 3
                if ($active.length) {
                    $active.click();

                    if (that.summary_type !== "custom") {
                        $wrapper.find("input:text").val("");
                    }

                // case 4
                } else if (that.summary_type == "default") {
                    $wrapper.find("input[value='calendar']").click();

                // case 5
                } else {
                    $wrapper.find("input:text").val( (that.summary ? that.summary : "" ) ).trigger("change");
                }

            // case 3
            } else {
                $wrapper.find("input:radio:first").click();
            }
        }

    };

    EventEditDialog.prototype.setStatusLocale = function() {
        var that = this,
            $startField = that.$wrapper.find("input[name='data[start]']"),
            $endField = that.$wrapper.find("input[name='data[end]']");

        that.start_date_locale = getLocale($startField);
        that.end_date_locale = getLocale($endField);

        function getLocale( $field ) {
            var value = $field.val().split(" ")[0],
                date_array = value.split("-"),
                day = parseInt(date_array[2]),
                month = parseInt(date_array[1]),
                year = parseInt(date_array[0]),
                current_month = ( new Date() ).getFullYear();

            var format = that.locales.status_type.format,
                month_locale = that.locales.status_type.months[month],
                datepicker_locale = $.datepicker.formatDate(format, new Date(year,month-1,day));

            datepicker_locale = datepicker_locale.replace("f", month_locale);

            if ( current_month == year) {
                datepicker_locale = datepicker_locale
                    .replace(", " + year, "")
                    .replace(" " + year, "")
                    .replace(year + " ", "");
            }

            return datepicker_locale;
        }
    };

    EventEditDialog.prototype.close = function() {
        var that = this;

        that.$wrapper.trigger("close");
    };

    return EventEditDialog;

    function getCalendarsArray($wrapper) {
        var result = {};

        $wrapper.find(".menu-v li").each( function() {
            var $li = $(this),
                $icon = $li.find(".userpic20"),
                id = $li.data("calendar-id");

            result[id] = {
                id: id,
                default_status: $li.data("default-status"),
                name: $.trim( $("<div />").html( $li.text() ).text() ).toLowerCase(),
                font_color: $icon.css("color"),
                bg_color: $icon.css("background-color")
            };

        });

        return result;
    }

    function getUsersArray($wrapper) {
        var result = {};

        $wrapper.find(".menu-v li").each( function() {
            var $li = $(this),
                $icon = $li.find(".userpic20"),
                id = $li.data("user-id");

            result[id] = {
                id: id,
                $icon: $icon,
                name: $.trim( $("<div />").html( $li.text() ).text() )
            };

        });

        return result;
    }

})(jQuery);

// Team :: Calendar :: Event View
var EventViewDialog = ( function($) {

    EventViewDialog = function(options) {
        var that = this;

        // DOM
        that.$dialogWrapper = options["$wrapper"];
        that.$wrapper = that.$dialogWrapper.find(".t-dialog-block");

        // VARS
        that.event_id = options["event_id"];
        that.event_edit_uri = "?module=schedule&action=eventEdit";

        // DYNAMIC VARS
        that.xhr = false;

        // INIT
        that.initClass();
    };

    EventViewDialog.prototype.initClass = function() {
        var that = this;

        that.$wrapper.on("click", ".js-edit-event", function(event) {
            event.preventDefault();
            that.showEditForm();
        });

    };

    EventViewDialog.prototype.showEditForm = function() {
        var that = this,
            data = {
                "data[id]": that.event_id
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post( that.event_edit_uri, data, function(response) {
            var position = that.$wrapper.offset(),
                calendar = $.team.calendar;

            that.close();

            var dialog = new TeamDialog({
                html: response
            });
            calendar.addDialog(dialog);
        });
    };

    EventViewDialog.prototype.close = function() {
        var that = this;

        that.$wrapper.trigger("close");
    };

    return EventViewDialog;

})(jQuery);

// Team :: Calendar :: Delete
var EventDeleteDialog = ( function($) {

    EventDeleteDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".t-dialog-block");

        // VARS
        that.event_id = options["event_id"];

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    EventDeleteDialog.prototype.initClass = function() {
        var that = this;

        that.$block.on("click", ".js-delete-event", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that['delete']();
            }
        });
    };

    EventDeleteDialog.prototype['delete'] = function() {
        var that = this,
            href = "?module=schedule&action=eventDelete",
            data = {
                id: that.event_id
            };

        that.is_locked = true;

        $.post(href, data, function(response) {
            if (response.status == "ok") {
                if (response.data && response.data.message) {
                    alert(response.data.message);
                }
                that.$wrapper.trigger("close");
                $.team.calendar.reload();
            }
        });
    };

    return EventDeleteDialog;

})(jQuery);
