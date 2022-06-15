// Team :: Sidebar
// Initialized in templates/actions/Sidebar.html
var Sidebar = ( function($) {

    Sidebar = function(wrapper, options) {
        var that = this;

        // DOM
        that.$wrapper = wrapper;

        // OPTIONS
        that.options = options;

        // INIT
        that.initClass();
    };

    Sidebar.prototype.initClass = function() {
        const that = this;

        that.$body = that.$wrapper.find('.sidebar-body');
        that.$addUserLink = that.$wrapper.find('#t-new-user-link');
        that.$groupsWrapper = that.$wrapper.find('.t-groups-list');
        that.$groups = that.$groupsWrapper.find('> li');
        that.$locationsWrapper = that.$wrapper.find('.t-locations-list');
        that.$locations = that.$locationsWrapper.find('> li');
        that.$searchForm = that.$wrapper.find('.t-search-form');
        that.$addGroupLink = that.$wrapper.find('.js-add-user-group');
        that.$addOfficeLink = that.$wrapper.find('.js-add-user-location');

        const options = {
            storage_count_name: 'team/sidebar_counts',
            link_count_update_date: false,
            counters: {},
            storageCount: false,
            xhr: false,
            timer: 0
        }

        $.extend(that.options, options);

        that.$activeMenuItem = (that.$wrapper.find(`li.${that.options.classes.selected}:first`) || false);
        that.groupDialog = {};

        that.setCounts();

        if (that.options.can_sort) {
            that.initSortable();
        }

        that.initDroppable();

        if (!that.$activeMenuItem.length) {
            that.selectLink();
        }

        that.initUpdater();

        that.bindEvents();
    };

    Sidebar.prototype.bindEvents = function() {
        var that = this;

        that.unBindEvents();

        that.$wrapper.on('click', 'li > a', $.proxy(that.onLinkClick, that));
        that.$addGroupLink.on('click', $.proxy(that.showGroupDialog, that));
        that.$addOfficeLink.on('click', $.proxy(that.showGroupDialog, that));
        that.$searchForm.on('submit', $.proxy(that.showSearch, that));
        that.$addUserLink.on('click', $.proxy(that.showInviteDialog, that));
    };

    Sidebar.prototype.unBindEvents = function() {
        const that = this;

        that.$wrapper.off('click', 'li > a');
        that.$addGroupLink.off('click');
        that.$addOfficeLink.off('click');
        that.$searchForm.off('submit');
        that.$addUserLink.off('click');
    };

    Sidebar.prototype.onLinkClick = function(event) {
        const that = this;
        const link = $(event.target).closest('a');
        const uri = link.attr('href');

        if (uri && uri.substr(0, 11) !== 'javascript:' && !link.hasClass(that.options.classes.noHighlight)) {
            that.setItem(link.closest('li'));
            $.team.setTitle(link.text());
        }
    };

    Sidebar.prototype.setItem = function($item) {
        const that = this;

        if (that.$activeMenuItem && that.$activeMenuItem[0] === $item[0]) {
            return;
        }

        if (that.$activeMenuItem) {
            that.$activeMenuItem.removeClass(that.options.classes.selected);
        }

        $item.addClass(that.options.classes.selected);
        that.$activeMenuItem = $item;
    };

    Sidebar.prototype.selectLink = function(uri) {
        const that = this;
        let $link;

        if (uri) {
            $link = that.$wrapper.find(`a[href="${uri}"]:first`);
        } else if (uri === false) {
            if (that.$activeMenuItem) {
                that.$activeMenuItem.removeClass(that.options.classes.selected);
                that.$activeMenuItem = null;
            }
            return;
        }

        if ($link && $link.length) {
            that.setItem($link.closest('li'));
        } else {
            const $links = that.$wrapper.find(`a[href^="${that.options.app_url}"]`);
            const location_string = location.pathname;
            let max_length = 0;
            let link_index = 0;

            $links.each(function(index) {
                const $link = $(this);
                const href = $link.attr('href');
                const href_length = href.length;

                if (location_string.indexOf(href) >= 0) {
                    if (href_length > max_length) {
                        max_length = href_length;
                        link_index = index;
                    }
                }
            });

            if (link_index || link_index === 0) {
                $link = $links.eq(link_index);
                that.setItem($link.closest('li'));
            }
        }
    };

    Sidebar.prototype.reload = function(background) {
        const that = this;
        let sidebar_uri = $.team.app_url + that.options.api.reload;

        if (background) {
            sidebar_uri += '&background_process=1'
        }

        clearTimeout(that.options.timer);

        if (that.options.xhr) {
            that.options.xhr.abort();
        }

        that.options.xhr = $.get(sidebar_uri, {is_reload: 1}, function(html) {
            that.options.xhr = false;
            that.$body.replaceWith(html);
            that.$wrapper.find('.sidebar-header').css('display', '');
            that.$wrapper.find('.sidebar-body').css('display', '');
            that.$wrapper.find('.sidebar-footer').css('display', '');

            that.initClass();
        });
    };

    Sidebar.prototype.showInviteDialog = function(event) {
        event.preventDefault();

        const that = this;

        if (that.inviteDialog) {
            that.inviteDialog.show();
            return;
        }

        const href = $.team.app_url + that.options.api.inviteDialog;
        $.get(href, function(html) {
            that.inviteDialog = $.waDialog({
                html,
                onClose(dialog) {
                    dialog.hide();
                    return false;
                }
            });
        });
    };

    Sidebar.prototype.showGroupDialog = function(event) {
        event.preventDefault();

        const that = this;

        const groupType = $(event.target).hasClass(that.options.classes.initGroupDialog) ? 'group' : 'location';

        if (that.groupDialog[groupType]) {
            that.groupDialog[groupType].show();
            return;
        }

        const href = $.team.app_url + that.options.api.group;
        const data = {
            type: groupType
        };

        $.get(href, data, function(html) {
            that.groupDialog[groupType] = $.waDialog({
                html,
                onOpen($dialog, dialog) {
                    dialog.$content.find('.js-edit-group-name').focus();
                },
                onClose(dialog) {
                    dialog.hide();
                    return false;
                }
            });
        });
    };

    // set counts
    Sidebar.prototype.setCounts = function() {
        const that = this;
        const currentCount = getCountArray();
        const storageCount = getStorage(that.options.storage_count_name);

        that.options.storageCount = $.extend(true, {}, currentCount);

        if (storageCount) {
            $.each(storageCount, function(href, item) {
                const is_exist = (href in currentCount);
                const is_number = (is_exist && currentCount[href].count >= 0);
                const is_changed = (is_number && currentCount[href].count !== item.count);
                const is_item_exist = (item.count >= 0);

                if (is_item_exist && is_changed) {
                    that.options.storageCount[href].count = item.count;
                    that.options.storageCount[href].date = item.date;
                    showCounter(href, currentCount[href].count, item.count, item.date);
                }
            });
        }

        that.setStorage();

        function showCounter(href, new_count, old_count, date) {
            const is_good_href = (href.indexOf(that.options.app_url) >= 0);
            const $link = that.$wrapper.find(`a[href="${href}"]`);

            if (!is_good_href || !$link.length) {
                return;
            }

            const delta_count = new_count - old_count;
            if (delta_count < 0) {
                return;
            }

            const $counter = $(`<strong class="highlighted small js-sidebar-counter" style="height: fit-content;">+ ${delta_count}</strong>`),
                $existed_counter = $link.find('.js-sidebar-counter');

            if ($existed_counter.length) {
                $existed_counter.text(`+ ${delta_count}`);
            }else{
                $link.append($counter);
            }

            $link.one('click', function(event) {
                event.preventDefault();
                $counter.remove();
                that.saveCount(href, new_count);
                that.options.link_count_update_date = date;

                $(document).one('wa_loaded', function() {
                    that.options.link_count_update_date = false;
                });
            });
        }

        function getStorage(storage_name) {
            let result = {};
            const storageData = localStorage.getItem(storage_name);

            if (storageData) {
                result = JSON.parse(storageData);
            }

            return result;
        }

        function getCountArray() {
            let result = {};
            const $counts = that.$wrapper.find("li .js-count");
            const current_date = getDate();

            $counts.each( function() {
                const $count = $(this);
                const count = parseInt($count.text());
                const $li = $count.closest("li");
                const $link = $li.find('> a');

                if (count >= 0) {
                    that.options.counters[$link.attr("href")] = $count;
                    result[$link.attr('href')] = {
                        count: count,
                        date: current_date
                    }
                }
            });

            return result;
        }

        function getDate() {
            const date = new Date();
            const day = parseInt(date.getUTCDate());
            const month = parseInt(date.getUTCMonth()) + 1;
            const year = parseInt(date.getUTCFullYear());
            const hours = parseInt(date.getUTCHours());
            const minutes = parseInt(date.getUTCMinutes());
            const seconds = parseInt(date.getUTCSeconds());

            return result = {
                'year': year,
                'month': month,
                'day': day,
                'hours': hours,
                'minutes': minutes,
                'seconds': seconds
            };
        }
    };

    // update count in class
    Sidebar.prototype.saveCount = function(href, count) {
        const that = this;
        if (href in that.options.storageCount) {
            that.options.storageCount[href].count = count;
        }
        that.setStorage();
    };

    // render count in dom
    Sidebar.prototype.updateCount = function(href, count) {
        const that = this;
        let $counter;

        if (!(href && (count || count === 0))) {
            return;
        }

        $counter = (that.options.counters[href] || false);

        that.saveCount(href, count);

        if ($counter.length) {
            $counter.text(count);
        } else {
            that.reload();
        }
    };

    // save to local storage
    Sidebar.prototype.setStorage = function() {
        const that = this;

        localStorage.setItem(that.options.storage_count_name , JSON.stringify(that.options.storageCount));
    };

    Sidebar.prototype.initUpdater = function() {
        const that = this;
        const time = that.options.updateTime;

        that.options.timer = setTimeout( function() {
            if ($.contains(document, that.$wrapper[0]) ) {
                that.reload(true);
            }
        }, time);
    };

    Sidebar.prototype.initSortable = function() {
        const that = this;

        const href = $.team.app_url + that.options.api.sort;
        let xhr = false;

        if (that.$groups.length > 1) {
            that.$groupsWrapper.sortable({
                animation: 150,
                handle: `.${that.options.classes.drop}`,
                direction: 'vertical',
                onEnd(event) {
                    if (event.oldIndex === event.newIndex) {
                        return;
                    }

                    const sortArray = getSortArray(that.$groupsWrapper);

                    const data = [];
                    $.each(sortArray, function(index, item) {
                        data.push({
                            name: 'groups[]',
                            value: item
                        });
                    });

                    saveSort(href, data);
                }
            });
        }

        if (that.$locations.length > 1) {
            that.$locationsWrapper.sortable({
                animation: 150,
                handle: `.${that.options.classes.drop}`,
                direction: 'vertical',
                onEnd(event) {
                    if (event.oldIndex === event.newIndex) {
                        return;
                    }

                    const sortArray = getSortArray(that.$locationsWrapper);

                    saveSort(href, {
                        locations: sortArray
                    });
                }
            });
        }

        function getSortArray($list) {
            const result = [];
            const $items = $list.find('> li');

            $items.each( function() {
                const $item = $(this);
                const id = $item.data('group-id');

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
        const that = this;
        let xhr = false;

        that.$groups.each(function() {
            const $group = $(this);
            const is_drop_place = $group.hasClass(that.options.classes.drop);

            if (!is_drop_place) {
                return;
            }

            $group.droppable({
                tolerance: 'pointer',
                hoverClass: that.options.classes.highlighted,
                over: function(event, ui) {
                    const is_drag_item = ui.draggable.hasClass(that.options.classes.uiDraggable);
                    if (!is_drag_item) {
                        $group.removeClass(that.options.classes.highlighted);
                    }
                },
                drop: function(event, ui) {
                    addUserToGroup($(this), ui.draggable);
                }
            });
        });

        that.$locations.each( function() {
            const $location = $(this);
            const is_drop_place = $location.hasClass(that.options.classes.drop);

            if (!is_drop_place) {
                return;
            }

            $location.droppable({
                tolerance: 'pointer',
                hoverClass: that.options.classes.highlighted,
                over: function(event, ui) {
                    var is_drag_item = ui.draggable.hasClass(that.options.classes.uiDraggable);
                    if (!is_drag_item) {
                        $location.removeClass(that.options.classes.highlighted);
                    }
                },
                drop: function(event, ui) {
                    addUserToGroup($(this), ui.draggable);
                }
            });
        });

        function addUserToGroup($dropZone, $item) {
            const group_id = parseInt($dropZone.data('group-id'));
            const user_id = parseInt($item.data('user-id'));
            const href = $.team.app_url + that.options.api.moveUser;

            if (!(group_id > 0 && user_id > 0)) {
                return;
            }

            const data = {
                user_id: user_id,
                group_id: group_id
            };

            if (xhr) {
                xhr.abort();
            }

            xhr = $.post(href, data, function(response) {
                xhr = false;

                if (response.status !== 'ok') {
                    console.warn(response);
                    return;
                }

                $.team.sidebar.reload();
            });
        }
    };

    Sidebar.prototype.showSearch = function(event) {
        event.preventDefault();

        const that = this;

        const searchValue = $(event.target).find('.t-search-field').val();
        const search_string = encodeURIComponent(searchValue);
        const content_uri = `${$.team.app_url}search/${search_string}/`;

        $.team.content.load(content_uri);
        that.$wrapper.find('.sidebar-mobile-toggle').trigger('click');
    };

    Sidebar.prototype.setSelected = function(data) {
        const that = this;

        let $item;

        switch(data.type) {
            case 'invited':
                $item = that.$body.find('[data-invited-item]');
                break;

            case 'inactive':
                $item = that.$body.find('[data-inactive-item]');
                break;

            case 'search':
                $item = that.$body.find('#all-users-sidebar-link');
                break;

            case 'group':
                $item = that.$body.find(`[data-group-id="${data.groupId}"]`);
                break;
        }

        that.setItem($($item));
    }

    return Sidebar;

})(jQuery);
