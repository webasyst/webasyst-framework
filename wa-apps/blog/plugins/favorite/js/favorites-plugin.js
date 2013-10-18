$.wa_blog.plugins_favorites = {
    init : function() {
        var self = this;
        $('.favorite-plugin a').live('click', self.clickHandler);
    },
    clickHandler : function() {
        var post_id = parseInt($(this).parents('.b-post').attr('id').replace(/^[\D]+/,''));
        var i = $(this).find('i');

        if (i.hasClass('star')) {
            $.get('?plugin=favorite&action=delete&post_id=' + post_id,
                    function() {
                        i.removeClass('star').addClass('star-empty');
                        $('.favorites_count').text(
                                parseInt($('.favorites_count').text()) - 1);
                    });
        } else if (i.hasClass('star-empty')) {
            $.get('?plugin=favorite&action=add&post_id=' + post_id, function() {
                i.removeClass('star-empty').addClass('star');
                $('.favorites_count').addClass('highlighted').addClass('bold').
                        text(parseInt($('.favorites_count').text()) + 1);
            });
        }
        return false;
    }
};