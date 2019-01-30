//  MAIN APP CONTROLLER
( function($) {
    $.team = {
        app_url: false,
        content: false,
        sidebar: false,
        calendar: false,
        /* Need for title generation */
        title_pattern: "Team — %s",

        /** One-time initialization called from layout */
        init: function(options) {

            'is_debug|app_url|locales'.split('|').forEach(function(k) {
                $.team[k] = options[k];
            });

            // Set up CSRF
            $(document).ajaxSend(function(event, xhr, settings) {
                if (settings.crossDomain || (settings.type||'').toUpperCase() !== 'POST') {
                    return;
                }

                var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
                var csrf = matches ? decodeURIComponent(matches[1]) : '';
                settings.data = settings.data || '';
                if (typeof(settings.data) == 'string') {
                    if (settings.data.indexOf('_csrf=') == -1) {
                        settings.data += (settings.data.length > 0 ? '&' : '') + '_csrf=' + csrf;
                        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    }
                } else if (typeof(settings.data) == 'object') {
                    settings.data['_csrf'] = csrf;
                }
            });

            /* Main content router */
            $.team.content = new ContentRouter({
                $content: $("#t-content")
            });

            /* Sync for external calendars */
            $.team.setSync();

        },

        /* Used on each content page */
        setTitle: function( title_string ) {
            if (title_string) {
                var state = history.state;
                if (state) {
                    state.title = title_string;
                }
                document.title = $.team.title_pattern.replace("%s", title_string);
            }
        },

        /* Open dialog to confirm contact deletion */
        confirmContactDelete: function(contact_ids, o) {
            $.post('?module=users&action=prepareDelete', { id: contact_ids }, function(html) {
                var dialog = new TeamDialog({
                    html: html
                });

                if (o && o.onInit) {
                    o.onInit();
                }
                if (o && o.onCancel) {
                    dialog.$wrapper.on('close', o.onCancel);
                }
                if (o.onCancel || o.onDelete) {
                    dialog.$wrapper.on('contacts_deleted', function(e, contact_ids) {
                        if (o && o.onCancel) {
                            dialog.$wrapper.off('close', o.onCancel);
                        }
                        if (o && o.onDelete) {
                            o.onDelete(contact_ids);
                        }
                    });
                }
            });
        },

        /* Initialized in templates/layouts/Default.html */
        setSync: function () {
            var coef = Math.floor(Math.random() * 100) / 100,
                delay = 30000 + coef * 30000,
                xhr, timer;

            setTimeout(run, $.team.is_debug ? 100 : delay / 2);

            function run() {
                console.log('send sync request');
                $.post($.team.app_url + "?module=calendarExternal&action=sync")
                    .always(function () {
                        xhr = null;
                        timer = setTimeout(run, delay);
                    })
                    .error( function () {
                        return false;
                    });
            }
        }
    };
})(jQuery);

// Team :: ContentRouter
// Initialized in templates/layouts/Default.html
var ContentRouter = ( function($) {

    ContentRouter = function(options) {
        var that = this;

        // DOM
        that.$window = $(window);
        that.$content = options["$content"];

        // VARS
        that.api_enabled = ( window.history && window.history.pushState );

        // DYNAMIC VARS
        that.xhr = false;
        that.is_enabled = true;

        // INIT
        that.initClass();
    };

    ContentRouter.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
    };

    ContentRouter.prototype.bindEvents = function() {
        var that = this;

        // When user clicks a link that leads to team app backend,
        // load content via XHR instead.
        var full_app_url = window.location.origin + $.team.app_url;
        $(document).on('click', 'a', function(event) {
            var use_content_router = ( that.is_enabled && ( this.href.substr(0, full_app_url.length) == full_app_url ) );

            if (event.ctrlKey || event.shiftKey || event.metaKey) {

            } else if (use_content_router) {
                event.preventDefault();
                that.load(this.href);
            }
        });

        $("#wa-app-team").on("click", "a", function(event) {
            event.stopPropagation();
        });

        if (that.api_enabled) {
            window.onpopstate = function(event) {
                event.stopPropagation();
                that.onPopState(event);
            };
        }
    };

    ContentRouter.prototype.load = function(content_uri, is_reload) {
        var that = this;

        var uri_has_app_url = ( content_uri.indexOf( $.team.app_url ) >= 0 );
        if (!uri_has_app_url) {
            // TODO:
            alert("Determine the path error");
            return false;
        }

        that.animate( true );

        if (that.xhr) {
            that.xhr.abort();
        }

        $(document).trigger('wa_before_load', {
            // for which these data ?
            content_uri: content_uri
        });

        that.xhr = $.get(content_uri, function(html) {
            if (!is_reload && that.api_enabled) {
                history.pushState({
                    reload: true,               // force reload history state
                    content_uri: content_uri    // url, string
                    // content: html,              // ajax html, string
                }, "", content_uri);
            }
            that.setContent( html );

            that.animate( false );

            that.xhr = false;
            $(document).trigger("wa_loaded");
        });
    };

    ContentRouter.prototype.reload = function() {
        var that = this,
            content_uri = (that.api_enabled && history.state && history.state.content_uri) ? history.state.content_uri : false;

        if (content_uri) {
            that.load(content_uri, true);
        }
    };

    ContentRouter.prototype.setContent = function( html ) {
        var that = this;

        that.$content.html( html );
    };

    ContentRouter.prototype.onPopState = function(event) {
        var that = this,
            state = ( event.state || false );

        if (state) {
            if (!state.content_uri) {
                // TODO:
                alert("Determine the path error");
                return false;
            }

            $(document).trigger('wa_before_load', {
                // for which these data?
                content_uri: state.content_uri
            });

            // CONTENT
            if (state.reload) {
                that.reload( state.content_uri );
            } else if (state.content) {
                that.setContent( state.content );
            }

            // TITLE
            if (state.title) {
                $.team.setTitle(state.title);
            }

            // SIDEBAR
            $.team.sidebar.selectLink( state.content_uri );

            $(document).trigger('wa_loaded');
        } else {
            location.reload();
        }
    };

    ContentRouter.prototype.animate = function( show ) {
        var that = this,
            $content = that.$content;

        $(".router-loading-indicator").remove();

        if (show) {
            var $header = $content.find(".t-content-header h1"),
                loading = '<i class="icon16 loading router-loading-indicator"></i>';

            if ($header.length) {
                $header.append(loading);
            }
        }
    };

    return ContentRouter;

})(jQuery);

// Team :: Dialog
// Helper used in many places.
var TeamDialog = ( function($) {

    TeamDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = $(options["html"]);
        that.$block = false;
        that.is_full_screen = ( that.$wrapper.hasClass("is-full-screen") );
        if (that.is_full_screen) {
            that.$block = that.$wrapper.find(".t-dialog-block");
        }

        // VARS
        that.position = ( options["position"] || false );

        // DYNAMIC VARS
        that.is_closed = false;

        //
        that.userPosition = ( options["setPosition"] || false );

        // HELPERS
        that.onBgClick = ( options["onBgClick"] || false );
        that.onOpen = ( options["onOpen"] || function() {} );
        that.onClose = ( options["onClose"] || function() {} );
        that.onRefresh = ( options["onRefresh"] || false );
        that.onResize = ( options["onResize"] || false );

        // INIT
        that.initClass();
    };

    TeamDialog.prototype.initClass = function() {
        var that = this;
        // save link on dialog
        that.$wrapper.data("teamDialog", that);
        //
        that.show();
        //
        that.bindEvents();
    };

    TeamDialog.prototype.bindEvents = function() {
        var that = this,
            $document = $(document),
            $block = (that.$block) ? that.$block : that.$wrapper;

        // Delay binding close events so that dialog does not close immidiately
        // from the same click that opened it.
        setTimeout(function() {

            $document.on("click", close);
            $document.on("wa_before_load", close);
            that.$wrapper.on("close", close);

            // Click on background, default nothing
            if (that.is_full_screen) {
                that.$wrapper.on("click", ".t-dialog-background", function(event) {
                    if (!that.onBgClick) {
                        event.stopPropagation();
                    } else {
                        that.onBgClick(event);
                    }
                });
            }

            $block.on("click", function(event) {
                event.stopPropagation();
            });

            $(document).on("keyup", function(event) {
                var escape_code = 27;
                if (event.keyCode === escape_code) {
                    that.close();
                }
            });

            $block.on("click", ".js-close-dialog", function() {
                close();
            });

            function close() {
                if (!that.is_closed) {
                    that.close();
                }
                $document.off("click", close);
                $document.off("wa_before_load", close);
            }

            if (that.is_full_screen) {
                $(window).on("resize", onResize);
            }

            function onResize() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.resize();
                } else {
                    $(window).off("resize", onResize);
                }
            }

        }, 0);

    };

    TeamDialog.prototype.show = function() {
        var that = this;

        $("body").append( that.$wrapper );

        //
        that.setPosition();
        //
        that.onOpen(that.$wrapper, that);
    };

    TeamDialog.prototype.setPosition = function() {
        var that = this,
            $window = $(window),
            window_w = $window.width(),
            window_h = (that.is_full_screen) ? $window.height() : $(document).height(),
            $block = (that.$block) ? that.$block : that.$wrapper,
            wrapper_w = $block.outerWidth(),
            wrapper_h = $block.outerHeight(),
            pad = 10,
            css;

        if (that.position) {
            css = that.position;

        } else {
            var getPosition = (that.userPosition) ? that.userPosition : getDefaultPosition;
            css = getPosition({
                width: wrapper_w,
                height: wrapper_h
            });
        }

        if (css.left > 0) {
            if (css.left + wrapper_w > window_w) {
                css.left = window_w - wrapper_w - pad;
            }
        }

        if (css.top > 0) {
            if (css.top + wrapper_h > window_h) {
                css.top = window_h - wrapper_h - pad;
            }
        } else {
            css.top = pad;

            if (that.is_full_screen) {
                var $content = $block.find(".t-dialog-content");

                $content.hide();

                var block_h = $block.outerHeight(),
                    content_h = window_h - block_h - pad * 2;

                $content
                    .height(content_h)
                    .addClass("is-long-content")
                    .show();

            }
        }

        $block.css(css);

        function getDefaultPosition( area ) {
            // var scrollTop = $(window).scrollTop();

            return {
                left: parseInt( (window_w - area.width)/2 ),
                top: parseInt( (window_h - area.height)/2 ) // + scrollTop
            };
        }
    };

    TeamDialog.prototype.close = function() {
        var that = this;
        //
        that.is_closed = true;
        //
        that.$wrapper.remove();
        //
        that.onClose(that.$wrapper, that);
    };

    TeamDialog.prototype.refresh = function() {
        var that = this;

        if (that.onRefresh) {
            //
            that.onRefresh();
            //
            that.close();
        }
    };

    TeamDialog.prototype.resize = function() {
        var that = this,
            animate_class = "is-animated",
            do_animate = true;

        if (do_animate) {
            that.$block.addClass(animate_class);
        }

        that.setPosition();

        if (that.onResize) {
            that.onResize(that.$wrapper, that);
        }
    };

    return TeamDialog;

})(jQuery);

// Team :: Editable
// Helper used in many places. (group, profile)
var TeamEditable = ( function($) {

    TeamEditable = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.save = ( options["onSave"] || function() {} );
        that.render = ( options["onRender"] || false );

        // DYNAMIC VARS
        that.is_empty = that.$wrapper.hasClass("is-empty");
        that.text = that.is_empty ? "" : that.$wrapper.text();
        that.$field = false;
        that.is_edit = false;

        // INIT
        that.initClass();
    };

    TeamEditable.prototype.initClass = function() {
        var that = this;
        //
        that.$field = that.renderField();
        //
        that.bindEvents();
    };

    TeamEditable.prototype.bindEvents = function() {
        var that = this;

        that.$wrapper.on("click", function() {
            that.toggle();
        });

        that.$field.on("blur", function() {
            that.save(that);
        });

        that.$field.on("keyup", function(event) {
            var is_enter = ( event.keyCode === 13 ),
                is_escape = ( event.keyCode === 27 );

            if (is_enter) {
                that.save(that);

            } else if (is_escape) {
                that.$field.val( that.text );
                that.toggle("hide");
            }
        });
    };

    TeamEditable.prototype.renderField = function() {
        var that = this,
            text = that.$wrapper.text(),
            $field = $('<input class="bold" type="text" name="" />');

        if (!that.is_empty) {
            $field.val(text);
        }

        var parent_w = that.$wrapper.parent().width(),
            wrapper_w = that.$wrapper.width(),
            max_w = 600,
            field_w;

        field_w = ( parent_w > max_w ) ? max_w : parent_w - 50;
        field_w = ( wrapper_w > field_w ) ? wrapper_w : field_w;

        $field
            .width(field_w)
            .hide();

        that.$wrapper.after($field);

        if (that.render) {
            that.render(that, $field);
        }

        return $field;
    };

    TeamEditable.prototype.toggle = function( show ) {
        var that = this;

        var id_edit = (show !== "hide");
        if (id_edit) {
            that.$wrapper.hide();
            that.$field.show().focus();
        } else {
            that.$wrapper.show();
            that.$field.hide();
        }

        that.is_edit = id_edit;
    };

    return TeamEditable;

})($);