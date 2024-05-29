( function($) {

    var PostsPage = ( function($) {

        PostsPage = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$posts_list = that.$wrapper.find(".js-posts-list");

            // CONST
            that.urls = options["urls"];
            that.use_retina = $.wa_blog.use_retina;
            that.pages = parseInt(options["pages"]);
            that.templates = options["templates"];
            that.locales = options["locales"];
            that.blog_id = options["blog_id"];

            // DYNAMIC VARS
            that.page = parseInt(options["page"]);

            // INIT
            that.init();
        };

        PostsPage.prototype.init = function() {
            var that = this;

            that.initHeader();
            that.initPosts(that.$posts_list.find(".b-post-wrapper"));

            that.useRetina(that.$wrapper);

            if (that.page < that.pages) {
                that.initLazy();
            }
        };

        PostsPage.prototype.initHeader = function() {
            var that = this;

            initManageMode();

            initStickyStyles();

            initSearch();

            initMassActions();

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

            function initManageMode() {
                var is_short_enabled = false,
                    use_short_after_manage = false;

                that.$wrapper.on("click", ".js-manage-start", function() {
                    manageToggle(true);
                    if (is_short_enabled) {
                        viewToggle(false);
                        use_short_after_manage = true;
                    }
                });

                that.$wrapper.on("click", ".js-manage-done", function() {
                    manageToggle(false);
                    if (use_short_after_manage) {
                        viewToggle(true);
                        use_short_after_manage = false;
                    }
                });

                var $toggle = that.$wrapper.find("#js-blogs-view-toggle");

                $toggle.waToggle({
                    change: function(event, target, toggle) {
                        var id = $(target).data("id");
                        viewToggle( (id === "thin") );
                    }
                });

                function manageToggle(show) {
                    var active_class = "is-manage-mode";
                    if (show) {
                        that.$wrapper.addClass(active_class);
                    } else {
                        that.$wrapper.removeClass(active_class);
                    }
                }

                function viewToggle(show) {
                    var active_class = "is-short-mode";
                    if (show) {
                        that.$wrapper.addClass(active_class);
                    } else {
                        that.$wrapper.removeClass(active_class);
                    }

                    is_short_enabled = show;
                }
            }

            function initSearch() {
                var $section = that.$wrapper.find(".js-search-section"),
                    $root_section = $section.closest(".b-title-section"),
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

            function initMassActions() {
                var is_locked = false,
                    posts_ids = {};

                var $counter = that.$wrapper.find(".js-mass-action-count");

                that.$wrapper.on("change", ".js-post-checkbox", onSelectPost);
                function onSelectPost() {
                    var $checkbox = $(this),
                        $post = $checkbox.closest(".b-post-wrapper"),
                        post_id = $post.data("id"),
                        is_active = $checkbox.is(":checked");

                    var checked_class = "is-selected";

                    if (is_active) {
                        $post.addClass(checked_class);
                        posts_ids[post_id] = $post;
                    } else {
                        $post.removeClass(checked_class);
                        if (posts_ids[post_id]) {
                            delete posts_ids[post_id];
                        }
                    }
                    $counter.text(Object.keys(posts_ids).length);
                }

                // MASS DELETE
                that.$wrapper.on("click", ".js-posts-mass-delete", function(event) {
                    event.preventDefault();

                    if (!Object.keys(posts_ids).length) { return false; }

                    if (is_locked) { return false; }

                    $.waDialog({
                        html: that.templates["posts-delete-dialog"],
                        onOpen: function($wrapper, dialog) {
                            $wrapper.on("click", ".js-delete-posts", function() {
                                is_locked = true;
                                deletePosts()
                                    .always( function() {
                                        is_locked = false;
                                    }).done( function() {
                                        $.each(posts_ids, function(post_id, $post) {
                                            $post.remove();
                                        });
                                        dialog.close();
                                    });
                            });
                        }
                    });
                });
                function deletePosts() {
                    var href = "?module=post&action=delete",
                        data = getData(posts_ids);

                    return $.post(href, data, "json")
                        .done( function() {
                            $.each(posts_ids, function(post_id, $post) {
                                $post.remove();
                            });
                        });

                    function getData(posts_ids) {
                        var result = [];

                        $.each(posts_ids, function(post_id) {
                            result.push({
                                name: "id[]",
                                value: post_id
                            });
                        });

                        return result;
                    }
                }

                // MASS MOVE
                that.$wrapper.on("click", ".js-posts-mass-move", function(event) {
                    event.preventDefault();

                    if (!Object.keys(posts_ids).length) { return false; }

                    if (is_locked) { return false; }

                    $.waDialog({
                        html: that.templates["post-move-dialog"],
                        onOpen: function($wrapper, dialog) {
                            $wrapper.on("click", ".js-confirm-move", function() {
                                is_locked = true;

                                var blog_id = $wrapper.find(".js-blog-id-field").val();

                                movePost(blog_id, posts_ids)
                                    .always( function() {
                                        is_locked = false;
                                    })
                                    .done( function() {
                                        if (that.blog_id) {
                                            $.each(posts_ids, function(post_id, $post) {
                                                $post.remove();
                                            });
                                        } else {
                                            location.reload();
                                        }
                                        dialog.close();
                                    });
                            });

                            function movePost(blog_id, posts_ids) {
                                var href = "?module=post&action=move",
                                    data = getData(posts_ids);

                                return $.post(href, data, "json");

                                function getData(posts_ids) {
                                    var result = [];

                                    $.each(posts_ids, function(post_id, $post) {
                                        result.push({
                                            name: "id[]",
                                            value: post_id
                                        });
                                    });

                                    result.push({
                                        name: "blog",
                                        value: blog_id
                                    });

                                    return result;
                                }
                            }
                        }
                    });
                });
            }
        };

        PostsPage.prototype.initPosts = function($posts) {
            var that = this;

            $posts.each( function() {
                $.wa_blog.init.initPost({
                    $wrapper: $(this),
                    blog_id: that.blog_id,
                    locales: that.locales,
                    templates: that.templates
                });
            });
        };

        PostsPage.prototype.initLazy = function() {
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
                    getPosts(that.page + 1)
                        .done( function($posts) {
                            if ($posts) {
                                that.$posts_list.append($posts);
                                that.initPosts($posts);
                                that.useRetina(that.$posts_list);
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

                function getPosts(page_id) {
                    var deferred = $.Deferred();

                    $.get(that.urls["load"], { page: page_id }, "json")
                        .done( function(html) {
                            var $posts = $("<div />").html(html).find(".b-post-wrapper");
                            deferred.resolve($posts ? $posts : null);
                        });

                    return deferred.promise();
                }
            }

        };

        PostsPage.prototype.useRetina = function($wrapper) {
            var that = this;

            if (!that.retina) { return false; }

            var active_class = "retinify";

            $wrapper.find("." + active_class).each( function() {
                $(this).removeClass(active_class).retina();
            });
        };

        return PostsPage;

    })($);

    $.wa_blog.init.initPostsPage = function(options) {
        return new PostsPage(options);
    };

})(jQuery);