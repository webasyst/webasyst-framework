var ContentRouter = ( function($) {

    ContentRouter = function(options) {
        var that = this;

        // DOM
        that.$window = $(window);
        that.$content = options["$content"];

        // VARS
        that.app_url = options["app_url"];
        that.base_href = (options["base_href"] || null);
        that.api_enabled = !!(window.history && window.history.pushState);

        // DYNAMIC VARS
        that.xhr = false;
        that.is_enabled = true;

        // predicate that says ignore routing by this router - for example if we want turn of whole design redactor section be handled by this router
        that.ignore = options.ignore;
        if (typeof that.ignore !== "function") {
            delete that.ignore
        }

        // INIT
        that.initClass();
    };

    ContentRouter.prototype.initClass = function() {
        var that = this;

        //
        that.setupBaseHref();
        //
        that.bindEvents();
    };

    ContentRouter.prototype.setupBaseHref = function() {
        var that = this;

        if (!that.base_href) {
            return false;
        }

        var $base = $('base');
        if (!$base.length) {
            var base = document.createElement("base");
            document.getElementsByTagName("head")[0].appendChild(base);
            $base = $(base);
        }

        $base.attr("href", that.base_href);
    };

    ContentRouter.prototype.bindEvents = function() {
        var that = this;

        // When user clicks a link that leads to app backend, load content via XHR instead.
        var full_app_url = window.location.origin + that.app_url;

        $(document).on("click", "a", function(event) {

            // ignore routing
            if (that.ignore && that.ignore()) {
                return;
            }

            var $link = $(this),
                href = $link.attr("href");

            // hack for jqeury ui links without href attr
            if (!href) {
                $link.attr("href", "javascript:void(0);");
                href = $link.attr("href");
            }

            var stop_load = $link.hasClass("js-disable-router"),
                is_app_url = ( this.href.substr(0, full_app_url.length) == full_app_url ),
                is_normal_url = ( !(href === "#" || href.substr(0, 11) === "javascript:") ),
                use_content_router = ( that.is_enabled && !stop_load && is_app_url && is_normal_url );

            if (!event.ctrlKey && !event.shiftKey && !event.metaKey && use_content_router) {
                event.preventDefault();

                var content_uri = this.href;
                that.load(content_uri);
            }
        });

        // Click on header app icon
        $("#wa-header").on("click", "a", function(event) {
            // ignore routing
            if (that.ignore && that.ignore()) {
                return;
            }
            event.stopPropagation();
        });

        // Click on header app icon
        if (that.api_enabled) {
            window.onpopstate = function(event) {
                // ignore routing
                if (that.ignore && that.ignore()) {
                    return;
                }
                event.stopPropagation();
                that.onPopState(event);
            };
        }
    };

    ContentRouter.prototype.load = function(content_uri, unset_state) {
        var that = this;

        var uri_has_app_url = ( content_uri.indexOf( that.app_url ) >= 0 );
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

        that.xhr = $.ajax({
            method: 'GET',
            url: content_uri,
            dataType: 'html',
            global: false,
            cache: false
        }).done(function(html) {
            if (that.api_enabled && !unset_state) {
                history.pushState({
                    reload: true,               // force reload history state
                    content_uri: content_uri    // url, string
                }, "", content_uri);
            }

            that.setContent( html );
            that.animate( false );
            that.xhr = false;

            $(document).trigger("wa_loaded");
        }).fail(function(data) {
            if (data.responseText) {
                console.log(data.responseText);
            }
        });

        return that.xhr;
    };

    ContentRouter.prototype.reload = function() {
        var that = this,
            content_uri = (that.api_enabled && history.state && history.state.content_uri) ? history.state.content_uri : location.href;

        if (content_uri) {
            return that.load(content_uri, true);
        } else {
            return $.when(); // a resolved promise
        }
    };

    ContentRouter.prototype.setContent = function( html ) {
        var that = this;

        $(document).trigger("wa_before_render");

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

            $(document).trigger("wa_before_load");

            // CONTENT
            if (state.reload) {
                that.reload( state.content_uri );
            } else if (state.content) {
                that.setContent( state.content );
            }

            // TITLE
            if (state.title) {
                $.wa.setTitle(state.title);
            }

            $(document).trigger("wa_loaded");
        } else {
            location.reload();
        }
    };

    ContentRouter.prototype.animate = function( show ) {
        var that = this,
            $content = that.$content;

        //$(".router-loading-indicator").remove();
        //
        //if (show) {
        //    var $header = $content.find(".t-content-header h1"),
        //        loading = '<i class="icon16 loading router-loading-indicator"></i>';
        //
        //    if ($header.length) {
        //        $header.append(loading);
        //    }
        //}
    };

    return ContentRouter;

})(jQuery);
