class WaHeader {
    constructor() {
        // Variables
        let that = this
        /// dom
        this.$notification_wrapper = $('.js-notification-wrapper')
        this.$wa_nav = $('#wa-nav')
        this.$dashboard_wrapper = $('#dashboard-wrapper')
        this.$content = $('.js-main-content')
        this.$sidebar = $('.js-main-sidebar')
        this.dashboard_wa_apps = this.$dashboard_wrapper.find('#wa_apps')
        this.$wa_header = this.$wa_nav.find('#wa-header');
        this.$applist = this.$wa_header.find('.js-applist-header');
        this.$applists = $('.js-applist');
        this.$notification_close = this.$notification_wrapper.find('.js-announcement-close');
        /// params

        // Fns Init
        this.sortableApps()
        this.setRetina()
        this.closeNotification()

        this.panelToggle()
        this.appsToggle()
        this.searchPanel()

        // update counts immediately if there are no cached counts; otherwise, update later
        if (!this.$applist.is('.counts-cached')) {
            this.updateCount()
        } else {
            setInterval(this.updateCount, 60000);
        }
    }

    /**
     * @description Make header sticky
     * @param {string} target .class or #id
     * @param {Object} settings
     */
    static headerBehavior(target, settings) {
        if (!target || !settings) {
            throw new Error('Some method param is not set');
        }

        const $target = document.querySelector(target),
            $wa_header = document.querySelector('#wa-header'),
            $content = document.querySelector('.js-main-content')

            const handler = (entries) => {
                let entry = entries[0],
                    {boundingClientRect, isIntersecting} = entry;

                if (target === '.wa-dashboard-page') {
                    if (isIntersecting) {
                        $content.classList.add('header-apps')
                    } else {
                        if (!$content.classList.contains('header-fixed')) {
                            $content.classList.remove('header-apps')
                        }
                    }
                } else {
                    let target_height = boundingClientRect.height,
                        target_offset = boundingClientRect.y,
                        scroll_direction;

                    // detect scroll direction
                    if ((0 - target_height / 2) >= target_offset && isIntersecting) {
                        scroll_direction = 'up'
                    } else if ((0 - target_height / 2) <= target_offset && isIntersecting) {
                        scroll_direction = 'down'
                    }

                    if (scroll_direction === 'down') {
                        $wa_header.style.cssText = 'transform: translateY(-4rem)';
                    }

                    if (target_offset < (0 - target_height)) {
                        $wa_header.style.cssText = 'transform: translateY(0)';
                        $content.classList.add('header-apps');
                    }else if(target_offset > 0 && !isIntersecting){
                        $wa_header.style.cssText = 'transform: translateY(0)';
                    } else {
                        new Promise((resolve) => {
                            if (scroll_direction === 'up') {
                                $wa_header.style.cssText = 'transform: translateY(-4rem)';
                                setTimeout(() => resolve(), 10)
                            }
                        }).finally(() => {
                            $wa_header.style.cssText = 'transform: translateY(0)';
                            $content.classList.remove('header-apps');
                        })
                    }
                }
            }
            const observer = new IntersectionObserver(handler, settings);
            observer.observe($target)
    }

    /**
     * @description Insert page title into header
     * @param {Object} $sidebar jQuery Object
     * @param {Object} $wa_header jQuery Object
     */
    static setHeaderTitle($sidebar, $wa_header) {
        let title = $sidebar.find('li.selected').data('header-title'),
            header_title = $wa_header.find('.wa-header-sitename > span')

        if(title) {
            header_title.text(title)
        }
    }

    /**
     * @description Toggle search block
     */
    searchPanel() {
        let that = this,
            $button = that.$wa_header.find('.js-header-search'),
            button_text_default = $button.html(),
            button_text_close = '<i class="fas fa-times"></i>',
            $form = that.$wa_header.find('.wa-header-search-form')

        $button.on('click', function (e) {
            e.preventDefault();
            $form.toggleClass('active')
            if ($form.hasClass('active')) {
                $form.find('input').focus();
                $button.html(button_text_close);
            } else {
                $button.html(button_text_default);
            }
        })

        // toggle search field
        let $search_form_input = $form.find('input'),
            $search_form_button = $form.find('button')

        $search_form_button.on('click', function (e) {
            e.preventDefault()
            if($form.hasClass('collapsed')) {
                $form.removeClass('collapsed')
                $search_form_input.focus()
            }
        });
        $search_form_input.on('blur', function (e) {
            e.preventDefault()
            $form.toggleClass('collapsed', true)
        });
    }

    /**
     * @description Expand/Shrink header by horizontal
     */
    panelToggle() {
        let that = this,
            $toggle_btn = that.$wa_header.find('.js-toggle-panel'),
            $sidebar = $('#wa-app').find('.sidebar'),
            $sidebar_header = $sidebar.find('.sidebar-header');

        $toggle_btn.on('click', function (e) {
            e.preventDefault();
            let is_fixed = that.$content.toggleClass('header-fixed').hasClass('header-fixed');
            $sidebar.toggleClass('height-full').toggleClass('header-fixed');
            $sidebar_header.toggle();
            localStorage.setItem('wa/dashboard/header/fixed', is_fixed);
            JsCookie.setCookie('wa_header_fixed', is_fixed ? 1 : 0, {secure: true, 'max-age': 3600});
            if(!is_fixed) {
                that.$content.removeClass('header-apps')
            }
            $('body,html').animate({
                scrollTop: 0,
            }, 500);
        })
    }

    /**
     * @description Expand/Shrink header apps menu by vertical
     */
    appsToggle() {
        let that = this,
            $toggle_apps = that.$wa_header.find('.js-toggle-apps'),
            $background = that.$wa_nav.next('.js-header-background');

        const action = function ($toggler) {
            $toggler.toggleClass('down');
            $toggler.toggleClass('spin');
            setTimeout(() => $toggler.toggleClass('spin'), 1000);
            that.$content.toggleClass('wa-nav-unfolded');
            that.$wa_nav .toggleClass('wa-nav-unfolded');
        };

        $(document).keyup(function(e) {
            if (e.keyCode === 27 && $toggle_apps.hasClass('down')) {
                action($toggle_apps);
            }
        });

        $background.on('click', function () {
            action($toggle_apps);
        });

        $toggle_apps.on('click', function (e) {
            e.preventDefault();
            action($(this));
        });

    }

    /**
     * @description Able to sort apps
     */
    sortableApps() {
        let that = this;
        const app_list_sortable = () => {
            that.$applists.find('ul').sortable({
                distance: 5,
                helper: 'clone',
                items: 'li[id]',
                opacity: 0.75,
                tolerance: 'pointer',
                stop: function () {
                    let data = $(this).sortable("toArray", {attribute: 'data-app'}),
                        apps = [];

                    for (let i = 0; i < data.length; i++) {
                        let id = $.trim(data[i]);
                        if (id) {
                            apps.push(id);
                        }
                    }

                    let url = backend_url + "?module=settings&action=save";
                    $.post(url, {name: 'apps', value: apps});
                }
            })
        }

        if ($.fn.sortable) {
            app_list_sortable()
        } else if (!$('#wa').hasClass('disable-sortable-header')) {
            let urls = [];
            if (!$.browser) {
                urls.push('wa-content/js/jquery/jquery-migrate-1.2.1.min.js');
            }
            if (!$.ui) {
                urls.push('wa-content/js/jquery-ui/jquery.ui.core.min.js');
                urls.push('wa-content/js/jquery-ui/jquery.ui.widget.min.js');
                urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
            } else if (!$.ui.mouse) {
                urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
            }
            urls.push('wa-content/js/jquery-ui/jquery.ui.sortable.min.js');

            let $script = $("#wa-header-js"),
                path = $script.attr('src').replace(/wa-content\/js\/jquery-wa\/wa.header.js.*$/, '');

            $.when.apply($, $.map(urls, function(file) {
                return $.ajax({
                    cache: true,
                    dataType: "script",
                    url: path + file
                });
            })).done(app_list_sortable);

            // Determine user timezone when "Timezone: Auto" is saved in profile
            if ($script.data('determine-timezone') && !document.cookie.match(/\btz=/)) {
                let version = $script.attr('src').split('?', 2)[1];
                $.ajax({
                    cache: true,
                    dataType: "script",
                    url: path + "wa-content/js/jquery-wa/wa.js?" + version,
                    success: function () {
                        $.wa.determineTimezone(path);
                    }
                });
            }
        }
    }

    /**
     * @description Set retina image
     */
    setRetina() {
        let that = this,
            pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
        $(window).on("load", function () {
            if (pixelRatio > 1) {
                that.$applist.find('img').each(function () {
                    if ($(this).data('src2')) {
                        $(this).attr('src', $(this).data('src2'));
                    }
                });
            }
        });
    }

    /**
     * @description Close Announcement notification
     */
    closeNotification() {
        let that = this,
            $wa_notifications_bell = $('.wa-notifications-bell'),
            $wa_announcement_counter = $wa_notifications_bell.find('.badge');

        that.$notification_close.on('click', function (e) {
            e.preventDefault()

            let $close = $(this),
                app_id = $close.data('app-id'),
                $notification_block = $close.closest('.wa-notification');

            if ($notification_block.length) {
                $notification_block.remove();
                let counter = that.$notification_wrapper.children().length;
                if (counter) {
                    $wa_announcement_counter.text(counter)
                }else{
                    that.$notification_wrapper.parent('.dropdown-body').remove()
                }
            } else {
                that.$notification_wrapper.parent('.dropdown-body').remove()
                $wa_announcement_counter.remove();
            }

            let url = backend_url + "?module=settings&action=save";
            $.post(url, {app_id: app_id, name: 'announcement_close', value: 'now()'});
        });
    }

    /**
     * @description Update Apps action counter value
     */
    updateCount() {
        let is_idle = true,
            $wa_header = $('#wa-header');

        $(document).on("mousemove keyup scroll", function() {
            is_idle = false;
        });

        document.addEventListener("touchmove", function () {
            is_idle = false;
        }, false);

        const data = {
            background_process: 1
        };

        if (is_idle) {
            data.idle = "true";
        } else {
            is_idle = true;
        }

        $.ajax({
            url: backend_url + "?action=count",
            data,
            success(response) {
                if (response && response.status == 'ok') {
                    // announcements
                    if (response.data.__announce) {
                        $('#wa-announcement').remove();
                        $wa_header.before(response.data.__announce);
                        delete response.data.__announce;
                    }

                    // applications
                    $wa_header.find('a span.badge').hide();
                    for (let app_id in response.data) {
                        let n = response.data[app_id];
                        if (n) {
                            let a = $('.js-applist li[data-app="' + app_id + '"] a');
                            if (typeof (n) == 'object') {
                                a.attr('href', n.url);
                                n = n.count;
                            }
                            if (a.find('span.badge').length) {
                                if (n && n !== "0") {
                                    a.find('span.badge').html(n).show();
                                } else {
                                    a.find('span.badge').remove();
                                }
                            } else if(n && n !== "0") {
                                a.append('<span class="badge">' + n + '</span>');
                            }
                        } else {
                            $('.js-applist li[data-app="' + app_id + '"] a span.badge').remove();
                        }
                    }
                    $(document).trigger('wa.appcount', response.data);
                }
            },
            error(response) {
                console.error(response);
            },
            dataType: "json",
            async: true
        });

    }
}

class JsCookie {
    /**
     *
     * @param {string} name
     * @param {string|int} value
     * @param {Object} options
     */
    static setCookie(name, value, options = {}) {

        options = { path: '/', }

        if (options.expires instanceof Date) {
            options.expires = options.expires.toUTCString()
        }

        let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value)

        for (let optionKey in options) {
            updatedCookie += "; " + optionKey
            let optionValue = options[optionKey]
            if (optionValue !== true) {
                updatedCookie += "=" + optionValue
            }
        }

        document.cookie = updatedCookie
    }

    /**
     *
     * @param {string} name
     * @returns {string|undefined}
     */
    static getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    /**
     *
     * @param {string} name
     */
    static deleteCookie(name) {
        this.setCookie(name, "", {
            'max-age': -1
        })
    }
}
