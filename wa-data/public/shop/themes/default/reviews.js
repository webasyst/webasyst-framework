$(function() {
    /**
     * Hotkey combinations
     * {Object}
     */
    var hotkeys = {
        'alt+enter': {
            ctrl:false, alt:true, shift:false, key:13
        },
        'ctrl+enter': {
            ctrl:true, alt:false, shift:false, key:13
        },
        'ctrl+s': {
            ctrl:true, alt:false, shift:false, key:17
        }
    };

    var form_wrapper = $('#product-review-form');
    var form = form_wrapper.find('form');
    var content = $('#page-content .reviews');

    var input_rate = form.find('input[name=rate]');
    if (!input_rate.length) {
        input_rate = $('<input name="rate" type="hidden" value=0>').appendTo(form);
    }
    $('#review-rate').rateWidget({
        onUpdate: function(rate) {
            input_rate.val(rate);
        }
    });

    content.off('click', '.review-reply, .write-review a').on('click', '.review-reply, .write-review a', function() { 
        var self = $(this);
        var item = self.parents('li:first');
        var parent_id = parseInt(item.attr('data-id'), 10) || 0;
        prepareAddingForm.call(self, parent_id);
        $('.review').removeClass('in-reply-to');
        item.find('.review:first').addClass('in-reply-to');
        return false;
    });

    var captcha = $('.wa-captcha');
    var provider_list = $('#user-auth-provider li');
    var current_provider = provider_list.filter('.selected').attr('data-provider');
    if (current_provider == 'guest' || !current_provider) {
        captcha.show();
    } else {
        captcha.hide();
    }

    provider_list.find('a').click(function () {
        var self = $(this);
        var li = self.parents('li:first');
        if (li.hasClass('selected')) {
            return false;
        }
        li.siblings('.selected').removeClass('selected');
        li.addClass('selected');

        var provider = li.attr('data-provider');
        form.find('input[name=auth_provider]').val(provider);
        if (provider == 'guest') {
            $('div.provider-fields').hide();
            $('div.provider-fields[data-provider=guest]').show();
            captcha.show();
            return false;
        }
        if (provider == current_provider) {
            $('div.provider-fields').hide();
            $('div.provider-fields[data-provider='+provider+']').show();
            captcha.hide();
            return false;
        }

        var left = (screen.width - 600)/2;
        var top =  (screen.height- 400)/2;
        window.open(
            $(this).attr('href'), "oauth", "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no"
        );
        return false;
    });

    addHotkeyHandler('textarea', 'ctrl+enter', addReview);
    form.submit(function() {
        addReview();
        return false;
    });

    function addReview() {
        $.post(
            location.href.replace(/\/#\/[^#]*|\/#|\/$/g, '') + '/add/',
            form.serialize(),
            function (r) {
                if (r.status == 'fail') {
                    clear(form, false);
                    showErrors(form, r.errors);
                    return;
                }
                if (r.status != 'ok' || !r.data.html) {
                    if (console) {
                        console.error('Error occured.');
                    }
                    return;
                }
                var html = r.data.html;
                var parent_id = parseInt(r.data.parent_id, 10) || 0;
                var parent_item = parent_id ? form.parents('li:first') : content;
                var ul = $('ul.reviews-branch:first', parent_item);
                
                if (parent_id) {
                    //reply to a review
                    ul.show().append(html);
                    ul.find('li:last .review').addClass('new');
                } else {
                    //top-level review
                    ul.show().prepend(html);
                    ul.find('li:first .review').addClass('new');
                }
                
                $('.reviews-count-text').text(r.data.review_count_str);
                $('.reviews-count').text(r.data.count);
                form.find('input[name=count]').val(r.data.count);
                clear(form, true);
                content.find('.write-review a').click();
                
                form_wrapper.hide();
                if (typeof success === 'function') {
                    success(r);
                }
            },
        'json')
        .error(function(r) {
            if (console) {
                console.error(r.responseText ? 'Error occured: ' + r.responseText : 'Error occured.');
            }
        });
    };

    function showErrors(form, errors) {
        for (var name in errors) {
            $('[name='+name+']', form).after($('<em class="errormsg"></em>').text(errors[name])).addClass('error');
        }
    };

    function clear(form, clear_inputs) {
        clear_inputs = typeof clear_inputs === 'undefined' ? true : clear_inputs;
        $('.errormsg', form).remove();
        $('.error',    form).removeClass('error');
        $('.wa-captcha-refresh', form).click();
        if (clear_inputs) {
            $('input[name=captcha], textarea', form).val('');
            $('input[name=rate]', form).val(0);
            $('input[name=title]', form).val('');
            $('.rate', form).trigger('clear');
        }
    };

    function prepareAddingForm(review_id)
    {
        var self = this; // clicked link
        if (review_id) {
            self.parents('.actions:first').after(form_wrapper);
            $('.rate ', form).trigger('clear').parents('.review-field:first').hide();
        } else {
            self.parents('.write-review').after(form_wrapper);
            form.find('.rate').parents('.review-field:first').show();
        }
        clear(form, false);
        $('input[name=parent_id]', form).val(review_id);
        form_wrapper.show();
    };

    function addHotkeyHandler(item_selector, hotkey_name, handler) {
        var hotkey = hotkeys[hotkey_name];
        form.off('keydown', item_selector).on('keydown', item_selector,
            function(e) {
                if (e.keyCode == hotkey.key &&
                    e.altKey  == hotkey.alt &&
                    e.ctrlKey == hotkey.ctrl &&
                    e.shiftKey == hotkey.shift)
                {
                    return handler();
                }
            }
        );
    };
});