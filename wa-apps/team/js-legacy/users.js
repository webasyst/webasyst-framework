var UserList = ( function($) {

    UserList = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$items = that.$wrapper.find(".t-user-wrapper");

        // VARS

        // DYNAMIC VARS
        that.is_locked = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    UserList.prototype.initClass = function() {
        var that = this;
        //
        that.initHightlight();
        //
        that.bindEvents();
    };

    UserList.prototype.bindEvents = function() {
        var that = this,
            $dropZone = false,
            drop_class = "t-drop-here";

        that.$wrapper.find(".js-move-user").draggable({
            helper: "clone",
            delay: 200,
            cursorAt: {
                top: 10,
                left: 20
            },
            start: function(event, ui) {
                ui.helper.addClass("is-clone");

                $dropZone = $.team.sidebar.$wrapper.find(".js-drop-block");
                if ($dropZone.length) {
                    $dropZone.addClass(drop_class);
                }
            },
            stop: function(event, ui) {
                var $helper = ui.helper,
                    $clone = $helper.clone(),
                    time = 300;

                $clone
                    .insertAfter( $helper )
                    .fadeOut( time * .9 );

                setTimeout( function() {
                    $clone.remove()
                }, time);

                if ($dropZone.length) {
                    $dropZone.removeClass(drop_class);
                }
            }
        });
    };

    UserList.prototype.initHightlight = function() {
        var that = this,
            updateDate = $.team.sidebar.link_count_update_date;

        if (updateDate) {
            updateDate = getDate( updateDate );

            that.$items.each( function() {
                var $item = $(this),
                    item_date = $item.data("update-datetime");

                if (item_date && item_date.length) {
                    var itemDate = getDate( getDateArray( item_date ) );
                    if (itemDate > updateDate) {
                        $item.addClass("highlighted");
                    }
                }
            });
        }

        function getDateArray( string ) {
            var parts = string.split(" "),
                part1 = parts[0].split("-"),
                part2 = parts[1].split(":");

            return {
                "year": parseInt(part1[0]),
                "month": parseInt(part1[1]),
                "day": parseInt(part1[2]),
                "hours": parseInt(part2[0]),
                "minutes": parseInt(part2[1]),
                "seconds": parseInt(part2[2])
            };
        }

        function getDate( array ) {
            return new Date(array.year, (array.month - 1), array.day, array.hours, array.minutes, array.seconds);
        }
    };

    return UserList;

})(jQuery);