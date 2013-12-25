(function ($) {
    $.wa_blog.stream = {
        management: false,
        options: {
            pageless: {}
        },
        init: function (options) {
            this.options = $.extend(this.options, options);
            var self = this;

            $('a[href=\\#manage]').click(function (eventObject) {
                return self.manageHandler.apply(self, [this, eventObject]);
            });
            $('.js-manage-done').click(function (eventObject) {
                return self.manageCompleteHandler.apply(self, [this, eventObject]);
            });
            $('input.search').keydown(function (eventObject) {
                if (eventObject.keyCode == 13) {
                    var query = $(this).val(),
                        match = location.search.match(/[&\\?](text=.*?&|text=.*)/);
                    if (match) {
                        var text = match[1];
                        if (query) {
                            var new_text = text.substr(-1) == '&' ? 'text=' + encodeURIComponent(query) + '&' : 'text=' + encodeURIComponent(query);
                            location.search = location.search.replace(text, new_text);
                        } else {
                            if (text.substr(-1) != '&') {
                                text = '[&\\?]' + text;
                            }
                            location.search = location.search.replace(new RegExp(text), '');
                        }
                    } else if (query) {
                        location.search += (location.search ? '&' : '?') + 'text=' + encodeURIComponent(query);
                    }
                    return false;
                }
            });
            $('input.blog-post-checkbox').live('click', function (eventObject) {
                return self.counterHandler.apply(self, [this, eventObject]);
            });

            $('#postdelete-dialog input[type=button]').click(function (eventObject) {
                return self.deleteHandler.apply(self, [this, eventObject]);
            });

            $('#postmove-dialog input[type="button"]').click(function (eventObject) {
                return self.moveHandler.apply(self, [this, eventObject]);
            });

            var pageless_options = {
                scroll: function () {
                    $.wa_blog.common.onContentUpdate();
                }};
            pageless_options = $.extend(true, pageless_options, self.options.pageless);
            $.pageless(pageless_options);
        },
        manageHandler: function (element, event) {
            $('#blog-stream-primary-menu').hide();
            $('#blog-stream-manage-menu').show();
            this.management = true;
            this.onContentUpdate();
            return false;
        },
        manageCompleteHandler: function (element, event) {
            this.management = false;
            $('#blog-stream-manage-menu').hide();
            $('#blog-stream-primary-menu').show();
            $('.b-post.js-managed').each(function () {
                $(this).removeClass('js-managed');
                $(this).find('h3:hidden').show();
                $(this).find('h3:first').hide();
                $(this).find('.b-post-body:hidden, .profile.image20px:hidden').fadeIn();
            });
            return false;
        },
        counterHandler: function () {
            $('.js-blog-selected-posts-counter').text($('input.blog-post-checkbox:checked').length);
        },
        moveHandler: function (element, event) {
            var ids = new Array();
            $('input.blog-post-checkbox:checked').each(function () {
                ids.push($(this).val());
            });
            var blog_id = $('#postmove-dialog :input[name=blog_id]').val();
            if (ids.length) {
                $(element).attr('disabled', true).after('<i class="icon16 loading"></i>');
                $.ajax({
                    url: '?module=post&action=move',
                    data: {id: ids, blog: blog_id},
                    type: 'post',
                    dataType: 'json',
                    success: function (response) {
                        var moved = false;
                        if (response.status == 'ok') {
                            for (var i in response.data.moved) {
                                var post = $('#b-post-' + response.data.moved[i]);
                                if (post.length) {
                                    moved = true;
                                    post.animate({
                                        opacity: 0.1,
                                        height: 0
                                    }, 200, function () {
                                        post.remove();
                                    });
                                }
                            }
                        }
                        $.wa_blog.stream.counterHandler();
                        if (moved) {
                            window.location.reload();
                        } else {
                            $('#postmove-dialog .icon16.loading').remove();
                            $('#postmove-dialog input[type=button]:disabled').removeAttr('disabled');
                            $.wa_blog.dialogs.close('postmove');
                        }
                    },
                    error: function (response) {
                        $('#postmove-dialog .icon16.loading').remove();
                        $('#postmove-dialog input[type=button]:disabled').removeAttr('disabled');
                        $.wa_blog.dialogs.close('postmove');
                    }
                });
            } else {
                $.wa_blog.dialogs.close('postmove');
            }
        },
        deleteHandler: function (element, event) {
            var ids = new Array();
            $('input.blog-post-checkbox:checked').each(function () {
                ids.push($(this).val());
            });
            if (ids.length) {
                $(element).attr('disabled', true).after('<i class="icon16 loading"></i>');
                $.ajax({
                    url: '?module=post&action=delete',
                    data: {id: ids},
                    type: 'post',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status == 'ok' && response.data.deleted) {
                            window.location.reload();
                        } else {
                            $('#postdelete-dialog .icon16.loading').remove();
                            $('#postdelete-dialog input[type=button]:disabled').removeAttr('disabled');
                            $.wa_blog.dialogs.close('postdelete');
                            $.wa_blog.stream.counterHandler();
                        }
                    },
                    error: function (response) {
                        $('#postdelete-dialog .icon16.loading').remove();
                        $('#postdelete-dialog input[type=button]:disabled').removeAttr('disabled');
                        $.wa_blog.dialogs.close('postdelete');
                        $.wa_blog.stream.counterHandler();
                    }
                });
            } else {

                $.wa_blog.dialogs.close('postdelete');
            }
        },
        onContentUpdate: function () {
            if (this.management) {
                var self = this;
                var collapsed = false;
                $('.b-post-body:visible, .profile.image20px:visible').hide();
                $('.b-post').each(function () {
                    if (!$(this).hasClass('js-managed')) {
                        $(this).find('h3:visible').hide();
                        $(this).find('h3:first').show();
                        collapsed = true;
                        $(this).addClass('js-managed');
                    }
                });
                if (collapsed) {
                    setTimeout(function () {
                        $(self.options.pageless.target).trigger('scroll.pageless');
                    }, 100);
                }
            }
        }

    };
})(jQuery);
