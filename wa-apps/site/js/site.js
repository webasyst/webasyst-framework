(function ($) {

/*TODO
    default action?
    dynamic content wrapper?
    condition for
*/
var that = $.site = {

    opts: null,
    $wrapper: null,
    isWithoutReload: null,

    initBeforeLoad: function(opts) {
        this.opts = opts;
        initContentRouter();
        $(function() {
            $.site.initAfterLoad();
        });
        that.lang = opts.lang;
    },

    initAfterLoad: function(opts) {
        that.$wrapper = $('#wa-app');
        initAnimation();
    },

    navigate: function(absolute_url) {
        if (this.opts.content_router_mode == 'xhr' && isRoutableViaXHR(absolute_url)) {
            this.loadContent(absolute_url);
            return;
        }

        location.href = absolute_url;
    },

    reload: function() {
        this.navigate(location.href);
    },

    loadContent: function(url, xhr, unset_state) {
        if (that.last_xhr) {
            that.last_xhr.abort();
        }

        if (!xhr) {
            xhr = $.ajax({
                method: 'GET',
                url: url,
                global: false,
                cache: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();

                    xhr.addEventListener("progress", function(event) {
                        that.trigger("wa_loading", [{ xhr: xhr, event: event }]);
                    }, false);
                    xhr.addEventListener("abort", function(event) {
                        that.trigger("wa_abort", [{ xhr: xhr, event: event }]);
                    }, false);

                    return xhr;
                }
            });
        }
        that.last_xhr = xhr;

        that.trigger("wa_before_load", [{ xhr: xhr }]);


        return xhr.always(function() {
            that.last_xhr = false;
        }).done(function(html) {
            if (!unset_state) {
                history.pushState({
                    content_url: url
                }, "", url);
            }

            that.setContent(html);

            that.trigger("wa_loaded", [{
                xhr: xhr
            }]);
        }).fail(function(data) {
            console.log('Unable to load content for URL', url);
            if (data?.responseText?.match && data.responseText.match(/waException|id="Trace"/)) {
                console.log(data.responseText);
            }
            alert(that.opts.locale.unable_to_load);

            that.trigger("wa_load_fail", [{
                xhr: xhr
            }]);
        });
    },

    setContent: function(html) {
        if (!that.$wrapper) {
            $(function() {
                setTimeout(function() {
                    if (that.$wrapper) {
                        that.setContent(html);
                    }
                }, 50);
            });
        }
        that.$wrapper.html(html);
        that.trigger("wa_updated");
    },

    trigger: function() {
        if (!that.$wrapper) {
            return; // before DOM ready
        }
        that.$wrapper.trigger.apply(that.$wrapper, Array.from(arguments));
    },

    log: function () {
        if (this.opts.is_debug) {
            console.log.apply(null, arguments);
        }
    }
};

function initContentRouter() {
    $(document).on('click', 'a', function() {
        if (this.target === '_blank' || this.getAttribute('download') !== null) {
            return;
        }

        if (this.href) {
            var href = $(this).attr('href');
            if (href[0] === '#') {
                return;
            }
            if (href.substr(0, 11) === 'javascript:') {
                return;
            }
            var stop_load = this.classList.contains("js-disable-router");
            if (stop_load) {
                history.pushState({
                    content_url: this.href
                }, "", this.href);

                return false;
            }
        }

        that.navigate(this.href);
        return false;
    });

    window.onpopstate = function(event) {
        event.stopPropagation();

        var state = event.state;
        if (state && state.content_url) {
            const $link = $('.js-disable-router').filter('[href*="'+state.content_url.replace(location.origin, '')+'"]').first();
            if (!$link.trigger('click').length) {
                that.loadContent(state.content_url, null, true);
            }
        } else if(!$.site.isWithoutReload || (typeof $.site.isWithoutReload === 'function' && $.site.isWithoutReload() === false)) {
            location.reload();
        }
    };
}

function initHistory() {
    if (!history.state) {
        history.replaceState({
            content_url: location.href
        }, "", location.href);
    }
}

function initAnimation() {
    var waLoading = $.waLoading();

    var $wrapper = $("#wa"),
        locked_class = "is-locked";

    that.$wrapper
        .on("wa_before_load", function() {
            waLoading.show();
            waLoading.animate(10000, 95, false);
            $wrapper.addClass(locked_class);
        })
        .on("wa_loading", function(e, data) {
            var percent = (data.event.loaded / data.event.total) * 100;
            waLoading.set(percent);
        })
        .on("wa_abort, wa_load_fail", function() {
            waLoading.abort();
            $wrapper.removeClass(locked_class);
        })
        .on("wa_loaded", function() {
            waLoading.done();
            $wrapper.removeClass(locked_class);
        });
};


function isRoutableViaXHR(absolute_url) {

    // Outside of Site app?
    var absolute_main_url = window.location.origin + $.site.opts.app_url;
    var is_inside_site_app = (absolute_url.substr(0, absolute_main_url.length) === absolute_main_url);
    if (!is_inside_site_app) {
        return false;
    }

    // Inside of restricted area?
    var restricted_area_url = absolute_main_url + 'editor/';
    var is_inside_restricted_area = (absolute_url.substr(0, restricted_area_url.length) === restricted_area_url);
    if (is_inside_restricted_area) {
        return false;
    }

    return true;
}

// Props
$.site.helper = {
    xhr: null,
};
// Methods
$.site.helper = {
    loadSortableJS: () => {
        const dfd = $.Deferred();

        const $script = $("#wa-header-js"),
            path = $script.attr('src').replace(/wa-content\/js\/jquery-wa\/wa.header.js.*$/, '');

        const urls = [
            "wa-content/js/sortable/sortable.min.js",
            "wa-content/js/sortable/jquery-sortable.min.js",
        ];

        const sortableDeferred = urls.reduce((dfd, url) => {
            return dfd.then(() => {
                return $.ajax({
                    cache: true,
                    dataType: "script",
                    url: path + url
                });
            });
        }, $.Deferred().resolve());

        sortableDeferred.done(() => {
            dfd.resolve();
        });

        return dfd.promise();
    },
    // Cancels previous request
    preventDupeRequest: (fn) => {
        if ($.site.helper.xhr && typeof $.site.helper.xhr.abort === 'function') {
            $.site.helper.xhr.abort();
            $.site.helper.xhr = null;
        }

        $('#wa-app').trigger('wa_before_load');

        const resolver = () => {
            $.site.helper.xhr = null;
            $('#wa-app').trigger('wa_loaded');
        };
        $.site.helper.xhr = fn(resolver);
        if ($.site.helper.xhr && typeof $.site.helper.xhr.always === 'function') {
            $.site.helper.xhr.always(resolver);
        }
    }
};

})(jQuery);
