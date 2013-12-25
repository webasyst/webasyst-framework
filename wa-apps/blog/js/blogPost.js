(function ($) {
    $.wa_blog.post = {
        options: {
            redirect: '?'
        },
        init: function (options) {
            this.options = $.extend(this.options, options);
            var self = this;

            $('#postdelete-dialog input[type=button]').click(function (eventObject) {
                return self.deleteHandler.apply(self, [this, eventObject]);
            });
        },
        deleteHandler: function (element, event) {
            var form = $(element).parents('form');
            var id = parseInt(form.find('input[name=id]').val());
            var blog_id = parseInt(form.find('input[name=blog_id]').val());
            if (id) {
                $(element).attr('disabled', true).after(
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
