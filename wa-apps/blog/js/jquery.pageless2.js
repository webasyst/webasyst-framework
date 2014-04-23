(function ($) {
    var currentPage = 2, container = window, $container = $(container);
    var loading = false;

    var settings = {
        url: '',
        target: '.b-stream',
        count: 10,
        scroll: null,
        stop: null,
        bottom_distance: 80,
        content_distance: 120,
        auto: true,
        beforeLoad: null,
        afterLoad: null,
        renderContent: null,
        paging_selector: null,
        pageless_wrapper: null
    };

    var start = function () {
        var pageless_wrapper = settings.pageless_wrapper;

        pageless_wrapper.show();
        if (settings.paging_selector) {
            $(settings.paging_selector).hide();
        }
        pageless_wrapper.find('a.pageless-link').live('click', function () {
            watch(true);
            return false;
        });
        $container.bind('scroll.pageless resize.pageless', watch).trigger('scroll.pageless');

    };

    var stop = function () {
        $container.unbind('.pageless');
        if (settings.stop && (typeof (settings.stop) == 'function')) {
            settings.stop.apply(this, []);
        }

    };

    var scroll = function () {
        // show loader
        var pageless_wrapper = settings.pageless_wrapper;
        var handler = pageless_wrapper.find('.pageless-link');
        var progress = pageless_wrapper.find('.pageless-progress');
        if (progress.length) {
            handler.hide();
            progress.show();
        } else {
            handler.replaceWith('<i class="icon16 loading"></i>' + handler.text());
        }
        loading = true;

        if (typeof settings.beforeLoad === 'function') {
            settings.beforeLoad();
        }
        $.get(settings.url, {
            page: currentPage++
        }, function (response, textStatus, jqXHR) {
            var html = response.data ? response.data.content : response;
            if (typeof settings.prepareContent === 'function') {
                html = settings.prepareContent(html);
            }
            if (typeof settings.renderContent === 'function') {
                settings.renderContent(html, $(settings.target));
            } else {
                pageless_wrapper.remove();
                $(settings.target).append(html);
                pageless_wrapper = settings.pageless_wrapper = $(settings.target + ' .pageless-wrapper');
            }
            if (settings.scroll && (typeof (settings.scroll) == 'function')) {
                settings.scroll.apply(this, [ response, settings.target ]);
            }
            loading = false;
            if (typeof settings.afterLoad === 'function') {
                settings.afterLoad();
            }
            watch();
        });// ,'html');
    };
    // distance to end of the container
    var distanceToBottom = function () {
        return (container === window) ?
            ($(document).height() - $container.scrollTop() - $container.height())
            :
            ($container[0].scrollHeight - $container.scrollTop() - $container.height());
    };

    var distanceFromContent = function () {
        var handler = settings.pageless_wrapper;
        if (handler.length) {
            return $(window).height() - handler.position().top + $container.scrollTop();
        }
        return 0;
    };

    var watch = function (force) {
        if (currentPage >= parseInt(settings.count) + 1) {
            stop.apply(this, []);
        } else if (!loading) {
            var apply = false;
            if (force === true) {
                apply = true;
            } else if (settings.auto) {
                apply = (distanceToBottom() < settings.bottom_distance) || (distanceFromContent() > settings.content_distance);
            }

            if (apply) {
                scroll.apply(this, []);
            }
        }
    };

    $.pageless = function (option) {
        if (option == 'start' || option == 'refresh') {
            start.apply(this, []);
        }
        if (option == 'url') {
            settings.url = arguments[1];
        }
        if ($.isPlainObject(option)) {
            $.extend(settings, option);
            if (settings.pageless_wrapper === null) {
                settings.pageless_wrapper = $(settings.target + ' .pageless-wrapper');
            } else if (settings.pageless_wrapper === false) {
                settings.pageless_wrapper = $();
            }
            start.apply(this, []);
        }
    };
})(jQuery);