( function($) {

    var Post = ( function($) {

        Post = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];

            // CONST
            that.post_id = that.$wrapper.data("id");
            that.blog_id = options["blog_id"];
            that.locales = options["locales"];
            that.templates = options["templates"];

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        Post.prototype.init = function() {
            var that = this,
                is_locked = false;

            that.$wrapper.on("click", ".js-post-move", function(event) {
                event.preventDefault();

                if (is_locked) { return false; }

                var $post = $(this).closest(".b-post-wrapper"),
                    post_id = $post.data("id");

                $.waDialog({
                    html: that.templates["post-move-dialog"],
                    onOpen: function($wrapper, dialog) {
                        $wrapper.on("click", ".js-confirm-move", function() {
                            is_locked = true;

                            var blog_id = $wrapper.find(".js-blog-id-field").val();

                            movePost(blog_id, post_id)
                                .always( function() {
                                    is_locked = false;
                                })
                                .done( function() {
                                    if (that.blog_id) {
                                        $post.remove();
                                    } else {
                                        location.reload();
                                    }
                                    dialog.close();
                                });
                        });

                        function movePost(blog_id, post_id) {
                            var href = "?module=post&action=move",
                                data = {
                                    id: [post_id],
                                    blog: blog_id
                                };

                            return $.post(href, data, "json");
                        }
                    }
                });
            });

            that.$wrapper.on("click", ".js-post-delete", function(event) {
                event.preventDefault();

                if (is_locked) { return false; }

                var $post = $(this).closest(".b-post-wrapper"),
                    post_id = $post.data("id");

                $.waDialog({
                    html: that.templates["post-delete-dialog"],
                    onOpen: function($wrapper, dialog) {
                        $wrapper.on("click", ".js-delete-post", function() {
                            is_locked = true;
                            deletePost(post_id, $post)
                                .always( function() {
                                    is_locked = false;
                                }).done( function() {
                                    dialog.close();
                                    location.href = $.wa_blog.app_url;
                                });
                        });
                    }
                });

                function deletePost(post_id, $post) {
                    var href = "?module=post&action=delete",
                        data = { "id[]": post_id };

                    return $.post(href, data, "json")
                        .done( function() {
                            $post.remove();
                        });
                }
            });

            that.$wrapper.find(".dropdown").each( function() {
                $(this).waDropdown();
            });
        };

        return Post;

    })(jQuery);

    $.wa_blog.init.initPost = function(options) {
        return new Post(options);
    };

})(jQuery);