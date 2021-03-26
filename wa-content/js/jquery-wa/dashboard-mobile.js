// Контейнер для всех виджетов
const DashboardWidgets = {};

// Контейнер для контроллеров виджетов
const DashboardControllers = {};

const WaMobileDashboard = ( function($) {
    return class WaMobileDashboard {
        constructor(options) {
            this.$wrapper = options.$wrapper
            this.$header = options.$header
            this.$notification_wrapper = $('.js-notification-wrapper')
            this.$notification_close = this.$notification_wrapper.find('.js-announcement-close');
            this.$dashboard_widgets_wrapper = $('.js-dashboard-widgets-wrapper')
            this.$bottombar = $('.js-bottombar')
            this.$dashboard_tabs = this.$wrapper.find('.dashboard-tabs')
            this.$dashboard_apps = this.$wrapper.find('.js-dashboard-apps > ul')

            this.switchBottombar()
            this.searchPanel()
            setInterval(this.updateCount, 60000);
            this.closeNotification()
            this.switchDashboards()
            this.sortableApps()
        }

        searchPanel() {
            let that = this,
                $button = that.$header.find('.js-header-search'),
                button_text_default = $button.html(),
                button_text_close = '<span><i class="fas fa-times"></i></span>',
                $form = that.$header.find('form')

            $button.on('click', function () {
                $form.toggleClass('active')
                if ($form.hasClass('active')) {
                    $form.find('input').focus();
                    $button.html(button_text_close);
                } else {
                    $button.html(button_text_default);
                }
            })
        }

        switchBottombar() {
            let that = this,
                $bottombar_item = that.$bottombar.find('li'),
                current_item = localStorage.getItem('wa/dashboard/mobile/nav') || false,
                $dashboard_home_tab = that.$dashboard_tabs.find('ul > li:first-child');

            $bottombar_item.each(function () {
                let $item = $(this),
                    nav = $item.find('a').data('nav');

                if (current_item && nav === current_item) {
                    $item.addClass('selected').siblings().removeClass('selected')
                    that.$wrapper.find(`section[data-nav="${current_item}"]`).show().siblings().hide()
                }
            })

            $bottombar_item.on('click touchstart', 'a', function (e) {
                e.preventDefault();
                let nav = $(this).data('nav'),
                    $item = $(this).closest('li');

                $item.addClass('selected').siblings().removeClass('selected')
                $dashboard_home_tab.addClass('selected').siblings().removeClass('selected')
                that.$wrapper.find(`section[data-nav="${nav}"]`).show().siblings().hide()
                localStorage.setItem('wa/dashboard/mobile/nav', nav);

            })
        }

        updateCount() {
            let that = this,
                $dashboard_apps = $('.js-dashboard-apps');

            $.ajax({
                url: backend_url + "?action=count",
                data: {'background_process': 1},
                success: function (response) {
                    if (response && response.status == 'ok') {
                        // announcements
                        if (response.data.__announce) {
                            $('#wa-announcement').remove();
                            $dashboard_apps.before(response.data.__announce);
                            delete response.data.__announce;
                        }

                        // applications
                        $dashboard_apps.find('a span.badge').hide();
                        for (let app_id in response.data) {
                            if(response.data.hasOwnProperty(app_id)){
                                let n = response.data[app_id];
                                if (n) {
                                    let a = $dashboard_apps.find('> ul > li[data-app="' + app_id + '"] a');
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
                                    $dashboard_apps.find('> ul > li[data-app="' + app_id + '"] a span.badge').remove();
                                }
                            }
                        }
                        $(document).trigger('wa.appcount', response.data);
                    }
                    //setTimeout(that.updateCount, 1000);
                },
                error: function () {
                    //setTimeout(that.updateCount, 1000);
                },
                dataType: "json",
                async: true
            });
        }

        closeNotification() {
            let that = this,
                $wa_notifications_bell = $('.wa-notifications-bell'),
                $wa_announcement_counter = $wa_notifications_bell.find('.badge');

            that.$notification_close.on('click touchstart', function (e) {
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

        switchDashboards() {
            const that = this,
                $default_dashboard = document.querySelector('.d-widgets-block'),
                $user_dashboard = $('.js-dashboard-widgets-page');

            let is_move_event = false;

            $user_dashboard.on('touchmove', function () {
                is_move_event = true;
            })

            $user_dashboard.on('touchend', function () {
                if (is_move_event) {
                    is_move_event = false
                    return;
                }

                let self = $(this),
                    id = self.data('dashboard'),
                    waLoading = $.waLoading();

                if (id == 0) {
                    that.$bottombar.find('[data-nav="widgets"]').trigger('click')
                    self.parent().addClass('selected').siblings().removeClass('selected');
                    return;
                }

                waLoading.show();

                $.ajax({
                    xhr: function () {
                        let xhr = new window.XMLHttpRequest();
                        if (window.ActiveXObject) {
                            xhr = new window.ActiveXObject("Microsoft.XMLHTTP");
                        }

                        xhr.addEventListener("progress", downloadProgressHandler, false);
                        xhr.addEventListener("load", loadHandler, false);
                        xhr.addEventListener("error", errorHandler, false);
                        xhr.addEventListener("abort", abortHandler, false);
                        return xhr;
                    },
                    url: '?module=dashboard&action=Dashboard',
                    type: "POST",
                    data: { id },
                    contentType: false,
                    processData: true,
                    success: function(response) {
                        const $dashboard_page = $(response)[0],
                            $dashboard_page_html = $dashboard_page.querySelector('.js-dashboard-widgets').innerHTML;

                        if ($dashboard_page_html !== undefined) {
                            that.$dashboard_widgets_wrapper.empty().html($dashboard_page_html);
                        }

                        self.parent().addClass('selected').siblings().removeClass('selected');
                    }
                });

                function downloadProgressHandler(event) {
                    if ( event.lengthComputable ) {
                        let percent = (event.loaded / event.total) * 100;
                        waLoading.set(percent);
                    }else{
                        waLoading.animate();
                    }
                }

                function loadHandler() {
                    waLoading.done();
                }

                function errorHandler() {
                    waLoading.abort();
                }

                function abortHandler() {
                    waLoading.abort();
                }
            });

            is_move_event = false;
            /* Set Default Dashboard */
            that.$bottombar.on('click touchstart', 'a[data-nav="widgets"]', function (e){
                e.preventDefault();
                that.$dashboard_widgets_wrapper.empty().html($default_dashboard);
            })
        }

        sortableApps() {
            let that = this;

            const app_list_sortable = () => {
                that.$dashboard_apps.sortable({
                    delay: 100,
                    delayOnTouchOnly: true,
                    animation: 150,
                    dataIdAttr: 'data-app',
                    forceFallback: true,
                    onEnd() {
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
                })
            }

            if(typeof Sortable !== 'undefined') {
                app_list_sortable()
            } else {
                let urls = [];
                urls.push('/wa-content/js/sortable/sortable.min.js');
                urls.push('/wa-content/js/sortable/jquery-sortable.min.js');

                $.when.apply($, $.map(urls, function(file) {
                    return $.ajax({
                        cache: true,
                        dataType: "script",
                        url: file
                    });
                })).done(app_list_sortable);
            }
        }
    }
})(jQuery);

const DashboardWidget = (function($) {
    return class DashboardWidget {
        constructor(options) {
            // Settings
            this.widget_id = ( options.widget_id || false );
            this.widget_href = ( options.widget_href || false );
            this.widget_sort = parseInt( ( options.widget_sort || 0 ) );
            this.widget_group_index = parseInt( ( options.widget_group_index || 0 ) );
            this.widget_size = {
                width: parseInt( ( options.widget_size.width || 0 ) ),
                height: parseInt( ( options.widget_size.height || 0 ) )
            };
            this.widget_size_class = false;

            this.storage = {
                widget_type: {
                    "1": {
                        "1": "widget-1x1",
                        "2": "widget-1x2"
                    },
                    "2": {
                        "1": "widget-2x1",
                        "2": "widget-2x2"
                    }
                }
            };

            // DOM
            this.$widget = $("#widget-" + this.widget_id);
            this.$widget_wrapper = $("#widget-wrapper-" + this.widget_id);

            // Functions
            this.renderWidget(true);
        }

        renderWidget(force) {
            let that = this,
                widget_href = that.widget_href + "&id=" + that.widget_id + "&size=" + that.widget_size.width + "x" + that.widget_size.width,
                $widget = that.$widget;

            if ($widget.length) {

                // Проставляем класс (класс размера виджета)
                that.setWidgetType();

                // Загружаем контент
                $.ajax({
                    url: widget_href,
                    dataType: 'html',
                    global: false,
                    data: {}
                }).done(function(r) {
                    $widget.html(r);
                }).fail(function() {
                    if (force) {
                        $widget.html("");
                    }
                });
            }
        }

        setWidgetType() {
            let that = this,
                widget_width = that.widget_size.width,
                widget_height = that.widget_size.height,
                current_widget_type_class = that.widget_size_class;

            if ( widget_width > 0 && widget_height > 0 ) {
                let widget_type_class = that.storage.widget_type[widget_width][widget_height];

                if (widget_type_class) {

                    // Remove Old Type
                    if (current_widget_type_class) {

                        // Если новый класс равен старому
                        if (current_widget_type_class && ( current_widget_type_class == widget_type_class) ) {
                            return false;
                        }

                        that.$widget_wrapper.removeClass(that.widget_size_class);
                    }

                    // Set New Type
                    that.$widget_wrapper.addClass(widget_type_class);

                    that.widget_size_class = widget_type_class;
                }
            }
        }
    }
})(jQuery);