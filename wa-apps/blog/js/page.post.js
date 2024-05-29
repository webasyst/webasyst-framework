( function($) {

    var PostPage = ( function($) {

        PostPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$comments_list = that.$wrapper.find(".js-comments-list");

            // CONST
            that.use_retina = $.wa_blog.use_retina;
            that.post_id = options["post_id"];
            that.blog_id = options["blog_id"];

            that.templates = options["templates"];
            that.locales = options["locales"];
            that.urls = options["urls"];

            // DYNAMIC VARS

            // INIT
            that.init();
        };

        PostPage.prototype.init = function() {
            var that = this;

            $(document).ready( function() {
                if (location.hash === "#comments") {
                    setTimeout( function() {
                        $(window).scrollTop( that.$comments_list.offset().top - 150);
                    }, 10);
                }
            });

            that.initHeader();
            that.initPosts();
            that.useRetina(that.$wrapper);
            that.initCommentDelete();
            that.initCommentReply();
        };

        PostPage.prototype.initHeader = function() {
            var that = this;

            initStickyStyles();

            initSearch();

            function initStickyStyles() {
                var $wrapper = that.$wrapper.find(".js-page-header"),
                    active_class = "is-sticky-active";

                $(window).on("scroll", function() {
                    var scroll_top = $(this).scrollTop();
                    if (scroll_top > 0) {
                        $wrapper.addClass(active_class);
                    } else {
                        $wrapper.removeClass(active_class);
                    }
                });
            }

            function initSearch() {
                var $section = that.$wrapper.find(".js-search-section"),
                    $root_section = $section.closest(".b-actions-section"),
                    $field = $section.find(".js-search-field");

                var is_search_page = !!$field.val().length;

                var is_locked = false;

                $field.on("focus", function() {
                    sectionToggle(true);
                });

                $field.on("blur", function() {
                    var value = !!$field.val();
                    if (!value) { sectionToggle(false); }
                });

                $section.on("click", ".js-search-cancel", function(event) {
                    event.preventDefault();
                    sectionToggle(false);
                });

                $section.on("click", ".js-search-reset", function(event) {
                    event.preventDefault();
                    if (is_search_page) {
                        location.href = $.wa_blog.app_url;
                    } else {
                        $field.val("").trigger("focus");
                    }
                });

                function sectionToggle(show) {
                    var animate_class = "is-animated",
                        active_class = "is-search-extended";

                    var animation_time = 200;

                    if (show) {
                        $root_section
                            .addClass(animate_class)
                            .addClass(active_class);
                    } else {
                        if (!is_locked) {
                            is_locked = true;
                            $root_section.removeClass(active_class);

                            setTimeout( function() {
                                $root_section.removeClass(animate_class);
                                is_locked = false;
                            }, animation_time);
                        }
                    }
                }
            }
        };

        PostPage.prototype.initPosts = function() {
            var that = this;

            that.$wrapper.find(".b-post-wrapper").each( function() {
                $.wa_blog.init.initPost({
                    $wrapper: $(this),
                    blog_id: that.blog_id,
                    locales: that.locales,
                    templates: that.templates
                });
            });
        };

        PostPage.prototype.useRetina = function($wrapper) {
            var that = this;

            if (!that.retina) { return false; }

            var active_class = "retinify";

            $wrapper.find("." + active_class).each( function() {
                $(this).removeClass(active_class).retina();
            });
        };

        PostPage.prototype.initCommentDelete = function() {
            var that = this;

            var delete_class = "is-deleted";

            that.$wrapper.on("click", ".b-comment .js-comment-delete", function(event) {
                event.preventDefault();

                var $link = $(this),
                    $comment = $link.closest(".b-comment"),
                    comment_id = $comment.data("id"),
                    is_locked = (typeof $comment.data("locked") === "boolean" ? $comment.data("locked") : false);

                if (!is_locked) {
                    $comment
                        .addClass(delete_class)
                        .data("locked", true);

                    sendRequest(comment_id, "deleted")
                        .always( function() {
                            $comment.removeData("locked");
                        })
                        .done( function() {
                            $link.hide();
                            $comment.find(".js-comment-restore").show();
                        });
                }
            });

            that.$wrapper.on("click", ".b-comment .js-comment-restore", function(event) {
                event.preventDefault();

                var $link = $(this),
                    $comment = $link.closest(".b-comment"),
                    comment_id = $comment.data("id"),
                    is_locked = (typeof $comment.data("locked") === "boolean" ? $comment.data("locked") : false);

                if (!is_locked) {
                    $comment.data("locked", true);

                    sendRequest(comment_id, "approved")
                        .always( function() {
                            $comment.removeData("locked");
                        })
                        .done( function() {
                            $link.hide();
                            $comment
                                .removeClass(delete_class)
                                .find(".js-comment-delete").show();
                        });
                }
            });

            /**
             * @param {String} comment_id
             * @param {String} status
             * */
            function sendRequest(comment_id, status) {
                var href = "?module=comments&action=edit",
                    data = {
                        id: comment_id,
                        status: status
                    };

                return $.post(href, data,"json");
            }
        };

        PostPage.prototype.initCommentReply = function() {
            var that = this,
                $form = null,
                loading = "<span class=\"icon\" style=\"margin-left: .5rem\"><i class=\"fas fa-spinner fa-spin\"></i></span>";

            var $new_comment_form = that.$wrapper.find(".b-comment-form-section .b-comment-form");
            $new_comment_form.attr("action", "?module=comments&action=add&id=" + that.post_id);
            initCommentForm($new_comment_form, null);

            that.$wrapper.on("click", ".b-comment .js-comment-reply", function(event) {
                event.preventDefault();

                var $comment = $(this).closest(".b-comment"),
                    comment_id = $comment.data("id");

                if ($form) { $form.remove(); }

                $form = $(that.templates["comment_form"]);
                $form.find("[name=\"parent\"]:first").val(comment_id);
                $form.appendTo($comment.find(".js-comment-footer"));

                initCommentForm($form, $comment);
            });

            function initCommentForm($form, $comment) {
                var is_locked = false;

                var $submit_button = $form.find(".js-submit-button");

                $form.on("keyup", "textarea", function() {
                    var $textarea = $(this);

                    $textarea.css({
                        "min-height": 0,
                        "overflow": "hidden"
                    });

                    var scroll_h = $textarea[0].scrollHeight;

                    $textarea.css({
                        "min-height": scroll_h + "px",
                        "overflow": "visible"
                    });
                });

                $form.on("click", ".js-cancel-button", function(event) {
                    event.preventDefault();
                    $form.remove();
                });

                $form.on("submit", function(event) {
                    event.preventDefault();

                    if (!is_locked) {
                        is_locked = true;
                        var $loading = $(loading).appendTo($submit_button.attr("disabled", true));

                        sendRequest()
                            .always( function () {
                                is_locked = false;
                                $loading.remove();
                                $submit_button.attr("disabled", false);
                            })
                            .done( function(response) {
                                if (response.status === "ok") {
                                    // reply
                                    if ($comment) {
                                        var $comment_wrapper = $comment.closest(".b-comment-wrapper");
                                        $(response.data.template).find(".b-comment").each( function() {
                                            var $new_comment = $("<div class=\"b-comment-wrapper\" />").append(this).appendTo($comment_wrapper);
                                            highlightComment($new_comment);
                                        });

                                    // new
                                    } else {
                                        $(response.data.template).find(".b-comment").each( function() {
                                            var $new_comment = $("<div class=\"b-comment-wrapper\" />").append(this).appendTo(that.$comments_list);
                                            highlightComment($new_comment);
                                        });
                                    }
                                }

                                if ($comment) {
                                    $form.remove();
                                } else {
                                    $form[0].reset();
                                }
                            });
                    }
                });

                function sendRequest() {
                    var href = $form.attr("action"),
                        data = $form.serializeArray();

                    return $.post(href, data, "json");
                }

                function highlightComment($comment) {
                    var active_class = "is-highlighted";
                    $comment.addClass(active_class);
                    setTimeout( function() {
                        $comment.removeClass(active_class);
                    }, 2000);
                }
            }
        };

        return PostPage;

    })($);

    $.wa_blog.init.initPostPage = function(options) {
        return new PostPage(options);
    };

})(jQuery);