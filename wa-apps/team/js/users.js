var UserList = ( function($) {

    UserList = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$items = that.$wrapper.find('.t-user-wrapper');
        that.$draggableItems = that.$wrapper.find('.js-move-user');

        // INIT
        that.initClass();
    }

    UserList.prototype.initClass = function() {
        const that = this;

        that.initHightlight();
        that.initDraggable();
    }

    UserList.prototype.initDraggable = function() {
        const that = this;

        let $dropZone;
        const drop_class = 't-drop-here';

        // blur editable name & description
        $('.js-move-user').mousedown(function(){
            document.activeElement.blur();
        });

        that.$draggableItems.draggable({
            helper: 'clone',
            delay: 200,
            handle: '.userpic, .details',
            appendTo: document.body,
            start: function(event, ui) {
                ui.helper
                    .addClass('is-clone align-center list')
                    .find(' > *:not(.userpic, .image)')
                    .addClass('hidden')
                    .end()
                    .find(' > a')
                    .css({
                        'border': '0.1875rem solid var(--thumbs-highlighted-color)',
                        'border-radius': '50%',
                        'display': 'inline-block'
                    })
                ;

                $dropZone = $.team.sidebar.$wrapper.find('.js-drop-block');
                if ($dropZone) {
                    $dropZone.addClass(drop_class);
                }
            },
            stop: function(event, ui) {
                const $helper = ui.helper;
                const $clone = $helper.clone();
                const time = 300;

                $clone.insertAfter($helper).fadeOut(time * .9);

                setTimeout( function() {
                    $clone.remove();
                }, time);

                if ($dropZone) {
                    $dropZone.removeClass(drop_class);
                }
            },
        });
    }

    UserList.prototype.initHightlight = function() {
        const that = this;
        let updateDate = $.team.sidebar.options.link_count_update_date;

        if (!updateDate) {
            return;
        }

        updateDate = getDate(updateDate);

        that.$items.each(function() {
            const $item = $(this);
            const item_date = $item.data('update-datetime');

            if (item_date && item_date.length) {
                const itemDate = getDate(getDateArray(item_date));

                if (itemDate > updateDate) {
                    $item.find('.userpic').addClass('t-users-avatar-highlight');
                }
            }
        });

        function getDateArray(string) {
            const parts = string.split(' ');
            const part1 = parts[0].split('-');
            const part2 = parts[1].split(':');

            return {
                "year": parseInt(part1[0]),
                "month": parseInt(part1[1]),
                "day": parseInt(part1[2]),
                "hours": parseInt(part2[0]),
                "minutes": parseInt(part2[1]),
                "seconds": parseInt(part2[2])
            };
        }

        function getDate(array) {
            return new Date(array.year, (array.month - 1), array.day, array.hours, array.minutes, array.seconds);
        }
    }

    return UserList;

})(jQuery);
