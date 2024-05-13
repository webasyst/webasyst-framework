/**
 * MAIN APP CONTROLLER
 */
/*( function($) { "use strict";
    $.wa_blog = {
        app_url: null,
        backend_url: null,
        rights: null,
        use_retina: false,
        ui_version: 2,

        init: {}
    };
})(jQuery);*/



$.storage = new $.store();
$.wa_blog_options = $.wa_blog_options ||{};
$.wa_blog = $.extend(true, $.wa_blog, {
    rights : {
        admin : false
    },
    ui_version: 2,
    common : {
        options : {},
        parent : null,
        init_stack : {},
        init : function(options) {
            var self = this;
            this.parent = $.wa_blog;
            this.options = $.extend(this.options, options);

            $(document).ready(function() {
                self.onDomReady(self.parent);
            });

        },
        onDomReady : function(blog) {
            blog = blog || $.wa_blog;
            $(window).scrollTop(0);
            for ( var i in blog) {
                if (i != 'common') {
                    if (blog[i].init && (typeof (blog[i].init) == 'function')) {
                        try {
                            blog[i].init($.wa_blog_options[i]||{});
                        } catch (e) {
                            if (typeof (console) == 'object') {
                                console.log(e);
                            }
                        }
                    }
                }
            }
        },
        ajaxInit : function(blog) {
            blog = blog || $.wa_blog;
            var stack = [];
            $(window).scrollTop(0);
            for ( var i in blog) {
                try {
                    if (i != 'common') {
                        if (blog[i].ajaxInit && (typeof (blog[i].ajaxInit) == 'function')) {

                            if (!this.init_stack[i]) {
                                blog[i].ajaxInit();
                                this.init_stack[i] = true;
                                stack[i] = true;
                            }

                        }
                    }
                } catch (e) {
                    stack[i] = false;
                    if (typeof (console) == 'object') {
                        console.log(e);
                    }
                }
            }
            return stack;
        },
        ajaxPurge : function(id) {
            if (this.init_stack[id]) {
                if ($.wa_blog[id]) {
                    try {
                        if ($.wa_blog[id].ajaxPurge && (typeof ($.wa_blog[id].ajaxPurge) == 'function')) {
                            $.wa_blog[id].ajaxPurge();
                        }
                    } catch (e) {
                        if (typeof (console) == 'object') {
                            console.log(e);
                        }
                    }
                    $.wa_blog[id] = {};
                }
                this.init_stack[id] = null;
            }
        },
        onContentUpdate : function(response, target) {
            var blog = this.parent;
            for ( var i in blog) {
                if (i != 'common') {
                    if (blog[i].onContentUpdate
                        && (typeof (blog[i].onContentUpdate) == 'function')) {
                        try {
                            blog[i].onContentUpdate();
                        } catch (e) {
                            if (typeof (console) == 'object') {
                                console.log(e);
                            }
                        }
                    }
                }
            }
        }
    },
    plugins : {
        // placeholder for plugins js code
    },
    dialogs : {
        pull : {},
        init : function() {
            var self = this;
            $(".dialog-confirm").on('click', self.confirm);
            $(".js-confirm").on('click', self.jsConfirm);
        },
        close : function(id) {
            if ($.wa_blog.dialogs.pull[id]) {
                $.wa_blog.dialogs.pull[id].trigger('close');
            }
        },
        confirm : function() {
            var id = $(this).attr('id').replace(/-.*$/, '');

            if (!$.wa_blog.dialogs.pull[id]?.length) {
                $.wa_blog.dialogs.pull[id] = $("#" + id + "-dialog").detach();
            }

            $.waDialog({
                $wrapper: $.wa_blog.dialogs.pull[id],
                disableButtonsOnSubmit : true,
                onSubmit : function() {
                    return false;
                }
            });
            return false;
        },
        jsConfirm : function() {
            var question = $(this).attr('title') || 'Are you sure?';
            if (!confirm(question)) {
                return false;
            }
        }

    },
    sidebar : {
        options : {
            key : 'blog/collapsible/'
        },
        init : function() {
            const self = this;

            $("details").each(function() {
                const $details = $(this);
                const details_id = $details.data("id");
                const $toggle = $details.find("summary > span");

                self.restore($details);

                $toggle.on('click', function() {
                    const is_hidden = !$details.is('[open]');
                    $.storage.set(self.options.key + details_id, is_hidden);
                });
            });

            // all drafts / my drafts filter
            (function() {
                const all_drafts_link = $('#b-all-drafts');
                const my_drafts_link = $('#b-my-drafts');
                const $drafts_handler = $('details[data-id="drafts"]');
                const $drafts_title = $drafts_handler.find('.title');

                function showMyDrafts(contact_id) {
                    $drafts_handler.find('li[data-contact-id!=' + contact_id + ']').hide();
                    $.storage.set('blog/my-drafts', 1);
                    all_drafts_link.show();
                    my_drafts_link.hide();
                    $drafts_title.hide().filter('.b-my-drafts').show();
                }

                function showAllDrafts() {
                    $drafts_handler.find('li').show();
                    $.storage.del('blog/my-drafts');
                    my_drafts_link.show();
                    all_drafts_link.hide();
                    $drafts_title.hide().filter('.b-all-drafts').show();
                }

                function clickHandler() {
                    const contact_id = $(this).data('contact-id');
                    if (contact_id) {
                        showMyDrafts(contact_id);
                    } else {
                        showAllDrafts();
                    }
                    return false;
                }

                function onCollapse(is_open) {
                    if (typeof is_open !== "boolean") {
                        is_open = !$(this).is('[open]');
                    }
                    const show_my_drafts = $.storage.get('blog/my-drafts');
                    if (is_open) {
                        if (show_my_drafts) {
                            clickHandler.apply(my_drafts_link.find('a').get(0));
                        } else {
                            clickHandler.apply(all_drafts_link.find('a').get(0));
                        }
                        $(this).find('.counter').hide();
                        $(this).find('.title').hide().filter(show_my_drafts ? '.b-my-drafts' : '.b-all-drafts').show();
                    } else {
                        my_drafts_link.hide();
                        all_drafts_link.hide();
                        $(this).find('.counter').hide().filter(show_my_drafts ? '.b-my-drafts' : '.b-all-drafts').show();
                        $(this).find('.title').hide().filter(show_my_drafts ? '.b-my-drafts' : '.b-all-drafts').show();
                    }
                }

                $drafts_handler.on('click', onCollapse);
                onCollapse.apply($drafts_handler.get(0), [$drafts_handler.is('[open]')]);
                all_drafts_link.find('a').click(clickHandler);
                my_drafts_link.find('a').click(clickHandler);
            })();

        },
        restore : function(Element) {
            const details_id = Element.data("id");
            if (details_id) {
                try {
                    if (details_id === 'blogs' && $.storage.get(this.options.key + details_id) == null) {
                        Element.attr('open', true);
                    }else{
                        Element.attr('open', $.storage.get(this.options.key + details_id));
                    }
                } catch (e) {
                    if (typeof (console) == 'object') {
                        console.log(e);
                    }
                }
            }
        },
        lockPosition: function () {
            const sidebar = document.querySelector('.sidebar-body');

            if (!sidebar) {
                return;
            }

            const sessionKey = 'blog/sidebar/position';

            function saveScrollPosition() {
                sessionStorage.setItem(sessionKey, sidebar.scrollTop.toString());
            }

            function loadScrollPosition() {
                const savedPosition = sessionStorage.getItem(sessionKey);
                if (savedPosition !== null) {
                    sidebar.scrollTop = parseInt(savedPosition, 10);
                }
            }

            window.onload = function() {
                loadScrollPosition();
                window.onbeforeunload = saveScrollPosition;
            };
        }
    },
    helpers : {
        init : function() {
            this.compileTemplates();
        },
        compileTemplates : function() {
            var pattern = /<\\\/(\w+)/g;
            var replace = '</$1';

            $("script[type$='x-jquery-tmpl']").each(function() {
                var id = $(this).attr('id').replace(/-template-js$/, '');
                try {
                    var template = $(this).html().replace(pattern, replace);
                    $.template(id, template);
                } catch (e) {
                    if (typeof (console) == 'object') {
                        console.log(e);
                    }
                }
            });
        }
    }

});

$.wa_blog.common.init();

