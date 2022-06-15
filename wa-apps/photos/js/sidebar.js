(function($){
    $.photos_sidebar = {
        width: 200,
        options: {},
        init: function(options) {
            var that = this;
            this.options = options || {};
            if (options.width) {
                this.width = options.width;
            }

            that.wa2 = options.wa2 || false;

            if (that.wa2) {
                that.$wrapper = options.$wrapper;
                this.initCollapsibleWa2();
                this.initHandlers();
            }else{
                this.initCollapsible();
                this.initHandlers();
                this.initView();

                setTimeout( function() {
                    that.initFixedSidebar();
                }, 1000);
            }
        },

        initFixedSidebar: function() {
            // Class names
            var top_fix_class = "fixed-to-top",
                bottom_fix_class = "fixed-to-bottom";

            // DOM
            var $window = $(window),
                $wrapper = $("#wa-app"),
                $sidebarWrapper = $wrapper.find(".p-sidebar-wrapper"),
                $sidebar = $wrapper.find(".p-sidebar-block");

            // VARS
            var display_height = $window.height(),
                sidebar_top = $sidebar.offset().top;

            // DINAMIC VARS
            var is_top_set = false,
                is_fixed_to_bottom = false,
                is_fixed_to_top = false,
                scroll_value = 0;

            // EVENT
            $window.on("scroll", scrollingSidebar);

            // HANDLER
            function scrollingSidebar() {
                var scroll_top = $window.scrollTop(),
                    sidebar_height = $sidebar.outerHeight(),
                    wrapper_height = $wrapper.height(),
                    dynamic_sidebar_top = $sidebar.offset().top,
                    direction = ( scroll_value > scroll_top ) ? 1 : -1,
                    delta = scroll_top - sidebar_top,
                    sidebar_width = $sidebar.width();

                var is_sidebar_large = ( sidebar_height + parseInt($sidebarWrapper.css("padding-top")) + parseInt($sidebarWrapper.css("padding-bottom")) >= wrapper_height ),
                    active_scroll = ( wrapper_height > display_height && !is_sidebar_large );

                if (active_scroll) {

                    // If the height of the slider is smaller than the display, it's simple
                    if (sidebar_height < display_height) {
                        if (delta > 0) {
                            if (is_top_set || !is_fixed_to_bottom || is_fixed_to_top) {
                                $sidebar
                                    .removeAttr("style")
                                    .width(sidebar_width)
                                    .addClass(top_fix_class);
                            }
                            is_fixed_to_top = true;
                        } else {
                            $sidebar
                                .removeAttr("style")
                                .removeClass(bottom_fix_class)
                                .removeClass(top_fix_class);
                        }

                        // If the height is larger than the screen
                    } else {
                        // If less than the original position to turn off
                        if (scroll_top <= sidebar_top) {
                            if (is_top_set || is_fixed_to_bottom || is_fixed_to_top) {
                                $sidebar
                                    .removeAttr("style")
                                    .removeClass(bottom_fix_class)
                                    .removeClass(top_fix_class);

                                is_top_set = is_fixed_to_bottom = is_fixed_to_top = false;
                            }

                            // If the above start after scrolling fix up
                        } else if (scroll_top <= dynamic_sidebar_top && dynamic_sidebar_top >= sidebar_top) {

                            if (direction > 0) {
                                if (is_top_set || !is_fixed_to_top || is_fixed_to_bottom) {
                                    $sidebar
                                        .removeAttr("style")
                                        .width(sidebar_width)
                                        .removeClass(bottom_fix_class)
                                        .addClass(top_fix_class);

                                    is_top_set = is_fixed_to_bottom = false;
                                    is_fixed_to_top = true;
                                }
                            } else {
                                if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                    $sidebar
                                        .css("top", dynamic_sidebar_top - sidebar_top)
                                        .removeClass(top_fix_class)
                                        .removeClass(bottom_fix_class);

                                    is_top_set = true;
                                    is_fixed_to_top = is_fixed_to_bottom = false;
                                }
                            }

                            // If the lower end
                        } else if (scroll_top + display_height >= dynamic_sidebar_top + sidebar_height) {
                            // If the direction of scrolling up
                            if (direction > 0) {
                                if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                    $sidebar
                                        .css("top", dynamic_sidebar_top - sidebar_top)
                                        .removeClass(top_fix_class)
                                        .removeClass(bottom_fix_class);

                                    is_top_set = true;
                                    is_fixed_to_top = is_fixed_to_bottom = false;
                                }

                                // If the direction of scrolling down
                            } else {
                                if (is_top_set || is_fixed_to_top || !is_fixed_to_bottom) {
                                    $sidebar
                                        .removeAttr("style")
                                        .width(sidebar_width)
                                        .removeClass(top_fix_class)
                                        .addClass(bottom_fix_class);

                                    is_top_set = is_fixed_to_top = false;
                                    is_fixed_to_bottom = true;
                                }
                            }
                            // In all other cases
                        } else {
                            if (!is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                                $sidebar
                                    .css("top", dynamic_sidebar_top - sidebar_top)
                                    .removeClass(top_fix_class)
                                    .removeClass(bottom_fix_class);

                                is_top_set = true;
                                is_fixed_to_top = is_fixed_to_bottom = false;
                            }
                        }
                    }
                } else {
                    if (is_top_set || is_fixed_to_top || is_fixed_to_bottom) {
                        $sidebar
                            .removeAttr("style")
                            .removeClass(bottom_fix_class)
                            .removeClass(top_fix_class);

                        is_top_set = is_fixed_to_top = is_fixed_to_bottom = false;
                    }
                }

                // Save New Data
                scroll_value = scroll_top;
            }
        },

        initView: function() {
            var sidebar = $('#p-sidebar');
            var arrows_panel = $('#p-sidebar-width-control');
            arrows_panel.find('a.arrow').unbind('click').
                    bind('click', function() {
                        var max_width = 400;
                        var min_width = 200;
                        var cls = sidebar.attr('class');
                        var width = 0;

                        var m = cls.match(/left([\d]{2,3})px/);
                        if (m && m[1] && (width = parseInt(m[1]))) {
                            var new_width = width + ($(this).is('.right') ? 50 : -50);
                            new_width = Math.max(Math.min(new_width, max_width), min_width);

                            if (new_width != width) {

                                arrows_panel.css({'width': new_width.toString() + 'px'});

                                var replace = ['left' + width + 'px', 'left' + new_width + 'px'];
                                sidebar.attr('class', cls.replace(replace[0], replace[1]));
                                sidebar.css('width', '');

                                var content = $('#p-content');
                                if (content.length) {
                                    cls = content.attr('class');
                                    content.attr('class', cls.replace(replace[0], replace[1]));
                                    content.css('margin-left', '');
                                }
                                $.photos_sidebar.width = new_width;
                                $.post('?action=sidebarSaveWidth', {
                                    width: new_width
                                }, 'json');
                            }
                        }

                        return false;
                    });
        },

        initCollapsible: function() {
            $('#p-sidebar').off('click', '.collapse-handler').on('click', '.collapse-handler', function () {
                $.photos_sidebar._collapseSidebarSection(this, 'toggle');
            });
            $("#p-sidebar .collapse-handler").each(function() {
                $.photos_sidebar._collapseSidebarSection(this, 'restore');
            });
            $('#album-list-container').off('uncollapse_section').on('uncollapse_section', function(e, album_item) {
                album_item = $(album_item);
                var container = $(this),
                    container_handler = container.find('>.collapse-handler'),
                    section_handler = album_item.find('>i.collapse-handler');

                $.photos_sidebar._collapseSidebarSection(section_handler, 'uncollapse');

                var item = album_item.parent().parent();
                while (item.length && item.get(0) != this) {
                    var item_handler = item.find('>i.collapse-handler');
                    if (!item_handler.length) {
                        break;
                    }
                    $.photos_sidebar._collapseSidebarSection(item_handler, 'uncollapse');
                    item = item.parent().parent();
                }
                $.photos_sidebar._collapseSidebarSection(container_handler, 'uncollapse');
            });
        },

        initCollapsibleWa2: function() {

            var that = this,
                section_storage_name = "photos/sidebar/collapsed_sections",
                album_storage_name = "photos/sidebar/collapsed_albums",
                $sections = that.$wrapper.find(".p-sidebar-section"),
                $albums = that.$wrapper.find(".js-album-list li");

            $albums.each( function() {
                var $album = $(this),
                    album_id = $album.data("id"),
                    $caret = $album.find(".caret");

                if ($caret.length) {
                    $caret.on("click", function(event) {
                        event.preventDefault();
                        $album.trigger("collapse_a");
                    });

                    $album.on("collapse_a", function(event) {
                        event.preventDefault();
                        toggle($album, 'album');
                    });

                    if (album_id) {
                        var is_hidden = isSectionHidden(album_id, 'album');
                        if (is_hidden) {
                            $album.trigger("collapse_a");
                        }
                    }
                }
            });

            $sections.each( function() {
                var $section = $(this),
                    section_id = $section.data("id"),
                    $caret = $section.find(".heading .caret");

                if ($caret.length) {
                    $caret.on("click", function(event) {
                        event.preventDefault();
                        $section.trigger("collapse_s");
                    });

                    $section.on("collapse_s", function(event) {
                        event.preventDefault();
                        toggle($section, 'section');
                    });

                    if (section_id) {
                        var is_hidden = isSectionHidden(section_id, 'section');
                        if (is_hidden) {
                            $section.trigger("collapse_s");
                        }
                    }
                }
            });

            function isSectionHidden(block_id, type) {
                var storage_name = album_storage_name;
                if (type === 'section') {
                    storage_name = section_storage_name;
                }
                var storage = getStorage(storage_name);
                return !!(storage[block_id]);
            }

            function toggle($block, type) {
                var block_id = $block.data("id"),
                    hidden_class = "is-collapsed",
                    is_hidden = $block.hasClass(hidden_class),
                    storage_name = album_storage_name;
                if (type === 'section') {
                    storage_name = section_storage_name;
                }
                var storage = getStorage(storage_name);

                if (block_id) {
                    if (is_hidden) {
                        delete storage[block_id];
                    } else {
                        storage[block_id] = true;
                    }
                }

                setStorage(storage, storage_name);

                $block.toggleClass(hidden_class);
            }

            function setStorage(storage, storage_name) {
                localStorage.setItem(storage_name , JSON.stringify(storage));
            }

            function getStorage(storage_name) {
                var storage = localStorage.getItem(storage_name);
                return (storage ? JSON.parse(storage) : {});
            }
        },

        initHandlers: function() {
            let dialog;

            $("#p-upload-link").on('click', function (e) {
                e.preventDefault();

                if (dialog) {
                    dialog.show();
                    return;
                }

                let $dialog = $('#p-uploader');

                if($dialog.length) {
                    dialog = $.waDialog({
                        $wrapper: $dialog,
                        onOpen($_dialog, dialog_instance) {
                            $.photos.onUploadDialog($_dialog, dialog_instance)
                        },
                        onClose(dialog_instance) {
                            $.photos.dialogClearSteps.call($dialog);
                            dialog_instance.hide();
                            return false;
                        }
                    });
                }else{
                    $.photos.uploadDialog();
                }
            });

            $('#album-list-container')
                .off('click', '.p-new-album')
                .on('click', '.p-new-album',
                    function () {
                        const self = $(this);
                        let parent_id = 0;
                        if (!self.is('#p-new-album')) {
                            parent_id = parseInt(self.parents('li:first').attr('rel'), 10) || 0;
                        }
                        const showDialog = function () {
                            $.waDialog({
                                $wrapper: $('#album-create-dialog'),
                                onOpen ($dialog, dialog) {
                                    $dialog.find('input[type=text]').val('');
                                    let $form = $dialog.find('form');

                                    $form.find('.js-privacy-settings-link').on('click', function () {
                                        $(window).resize()
                                    });

                                    $form.on('submit', function (e) {
                                        e.preventDefault();
                                        $.post($form.attr('action'), $form.serialize(), function (r) {
                                            if (r.status == 'ok') {
                                                $.photos.onCreateAlbum(r.data, parent_id);
                                                dialog.close();
                                                if (r.data.id) {
                                                    $.photos.goToHash('/album/' + r.data.id);
                                                }
                                            }
                                        }, "json");
                                    })
                                }
                            });
                        };
                        let d = $('#album-create-dialog-acceptor');
                        if (!d.length) {
                            d = $("<div id='album-create-dialog-acceptor'></div>");
                            $("body").append(d);
                        }
                        d.load("?module=dialog&action=createAlbum&parent_id="+parent_id, showDialog);
                        return false;
                });
        },

        countSubtree: function(item) {
            var counter = item.find('>.count:not(.subtree)'),
                subtree_counter = item.find('>.subtree');
            if (!subtree_counter.length) {
                subtree_counter = counter.clone().addClass('subtree').hide();
                counter.after(subtree_counter);
            }
            var total_count = parseInt(counter.text(), 10) || 0;
            item.find('li.static>.count:not(.subtree)').each(function() {
                var count = parseInt($(this).text(), 10) || 0;
                total_count += count;
            });
            if (!subtree_counter.hasClass('never-recount')) {
                subtree_counter.text(total_count);
            }
            subtree_counter.show();
            counter.hide();
            return total_count;
        },

        countItem: function(item) {
            var counter = item.find('>.count:not(.subtree)').show(),
                subtree_counter = item.find('>.subtree').hide();
            return parseInt(counter.text(), 10) || 0;
        },

        _collapseSidebarSection: function(el, action) {
            if (!action) {
                action = 'coollapse';
            }
            el = $(el);
            if (!el.length) {
                return;
            }

            var arr;
            if (el.hasClass('darr') || el.hasClass('rarr')) {
                arr = el;
            } else {
                arr = el.find('.darr, .rarr');
            }
            if (!arr.length) {
                return;
            }

            var item = el.parent();
            var list = item.find('.hierarchical:first');
            if (!list.length) {
                list = item.find('.collapsible-content:first');
            }
            if (!list.length) {
                list = item.find('ul:first');
            }

            var newStatus,
                id = el.attr('id') || el.parent().attr('id'),
                oldStatus = arr.hasClass('darr') ? 'shown' : 'hidden',

                hide = function() {
                    list.hide();
                    arr.removeClass('darr').addClass('rarr');
                    $.photos_sidebar.countSubtree(item);
                    newStatus = 'hidden';
                },

                show = function() {
                    list.show();
                    arr.removeClass('rarr').addClass('darr');
                    $.photos_sidebar.countItem(item);
                    newStatus = 'shown';
                };

            switch(action) {
                case 'toggle':
                    if (oldStatus == 'shown') {
                        hide();
                    } else {
                        show();
                    }
                    break;
                case 'restore':
                    if (id) {
                        var status = $.storage.get('photos/collapsible/'+id);
                        if (status == 'hidden') {
                            hide();
                        } else {
                            show();
                        }
                    }
                    break;
                case 'uncollapse':
                    show();
                    break;
                case 'collapse':
                default:
                    hide();
                    break;
            }

            // save status in persistent storage
            if (id && newStatus) {
                $.storage.set('photos/collapsible/'+id, newStatus);
            }
        }
    }
})(jQuery);