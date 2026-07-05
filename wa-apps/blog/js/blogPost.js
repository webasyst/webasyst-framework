(function ($) {
    $.wa_blog.post = {
        options: {
            redirect: '?',
            $form: $('#post-form')
        },
        init: function (options) {
            this.options = $.extend(this.options, options);
            var self = this;

            $('#postdelete-dialog input[type=button]').click(function (eventObject) {
                return self.deleteHandler.call(this);
            });

            $('#blog-stream-primary-menu .b-search-form input.search').keydown(function (eventObject) {
                if (eventObject.keyCode == 13) {
                    var query = $(this).val();
                    location.search = '?text=' + encodeURIComponent(query);
                    return false;
                }
            });
        },
        deleteHandler: function () {
            var $form = $.wa_blog.post.options.$form;
            var id = parseInt($form.find('input[name=id]').val());
            var blog_id = parseInt($form.find('input[name=blog_id]').val());

            if (id) {
                $(this).attr('disabled', true).after(
                    '<i class="icon16 loading"></i>');
                $.ajax({
                    url: '?module=post&action=delete',
                    data: {
                        id: [id]
                    },
                    type: 'post',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status == 'ok' && response.data.deleted) {
                            location.href = blog_id ? ('?blog=' + blog_id) : $.wa_blog.post.options.redirect;
                        } else {
                            $('#postdelete-dialog .icon16.loading').remove();
                            $('#postdelete-dialog input[type=button]:disabled')
                                .removeAttr('disabled');
                            $.wa_blog.dialogs.close('postdelete');
                        }
                    },
                    error: function (response) {
                        $('#postdelete-dialog .icon16.loading').remove();
                        $('#postdelete-dialog input[type=button]:disabled')
                            .removeAttr('disabled');
                        $.wa_blog.dialogs.close('postdelete');
                    }
                });
            } else {
                $.wa_blog.dialogs.close('postdelete');
            }
        }
    };
})(jQuery);
