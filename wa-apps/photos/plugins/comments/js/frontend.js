$(function() {
    $('.comment-reply').live('click', function() {
        var item = $(this).parents('.comment');
        var form = $('#add-comment-form'),
            self = $(this),
            comment_id = (self.parents('[id^="comment-"]').attr('id') || '').replace('comment-', '') || 0;
        $.photos.comments_plugin.prepareAddingForm.call(self, form, comment_id);
        $('.comment').removeClass('in-reply-to');
        item.addClass('in-reply-to');
        return false;
    });

    // add comment action
    $('#add-comment-button').live('click', function() {
        $.photos.comments_plugin.addComment('frontend');
        $('.comment').removeClass('in-reply-to');
        return false;
    });
    $.photos.comments_plugin.addHotkeyHandler(
        $('#comment-text'),
        'ctrl+enter',
        function() {
            $.photos.comments_plugin.addComment('frontend');
            return false;
        }
    );
});