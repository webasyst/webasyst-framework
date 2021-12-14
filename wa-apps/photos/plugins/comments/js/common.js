$.photos = $.photos || {};
$.photos.comments_plugin = {
    hotkeys: {
        'alt+enter': {
            ctrl:false,
            alt:true,
            shift:false,
            key:13
        },
        'ctrl+enter': {
            ctrl:true,
            alt:false,
            shift:false,
            key:13
        },
        'ctrl+s': {
            ctrl:true,
            alt:false,
            shift:false,
            key:17
        }
    },
    prepareAddingForm: function(form, comment_id)
    {
        var self = this; // clicked link
        var target = $(this).parents('.comment');
        if(target.length) {
            target.append(form);
        } else if (comment_id) {
            self.after(form);
        } else {
            var acceptor = $('#add-comment-form-acceptor');
            if (!acceptor.find('form').length) {
                acceptor.append(form);
            }
        }
        $('#comment-id', form).val(comment_id);
    },

    addHotkeyHandler: function(item, hotkey, handler) {
        var self = this,
            hotkey = self.hotkeys[hotkey];
        item.on('keydown', function(e) {
            if (e.keyCode == hotkey.key &&
                e.altKey == hotkey.alt &&
                e.ctrlKey == hotkey.ctrl &&
                e.shiftKey == hotkey.shift)
            {
                return handler();
            }
        });
    },

    addComment: function(env) {
        var url = (env == 'backend' || !env) ? '?plugin=comments&module=backend&action=add' : 'comments/add',
            form = $('#add-comment-form'), data;
            if(form.is('form')) {
                data = form.serializeArray();
            } else {
                data = form.find('form:first').serializeArray();
            }
            var comment_id = $('#comment-id', form).val(),
            photo_id = $('#photo-id', form).val();

        data.push({
            name: 'photo_id',
            value: photo_id
        });
        data.push({
            name: 'photo_comments_count_text',
            value: $('#photo-comments-count-text').text()
        });
        // send request
        $.post(url, data, function (r) {
            if (r.status == 'fail') {
                $.photos.comments_plugin.clearFormErrors();
                for (var i = 0, n = r.errors.length, errors = r.errors[i]; i < n; errors = r.errors[++i]) {
                    for (var name in errors) {
                        var elem = $('#add-comment-form').find('[name='+name+']'),
                            error = $('<em class="errormsg state-error"></em>').text(errors[name]);
                        elem.after(error).addClass('error state-error');
                    }
                }
                $.photos.comments_plugin.refreshCaptcha();
                return;
            }
            if (r.status != 'ok' || !r.data.html) {
                return;
            }
            var html = r.data.html;
            var parent_li = $('#comment-' + comment_id),
                ul = $('ul:not(#user-auth-provider):first', parent_li),
                comments_block = null;

            if (!parent_li.length) {
                comments_block = $('#comments-block').show();
                ul = $('ul:first', comments_block).show();
                if (!ul.find('li[id^="comment-"]').length) {
                    ul.html('');
                }
                $('.comments-header', comments_block).show();
            }
            if (!ul.length) {
                ul = $('<ul class="menu-v with-icon menu"></ul>');
                parent_li.append(ul);
            }
            ul.append(html);

            // back form to 'add-comment' place and clear
            $('#comment-text', form).val('');
            var acceptor = $('#add-comment-form-acceptor');
            if(acceptor.length) {
                if (!acceptor.find('form').length) {
                    acceptor.append(form);
                    $('#comment-id', form).val(0);
                }
            } else if(!$('#add-comment-form').is('form')) {
                if(!$('#comments-block').nextAll('#add-comment-form').length) {
                    $('#comments-block').after(form);
                    $('#comment-id', form).val(0);
                }
            }

            // update count of comments in adding form
            $('#photo-comments-count-text').text(r.data.photo_comments_count_text);
            $.photos.comments_plugin.updateSidebarCounter('+1');//?

            $.photos.comments_plugin.clearFormErrors();
            $.photos.comments_plugin.clearFormInputs();
            $.photos.comments_plugin.refreshCaptcha();
        }, 'json');
    },
    clearFormErrors: function() {
        var form = $('#add-comment-form');
        form.find('.errormsg').remove();
        form.find('.error').removeClass('error state-error');
    },
    clearFormInputs: function() {
        var form = $('#add-comment-form');
        form.find('input[name=captcha]').val('');
    },
    refreshCaptcha: function() {
        var form = $('#add-comment-form');
        form.find('.wa-captcha-refresh').click();
    },
    updateSidebarCounter: function(count, new_count) {
        var counter = $('#comments-count');
        if (typeof count == 'string' && count.charAt(0) == '+') {
            count = parseInt(count, 10) + parseInt(counter.text()) || 0;
        }
        counter.text(count);
        new_count = parseInt(new_count, 10) || 0;
        if (new_count) {
            counter = $('#new-comments-count');
            counter.text('+' + new_count);
        }
    },
    updatePhotoCounter: function(count) {
        var counter = $('#photo-comments-count');
        if (typeof count == 'string' && count.charAt(0) == '+') {
            count = parseInt(count, 10) + parseInt(counter.text()) || 0;
        }
        counter.text(count);
    },
};