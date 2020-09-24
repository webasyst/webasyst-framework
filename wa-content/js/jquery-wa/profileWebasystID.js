var ProfileWebasystID = ( function($) {

    var ProfileWebasystID = function(options) {
        var that = this;

        that.is_own_profile = options.is_own_profile || '';
        that.user = options.user || { id: 0 };
        that.backend_url = options.backend_url || '';
        that.wa_url = options.wa_url || '';
        that.wa_version = options.wa_version || '';
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';

        // INIT
        that.init();
    };

    ProfileWebasystID.prototype.init = function() {
        var that = this;

        if (that.is_own_profile) {
            that.initProfileWebasystIDAuth();
        }

        that.initProfileWebasystIDUnbindAuth();
        that.initProfileWebasystIDHelpLink();
    };

    ProfileWebasystID.prototype.initProfileWebasystIDAuth = function() {
        var that = this;

        var onAuth = function() {
            var href = that.webasyst_id_auth_url,
                referrer_url = window.location.href;
            window.location = href + '&referrer_url=' + referrer_url;
        };

        $(document).on('wa_webasyst_id_auth', function () {
            onAuth();
        });

        $('.js-webasyst-id-auth').on('click', function (e) {
            e.preventDefault();
            onAuth();
        });
    };

    ProfileWebasystID.prototype.initProfileWebasystIDHelpLink = function() {
        var that = this;

        var onHelp = function() {
            var url = that.backend_url + "?module=backend&action=webasystIDHelp";
            $.get(url, function (html) {
                $('body').append(html);
            });
        };

        // for availability in another frame
        $(document).on('wa_waid_help_link', function () {
            onHelp();
        });

        // click on link in current document
        $('.js-webasyst-id-help-link').on('click', function (e) {
            e.preventDefault();
            onHelp();
        });
    };

    ProfileWebasystID.prototype.initProfileWebasystIDUnbindAuth = function() {

        var that = this,
            wa_app_url = that.backend_url + 'webasyst/',
            contact_id = that.user.id || 0,
            url = wa_app_url + '?module=profile&action=waidUnbindConfirm';

        var onUnbind = function() {
            var sources = [{
                id: 'wa-dialog-css',
                type: 'css',
                uri: that.wa_url + 'wa-content/js/dialog/dialog.css?' + that.wa_version
            },{
                id: 'wa-dialog-js',
                type: 'js',
                uri: that.wa_url + 'wa-content/js/dialog/dialog.js?' + that.wa_version
            }];

            sourceLoader(sources);

            $.get(url, { id: contact_id }, function(html) {

                $.waDialog({
                    html: html,
                    animate: false,
                    onOpen: function($dialog) {

                        var $unbind_link = $dialog.find('.js-unbind');

                        $unbind_link.on('click', function () {

                            var $loading = $("<i class=\"icon16 loading\"></i>");

                            $loading.insertAfter($unbind_link);

                            $.post(wa_app_url + '?module=profile&action=waidUnbind', { id: contact_id })
                                .always(function () {
                                    $dialog.trigger('close');
                                    $loading.remove();
                                    location.reload();
                                });
                        });

                    }
                });
            });
        };

        var sourceLoader = function(sources) {
            var deferred = $.Deferred();

            loader(sources).then( function() {
                deferred.resolve();
            }, function(bad_sources) {
                if (console && console.error) {
                    console.error("Error loading resource", bad_sources);
                }
                deferred.reject(bad_sources);
            });

            return deferred.promise();

            function loader(sources) {
                var deferred = $.Deferred(),
                    counter = sources.length;

                var bad_sources = [];

                $.each(sources, function(i, source) {
                    switch (source.type) {
                        case "css":
                            loadCSS(source).then(onLoad, onError);
                            break;
                        case "js":
                            loadJS(source).then(onLoad, onError);
                            break;
                    }
                });

                return deferred.promise();

                function loadCSS(source) {
                    var deferred = $.Deferred(),
                        promise = deferred.promise();

                    var $link = $("#" + source.id);
                    if ($link.length) {
                        promise = $link.data("promise");

                    } else {
                        $link = $("<link />", {
                            id: source.id,
                            rel: "stylesheet"
                        }).appendTo("head")
                            .data("promise", promise);

                        $link
                            .on("load", function() {
                                deferred.resolve(source);
                            }).on("error", function() {
                            deferred.reject(source);
                        });

                        $link.attr("href", source.uri);
                    }

                    return promise;
                }

                function loadJS(source) {
                    var deferred = $.Deferred(),
                        promise = deferred.promise();

                    var $script = $("#" + source.id);
                    if ($script.length) {
                        promise = $script.data("promise");

                    } else {
                        var script = document.createElement("script");
                        document.getElementsByTagName("head")[0].appendChild(script);

                        $script = $(script)
                            .attr("id", source.id)
                            .data("promise", promise);

                        $script
                            .on("load", function() {
                                deferred.resolve(source);
                            }).on("error", function() {
                            deferred.reject(source);
                        });

                        $script.attr("src", source.uri);
                    }

                    return promise;
                }

                function onLoad(source) {
                    counter -= 1;
                    watcher();
                }

                function onError(source) {
                    bad_sources.push(source);
                    counter -= 1;
                    watcher();
                }

                function watcher() {
                    if (counter === 0) {
                        if (!bad_sources.length) {
                            deferred.resolve();
                        } else {
                            deferred.reject(bad_sources);
                        }
                    }
                }
            }
        }

        // for availability in another frame
        $(document).on('wa_waid_unbind_auth', function (event, contact) {
            if(contact.id == contact_id){
                event.stopImmediatePropagation();
                onUnbind();
            }
        });

        // click on link in current document
        $('.js-webasyst-id-unbind-auth').on('click', function () {
            onUnbind();
        });
    };

    return ProfileWebasystID;

})(jQuery);
