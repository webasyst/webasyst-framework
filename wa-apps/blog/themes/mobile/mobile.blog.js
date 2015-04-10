$(function(){

    $.blog_utils.highlight('#post-stream')

    var move_form_add = function(target, id, setfocus) {
        form_refresh();
        $('.comment-form').find('input[name=parent]').val( id );
        var textarea = $('.comment-form').show().insertAfter(target).find('[name=text]').val('');
        if(setfocus) {
            textarea.focus();
        }
    };

    var form_refresh = function(empty) {
    
        //comment form submit refresh
        var form = $('.comment-form');
        form.find('.errormsg').remove();
        form.find('.error').removeClass('error');
        form.find('.wa-captcha-refresh').click();

        if (empty) {
            form.find('textarea').val('');
        }
    };

    $('.comment-reply').live('click', function(){
    
        // reply to a comment
        var item = $(this).parents('.comment');
        var id = item.length?parseInt($(item).attr('id').replace(/^[\D]+/,'')):0;
        move_form_add($(this).parent(), id, true);
        $('.comment').removeClass('in-reply-to');
        item.addClass('in-reply-to');
        return false;
    });

    $('.comment-form input:submit').click(function(){
    
        //save comment
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
            
                // saved
                var template = $(response.data.template);
                var count_str = response.data.count_str;

                var target;
                if (container.prev().is('.comments')) {
                    target = container.prev('.comments').children('ul');
                } else {
                    target = container.parent();
                    if (!target.next('ul').size()) {
                        target.after('<ul></ul>');
                    }
                    target = target.next('ul');
                }

                target.append( $('<li />').append(template) );
                move_form_add('.comments', 0);

                if ( response.data.comment_id )
                    $('#comment-' + response.data.comment_id).addClass('new');
                    
                $('.not-comment').remove();
                $('.comment').removeClass('in-reply-to');
                $('.comment-count').show().html(count_str);

                template.trigger('plugin.comment_add');
                form_refresh(true);
                
            } else if( response.status && response.status == 'fail' ) {
            
                // error
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

$.blog_utils = {
    query: '',
    init: function() {
        // search highlight
        if (location.href.indexOf('?') !== -1) {
            var pos = location.href.indexOf('?');
            var params = location.href.slice(pos + 1).split('&');
            for (var i = 0; i < params.length; i += 1) {
                if (params[i].indexOf('query=') !== -1) {
                    this.query = params[i].slice(params[i].indexOf('query=') + 6);
                    break;
                }
            }
        }
    },
    highlight: function(container) {
        if (this.query) {
            $(container).find('.search-match').find('h3 a, p').each(function() {
                var text = $(this).html();
                text = text.replace(new RegExp('(' + $.blog_utils.query + ')', 'i'), '<span class="highlighted">$1</span>');
                $(this).html(text);
            });
        }
    }
};
$.blog_utils.init();