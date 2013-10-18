(function($) {
    $.wa_blog.settings = {
        options : {
            content_id : 'post_text',
            dateFormat : 'yy-mm-dd',
            dateFirstDay : 1,
            dateMonthCount : 2,
            dateShowWeek : false
        },
        init : function(options) {
            this.options = $.extend(this.options, options);
            var self = this;

            $("#b-settings-blog-type").iButton({
                labelOn : "",
                labelOff : "",
                className: 'mini'
            });

            setupBlogUrlWidget();

            $('#blog-name').keyup(
                    function() {
                        var input = $(this), msg = input.next('.max255');
                        if (input.val().length > 255 && !msg.length) {
                            input.after('<em class="hint max255">' + $.cl.loc.max + '</em>');
                        } else if (input.val().length <= 255 && msg.length) {
                            msg.remove();
                        }
                    });

            $('.b-setting-icon').click(
                    function() {
                        $(this).parent().find('li.selected').removeClass('selected');
                        $(this).addClass('selected');
                        $(this).parents('.value').find('input[name="settings\\[icon\\]"]').val($(this).attr('id').replace(/^b-icon-/,''));
                        $('#b-icon-url').val('');
                        return false;
                    });

            $('.b-blog-settings-colorbox a').click(
                    function(e) {
                        var color = $(this).find('input').attr('checked', true)    .val();
                        $(".triple-padded:not(.b-stream-title)").attr('class','block triple-padded b-post ' + color);
                        if ($(e.target).is(':radio')) {
                            return true;
                        }
                        return false;
                    });


            self.status_check($('#b-settings-blog-type'));
            $('#b-settings-blog-type').change(function() {
                self.status_check(this);
            });

            function showErrorMsg(input, msg)
            {
                var selector = '#message-' + input.attr('id');
                input.addClass('error');
                $(selector).addClass('errormsg').text(msg);
            }

            function hideErrorMsg(input)
            {
                var selector = '#message-' + input.attr('id');
                input.removeClass('error');
                $(selector).removeClass('errormsg').text('');
            }

            $('#blog-name, #blog-url').focus(function() {
                hideErrorMsg($(this));
            });

            $('form input:submit').click(function() {
                $(this).after('<i class="icon16 loading"></i>');
                return true;
            });

            $('#inline-edit-url').click(function() {
                $(this).prev().andSelf().hide();
                $(this).next().removeAttr('disabled');
                $(this).next().show();
                return false;
            });

            function setupBlogUrlWidget()
            {
                var slug = null;
                var id = $('#blog-id').length ? $('#blog-id').val() : null;

                init();

                function getDescriptor(blogName)
                {
                    var descriptor = null;

                    $.ajax({
                        url : '?module=blog&action=GetBlogUrl',
                        data: { 'blog_name': blogName },
                        dataType: 'json',
                        type: 'post',
                        async: false,
                        success: function(response) {
                            descriptor = response['data'];
                        }
                    });

                    return descriptor;
                }

                function show(descriptor)
                {
                    if (descriptor.slug) {
                        $('#blog-url').val(descriptor.slug);
                        slug = descriptor.slug;
                    }

                    updateSlugs();

                    $('#b-settings-blog-url').show();

                }

                function hide()
                {
                    $('#b-settings-blog-url').hide();
                }

                function updateSlugs()
                {
                    $('.slug[data-single!=1]').each(function() {
                        if (slug) {
                            var a = $(this).text(slug + '/').parents('a:first');
                            a.attr('href', a.text());
                        } else {
                            $(this).text('');
                        }
                    });
                }

                function init()
                {
                    var blogUrlHandler = function() {

                        if (this.value && $('#b-settings-blog-type').attr('checked')) {

                            var descriptor = getDescriptor(this.value);

                            if (descriptor) {
                                show(descriptor);
                            }

                        }

                    };

                    var timerId = null;
                    // when stop typing trigger handler
                    $('#blog-name').bind('keydown', function() {
                        if (id) {
                            return ;
                        }

                        var self = this;
                        if (timerId) {
                            clearInterval(timerId);
                        }
                        timerId = setTimeout(function() {
                            blogUrlHandler.call(self);
                        }, 500);

                    });
                    // if you edit url must not be trigger of handler
                    $('#blog-url').keydown(function() {
                        if (timerId) {
                            clearInterval(timerId);
                            $('#blog-name').unbind('keydown');
                        }
                    }).keyup(function(e) {
                        if (e.keyCode != 9) {        // ignore when blur from other input to this input
                            slug = this.value;
                            updateSlugs();
                        }
                    });

                }

            } // setupBlogUrlWidget

        },
        status_check : function(item) {
            if ($(item).is(':checked')) {
                $('#b-settings-blog-type-open-hint').show();
                $('#b-settings-blog-type-private-hint').hide();

                $('#b-settings-blog-type-open-label').addClass('b-unselected');
                $('#b-settings-blog-type-private-label').removeClass('b-unselected');

                $('#blog-name').val() ? $('#b-settings-blog-url').fadeIn() : null;
            } else {
                $('#b-settings-blog-type-open-hint').hide();
                $('#b-settings-blog-type-private-hint').show();

                $('#b-settings-blog-type-open-label').removeClass('b-unselected');
                $('#b-settings-blog-type-private-label').addClass('b-unselected');

                $('#b-settings-blog-url').fadeOut();
            }
        }

    };
})(jQuery);
