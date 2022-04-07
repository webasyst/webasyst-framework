//  MAIN APP CONTROLLER
( function($) {
    $.team = {
        app_url: false,
        content: false,
        sidebar: false,
        calendar: false,
        profile: false,
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
        confirmContactDelete: function(contact_ids, reloadPage) {
            $.post('?module=users&action=prepareDelete', { id: contact_ids }, function(html) {
                $.waDialog({
                    html,
                    onOpen($dialog, dialog){
                        const allowed_ids = $dialog.data('allowed-ids'),
                            $delete_button = $dialog.find('.js-delete-button')

                        dialog.$document.trigger('wa_confirm_contact_delete_dialog')

                        if (allowed_ids) {
                            $delete_button.on('click', function() {
                                let btn_text = $delete_button.text();
                                $delete_button.attr('disabled', true).html(`${btn_text} <i class="fas fa-spin fa-spinner wa-animation-spin speed-1000"></i>`);

                                $.post('?module=users&action=delete', { id: allowed_ids }, function(){
                                    dialog.close();
                                    $.team.sidebar.reload();

                                    if (!reloadPage) {
                                        $.team.content.load($.team.app_url);
                                        return;
                                    }

                                    $.team.content.reload();
                                }).always(function () {
                                    $delete_button.attr('disabled', false).html(btn_text);
                                });
                            });
                        }
                    }
                });
            });
        },

        /* Initialized in templates/layouts/Default.html */
        setSync: function () {
            var coef = Math.floor(Math.random() * 100) / 100,
                delay = 30000 + coef * 30000,
                xhr, timer;

            setTimeout(run, $.team.is_debug ? 100 : delay / 2);

            function run() {
                $.post($.team.app_url + "?module=calendarExternal&action=sync&background_process=1")
                    .always(function () {
                        xhr = null;
                        timer = setTimeout(run, delay);
                    })
                    .error( function () {
                        return false;
                    });
            }
        },

        /**
         * @description Popup alert notifications with any info
         * @param options
         */
        notification(options) {

            const $appendTo = options.appendTo || document.body,
                isCloseable = options.isCloseable ?? true,
                alertTimeout = options.alertTimeout || false;
            let $alertWrapper = $appendTo.querySelector('#t-notifications');

            // Create notification
            const $alert = document.createElement('div');
            $alert.classList.add('alert', options.alertClass || 'info');
            $alert.innerHTML = options.alertContent || '';

            if(isCloseable){
                const closeClass = options.alertCloseClass || 'js-alert-error-close',
                    $alertClose = document.createElement('a');

                $alertClose.classList.add('alert-close', closeClass);
                $alertClose.setAttribute('href', 'javascript:void(0)');
                $alertClose.innerHTML = '<i class="fas fa-times"></i>';
                $alert.insertAdjacentElement('afterbegin', $alertClose);
                // Event listener for close notification
                $alertClose.addEventListener('click', function() {
                    this.closest('.alert').remove();
                });
            }

            if(!$alertWrapper) {
                // Create notification wrapper
                $alertWrapper = document.createElement('div');
                $alertWrapper.className = 'alert-fixed-box';
                if (options.alertPlacement) {
                    $alertWrapper.classList.add(options.alertPlacement);
                }
                if (options.alertSize) {
                    $alertWrapper.classList.add(options.alertSize);
                }
                $alertWrapper.id = 't-notifications';
                $appendTo.append($alertWrapper);
            }

            if (options.alertPlacement) {
                $alertWrapper.prepend($alert);
            }else{
                $alertWrapper.append($alert);
            }

            if(alertTimeout) {
                setTimeout(() => $alert.remove(), alertTimeout)
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
        that.$app = $('#wa-app');

        // VARS
        that.api_enabled = ( window.history && window.history.pushState );
        that.scrollTop = options.scrollTop || true;

        // DYNAMIC VARS
        that.xhr = false;
        that.is_enabled = true;

        // LOADER
        that.waLoading = $.waLoading();

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
        that.$app.on('click', 'a', function(event) {
            if ($(this)[0].hasAttribute('data-disable-routing')) {
                return;
            }

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

        if (that.xhr) {
            that.xhr.abort();
        }

        $(document).trigger('wa_before_load', {
            // for which these data ?
            content_uri: content_uri
        });

        that.animate(true);

        that.xhr = $.get(content_uri, function(html) {
            if (!is_reload && that.api_enabled) {
                history.pushState({
                    reload: true,               // force reload history state
                    content_uri: content_uri    // url, string
                    // content: html,              // ajax html, string
                }, "", content_uri);
            }

            that.setContent( html );

            that.animate(false);

            that.xhr = false;
            $(document).trigger("wa_loaded");
        });
    };

    ContentRouter.prototype.reload = function(force) {
        const that = this;
        let content_uri = (that.api_enabled && history.state && history.state?.content_uri) ? history.state.content_uri : false;

        if (force) {
            content_uri = location.href;
        }

        if (content_uri || force) {
            that.load(content_uri, true);
        }
    };

    ContentRouter.prototype.setContent = function( html ) {
        var that = this;

        that.$content.html( html );

        if (that.scrollTop) {
            window.scrollTo(0, 0)
        }
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

    ContentRouter.prototype.animate = function(show, ) {
        const that = this;

        if (show) {
            that.waLoading.animate(3000, 96, false);
            return;
        }

        that.waLoading.done();
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

    TeamEditable = function(wrapper, options) {
        var that = this;

        // DOM
        that.$wrapper = wrapper;

        // OPTIONS
        this.options = options;

        // INIT
        that.initClass();
    };

    TeamEditable.prototype.initClass = function() {
        const that = this;

        that.bindEvents();
    };

    TeamEditable.prototype.bindEvents = function() {
        const that = this;

        that.$wrapper.on('keydown', $.proxy(that.checkPressEnter, that));
        that.$wrapper.on('focus', $.proxy(that.enableEditor, that));
        that.$wrapper.on('blur', $.proxy(that.disableEditor, that));
        that.$wrapper.on('paste', $.proxy(that.clearHtml, that));
    }

    TeamEditable.prototype.checkPressEnter = function(event) {
        const that = this;

        if (event.keyCode !== 13) {
            return;
        }

        event.preventDefault();
        that.$wrapper.blur();
    }

    TeamEditable.prototype.cacheText = function() {
        const that = this;

        that.cachedText = that.$wrapper.text();
    }

    TeamEditable.prototype.enableEditor = function() {
        const that = this;

        that.cacheText();

        that.$wrapper.addClass('editable-highlight');
    }

    TeamEditable.prototype.disableEditor = function() {
        const that = this;

        that.$wrapper.removeClass('editable-highlight');

        if (that.$wrapper.text() === that.cachedText) {
            return;
        }

        that.save();
    }

    TeamEditable.prototype.clearHtml = function(event) {
        event.preventDefault();

        let text = event.originalEvent.clipboardData.getData('text/plain');
        text = text.replace(/<[^>]*>?/gm, '');

        if (document.queryCommandSupported('insertText')) {
            document.execCommand('insertText', false, text);
        } else {
            document.execCommand('paste', false, text);
        }
    }

    TeamEditable.prototype.save = function() {
        const that = this;

        const data = {
            "data[id]": that.options.groupId,
            [that.options.target]: that.$wrapper.text()
        };

        const $loading = $('<span class="smaller text-gray custom-ml-4"><i class="fas fa-spin fa-spinner wa-animation-spin speed-1000"></i></span>');
        that.$wrapper.append($loading);

        $.post(that.options.api.save, data, function() {
            $loading.remove();

            if (that.options.reloadSidebar) {
                $.team.sidebar.reload();
            }
        });
    }

    return TeamEditable;

})($);
