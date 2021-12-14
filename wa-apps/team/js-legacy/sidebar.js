// Team :: Sidebar
// Initialized in templates/actions/Sidebar.html
var Sidebar = ( function($) {

    var ElasticBlock = ( function() {

        ElasticBlock = function(options) {
            var that = this;

            // DOM
            that.$window = $(window);
            that.$wrapper = options["$wrapper"];
            that.$block = options["$block"];
            that.$content = options["$content"];

            // VARS
            that.top_fix_class = "fixed-to-top";
            that.bottom_fix_class = "fixed-to-bottom";

            // DYNAMIC VARS
            that.debug = false;

            // INIT
            that.initClass();
        };

        ElasticBlock.prototype.log = function( string ) {
            var that = this;
            if (that.debug) {
                console.log( string );
            }
        };

        ElasticBlock.prototype.initClass = function() {
            var that = this;

            // Class names
            var top_fix_class = that.top_fix_class,
                bottom_fix_class = that.bottom_fix_class;

            // DOM
            var $window = that.$window,
                $wrapper = that.$wrapper,
                $block = that.$block;

            // VARS
            var display_width = Math.floor( $window.width() ),
                display_height = Math.floor( $window.height() ),
                block_top = $block.offset().top,
                wrapper_margin_top = 0,
                set_force = true;

            // DYNAMIC VARS
            var is_top_set = false,
                is_fixed_to_bottom = false,
                is_fixed_to_top = false,
                is_fixed_top_set = false,
                scroll_value = 0,
                content_height,
                block_width;

            $window
                .on("scroll", setScrollWatcher)
                .on("resize", setResizeWatcher);

            function setScrollWatcher() {
                if ($.contains(document, $block[0])) {
                    onScroll();
                } else {
                    unsetScrollWatcher();
                }
            }

            function setResizeWatcher() {
                if ($.contains(document, $block[0])) {
                    onResize();
                } else {
                    unsetResizeWatcher();
                }
            }

            function unsetScrollWatcher() {
                $window.off("scroll", setScrollWatcher);
            }

            function unsetResizeWatcher() {
                $window.off("scroll", setResizeWatcher);
            }

            function setTop( top ) {
                that.log("Manual top scroll position");

                $block
                    .css("top", top)
                    .width(block_width)
                    .removeClass(top_fix_class)
                    .removeClass(bottom_fix_class);

                is_top_set = true;
                is_fixed_to_top = is_fixed_to_bottom = is_fixed_top_set = false;
            }

            function setFixTop( top ) {
                that.log("Fixed to top scroll position");

                $block
                    .removeAttr("style")
                    .width(block_width)
                    .removeClass(bottom_fix_class)
                    .addClass(top_fix_class);

                if (top) {
                    is_fixed_top_set = true;
                    $block.css("top", top);
                }

                is_top_set = is_fixed_to_bottom = false;
                is_fixed_to_top = true;
            }

            function setFixBottom() {
                that.log("Fixed to bottom scroll position");

                $block
                    .removeAttr("style")
                    .width(block_width)
                    .removeClass(top_fix_class)
                    .addClass(bottom_fix_class);

                is_top_set = is_fixed_to_top = is_fixed_top_set = false;
                is_fixed_to_bottom = true;
            }

            function setDefault() {
                that.log("Default scroll position");

                $block
                    .removeAttr("style")
                    .removeClass(bottom_fix_class)
                    .removeClass(top_fix_class);

                is_top_set = is_fixed_to_top = is_fixed_to_bottom = is_fixed_top_set = false;
            }

            function onScroll() {
                var content_height = Math.floor( that.$content.outerHeight() ),
                    block_height = Math.floor( $block.outerHeight() ),
                    wrapper_height = Math.floor( $wrapper.height() ),
                    scroll_top = $window.scrollTop(),
                    dynamic_block_top = Math.floor( $block.offset().top ),
                    direction = ( scroll_value > scroll_top ) ? 1 : -1,
                    delta = scroll_top - block_top,
                    min_width = 760;

                block_width = $block.width();

                var active_scroll = ( !set_force && wrapper_height > display_height && display_width >= min_width && !(content_height && content_height < block_height));

                if (!active_scroll) {
                    if (set_force) {
                        setForceTop(scroll_top, block_height);
                    } else {
                        setDefault();
                        // unsetScrollWatcher();
                        // unsetResizeWatcher();
                    }
                } else {

                    var is_display_longer_block = ( display_height > block_height + wrapper_margin_top ),
                        is_above_block = (scroll_top <= block_top),
                        my_case = parseInt(dynamic_block_top + block_height - scroll_top - display_height),
                        is_middle_of_block = ( my_case > 0 ),
                        is_bottom_of_block = ( my_case <= 0 );

                    // If the height of the slider is smaller than the display, it's simple
                    if (is_display_longer_block) {

                        if (delta + wrapper_margin_top > 0) {
                            if (is_top_set || is_fixed_to_bottom || !is_fixed_to_top) {
                                setFixTop( wrapper_margin_top );
                            }
                        } else {
                            if (is_top_set || is_fixed_to_top || is_fixed_to_bottom || is_fixed_top_set) {
                                setDefault();
                            }
                        }

                        // If the height is larger than the screen
                    } else {

                        // If less than the original position to turn off
                        if (is_above_block) {
                            // that.log( 0 );
                            if (is_top_set || is_fixed_to_bottom || is_fixed_to_top) {
                                if (is_fixed_top_set) {
                                    var use_default = (dynamic_block_top <= block_top);
                                    if (use_default) {
                                        setDefault();
                                    }
                                } else {
                                    setDefault();
                                }
                            }

                            // If the above start after scrolling fix up
                        } else if (is_middle_of_block) {

                            if (direction > 0) {
                                var set_fix_top = (dynamic_block_top >= (wrapper_margin_top + scroll_top) );
                                if (set_fix_top && ( is_top_set || !is_fixed_to_top || is_fixed_to_bottom ) ) {
                                    if (wrapper_margin_top) {
                                        setFixTop( wrapper_margin_top );
                                    } else {
                                        setFixTop();
                                    }
                                }
                            } else {
                                if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                    setTop( dynamic_block_top - block_top );
                                }
                            }

                            // If the lower end
                        } else if (is_bottom_of_block) {
                            // If the direction of scrolling up
                            if (direction > 0) {
                                if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                    setTop( dynamic_block_top - block_top );
                                }

                                // If the direction of scrolling down
                            } else {
                                if (is_top_set || is_fixed_to_top || !is_fixed_to_bottom) {
                                    setFixBottom();
                                }
                            }
                            // In all other cases
                        } else {
                            if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                setTop( dynamic_block_top - block_top );
                            }
                        }

                    }
                }

                // Save New Data
                scroll_value = scroll_top;
            }

            function setForceTop(scroll_top, block_height) {
                var wrapper_height = Math.floor( $wrapper.height() ),
                    wrapper_top = Math.floor( $wrapper.offset().top ),
                    space_after = wrapper_height + wrapper_top - display_height - scroll_top,
                    hidden_block_part = block_height - display_height;

                set_force = false;

                var use_force = ( wrapper_height > block_height && scroll_top > block_top);

                if (use_force) {
                    if (hidden_block_part < space_after) {
                        setFixTop( wrapper_margin_top );
                    } else {
                        setFixBottom();
                    }
                }
            }

            function onResize() {
                display_width = Math.floor( $window.width() );
                display_height = Math.floor( $window.height() );
                setDefault();
                $window.trigger("scroll");
            }
        };

        return ElasticBlock;

    })();

    //

    Sidebar = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$groupsWrapper = that.$wrapper.find(".t-groups-list");
        that.$groups = that.$groupsWrapper.find("> li");
        that.$locationsWrapper = that.$wrapper.find(".t-locations-list");
        that.$locations = that.$locationsWrapper.find("> li");
        that.$searchForm = that.$wrapper.find(".t-search-form");

        // VARS
        that.app_url = options["app_url"];
        that.selected_class = "selected";
        that.storage_count_name = "team/sidebar_counts";
        that.can_sort = options["can_sort"];

        // DYNAMIC VARS
        that.link_count_update_date = false;
        that.$activeMenuItem = ( that.$wrapper.find("li." + that.selected_class + ":first") || false );
        that.counters = {};
        that.storageCount = false;
        that.is_locked = false;
        that.xhr = false;
        that.timer = 0;

        // INIT
        that.initClass();
    };

    Sidebar.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        that.setCounts();
        //
        that.initElasticBlock();
        //
        that.initUpdater();
        //
        if (that.can_sort) {
            that.initSortable();
        }
        //
        that.initDroppable();
        //
        if (!that.$activeMenuItem.length) {
            that.selectLink();
        }
    };

    Sidebar.prototype.bindEvents = function() {
        var that = this;

        // Change
        that.$wrapper.on("click", "li > a", function(event) {
            that.onLinkClick( $(this) );
        });

        that.$wrapper.on("click", ".js-add-user-group", function(event) {
            event.preventDefault();
            that.showGroupDialog();
        });

        that.$wrapper.on("click", ".js-add-user-location", function(event) {
            event.preventDefault();
            that.showGroupDialog( true );
        });

        that.$searchForm.on("submit", function(event) {
            event.preventDefault();
            var search_string = $.trim( that.$searchForm.find(".t-search-field").val() );
            if (search_string) {
                that.showSearch(search_string);
            }
        });

        $('#t-new-user-link').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            that.showInviteDialog();
        });
    };

    Sidebar.prototype.onLinkClick = function( $link ) {
        var that = this,
            uri = $link.attr("href");

        if (uri && uri.substr(0, 11) != 'javascript:' && !$link.hasClass('js-no-highlight')) {
            that.setItem( $link.closest("li") );
            $.team.setTitle( $link.text() );
        }
    };

    Sidebar.prototype.setItem = function( $item ) {
        var that = this;

        if (that.$activeMenuItem && that.$activeMenuItem[0] == $item[0]) {
            return false;
        }

        if (that.$activeMenuItem) {
            that.$activeMenuItem.removeClass(that.selected_class);
        }

        $item.addClass(that.selected_class);
        that.$activeMenuItem = $item;
    };

    Sidebar.prototype.selectLink = function( uri ) {
        var that = this,
            $link;

        if (uri) {
            $link = that.$wrapper.find('a[href="' + uri + '"]:first');

        } else if ( uri === false ) {
            if (that.$activeMenuItem) {
                that.$activeMenuItem.removeClass(that.selected_class);
                that.$activeMenuItem = null;
            }
            return false;
        }

        if ($link && $link.length) {
            that.setItem( $link.closest("li") );

        } else {
            var $links = that.$wrapper.find("a[href^='" + that.app_url + "']"),
                location_string = location.pathname,
                max_length = 0,
                link_index = 0;

            $links.each( function(index) {
                var $link = $(this),
                    href = $link.attr("href"),
                    href_length = href.length;

                if (location_string.indexOf(href) >= 0) {
                    if ( href_length > max_length ) {
                        max_length = href_length;
                        link_index = index;
                    }
                }
            });

            if (link_index || link_index === 0) {
                $link = $links.eq(link_index);
                that.setItem( $link.closest("li") );
            }
        }
    };

    Sidebar.prototype.reload = function() {
        var that = this,
            app_url = that.app_url,
            sidebar_uri = app_url + "?module=sidebar";

        clearTimeout(that.timer);

        if (that.xhr) {
            that.xhr.abort();
        }

        that.xhr = $.get(sidebar_uri, function(html) {
            that.xhr = false;
            that.$wrapper.replaceWith(html);
        });
    };

    Sidebar.prototype.showInviteDialog = function() {
        var that = this;
        if (!that.is_locked) {
            that.is_locked = true;
            $.get($.team.app_url + '?module=users&action=inviteform', function(response) {
                that.is_locked = false;
                new TeamDialog({
                    html: response
                });
            });
        }
    };

    Sidebar.prototype.showGroupDialog = function( is_location ) {
        var that = this,
            href = $.team.app_url + "?module=group&action=edit",
            data = {
                type: (is_location) ? "location" : "group"
            };

        if (!that.is_locked) {
            that.is_locked = true;

            $.get(href, data, function (response) {
                new TeamDialog({
                    html: response
                });
                that.is_locked = false;
            });
        }
    };

    // set counts
    Sidebar.prototype.setCounts = function() {
        var that = this,
            currentCount = getCountArray(),
            storageCount = getStorage( that.storage_count_name );

        that.storageCount = $.extend(true, {}, currentCount);

        if (storageCount) {
            $.each(storageCount, function(href, item) {
                var is_exist = ( href in currentCount),
                    is_number = ( is_exist && currentCount[href].count >= 0 ),
                    is_changed = ( is_number && currentCount[href].count != item.count ),
                    is_item_exist = ( item.count >= 0 );

                if (is_item_exist && is_changed) {
                    that.storageCount[href].count = item.count;
                    that.storageCount[href].date = item.date;
                    showCounter(href, currentCount[href].count, item.count, item.date);
                }
            });
        }

        that.setStorage();

        function showCounter( href, new_count, old_count, date ) {
            var is_good_href = ( href.indexOf( that.app_url ) >= 0 );
            if (is_good_href) {
                var $link = that.$wrapper.find('a[href="'+ href + '"]');
                if ($link.length) {
                    var delta_count = new_count - old_count,
                        $counter = $('<strong class="small highlighted t-indicator ' + ( (delta_count >= 0) ? 'is-green' : 'is-red' ) + '">' + delta_count + '</strong>');

                    if (delta_count >= 0) {
                        $link.append( $counter );
                        $link.one("click", function(event) {
                            event.preventDefault();
                            $counter.remove();
                            that.saveCount(href, new_count);
                            that.link_count_update_date = date;

                            $(document).one("wa_loaded", function() {
                                that.link_count_update_date = false;
                            });
                        });
                    }
                }
            }
        }

        function getStorage(storage_name) {
            var result = {},
                storage = localStorage.getItem(storage_name);

            if (storage) {
                result = JSON.parse(storage);
            }

            return result;
        }

        function getCountArray() {
            var result = {},
                $counts = that.$wrapper.find("li .js-count"),
                current_date = getDate();

            $counts.each( function() {
                var $count = $(this),
                    count = parseInt( $count.text() ),
                    $li = $count.closest("li"),
                    $link = $li.find("> a");

                if (count >= 0) {
                    that.counters[ $link.attr("href") ] = $count;
                    result[ $link.attr("href") ] = {
                        count: count,
                        date: current_date
                    }
                }
            });

            return result;
        }

        function getDate() {
            var date = new Date(),
                day = parseInt( date.getUTCDate() ),
                month = parseInt( date.getUTCMonth() ) + 1,
                year = parseInt( date.getUTCFullYear() ),
                hours = parseInt( date.getUTCHours() ),
                minutes = parseInt( date.getUTCMinutes() ),
                seconds = parseInt( date.getUTCSeconds() );

            return result = {
                    "year": year,
                    "month": month,
                    "day": day,
                    "hours": hours,
                    "minutes": minutes,
                    "seconds": seconds
                };
        }
    };

    // update count in class
    Sidebar.prototype.saveCount = function(href, count) {
        var that = this;
        if (href in that.storageCount) {
            that.storageCount[href].count = count;
        }
        that.setStorage();
    };

    // render count in dom
    Sidebar.prototype.updateCount = function(href, count) {
        var that = this,
            $counter;

        if ( !(href && (count || count === 0) ) ) {
            return false;
        }

        $counter = ( that.counters[href] || false );

        that.saveCount(href, count);

        if ($counter.length) {
            $counter.text(count);
        } else {
            that.reload();
        }
    };

    // save to local storage
    Sidebar.prototype.setStorage = function() {
        var that = this;

        localStorage.setItem( that.storage_count_name , JSON.stringify( that.storageCount ) );
    };

    Sidebar.prototype.initElasticBlock = function() {
        var that = this;

        // Init elastic block
        $(document).ready( function() {
            new ElasticBlock({
                $wrapper: $("#wa-app"),
                $block: that.$wrapper,
                $content: $("#t-content")
            });

            var $window = $(window);
            if ( $window.scrollTop() > 0 ) {
                $window.trigger("scroll");
            }
        });
    };

    Sidebar.prototype.initUpdater = function() {
        var that = this,
            time = 1000 * 60 * 5;

        that.timer = setTimeout( function() {
            if ( $.contains(document, that.$wrapper[0]) ) {
                that.reload();
            }
        }, time);
    };

    Sidebar.prototype.initSortable = function() {
        var that = this,
            $groupsWrapper = that.$groupsWrapper,
            $groups = that.$groups,
            $locationsWrapper = that.$locationsWrapper,
            $locations = that.$locations,
            href = that.app_url + "?module=group&action=sortSave",
            item_index,
            xhr = false;

        if ($groups.length > 1) {
            $groupsWrapper.sortable({
                items: "> li",
                axis: "y",
                delay: 200,
                tolerance: "pointer",
                start: function(event,ui) {
                    item_index = ui.item.index();
                },
                stop: function(event,ui) {
                    ui.item.removeAttr("style");
                    if (item_index != ui.item.index() ) {
                        var sortArray = getSortArray( $groupsWrapper );

                        var data = [];
                        $.each(sortArray, function(index, item) {
                            data.push({
                                name: "groups[]",
                                value: item
                            });
                        });

                        saveSort(href, data);
                    }
                }
            });
        }

        if ($locations.length > 1) {
            $locationsWrapper.sortable({
                items: "> li",
                axis: "y",
                delay: 200,
                tolerance: "pointer",
                start: function(event,ui) {
                    item_index = ui.item.index();
                },
                stop: function(event,ui) {
                    ui.item.removeAttr("style");
                    if (item_index != ui.item.index() ) {
                        var sortArray = getSortArray( $locationsWrapper );
                        saveSort(href, {
                            locations: sortArray
                        });
                    }
                }
            });
        }

        function getSortArray( $list ) {
            var result = [],
                $items = $list.find("> li");

            $items.each( function() {
                var $item = $(this),
                    id = $item.data("group-id");

                if (id && id > 0) {
                    result.push(id);
                }
            });

            return result;
        }

        function saveSort(href, data) {
            if (xhr) {
                xhr.abort();
                xhr = false;
            }
            xhr = $.post(href, data, function() {
                xhr = false;
            });
        }

    };

    Sidebar.prototype.initDroppable = function() {
        var that = this,
            drop_class = "js-drop-place",
            hover_class = "is-hovered",
            xhr = false;

        that.$groups.each( function() {
            var $group = $(this),
                is_drop_place = $group.hasClass(drop_class);

            if (is_drop_place) {
                $group.droppable({
                    tolerance: "pointer",
                    hoverClass: hover_class,
                    over: function(event, ui) {
                        var is_drag_item = ui.draggable.hasClass("ui-draggable");
                        if (!is_drag_item) {
                            $group.removeClass(hover_class);
                        }
                    },
                    drop: function(event, ui) {
                        addUserToGroup( $(this), ui.draggable );
                    }
                });
            }

        });

        that.$locations.each( function() {
            var $location = $(this),
                is_drop_place = $location.hasClass(drop_class);

            if (is_drop_place) {
                $location.droppable({
                    tolerance: "pointer",
                    hoverClass: hover_class,
                    over: function(event, ui) {
                        var is_drag_item = ui.draggable.hasClass("ui-draggable");
                        if (!is_drag_item) {
                            $location.removeClass(hover_class);
                        }
                    },
                    drop: function (event, ui) {
                        addUserToGroup($(this), ui.draggable);
                    }
                });
            }
        });

        function addUserToGroup( $dropZone, $item ) {
            var group_id = parseInt( $dropZone.data("group-id") ),
                user_id = parseInt( $item.data("user-id") ),
                href = $.team.app_url + "?module=group&action=userAdd",
                data;

            if ( !(group_id > 0 && user_id > 0) ) {
                return false;
            }

            data = {
                user_id: user_id,
                group_id: group_id
            };

            if (xhr) {
                xhr.abort();
            }

            xhr = $.post(href, data, function(response) {
                if (response.status == "ok") {
                    $.team.sidebar.reload();
                }
                xhr = false;
            });
        }
    };

    Sidebar.prototype.showSearch = function(search_string) {
        var that = this;

        search_string = encodeURIComponent(search_string);

        var content_uri = that.app_url + "search/" + search_string + "/";

        $.team.content.load( content_uri );
    };

    // TODO: delete it before release
    // Sidebar.prototype.setDemo = function() {
    //     var that = this,
    //         storageCount = {};
    //
    //     storageCount[ that.app_url ] = Math.floor( Math.random() * 15 );
    //     storageCount[ that.app_url + "online/" ] = Math.floor( Math.random() * 15 );
    //     for (var i = 1; i < 20; i++) {
    //         storageCount[ that.app_url + "group/" + i + "/" ] = Math.floor( Math.random() * 15 ) ;
    //     }
    //
    //     localStorage.setItem( that.storage_count_name , JSON.stringify(storageCount) );
    // };

    return Sidebar;

})(jQuery);
