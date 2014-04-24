$(function(){

    var move_form_add = function(target, id, setfocus) {
        form_refresh();
        $('.comment-form').find('input[name=parent]').val( id );
        var textarea = $('.comment-form').show().insertAfter(target).find('[name=text]').val('');
        if(setfocus) {
            textarea.focus();
        }
    };

    var form_refresh = function(empty) {
        var form = $('.comment-form');
        form.find('.errormsg').remove();
        form.find('.error').removeClass('error');
        form.find('.wa-captcha-refresh').click();

        if (empty) {
            form.find('textarea').val('');
        }
    };

    // comments
    $('.comment-reply').live('click', function(){
        var item = $(this).parents('.comment');
        var id = item.length?parseInt($(item).attr('id').replace(/^[\D]+/,'')):0;
        move_form_add($(this).parent(), id, true);
        return false;
    });

    $('.comment-form input:submit').click(function(){
        var button = $(this);
        button.attr('disabled', true).next().show();

        var container = $('.comment-form');
        var form = $('.comment-form form');
        $.post(form.attr('action')+'?json=1', form.serialize(), function(response){

            button.attr('disabled', false).next().hide();

            if (response.status && response.status == 'ok' && response.data.redirect) {
                window.location.replace(response.data.redirect);
                window.location.href = response.data.redirect;
                return;
            }
            if ( response.status && response.status == 'ok' && response.data) {
                var template = $(response.data.template);
                var count_str = response.data.count_str;

                var target;
                if (container.prev().is('.comments')) {
                    target = container.prev('.comments').children('ul');
                } else {
                    target = container.parent();
                    if (!target.next('ul').size()) {
                        target.after('<ul class="menu-v with-icons"></ul>');
                    }
                    target = target.next('ul');
                }

                target.append( $('<li />').append(template) );
                move_form_add('.comments', 0);

                $('.not-comment').remove();
                $('.comment-count').show().html(count_str);

                template.trigger('plugin.comment_add');
                form_refresh(true);
            } else if( response.status && response.status == 'fail' ) {
                form_refresh();
                var errors = response.errors;
                $(errors).each(function($name){
                    var error = this;
                    for (name in error) {
                        var elem = $('.comment-form form').find('[name='+name+']');
                        elem.after($('<em class="errormsg"></em>').text(error[name])).addClass('error');
                    }
                });
            }
            else {
                form_refresh(false);
            }

        }, "json")
        .error(function(){
            form_refresh(false);
        });
        return false;
    });

    // view current auth profile
    var provider = 'guest';
    var selected = $('ul#user-auth-provider li.selected');
    if(selected.length){
        provider = selected.attr('data-provider') || provider;
    } else {
        selected = $('#user-auth-provider');
        if(selected.length){
            provider = selected.attr('data-provider') || provider;
        }

    }
    if(provider) {
        $('.tab').hide();
        if(provider == 'signup') {
            $('.comment-submit, .comment-body').hide();
        } else {
            $('.comment-submit, .comment-body').show();
        }

        $('div.tab[data-provider=\''+provider+'\']').show();
        $('input[name=auth_provider]').val(provider);
        if (provider == 'guest') {
            $('.wa-captcha').show();
        } else {
            $('.wa-captcha').hide();
        }
    }

    $('ul#user-auth-provider li.selected a, ul#user-auth-provider li:eq(0) a').click(function(){
        if ( $(this).parent().hasClass('selected') ) {
            return false;
        }
        var provider = $(this).parent().attr('data-provider');

        $(this).parent().addClass('selected').siblings().removeClass('selected');

        $('.tab').hide();
        $('div.tab[data-provider=\''+provider+'\']').show();
        if (provider == 'guest') {
            $('.wa-captcha').show();
        } else {
            $('.wa-captcha').hide();
        }


        if(provider == 'signup') {
            $('.comment-submit, .comment-body').hide();
        } else {
            $('.comment-submit, .comment-body').show();
        }

        $('input[name=auth_provider]').val(provider);

        return false;
    });

});

