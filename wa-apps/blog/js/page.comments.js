( function($) {

    var CommentsPage = ( function($) {

        CommentsPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$comments_list = that.$wrapper.find(".js-comments-list");

            // CONST
            that.pages = parseInt(options["pages"]);
            that.templates = options["templates"];

            // DYNAMIC VARS
            that.page = parseInt(options["page"]);

            // INIT
            that.init();
        };

        CommentsPage.prototype.init = function() {
            var that = this;

            that.initCommentDelete();
            that.initCommentReply();
            that.initLazy();
        };

        CommentsPage.prototype.initCommentDelete = function() {
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

        CommentsPage.prototype.initCommentReply = function() {
            var that = this,
                $form = null,
                loading = "<span class=\"icon\" style=\"margin-left: .5rem\"><i class=\"fas fa-spinner fa-spin\"></i></span>";

            that.$wrapper.on("click", ".b-comment .js-comment-reply", function(event) {
                event.preventDefault();

                var $link = $(this),
                    $comment = $(this).closest(".b-comment"),
                    comment_id = $comment.data("id");

                $link.hide();

                if ($form) { $form.remove(); }

                $form = $(that.templates["comment_form"]);
                $form.find("[name=\"parent\"]:first").val(comment_id);
                $form.appendTo($comment.find(".js-comment-footer"));

                initCommentForm($form, $comment).always( function() {
                    $link.show();
                });
            });

            function initCommentForm($form, $comment) {
                var deferred = $.Deferred(),
                    is_locked = false;

                var $textarea = $form.find("textarea:first"),
                    $submit_button = $form.find(".js-submit-button");

                $form.on("click", ".js-cancel-button", function(event) {
                    event.preventDefault();
                    $form.remove();
                    deferred.reject();
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
                                    var $comment_wrapper = $comment.closest(".b-comment-wrapper");
                                    $(response.data.template).find(".b-comment").each( function() {
                                        var $new_comment = $("<div class=\"b-comment-wrapper\" />").append(this).appendTo($comment_wrapper);
                                        highlightComment($new_comment);
                                    });
                                }

                                $form.remove();

                                deferred.resolve();
                            });
                    }
                });

                $textarea.on("keyup", function () {
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

                return deferred.promise();

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

        CommentsPage.prototype.initLazy = function() {
            var that = this,
                $page_footer = that.$wrapper.find(".b-page-footer"),
                $message = $page_footer.find(".js-loading-message");

            var is_loading = false;

            initWatcher($page_footer[0]);

            function initWatcher(node) {
                var observer = new IntersectionObserver(function(entries) {
                    var is_visible = entries[0].isIntersecting;
                    if (is_visible) { load(); }
                }, { threshold: [0,1] });
                observer.observe(node);
            }

            function load() {
                if (that.page < that.pages && !is_loading) {
                    is_loading = true;
                    getComments(that.page + 1)
                        .done( function($comments) {
                            if ($comments) {
                                that.$comments_list.append($comments);
                            }

                            that.page += 1;

                            if (that.page === that.pages) {
                                $message.remove();
                            }
                        })
                        .always( function() {
                            is_loading = false;
                        });
                }

                function getComments(page_id) {
                    var deferred = $.Deferred();

                    $.get("?module=comments", { page: page_id }, "json")
                        .done( function(html) {
                            var $comments = $("<div />").html(html).find(".b-comment-wrapper");
                            deferred.resolve($comments ? $comments : null);
                        });

                    return deferred.promise();
                }
            }

        };

        return CommentsPage;

    })($);

    $.wa_blog.init.initCommentsPage = function(options) {
        return new CommentsPage(options);
    };

})(jQuery);