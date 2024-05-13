( function($) {

/**
 * Initialize for legacy version
 * */
if (!$.wa_blog.ui_version) {
    $.wa_blog.plugins_favorites = {
        init: function () {
            var self = this;
            $(document).on("click", ".favorite-plugin a", self.clickHandler);
        },
        clickHandler: function(event) {
            event.preventDefault();

            var post_id = parseInt($(this).parents('.b-post').attr('id').replace(/^[\D]+/, ''));
            var i = $(this).find('i');

            if (i.hasClass('star')) {
                $.get('?plugin=favorite&action=delete&post_id=' + post_id,
                    function () {
                        i.removeClass('star').addClass('star-empty');
                        $('.favorites_count').text(
                            parseInt($('.favorites_count').text()) - 1);
                    });
            } else if (i.hasClass('star-empty')) {
                $.get('?plugin=favorite&action=add&post_id=' + post_id, function () {
                    i.removeClass('star-empty').addClass('star');
                    $('.favorites_count').addClass('highlighted').addClass('bold').text(parseInt($('.favorites_count').text()) + 1);
                });
            }
        }
    };
}

/**
 * Initialize for wa2.0 version
 * */
if ($.wa_blog.ui_version) {

    var xhr = null;

    $(document).on("click", ".js-post-favorite-toggle", function(event) {
        event.preventDefault();

        if (xhr) { return false; }

        var active_class = "is-active";

        var $target = $(this),
            $post = $target.closest(".b-post"),
            post_id = $post.attr("id").replace(/^\D+/, ''),
            is_active = $target.hasClass(active_class);

        if (!post_id) {
            console.error("POST ID is undefined");
            return false;
        }

        $target.toggleClass(active_class);
        $target.attr("title", $target.data(is_active ? "inactive-title" : "active-title"));

        $target.toggleClass('text-yellow');
        $target.toggleClass('text-light-gray');

        sendRequest(post_id, !is_active);
    });

    function sendRequest(post_id, plus) {
        if (xhr) { xhr.abort(); }

        xhr = $.get('?plugin=favorite&action=' + (plus ? "add" : "delete") + '&post_id=' + post_id)
            .always( function() { xhr = null; })
            .done( function() { updateCounter(plus); });

        return xhr;
    }

    function updateCounter(plus) {
        var $counter = $("#blog-plugin-favorites-counter");
        if ($counter.length) {
            var value = parseInt($counter.text());
            if (value >= 0) {
                $counter.text(value + (plus ? 1 : -1));
            }
        }
    }
}

})(jQuery);
