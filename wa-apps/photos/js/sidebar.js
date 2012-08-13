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

            $("#p-new-album").click(function () {
                var showDialog = function () {
                    $('#album-create-dialog').waDialog({
                        onLoad: function (d) {
                            $(this).find('input[type=text]').val('');
                        },
                        onSubmit: function (d) {
                            var f = $(this);
                            $.post(f.attr('action'), f.serialize(), function (r) {
                                if (r.status == 'ok') {
                                    $.photos.onCreateAlbum(r.data);
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
                var d = $('#album-settings-create-acceptor');
                if (!d.length) {
                    d = $("<div id='album-create-dialog-acceptor'></div>");
                    $("body").append(d);
                }
                d.load("?module=dialog&action=createAlbum", showDialog);
                return false;
            });
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
                    el.parent().find('ul:first').hide();
                    arr.removeClass('darr').addClass('rarr');
                    newStatus = 'hidden';
                },

                show = function() {
                    el.parent().find('ul:first').show();
                    arr.removeClass('rarr').addClass('darr');
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