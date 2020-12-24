(function($) {

    $.fn.lazyLoad = function(options, ext) {

        if (options == 'stop') {
            var settings = this.data('lazyLoadSettings');
            if (settings) {
                settings.stopped = true;
            }
            return;
        }

        if (options == 'sleep') {
            var settings = this.data('lazyLoadSettings');
            if (settings) {
                settings.loading = true;
            }
            return;
        }

        if (options == 'wake') {
            var settings = this.data('lazyLoadSettings');
            if (settings) {
                settings.loading = false;
            }
            return;
        }

        if (options == 'force') {
            var settings = this.data('lazyLoadSettings');
            if (settings) {
                if (!settings.loading) {
                    settings.load();
                }
            }
            return;
        }

        this.data('lazyLoadSettings', $.extend({
            distance: 50,
            load: function() {},
            container: container,
            state: 'wake'
        }, options || {}));

        var settings = this.data('lazyLoadSettings');
        settings.loading = false;
        settings.stopped = false;

        var win = this;
        var container = $(settings.container);

        init();

        function init()
        {
            initHandler();
            $.fn.lazyLoad.call($(this), settings.state);
        }
        
        function scrollHandler()
        {
            if (settings.stopped) {
                this.onscroll = null;
            }
            if (!settings.stopped && !settings.loading && distanceBetweenBottoms() <= settings.distance) {
                settings.load();
            }
        }

        function initHandler()
        {
            var interval = 350;
            var h, timerId = setTimeout(h = function() {
                if (settings.stopped) {
                    clearTimeout(timerId);
                    return;
                }
                if (!settings.loading) {
                    if (distanceBetweenBottoms() <= settings.distance) {
                        settings.load();
                        timerId = setTimeout(h, interval);
                    } else {
                        this.onscroll = scrollHandler;
                        clearTimeout(timerId);
                    }
                } else {
                    timerId = setTimeout(h, interval);
                }
            }, interval);
        }

        function distanceBetweenBottoms(offset)
        {
            offset = offset || 0;
            return (container.position().top + container.outerHeight() - offset) - (win.scrollTop() + win.outerHeight());
        }
    };
})(jQuery);