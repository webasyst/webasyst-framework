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
        this.header_apps_tooltips = $('.js-applist-header a[data-wa-tooltip-content]') || null;
        /// params

        // Fns Init
        this.sortableApps()
        this.setRetina()
        this.closeNotification()

        this.panelToggle()
        this.appsToggle()
        this.searchPanel()

        this.appsTooltip()

        // update counts immediately if there are no cached counts; otherwise, update later
        if (!this.$applist.is('.counts-cached')) {
            this.updateCount()
        } else {
            setInterval(this.updateCount, 60000);
        }
    }

    /**
     * @description Add tooltips for apps icons
     */
    appsTooltip() {
        if (this.header_apps_tooltips) {
            this.header_apps_tooltips.waTooltip({
                arrow: false,
                placement: "bottom",
                theme: "bordered",
                offset:[0, 3]
            });
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

        const handler = () => {
            let rect = $target.getBoundingClientRect(),
                $target_height = rect.height,
                $target_top = rect.top,
                is_edit_mode = document.querySelector('body').classList.contains('is-custom-edit-mode');

            if (!is_edit_mode) {
                if (target === '.wa-dashboard-page') {
                    if ($target_top < 0 && (0 - $target_top) > $target_height) {
                        $content.classList.add('header-apps')
                    } else {
                        if (!$content.classList.contains('header-fixed')) {
                            $content.classList.remove('header-apps')
                        }
                    }
                } else {
                    // элемент полностью не виден
                    if ((0 - $target_top) > $target_height) {
                        $wa_header.style.cssText = 'opacity:1';
                        $content.classList.add('header-apps')
                    }else{
                        $wa_header.style.cssText = 'opacity:0';
                    }
                    // элемент полностью виден
                    if ($target_top < $target_height && $target_top > 0) {
                        $content.classList.remove('header-apps');
                        $wa_header.style.cssText = 'opacity:1';
                    }
                }
            }
        }

        addEventListener('load', handler, false);
        addEventListener('scroll', handler, false);
        addEventListener('resize', handler, false);

    }

    /**
     * @description Insert page title into header
     * @param {Object} options
     */
    static setHeaderTitle(options) {
        let title_text = options.title_text || '',
            place_after = options.place_after || '.wa-sitename',
            truncate = options.truncate || false;

        if (title_text) {
            if (truncate && (title_text.length > truncate)) {
                title_text= title_text.substring(0,truncate);
            }

            let $place_after = document.querySelector('#wa-header').querySelector(place_after);
            $place_after.insertAdjacentHTML("afterEnd", `<span class="h2 wa-pagename">${title_text}</span>`);
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
            $toggler.toggleClass('wa-animation-spin');
            setTimeout(() => $toggler.toggleClass('wa-animation-spin'), 1000);
            that.$content.toggleClass('wa-nav-unfolded');
            that.$wa_nav .toggleClass('wa-nav-unfolded');

            // Disable tooltip when apps panel is down
            if ($toggler.hasClass('down')) {
                that.header_apps_tooltips.each(function () {
                    this._tippy.disable();
                })
            }else{
                that.header_apps_tooltips.each(function () {
                    this._tippy.enable();
                })
            }

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
        let that = this,
            is_mobile = that.$applists.hasClass('is-mobile'),
            $app_list = that.$applists.find('ul');

        const app_list_sortable = () => {
            const options = {
                animation: 150,
                dataIdAttr: 'data-app',
                forceFallback: true,
                onStart(event) {

                },
                onEnd(event) {
                    /* хак для предотвращения срабатывания клика по элементу после его перетаскивания*/
                    let $link = $(event.item).find('a'),
                        href = $link.attr('href');
                    $link.attr('href', 'javascript:void(0);');
                    setTimeout(() => $link.attr('href', href),500)

                    let data = this.toArray(),
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
            };

            if (is_mobile) {
                options.delay = 100;
                options.delayOnTouchOnly = true;
            }

            $app_list.sortable(options);
        }

        if(typeof Sortable !== 'undefined') {
            app_list_sortable()
        } else if (!$('#wa').hasClass('disable-sortable-header')) {
            let urls = [];
            urls.push('wa-content/js/sortable/sortable.min.js');
            urls.push('wa-content/js/sortable/jquery-sortable.min.js');

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
