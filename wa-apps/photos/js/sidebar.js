(function($){
    $.photos_sidebar = {
        init: function() {
            this.initCollapsible();
            this.initHandlers();
        },

        initCollapsible: function() {
            $("#album-list-container .collapse-handler").die('click').live('click', function () {
                $.photos_sidebar._collapseSidebarSection(this, 'toggle');
            });
            $("#album-list-container .collapse-handler").each(function() {
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
                $("#p-uploader").waDialog({
                    'onLoad':$.photos.onUploadDialog,
                    'onSubmit': function () {
                        $('#p-start-upload-button').click();
                        return false;
                    }
                });
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
            subtree_counter.text(total_count).show();
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
            var newStatus,
                id = el.attr('id'),
                oldStatus = arr.hasClass('darr') ? 'shown' : 'hidden',

                hide = function() {
                    var item = el.parent();
                    item.find('ul:first').hide();
                    arr.removeClass('darr').addClass('rarr');
                    $.photos_sidebar.countSubtree(item);
                    newStatus = 'hidden';
                },

                show = function() {
                    var item = el.parent();
                    item.find('ul:first').show();
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