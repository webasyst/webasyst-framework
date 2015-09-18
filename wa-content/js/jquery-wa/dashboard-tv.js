// Контейнер для всех виджетов
var DashboardWidgets = {};

// Контейнер для контроллеров виджетов
var DashboardControllers = {};

// Будующий конструктор виджета
var DashboardWidget;

// Общая логика страницы с виджетами
( function($) {

    // Показ/скрытие сообщения о пустом дэшборде
    var toggleEmptyWidgetNotice = function(hide) {
        var $wrapper = $("#empty-widgets-wrapper"),
            activeClass = "is-shown";

        if (hide) {
            $wrapper.removeClass(activeClass);
        } else {
            $wrapper.addClass(activeClass);
        }
    };

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
        that.renderWidget();

        // Hide Notice after create
        toggleEmptyWidgetNotice(true);
    };

    DashboardWidget.prototype.renderWidget = function() {
        var that = this,
            widget_href = that.widget_href + "&id=" + that.widget_id + "&size=" + that.widget_size.width + "x" + that.widget_size.width,
            $widget = that.$widget;

        if ($widget.length) {

            // Clear old HTML
            $widget.html("");

            // Проставляем класс (класс размера виджета)
            setWidgetType(that);

            // Загружаем контент
            $widget.load(widget_href, function() {});
        }
    };

})(jQuery);

( function($) {

    var storage = {
        timeout: 0,
        time: 2000,
        hiddenClass: "is-hidden"
    };

    var bindEvents = function() {
        $(document).on("keyup", function(event) {
            var is_escape = ( event.keyCode == "27" );
            if (is_escape) {
                refreshPage();
            }
        });

        $(window).on("resize", function() {
            clearTimeout(storage.timeout);
            storage.timeout = setTimeout(refreshPage, storage.time);
        });

        window.addEventListener("orientationchange", function() {
            clearTimeout(storage.timeout);
            refreshPage();
        }, false);
    };

    var refreshPage = function() {
        //
        setTopPadding();

        $.each(DashboardWidgets, function(i, widget) {
            widget.renderWidget();
        });
    };

    var setTopPadding = function() {
        var $header = $(".page-header-wrapper"),
            $headerBlock = $header.find(".page-header"),
            $widgets = $("#d-widgets-wrapper"),
            $document = $(window),
            display = {
                width: $document.width(),
                height: $document.height()
            },
            min_header_height = parseInt( $headerBlock.css("font-size")) + parseInt($headerBlock.css("padding-bottom")) + 30,
            min_side, lift, delta;

        // Min Side
        min_side = Math.min.apply(Math, [display.width, display.height]);

        // Delta
        delta = min_side - $widgets.outerHeight();
        if (delta < 0) { delta = 0; }

        // Header && Lift
        if ( delta >= min_header_height) {
            lift = min_header_height + parseInt( ( delta - min_header_height ) / 2 );
            $header.removeClass(storage.hiddenClass);
        } else {
            lift = parseInt( delta / 2 );
            $header.addClass(storage.hiddenClass);
        }

        // Render Header Top
        $header.height(lift);

        //alert( "DocW:" + $(document).width() + "\nDocH:" + $(document).height()  + "\nWW:" +  $(window).width()  + "\nWH" +  $(window).height() );
    };

    $(document).ready( function() {
        //
        setTopPadding();
        //
        bindEvents();
    });

})(jQuery);