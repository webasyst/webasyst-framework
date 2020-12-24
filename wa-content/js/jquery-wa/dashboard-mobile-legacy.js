// Контейнер для всех виджетов
var DashboardWidgets = {};

// Контейнер для контроллеров виджетов
var DashboardControllers = {};

// Будующий конструктор виджета
var DashboardWidget;

// Общая логика страницы с виджетами
( function($) {

    // WIDGET | Скрипты относящиеся к Виджету и его внутренностям
    var storage = {
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

    var setWidgetType = function(that) {
        var widget_width = that.widget_size.width,
            widget_height = that.widget_size.height,
            current_widget_type_class = that.widget_size_class;

        if ( widget_width > 0 && widget_height > 0 ) {
            var widget_type_class = storage.widget_type[widget_width][widget_height];

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
    };

    // CONSTRUCTOR
    DashboardWidget = function(options) {
        var that = this;

        // Settings
        that.widget_id = ( options.widget_id || false );
        that.widget_href = ( options.widget_href || false );
        that.widget_sort = parseInt( ( options.widget_sort || false ) );
        that.widget_group_index = parseInt( ( options.widget_group_index || false ) );
        that.widget_size = {
            width: parseInt( ( options.widget_size.width || false ) ),
            height: parseInt( ( options.widget_size.height || false ) )
        };
        that.widget_size_class = false;

        // DOM
        that.$widget = $("#widget-" + that.widget_id);
        that.$widget_wrapper = $("#widget-wrapper-" + that.widget_id);

        // Functions
        that.renderWidget(true);
    };

    DashboardWidget.prototype.renderWidget = function(force) {
        var that = this,
            widget_href = that.widget_href + "&id=" + that.widget_id + "&size=" + that.widget_size.width + "x" + that.widget_size.width,
            $widget = that.$widget;

        if ($widget.length) {

            // Проставляем класс (класс размера виджета)
            setWidgetType(that);

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
    };

})(jQuery);

( function($) {

    var storage = {
        activeClass: "is-active",
        dashboardActiveClass: "is-dashboard-shown",
        $activeLink: false,
        timeout: 0,
        $loading: '<div style="margin: 1rem 0; text-align:center;"><i class="icon16 loading"></i></div>',
        time: 500
    };

    var bindEvents = function() {
        $("#d-mobile-show-apps").on("click", function() {
            toggleContent( $(this), "apps");
            return false;
        });

        $("#d-mobile-show-dashboard").on("click", function() {
            toggleContent( $(this), "dashboard");
            return false;
        });

        window.addEventListener("orientationchange", function() {
            refreshPage();
        }, false);
    };

    var setActiveLink = function( $link, is_active ) {
        var activeClass = storage.activeClass;

        if (!is_active) {
            // Set Inactive old link
            if (storage.$activeLink && storage.$activeLink.length) {
                storage.$activeLink.removeClass(activeClass);
            } else {
                $(".d-mobile-view-toggle").find("." + activeClass).removeClass(activeClass);
            }

            // Set Active
            $link.addClass(activeClass);
            storage.$activeLink = $link;
        }
    };

    var toggleContent = function($link, type) {
        var $body = $("body"),
            $apps = $("#wa-mobile-apps-list"),
            $dashboard = $("#wa-mobile-dashboard-wrapper"),
            $loading = storage.$loading,
            activeClass = storage.dashboardActiveClass,
            is_active = ( $link.hasClass(storage.activeClass) );

        if (type == "apps") {
            //
            $body.removeClass(activeClass);

            //
            $dashboard
                .hide()
                .html($loading);

            // Clear Container
            DashboardWidgets = {};
            DashboardControllers = {};

            // Render
            $apps.show();

        } else if (type == "dashboard") {
            if (!is_active) {
                //
                $apps.hide();

                //
                $body.addClass(activeClass);

                //
                loadDashboard( $dashboard );
            }

        } else {
            location.reload();
        }

        setActiveLink( $link, is_active );
    };

    var loadDashboard = function( $dashboard ) {
        var $deferred = $.Deferred(),
            dashboard_href = "?action=dashboard",
            dashboard_data = {};

        $dashboard.show();

        $.post(dashboard_href, dashboard_data, function(responce) {
           $deferred.resolve(responce);
        });

        $deferred.done( function(html) {
            $dashboard.html(html);
        });
    };

    var refreshPage = function() {
        if (DashboardWidgets) {
            $.each(DashboardWidgets, function(i, widget) {
                widget.renderWidget();
            });
        }
    };

    $(document).ready( function() {
        bindEvents();
    });

})(jQuery);