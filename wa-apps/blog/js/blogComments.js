(function($) {
    $.wa_blog.comments = {
        options: {
            form_selector:'#b-comment-add-form',
            form_wrapper_selector:'#b-comment-add'
        },
        init : function() {
            this.parent = $.wa_blog;
            var self = this;
            $('a.b-comment-delete, a.b-comment-restore').live('click',function(eventObject){
                return self.manageComment.apply(self,[this,eventObject]);
            });


            if(self.options.pageless) {
                var pageless_options = {
                    scroll:function() {
                        self.parent.common.onContentUpdate();
                }};
                pageless_options = $.extend(true,pageless_options,self.options.pageless);
                $.pageless(pageless_options);
            }
            self.parent.common.onContentUpdate();

            // comments
            $('.b-comment-reply').live('click', self.replyClick);
            $(this.options.form_selector+' #send').click(self.formSubmit);
            
            // filter
            /*
            $('#b-comments-filter').on('click', 'a', function() {
                var self = $(this);
                var filter = self.data('filter');
                self.closest('ul').find('li.selected').removeClass('selected');
                self.closest('li').addClass('selected');
                $.get('?module=comments&filter='+filter, function(html) {
                    var tmp = $('<div></div>').html(html);
                    $('.b-comments').replaceWith(tmp.find('.b-comments'));
                    tmp.remove();
                });
                return false;
            });*/

        },
        replyClick: function() {
            var item = $(this).parents('.b-comment');
            var id = item.length?parseInt($(item).attr('id').replace(/^[\D]+/,'')):0;
            $.wa_blog.comments.formAdd($(this).parent(), id);
            $(item).parents('.b-comments').find('.b-comment-reply:hidden').show();
            $(item).find('.b-comment-reply:visible').hide();
            return false;

        },
        formSubmit: function() {
            var button = $(this);
            button.attr('disabled', true);
            var status_container = button.parent().find('.b-comment-add-form-status');
            status_container.show();

            var container = $('ul#b-comment-add');
            var form = $($.wa_blog.comments.options.form_selector);
            $.post(form.attr('action'), form.serialize(), function(response) {
                button.attr('disabled', false);
                status_container.hide();

                if (response.status && response.status == 'ok' && response.data) {
                    var count_str = response.data.count_str;
                    var template = $(response.data.template).find('.b-comment');
                    var target = null;

                    // root
                    if (container.prev().is('.b-form-home')) {
                        target = container.prev().prev();
                    } else {
                        target = container.parent();
                        if (!target.children('ul.menu-v').not(container).size()) {
                            target = target
                                    .append('<ul class="menu-v with-icons"></ul>')
                                    .find('ul').not(container);
                        }
                    }

                    target.append($('<li />').append(template));
                    $.wa_blog.comments.formAdd('.b-form-home', 0);

                    // increment count comments for sidebur
                    $('.comment-count').each(function() {
                                $(this).text(parseInt($(this).text()) + 1);
                    });

                    $('.b-not-comment').remove();
                    $('.b-comment-count').show().html(count_str);

                    template.trigger('plugin.comment_add');
                    $.wa_blog.comments.formRefresh(true);
                } else if (response.status && response.status == 'fail') {
                    $.wa_blog.comments.formRefresh();
                    var errors = response.errors;
                    $(errors).each(function($name) {
                        var error = this;
                        for (name in error) {
                            var elem = $($.wa_blog.comments.options.form_selector).find('[name=' + name + ']');
                            error = $('<div class="hint errormsg"></div>').text(error[name]);
                            elem.after(error).addClass('error');
                        }
                    });
                } else {
                    $.wa_blog.comments.formRefresh();
                }
            }, 'json');
            return false;

        },
        formRefresh: function(empty) {
            var form = $($.wa_blog.comments.options.form_selector);
            form.parents('.b-comments').find('.b-comment-reply:hidden').show();
            form.find('.errormsg').remove();
            form.find('.error').removeClass('error');

            if (empty) {
                form.find('textarea').val('');
            }

        },
        formAdd: function(target, id) {
            this.formRefresh(false);
            if ($(target).size() == 0) {
                $($.wa_blog.comments.options.form_wrapper_selector).hide();
            } else {
                $($.wa_blog.comments.options.form_wrapper_selector).show().insertAfter(target).find('[name=text]').val('');
                $($.wa_blog.comments.options.form_wrapper_selector).find('input[name=parent]').val(id);
            }
        },
        manageComment : function(element,event) {
            var action = $(element).hasClass('b-comment-restore')
                    ? 'approved'//'restore'
                    : 'deleted';//'delete'
            var item = $(element).parents('.b-comment');
            var id = parseInt($(item).attr('id').replace(/^[\D]+/,''));
            var url = '?module=comments&action=edit';
            var self =this;
            $(element).hide().after('<i class="b-ajax-status-loading icon16 loading"></i>');
            $.ajax({
                        url : url,
                        type: 'POST',
                        data: {
                            'status':action,
                            'id':id
                        },
                        success : function(response) {
                            if ((response.data.status == 'ok') && response.data.changed) {
                                var delta = (response.data.status == 'deleted') ? -1 : 1;
                                $('.comment-count').each(function() {
                                    $(this).text(parseInt($(this).text())
                                            + delta);
                                });
                            }
                            item.find('i.icon16.loading').remove();
                            var count = null;
                            if (response && response.data && response.data.count_str) {
                                count = response.data.count_str;
                            }
                            self.setCommentStatus(id, response.data.status,count);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            item.find('i.icon16.loading').remove();
                            self.setCommentStatus(id, (action == 'approved')?'approved':'deleted',null);
                        },
                        dataType : 'json'
                    });
            return false;

        },
        setCommentStatus: function(id,status,count) {
            var item = $('#b-comment-'+id);
            if (status == 'deleted') {
                item.addClass('b-deleted');
                item.find('.b-comment-delete').hide();
                item.find('.b-comment-restore').show();
                $.wa_blog.common.onContentUpdate();
            } else if (status == 'approved') {
                item.removeClass('b-deleted');
                item.find('.b-comment-delete').show();
                item.find('.b-comment-restore').hide();
                $.wa_blog.common.onContentUpdate();
            }
            if(count !==null) {
                $('.b-comment-count').show().html(count);
            }
        }
    };
})(jQuery);
