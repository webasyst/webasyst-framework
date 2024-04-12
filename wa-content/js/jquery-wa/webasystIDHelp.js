var webasystIDHelp = ( function($) {

    var webasystIDHelp = function(options) {
        var that = this;

        // DOM
        that.$dialog = options.$dialog;

        // VARS
        that.dialog = null;
        that.steps = options.steps || 4;    // total number of steps in dialog
        that.current_step = 1;              // current step number (start from 1, not 0)
        that.wa_url = options.wa_url || '';
        that.wa_version = options.wa_version || '';
        // DYNAMIC VARS

        const sources = [{
            id: 'wa-dialog-css',
            type: 'css',
            uri: that.wa_url + 'wa-content/js/dialog/dialog.css?' + that.wa_version
        },{
            id: 'wa-dialog-js',
            type: 'js',
            uri: that.wa_url + 'wa-content/js/dialog/dialog.js?' + that.wa_version
        }];

        if(!$('html').hasClass('is-wa2') && (!$('#wa-dialog-css').length || !$('#wa-dialog-js').length)) {
            sourceLoader(sources).then(() => {
                initDialog()
            });
        }else{
            initDialog();
        }

        function initDialog() {
            $.waDialog({
                html: that.$dialog,
                animate: false,
                onOpen: function ($dialog, dialog) {
                    that.dialog = dialog;
                    that.init();
                },
                onClose: function () {
                    that.onClose();
                }
            });
        }

        function sourceLoader(sources) {
            const deferred = $.Deferred();

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
                let deferred = $.Deferred(),
                    counter = sources.length;

                let bad_sources = [];

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
                    let deferred = $.Deferred(),
                        promise = deferred.promise();

                    let $link = $("#" + source.id);
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
                    let deferred = $.Deferred(),
                        promise = deferred.promise();

                    let $script = $("#" + source.id);
                    if ($script.length) {
                        promise = $script.data("promise");

                    } else {
                        let script = document.createElement("script");
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
    };

    webasystIDHelp.prototype.init = function() {
        var that = this;
        that.initNavigation();
        that.initAuth();
        that.initConnect();
        setTimeout(function () {
            that.dialog.show();
            that.dialog.resize();
        }, 200);
    };

    webasystIDHelp.prototype.initNavigation = function() {
        var that = this,
            $dialog = that.$dialog,
            $back = $dialog.find('.js-back'),
            $next = $dialog.find('.js-next'),
            $finish = $dialog.find('.js-finish'),
            $steps = $dialog.find('.js-step'),
            $dots = $dialog.find('.js-dots li');

        var renderCurrentStep = function() {
            $steps.hide();
            $steps.filter('[data-id="' + that.current_step + '"]').show();
            that.dialog.resize();
        };

        var renderNavButtons = function() {
            $back.show();
            $next.show();
            $finish.hide();
            if (that.current_step <= 1) {
                $back.hide();
            } else if (that.current_step >= that.steps) {
                $next.hide();
                $finish.show();
            }
        };

        var renderNavDots = function() {
            $dots.filter('[data-id="' + that.current_step + '"]').addClass('active').siblings().removeClass('active');
        };

        $back.on('click', function (e) {
            e.preventDefault();
            that.current_step = Math.max(that.current_step - 1, 0);
            renderCurrentStep();
            renderNavDots();
            renderNavButtons();
        });

        $next.on('click', function (e) {
            e.preventDefault();
            that.current_step = Math.min(that.current_step + 1, that.steps);
            renderCurrentStep();
            renderNavDots();
            renderNavButtons();
        });
    };

    webasystIDHelp.prototype.initAuth = function(oauth_modal) {
        var that = this,
            $dialog = that.$dialog,
            $link = $dialog.find('.js-auth');

        $link.on('click', function (e) {
            e.preventDefault();

            var href = $(this).attr('href');

            if (!oauth_modal) {
                var referrer_url = window.location.href;
                window.location = href + '&referrer_url=' + encodeURIComponent(referrer_url);
                return;
            }

            var width = 600;
            var height = 500;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;

            window.open(href,'oauth', "width=" + 600 + ",height=" + height + ",left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
            return false;
        });
    };

    webasystIDHelp.prototype.initConnect = function() {
        var that = this,
            $dialog = that.$dialog,
            dialog = that.dialog,
            $link = $dialog.find('.js-connect');
            $link.on('click', function () {
                dialog.close();
            });
    };

    webasystIDHelp.prototype.onClose = function () {
        var that = this;
    };

    return webasystIDHelp;

})(jQuery);
