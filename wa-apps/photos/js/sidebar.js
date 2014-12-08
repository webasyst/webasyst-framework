(function($){
    $.photos_sidebar = {
        width: 200,
        options: {},
        init: function(options) {
            this.options = options || {};
            if (options.width) {
                this.width = options.width;
            }
            this.initCollapsible();
            this.initHandlers();
            this.initView();
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
            $('#album-list-container').die('uncollapse_section').live('uncollapse_section', function(e, album_item) {
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

        initHandlers: function() {
            $("#p-upload-link").click(function () {
                $.photos.uploadDialog();
                return false;
            });

            $('#album-list-container').off('click', '.p-new-album').
                on('click', '.p-new-album',
                    function () {
                        var self = $(this);
                        var parent_id = 0;
                        if (!self.is('#p-new-album')) {
                            parent_id = parseInt(self.parents('li:first').attr('rel'), 10) || 0;
                        }
                        var showDialog = function () {
                            $('#album-create-dialog').waDialog({
                                onLoad: function (d) {
                                    $(this).find('input[type=text]').val('');
                                },
                                onSubmit: function (d) {
                                    var f = $(this);
                                    $.post(f.attr('action'), f.serialize(), function (r) {
                                        if (r.status == 'ok') {
                                            $.photos.onCreateAlbum(r.data, parent_id);
                                            d.trigger('close');
                                            if (r.data.id) {
                                                $.photos.goToHash('/album/' + r.data.id);
                                            }
                                        }
                                    }, "json");
                                    return false;
                                }
                            });
                        };
                        var d = $('#album-create-dialog-acceptor');
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