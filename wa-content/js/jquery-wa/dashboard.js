// Включаем дополнительные параметры в jQuery Event
jQuery.event.props.push("dataTransfer");
jQuery.event.props.push("pageX");
jQuery.event.props.push("pageY");

// Контейнер для всех виджетов
var DashboardWidgets = {};

// Контейнер для контроллеров виджетов
var DashboardControllers = {};

// Будующий конструктор виджета
var DashboardWidget;

// Общая логика страницы с виджетами
( function($, backend_url) {

    // Проверка на пустую группу
    var checkEmptyGroup = function( $currentGroup, $groups, is_new_widget ) {
        var is_empty = !( $currentGroup.find(".widget-wrapper").length ),
            group_index = parseInt( $currentGroup.index() ),
            last_group_index = parseInt( $groups.length - 1),
            is_not_last = !(group_index == last_group_index),
            animation_time = (is_new_widget) ? 1 : 300,
            result_time = 0;

        // Если группа пустая и не последняя
        if (is_empty && is_not_last) {
            // Set Time
            result_time = animation_time;

            // Lock Widget Space
            lockWidgetsToggle();

            // Delete Group
            deleteEmptyGroup( $currentGroup, ( animation_time - 1 ) );
        }

        return result_time;
    };

    // Удаляем пустую группу
    var deleteEmptyGroup = function( $group, time ) {
        if (time > 0) {
            $group.addClass("is-removed");
        }

        setTimeout( function () {
            // Remove Lock
            lockWidgetsToggle();

            // Remove Group
            $group.remove();
        }, time );
    };

    // Получаем данные виджетов в группе
    var getGroupData = function($group) {
        var $widgets_in_group = $group.find(".widget-wrapper"),
            groupData = {
                group_index: parseInt( $group.index() ),
                group_area: 0,
                widgetsArray: []
            };

        $widgets_in_group.each( function(index) {
            var widget_id = $(this).data("widget-id"),
                widget_size = DashboardWidgets[widget_id].widget_size;

            groupData.widgetsArray.push({
                $widget_wrapper: $(this),
                widget_id: widget_id,
                widget_size: widget_size,
                widget_index: index
            });

            groupData.group_area += ( widget_size.width * widget_size.height );
        });

        return groupData;
    };

    // Блокировка области виджетов, чтобы пользователь не делал много запросов одновременно (параллельно)
    var lockWidgetsToggle = function() {
        var $wrapper = $("#d-widgets-wrapper"),
            activeClass = "is-locked";

        $wrapper.toggleClass(activeClass);
    };

    // Проверка на наличии любых виджетов
    var checkEmptyWidgets = function() {
        var result = false;

        for (var widget_id in DashboardWidgets) {
            if (DashboardWidgets.hasOwnProperty(widget_id)) {
                result = true;
                break;
            }
        }

        toggleEmptyWidgetNotice(result);
    };

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

    // Определяем СпроллТоп
    var getScrollTop = function() {
        return $(window)['scrollTop']();
    };

    var isEditMode = function() {
        var $pageWrapper = $("#d-page-wrapper"),
            activeClass = "is-editable-mode";
        return $pageWrapper.hasClass(activeClass);
    };

    var getDashboardSelect = function() {
        return ( $("#d-dashboards-select") || false )
    };

    var getDashboardID = function() {
        var $select = getDashboardSelect(),
            value = ( $select.length ) ? $select.val() : false;

        // If Default Dashboard
        if (value == 0) {
            value = false
        }

        return value;
    };

    // WIDGET | Скрипты относящиеся к Виджету и его внутренностям
    ( function() {

        var storage = {
            isLocked: false,
            settingsStartPosition: false,
            settingsWidget: false,
            isControlsShownClass: "is-settings-shown",
            isEditableModeClass: "is-editable-mode",
            isWidgetsLockedClass: "is-locked",
            activeControlClass: "is-active",
            isWidgetMoveClass: "is-moved",
            isAnimatedClass: "is-animated",
            isRotatedClass: "is-rotated",
            hasShadowClass: "has-shadow",
            maxGroupArea: 4,
            animateTime: 666,
            widget_type: {
                "1": {
                    "1": "widget-1x1",
                    "2": "widget-1x2"
                },
                "2": {
                    "1": "widget-2x1",
                    "2": "widget-2x2"
                }
            },
            getWidgetWrapper: function() {
                return $("#d-widgets-wrapper");
            },
            getControlsWrapper: function(that) {
                return that.$widget_wrapper.find(".widget-controls-wrapper");
            },
            getResizeHref: function( widget_id ) {
                return "?module=dashboard&action=widgetResize&id=" + widget_id;
            },
            getDeleteHref: function() {
                return "?module=dashboard&action=widgetDelete";
            },
            getSettingsHref: function( widget_id ) {
                return "?module=dashboard&action=widgetSettings&id=" + widget_id;
            },
            getPageWrapper: function() {
                return $("#d-page-wrapper");
            },
            getSettingsWrapper: function() {
                return $("#d-settings-wrapper");
            },
            getSettingsContainer: function() {
                return $("#d-settings-container");
            },
            getSettingsBlock: function() {
                return $("#d-settings-block");
            }
        };

        // Ивенты с Настройками Окошка виджетов
        $(document).ready( function() {
            var $settingWrapper = storage.getSettingsWrapper(),
                $settingContainer = storage.getSettingsContainer();

            $settingWrapper.on("click", function() {
                var that = storage.settingsWidget;
                closeSettings(that);
                return false;
            });

            $settingContainer.on("click", function(event) {
                event.stopPropagation();
            });

            $settingContainer.on("click", ".hide-settings-link", function() {
                $settingWrapper.trigger("click");
            });

            $settingWrapper.on("submit", "form", function(event) {
                var that = storage.settingsWidget;
                onSaveSettings(that, $(this));
                event.preventDefault();
                return false;
            });
        });

        var widgetBindEvents = function(that) {
            var $widgetControls = storage.getControlsWrapper(that);

            $widgetControls.on("click", ".control-item", function() {
                var $link = $(this),
                    activeClass = storage.activeControlClass,
                    is_click_myself = ( $link.hasClass(activeClass) );

                if ( is_click_myself ) {
                    return false;

                } else {

                    // Check and remove old active control
                    storage.getControlsWrapper(that).find("." + activeClass).removeClass(activeClass);

                    // Set active control
                    $link.addClass(activeClass);
                }
            });

            $widgetControls.on("click", ".show-settings-link", function() {
                prepareShowWidgetSettings(that);
                return false;
            });

            $widgetControls.on("click", ".set-small-size", function() {
                prepareChangeWidgetType(that, {
                    width: 1,
                    height: 1
                });
                return false;
            });

            $widgetControls.on("click", ".set-medium-size", function() {
                prepareChangeWidgetType(that, {
                    width: 2,
                    height: 1
                });
                return false;
            });

            $widgetControls.on("click", ".set-big-size", function() {
                prepareChangeWidgetType(that, {
                    width: 2,
                    height: 2
                });
                return false;
            });

            $widgetControls.on("click", ".delete-widget-link", function() {
                // Hide button
                $(this).css("visibility","hidden");
                // Delete
                that.deleteWidget();
                return false;
            });

            //that.$widget_wrapper.on("mouseenter", function() {
            //    var is_edit_mode = isEditMode();
            //    if (is_edit_mode) {
            //        that.initControlsController();
            //    }
            //});
        };

        var getWidgetSettings = function(that) {
            var $deferred = $.Deferred(),
                href = storage.getSettingsHref(that.widget_id);

            $.get(href, function(request) {
                $deferred.resolve(request);
            });

            return $deferred;
        };

        var prepareShowWidgetSettings = function(that) {
            var $pageWrapper = storage.getPageWrapper(),
                is_edit_mode = ($pageWrapper.hasClass(storage.isEditableModeClass)),
                $widget = that.$widget_wrapper,
                is_controls_shown = ( $widget.hasClass(storage.isControlsShownClass) );

            if (is_edit_mode && !is_controls_shown) {
                that.showWidgetSettings();
            }
        };

        var prepareChangeWidgetType = function(that, size) {
            var is_available = checkAvailability(that, size);

            if (is_available) {
                changeWidgetType(that, size);
            }
        };

        var changeWidgetType = function(that, size) {
            var $deferred = $.Deferred(),
                widget_id = that.widget_id,
                href = storage.getResizeHref(widget_id),
                dataArray = {
                    size: size.width + "x" + size.height
                };

            // Ставим на блок
            lockToggle();

            $.post(href, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done( function(response) {
                if (response.status === "ok") {
                    // Save new size
                    that.widget_size = size;

                    // Render
                    that.renderWidget(true);
                }

                // Снимаем блок
                lockToggle();
            })
        };

        var checkAvailability = function(that, size) {
            // Current Widget Area
            var before_widget_area = that.widget_size.width * that.widget_size.height,
                after_widget_area = size.width * size.height,
                delta_area = after_widget_area - before_widget_area,
                result = false;

            if (delta_area > 0) {

                // Group Area
                var $group = that.$widget.closest(".widget-group-wrapper");
                var groupData = getGroupData( $group );

                // Ситуация 1х1, 1х1 => 2x1, 1х1
                if ( groupData.widgetsArray.length === 3 && ( parseInt( that.$widget_wrapper.index() ) === 1 ) && ( after_widget_area === 2 ) ) {
                    //console.log("Ситуация 1х1, 1х1 => 2x1, 1х1");
                    return false;
                }
                result = ( (groupData.group_area + delta_area) <= storage.maxGroupArea );
            }

            if (delta_area < 0) {
                result = true
            }

            return result;
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

        var lockToggle = function() {
            storage.isLocked = !(storage.isLocked);

            lockWidgetsToggle();
        };

        var onSaveSettings = function(that, $form) {
            var $deferred = $.Deferred(),
                saveSettingsHref = "?module=dashboard&action=widgetSave&id=" + that.widget_id,
                dataArray = $form.serializeArray();

            $.post(saveSettingsHref, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done( function () {
                // Clear HTML
                that.$widget.html("");

                // Закрываем настройки
                closeSettings(that);

                setTimeout( function() {
                    // Перерисовываем виджет
                    that.renderWidget(true);
                }, storage.animateTime);

                //console.log("Успешно сохранен");
            });
        };

        var renderSettings = function(that) {
            var $widget_block = that.$widget,
                $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                $settingsWrapper = storage.getSettingsWrapper(),
                $settingsContainer = storage.getSettingsContainer(),
                widgetOffset = $widget_block.offset(),
                scrollTop = getScrollTop(),
                //scrollLeft = 0,
                block_width = 450,
                animate_time = storage.animateTime,
                top_position,
                left_position;

            // Save link on widget
            storage.settingsWidget = that;

            // Display Area
            var windowArea = {
                width: $(window).width(),
                height: $(window).height()
            };

            // Set start widget position
            var widgetArea = {
                top: widgetOffset.top,
                left: widgetOffset.left,
                width: $widget_block.width(),
                height: $widget_block.height()
            };

            // Save start widget position
            storage.settingsStartPosition = widgetArea;

            // Set settings position
            $settingsContainer.css({
                top: widgetArea.top,
                left: widgetArea.left,
                width: widgetArea.width,
                height: widgetArea.height
            });

            // Set widget position
            $widget_container.css({
                width: widgetArea.width,
                height: widgetArea.height
            });

            $settingsWrapper
                .height($(document).height())
                .show();

            setTimeout( function() {
                // Adding Animate Class
                $settingsContainer.addClass(storage.isAnimatedClass);
                $widget_container.addClass(storage.isAnimatedClass);
                $widget_wrapper.addClass(storage.isWidgetMoveClass);

                setTimeout( function() {
                    $settingsContainer.addClass(storage.isRotatedClass);

                    top_position = parseInt( ( windowArea.height - widgetArea.height ) / 2 );
                    top_position += scrollTop;

                    left_position = ( parseInt ( windowArea.width - block_width) / 2 );

                    $widget_container.css({
                        top: top_position - widgetOffset.top,
                        left: left_position - widgetOffset.left,
                        width: block_width
                    });

                    $settingsContainer.css({
                        top: top_position,
                        left: left_position,
                        width: block_width
                    });

                }, 4);

            }, 4);

            setTimeout( function() {
                $settingsContainer.addClass(storage.hasShadowClass);
            }, animate_time + 8);

        };

        var liftSettings = function(that) {
            var $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                $settingsContainer = storage.getSettingsContainer(),
                startPosition = storage.settingsStartPosition,
                border_height = 10,
                settings_height = $("#d-settings-block").outerHeight() + border_height,
                scrollTop = getScrollTop();

            var lift = parseInt( ( $(window).height() - settings_height ) / 2 + scrollTop );

            $settingsContainer.css({
                top: lift,
                height: settings_height
            });

            $widget_container.css({
                top: lift - startPosition.top,
                height: settings_height
            });

        };

        var closeSettings = function(that) {
            var $settingsWrapper = storage.getSettingsWrapper(),
                $settingsContainer = storage.getSettingsContainer(),
                $settings = storage.getSettingsBlock(),
                $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                startPosition = storage.settingsStartPosition,
                animate_time = storage.animateTime;

            $settingsContainer.removeClass(storage.isRotatedClass);
            $settingsContainer.removeClass(storage.hasShadowClass);

            $widget_container.css({
                top: 0,
                left: 0,
                width: startPosition.width,
                height: startPosition.height
            });

            $settingsContainer.css(startPosition);

            setTimeout( function() {
                $settingsWrapper.hide();

                $settingsContainer
                    .removeClass(storage.isAnimatedClass)
                    .attr("style", "");

                $widget_container
                    .attr("style", "")
                    .removeClass(storage.isAnimatedClass);

                $widget_wrapper.removeClass(storage.isWidgetMoveClass);

                $settings.html("");

                storage.settingsStartPosition = false;
            }, animate_time);

            storage.settingsWidget = false;

            that.hideWidgetSettings();
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

            // Functions
            widgetBindEvents(that);

            // Hide Notice after create
            toggleEmptyWidgetNotice(true);
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
                }).fail(function(xhr, text_status, error) {
                    if (xhr.responseText && xhr.responseText.indexOf) {
                        console.log('Error getting widget contents', text_status, error);
                        if (xhr.responseText.indexOf('waException') >= 0 || xhr.responseText.indexOf('id="Trace"') >= 0) {
                            $widget.html('<div style="font-size:40%;">'+xhr.responseText+'</div>');
                            return;
                        }
                    }
                    if (force) {
                        $widget.html("");
                    }
                });
            }
        };

        DashboardWidget.prototype.showWidgetSettings = function() {
            var that = this,
                $widget = that.$widget_wrapper,
                activeClass = storage.isControlsShownClass;

            $widget.addClass(activeClass);

            renderSettings(that);

            var $deferred = getWidgetSettings(that);

            $deferred.done( function(response) {
                var $settings = storage.getSettingsBlock();

                $settings.html(response);

                liftSettings(that);
            });
        };

        DashboardWidget.prototype.hideWidgetSettings = function() {
            var that = this,
                $widget = that.$widget_wrapper,
                activeClass = storage.isControlsShownClass;

            $widget.removeClass(activeClass);
        };

        DashboardWidget.prototype.deleteWidget = function() {
            var that = this,
                $deferred = $.Deferred(),
                href = storage.getDeleteHref(),
                widget_id = that.widget_id,
                dataArray = {
                    id: widget_id
                };

            $.post(href, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done( function(response) {
                if (response.status === "ok") {
                    var $currentGroup = that.$widget_wrapper.closest(".widget-group-wrapper");
                    var $groups = storage.getWidgetWrapper().find(".widget-group-wrapper");

                    // Delete widget body
                    that.$widget_wrapper.remove();

                    // Delete Group if Empty
                    checkEmptyGroup( $currentGroup, $groups );

                    // Delete JS
                    delete DashboardWidgets[widget_id];

                    // Check Empty Widgets and Show Notice
                    checkEmptyWidgets();
                }
            });
        };

        DashboardWidget.prototype.initControlsController = function() {
            var that = this,
                $widgetControls = that.$widget_wrapper.find(".size-controls-wrapper .control-item");

            $widgetControls.each( function() {
                var $control = $(this),
                    is_active = ( $control.hasClass("is-active") ),
                    size = {
                        width: 0,
                        height: 0
                    };

                if ($control.hasClass("set-small-size")) {
                    size = {
                        width: 1,
                        height: 1
                    };
                }

                if ($control.hasClass("set-medium-size")) {
                    size = {
                        width: 2,
                        height: 1
                    };
                }

                if ($control.hasClass("set-big-size")) {
                    size = {
                        width: 2,
                        height: 2
                    };
                }

                var is_available = checkAvailability(that, size);

                if (is_available || is_active) {
                    $control.show();
                } else {
                    $control.hide();
                }

            });
        };

    })();

    // GROUP | Скрипты относящиеся к группе, и движении виджетов внутри них
    ( function() {

        var storage = {
            dropArea: {},
            draggedWidget: false,
            is_new_group: false,
            is_new_widget: false,
            $widget_group: false,
            $hover_group: false,
            doDragOver: true,
            newDraggedWidget: false,
            target_group_offset: false,
            is_widget_list_locked: false,
            $activeWidgetOrnament: false,
            isDraggedClass: "is-dragged",
            hoverGroupClass: "is-hovered",
            lockedGroupClass: "is-locked",
            activeClass: "is-active",
            lockedClass: "is-locked",
            showGroupOrnamentClass: "is-ornament-shown",
            showClass: "is-shown",
            max_group_area: 4,
            border_space: 10,
            getGroupsWrapper: function () {
                return $(document).find("#d-widgets-wrapper");
            },
            getGroupsBlock: function () {
                return $(document).find("#d-widgets-block");
            },
            getWidgetGroups: function () {
                return this.getGroupsWrapper().find(".widget-group-wrapper");
            },
            getNewWidgetHref: function () {
                return "?module=dashboard&action=widgetAdd";
            },
            getSaveHref: function (widget_id) {
                return "?module=dashboard&action=widgetMove&id=" + widget_id;
            },
            getLastGroupIndex: function () {
                return parseInt(this.getWidgetGroups().last().index());
            },
            getNewGroupHTML: function() {
                return $("<div />").addClass("widget-group-wrapper");
            },
            getListWrapper: function() {
                return $("#widgets-list-wrapper");
            }
            //getDropOrnament: function() {
            //    return $("#d-drop-ornament");
            //}
        };

        var bindEvents = function() {
            var $groups_wrapper = storage.getGroupsWrapper(),
                $widgetList = storage.getListWrapper();

            $widgetList.on("change", "#widgets-list-filter", function() {
                onSortWidgetList( $(this) );
            });

            $widgetList.on("dragstart", ".widget-item-wrapper .image-block", function(event) {
                onWidgetListDragStart(event, $(this));
            });

            $widgetList.on("click", ".widget-item-wrapper .image-block", function(event) {
                onWidgetListClick(event, $(this));
            });

            $widgetList.on("dragend", ".widget-item-wrapper .image-block", function() {
                onWidgetListDragEnd($(this));
            });

            $groups_wrapper.on("dragstart", ".widget-group-wrapper .widget-draggable-block", function(event) {
                onDragStart(event, $(this));
            });

            $groups_wrapper.on("dragend", ".widget-group-wrapper .widget-draggable-block", function() {
                onDragEnd($(this));
            });

            // Hack, иначе Drop не будет срабатывать
            $groups_wrapper.on("dragover", ".widget-group-wrapper", function(event) {
                prepareDragOver(event, $(this));
                event.preventDefault();
                return false;
            });

            // Custom Dashboard, подцветка при наведении на пустую группу
            $groups_wrapper.on("dragover", ".ornament-widget-group", function(event) {
                onEmptyGroupOver(event, $(this), $groups_wrapper );
                event.preventDefault();
                return false;
            });

            // Навели на группу
            $groups_wrapper.on("dragenter", ".widget-group-wrapper", function() {
                onDragEnter( $(this) );
            });

            $groups_wrapper.on("drop", ".widget-group-wrapper", function(event) {
                if (storage.draggedWidget) {
                    prepareDrop(event, $(this));

                } else if (storage.newDraggedWidget) {
                    onWidgetListDrop(event, $(this));
                }
                event.preventDefault();
            });

            // Custom Dashboard
            $groups_wrapper.on("drop", ".ornament-widget-group", function(event) {
                onEmptyGroupDrop(event);
                event.preventDefault();
            });
        };

        // Group Functions

        var onDragEnter = function($group) {

        };

        var onDragStart = function(event, $target) {
            var $dragged_widget_wrapper = $target.closest(".widget-wrapper"),
                dragged_widget_id = $dragged_widget_wrapper.data("widget-id");

            // Add class
            $dragged_widget_wrapper.addClass(storage.isDraggedClass);

            // Save widget to storage
            storage.draggedWidget = (typeof DashboardWidgets[dragged_widget_id] !== "undefined") ? DashboardWidgets[dragged_widget_id] : false;
            storage.$widget_group = $dragged_widget_wrapper.closest(".widget-group-wrapper");

            // Hack. In FF D&D doesn't work without dataTransfer
            event.originalEvent.dataTransfer.setData("text/html", "<div class=\"anything\"></div>");

            var $ornament = $dragged_widget_wrapper.find(".widget-draggable-ornament");
            var ornament_width = parseInt($ornament.width()/2);
            var ornament_height = parseInt($ornament.height()/2);
            event.dataTransfer.setDragImage(
                $ornament[0],
                ornament_width,
                ornament_height
            )
        };

        var prepareDragOver = function(event, $group) {
            var time = 150;
            // Flag
            if (storage.doDragOver) {
                storage.doDragOver = false;
                setTimeout( function() {
                    storage.doDragOver = true;
                }, time);

                onDragOver(event, $group);
            }
        };

        var onDragOver = function(event, $group) {
            var draggedWidget = ( storage.newDraggedWidget || storage.draggedWidget );
            if (!draggedWidget) {
                return false;
            }

            var groupData = getGroupData($group),
                dragged_widget_area = ( parseInt(draggedWidget.widget_size.width) * parseInt(draggedWidget.widget_size.height) ),
                is_group_locked = ( ( dragged_widget_area + groupData.group_area ) > storage.max_group_area ),
                $hover_group = storage.$hover_group,
                activeClass = storage.showGroupOrnamentClass,
                group_offset = $group.offset(),
                mouse_offset = {
                    left: event.pageX,
                    top: event.pageY
                },
                border_width = 10;

            var delta = Math.abs(parseInt(group_offset.left - mouse_offset.left));

            // Remove classes from old group
            if ($hover_group && $hover_group.length) {
                $hover_group.removeClass(activeClass);
                $hover_group.removeClass(storage.hoverGroupClass);
                $hover_group.removeClass(storage.lockedGroupClass);
                storage.$hover_group = false;
            }

            // Marking new group
            if (delta < border_width) {

                markingGroup("border-hover", $group);

                clearWidgetOrnament();

            } else {

                if ( !$group.hasClass(storage.hoverGroupClass) ) {

                    // Lock
                    if (is_group_locked) {
                        $group.addClass(storage.lockedGroupClass);
                    }

                    markingGroup("hover", $group);
                }

                if (!is_group_locked) {
                    renderDropArea(event, $group);
                }
            }
        };

        var onDragEnd = function() {
            // Clear
            clearGroupMarkers();
        };

        var prepareDrop = function(event, $group) {
            var $target_group = $group,
                border_space = storage.border_space,
                group_offset = $group.offset(),
                is_new_widget = ( storage.target_group_offset ),
                mouse_offset = {
                    left: parseInt(event.pageX),
                    top: parseInt(event.pageY)
                },
                drop_delta;

            // If Adding new widget from widget list
            if (is_new_widget) {
                group_offset = storage.target_group_offset;
                storage.target_group_offset = false;
            }

            drop_delta = {
                left: mouse_offset.left - group_offset.left,
                top: mouse_offset.top - group_offset.top
            };

            if (drop_delta.left <= border_space) {

                //console.log("Новый виджет", is_new_widget);

                // Отменяем перемещение если дропаем на полоску новой группы справа или слева от таргета.
                if (!is_new_widget) {

                    var $widget_group = storage.$widget_group,
                        widget_count = $widget_group.find(".widget-wrapper").length,
                        widget_group_index = $widget_group.index(),
                        target_group_index = $group.index();

                    var is_solo_widget = (widget_count === 1),
                        is_before_group = (target_group_index === widget_group_index),
                        is_next_group = (target_group_index - 1 === widget_group_index);

                        //console.log(storage.draggedWidget, is_solo_widget, target_group_index, widget_group_index, is_next_group);

                    if ( is_solo_widget && ( is_before_group || is_next_group ) ) {
                        return false;
                    }
                }

                // Добавляем новую группу
                $target_group = addNewGroup($group);
            }

            //
            onDrop(event, $target_group);
        };

        var onDrop = function(event, $group) {
            var is_available = checkAvailability($group);

            if (is_available) {

                var dropArea = getDropArea(event, $group);
                if (dropArea.side) {
                    renderWidget($group, dropArea);
                }

            }
        };

        var getSegment = function(event, $target) {
            var target_offset = $target.offset(),
                mouse_offset = {
                    left: parseInt(event.pageX),
                    top: parseInt(event.pageY)
                },
                left_percent,
                top_percent,
                target_area,
                segment,
                left,
                top;

            // Определяем размеры дро области
            target_area = {
                width: parseInt($target.width()),
                height: parseInt($target.height())
            };

            // Вычисление сегмента
            left = target_offset.left - mouse_offset.left;
            top = target_offset.top - mouse_offset.top;
            left_percent = Math.abs( parseInt( ( left/target_area.width ) * 100 ) );
            top_percent = Math.abs( parseInt( ( top/target_area.height ) * 100 ) );

            if (left_percent < 50) {
                segment = (top_percent < 50) ? 1 : 3;
            } else {
                segment = (top_percent < 50) ? 2 : 4;
            }

            return segment;
        };

        var getDropArea = function(event, $group) {
            var $target = $(event.target),
                draggedWidget = ( storage.newDraggedWidget || storage.draggedWidget ),
                is_widget = ( $target.closest(".widget-wrapper").length ),
                group_segment = getSegment(event, $group),
                groupData = getGroupData($group),
                widget_segment,
                is_left_part,
                target_widget_index,
                target_widget_width,
                dropArea = {
                    group_segment: group_segment,
                    widget_segment: false,
                    $widget: false,
                    side: false,
                    target: false
                },
                targetWidget,
                $widget,
                side,
                target;

            // Определяем переменные для виджета
            if (is_widget) {
                $widget = $target.closest(".widget-wrapper");
                targetWidget = DashboardWidgets[$widget.data("widget-id")];
                target_widget_width = targetWidget.widget_size.width;
                target_widget_index = $widget.index();
                widget_segment = getSegment(event, $widget);
                is_left_part = ( ( widget_segment == 1 ) || ( widget_segment == 3 ) );

                dropArea.widget_segment = widget_segment;
                dropArea.$widget = $widget;
            }

            // ОСНОВНОЙ АЛГОРИТМ
            // Дропнули на виджет
            if (is_widget) {

                side = (is_left_part) ? "before" : "after";
                target = $widget;

                //console.log("Дропнули на виджет. вставляем виджет " + side, target[0]);

            // или в группу виджетов
            } else {

                side = "after";
                target = false;

                //console.log("Дропнули на пустое место");
            }


            // HOOK. КОРРЕКЦИЯ ДРОП-ПОЗИЦИИ
            var moving_inside_bloc = ( draggedWidget.widget_group_index === groupData.group_index ),
                problem_area = ( (groupData.widgetsArray.length === 2) && (groupData.group_area === 3) ), // Ситуация 1x1 + 2x1 или наоборот
                //is_end = ( ( target_widget_width == 2 ) && ( group_segment == 4) ),
                is_dragged_widget_2x1 = ( draggedWidget.widget_size.width === 2 && draggedWidget.widget_size.height === 1 ),
                is_dragged_widget_1x1 = ( draggedWidget.widget_size.width === 1 );

            if (is_widget) {

                // Если дропнули виджет сам на себя
                if (draggedWidget.widget_id === $widget.data("widget-id")) {

                    side = false;
                    target = false;

                    //console.log("Дропнули сами на себя");
                }

                if (moving_inside_bloc) {
                    //console.log("перемещение внутри блока");

                    // 1x1, 1x1, 2x1
                    if (groupData.widgetsArray.length === 3 && groupData.group_area == 4) {

                        if (is_dragged_widget_1x1 && target_widget_width === 2 ) {
                            side = false;
                            target = false;
                        }

                        if (is_dragged_widget_2x1) {
                            side = false;
                            target = false;
                        }
                    }

                } else {
                    //console.log("перемещение виджета из другой  группы");

                    if (problem_area && target_widget_width === 2) {
                        if (group_segment === 1) {
                            //side = "after";
                            side = false;

                            //console.log("Сегмент 1 виджет 2х1");
                        }

                        if (group_segment === 4) {
                            //side = "before";
                            side = false;

                            //console.log("Сегмент 4 виджет 2х1");
                        }
                    }

                    // Ситуация 1х1 => 2x1 <= 1х1
                    if (is_dragged_widget_2x1 && (groupData.widgetsArray.length === 2) && (groupData.group_area === 2)) {

                        if ( (target_widget_index === 0) && !is_left_part ) {
                            //side = "before";
                            side = false;
                        }

                        if ( (target_widget_index === 1) && is_left_part ) {
                            //side = "after";
                            side = false;
                        }

                    }
                }

            }


            // ситуация 1x1 + 2x1, кинули в сегмент 2
            if (groupData.widgetsArray.length === 2 && groupData.widgetsArray[0].widget_size.width === 1 && groupData.widgetsArray[1].widget_size.width === 2) {
                side = "after";
                target = groupData.widgetsArray[0].$widget_wrapper;
                //console.log("Случай 1х1 + 2х1, кидаем во 2й сегмент");
            }

            dropArea.side = side;
            dropArea.target = target;

            return dropArea
        };

        var checkAvailability = function($group) {
            var draggedWidget = storage.draggedWidget,
                current_widget_area = 0,
                new_group_area = 0,
                groupData = getGroupData( $group );

            // Check for movement inside group
            var drop_group_index = $group.index(),
                widget_group_index = draggedWidget.$widget_wrapper.closest(".widget-group-wrapper").index();

            if (drop_group_index === widget_group_index) {
                storage.is_new_group = false;
                return true;
            }

            // Current Widget Area
            if (draggedWidget) {
                current_widget_area = ( parseInt(draggedWidget.widget_size.width) * parseInt(draggedWidget.widget_size.height) );
                new_group_area = current_widget_area + groupData.group_area;

                return (new_group_area <= storage.max_group_area);
            }
        };

        var renderWidget = function($group, drop_place) {
            var widget = storage.draggedWidget,
                $widget_wrapper = widget.$widget_wrapper,
                $target = drop_place.target;

            // Добавляем в самый конец
            if (!$target || !$target.length) {
                $group.append($widget_wrapper);

            } else {
                if (drop_place.side === "before") {
                    $target.before($widget_wrapper);
                } else if (drop_place.side === "after") {
                    $target.after($widget_wrapper);
                }
            }
            prepareSaveWidget($group);
        };

        var prepareSaveWidget = function($group) {
            var widget = storage.draggedWidget,
                $before_widget_group = storage.$widget_group,
                new_widget_group_index = parseInt($group.index()),
                new_widget_sort = parseInt(widget.$widget_wrapper.index()),
                is_changed = !( ( new_widget_sort === widget.widget_sort ) && (new_widget_group_index === storage.$widget_group.index() ) ),
                is_last_group = storage.getLastGroupIndex(),
                is_new_group = ( $group.find(".widget-wrapper").length < 2 ),
                create_new_group = ( is_last_group == new_widget_group_index );

            $.each([$group,$before_widget_group], function() {
                var $group = $(this);
                if ($group.length) {
                    $group.find(".widget-wrapper").each( function() {
                        var id = $(this).data("widget-id"),
                            widget;

                        if (typeof DashboardWidgets[id] !== "undefined") {
                            widget = DashboardWidgets[id];
                            widget.initControlsController();
                        }
                    });
                }
            });

            //console.log(is_changed, $before_widget_group);

            // Если позиция виджета изменилась (как виджета внутри блока, так перемещение между блоками)
            if (is_changed) {

                // Удаляем старый блок, если он пуст
                var is_new_widget = storage.is_new_widget;
                var animation_time = checkEmptyGroup($before_widget_group, storage.getWidgetGroups(), is_new_widget);
                storage.is_new_widget = false;

                // Создаём новый блок для перемещений
                if (create_new_group) {
                    addNewGroup();
                }

                // Сохраняем виджет, после того пустая группа удалиться
                setTimeout( function() {
                    // Переопределяем индекс блока, на случай если он был удалён
                    new_widget_group_index = parseInt( $group.index() );

                    saveWidget({
                        widget: widget,
                        widget_id: widget.widget_id,
                        widget_sort: new_widget_sort,
                        group_index: new_widget_group_index,
                        is_new: is_new_group
                    });
                }, animation_time);

            }
        };

        var saveWidget = function(options) {
            var widget = options.widget,
                $deferred = $.Deferred(),
                href = storage.getSaveHref(options.widget_id),
                dataArray = {
                    block: options.group_index,
                    sort:  options.widget_sort
                };

            if (options.is_new) {
                dataArray.new_block = 1;
            }

            $.post(href, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done( function(response) {
                // Действия после успешного ответа
                if (response.status === "ok") {
                    // Перезаписываем новые данные сортировки для виджета
                    widget.widget_sort = options.widget_sort;
                    widget.widget_group_index = options.group_index;

                    // Виджет успешно сохранён
                    //console.log("Виджет успешно сохранён.Блок \"" + widget.widget_group_index + "\". Позиция \"" + widget.widget_sort + "\"");
                }
            });
        };

        var addNewGroup = function($group) {
            var $wrapper = storage.getGroupsBlock(),
                $new_group = storage.getNewGroupHTML();

            // Создаём группу перед группой
            if ($group && $group.length) {
                $group.before($new_group);
                return $new_group;

                // Иначе создаём группу в конце
            } else {
                //$wrapper.append($new_group);

                // After last group
                var $lastGroup = $wrapper.find(".widget-group-wrapper").last();
                $lastGroup.after($new_group);
            }

        };

        var renderDropArea = function(event, $group) {
            var dropArea = getDropArea(event, $group),
                is_widget = ( dropArea.$widget && dropArea.$widget.length ),
                positionClass = "",
                positionClasses = [
                    "segment-1",
                    "segment-2",
                    "segment-3",
                    "segment-4"
                ],
                $ornament;

            clearWidgetOrnament();

            // Показывает подцветку на виджете
            if (is_widget) {
                $ornament = dropArea.$widget.find(".widget-drop-ornament");

                positionClass = positionClasses[dropArea.widget_segment - 1];
                $ornament.addClass(positionClass);

                $ornament.addClass(storage.showClass);

                storage.$activeWidgetOrnament = $ornament;

                if (dropArea.side) {
                    $ornament.addClass(storage.activeClass);
                } else {
                    $ornament.addClass(storage.lockedClass);
                }

            } else {
                //$ornament = storage.getDropOrnament(),
                //

                //positionClass = positionClasses[dropArea.group_segment - 1];
                //
                //$ornament.css({
                //    width: 100,
                //    height: 100
                //});
                //
                //$ornament.addClass("in-group");
                //$group.append($ornament);
            }
        };

        // Widget List Functions

        var onWidgetListClick = function(event, $target) {

            prepareToDropNewWidget(event, $target);

            onWidgetListDrop(event);
        };

        var onSortWidgetList = function( $select ) {
            var value = $select.val(),
                $groups = storage.getListWrapper().find(".list-group-wrapper"),
                $targetGroup = storage.getListWrapper().find("." + value + "-list-group");

            if (value) {
                // Hide all
                $groups.hide();

                // Show current
                $targetGroup.show();
            } else {
                $groups.show();
            }
        };

        var onWidgetListDragStart = function(event, $widgetImage) {
            prepareToDropNewWidget(event, $widgetImage);

            // Hack. In FF D&D doesn't work without dataTransfer
            event.originalEvent.dataTransfer.setData("text/html", "<div class=\"anything\"></div>");
        };

        var onWidgetListDragEnd = function() {
            // Clear
            clearGroupMarkers();
        };

        var clearGroupMarkers = function() {
            // Clear Group
            var $hoverGroup = storage.$hover_group;
            if ($hoverGroup && $hoverGroup.length) {
                $hoverGroup.removeClass(storage.hoverGroupClass);
                $hoverGroup.removeClass(storage.lockedGroupClass);
                $hoverGroup.removeClass(storage.showGroupOrnamentClass);
                storage.$hover_group = false;
                storage.$widget_group = false;
            }

            clearWidgetOrnament();

            // Clear Widget
            var draggedWidget = storage.draggedWidget;
            if (draggedWidget) {
                draggedWidget.$widget_wrapper.removeClass(storage.isDraggedClass);
                storage.draggedWidget = false;
            }

            var newWidget = storage.newDraggedWidget;
            if (newWidget) {
                storage.newDraggedWidget = false;
            }
        };

        var clearWidgetOrnament = function() {
            var $ornament = (storage.$activeWidgetOrnament || false);
            var positionClasses = [
                "segment-1",
                "segment-2",
                "segment-3",
                "segment-4",
                storage.activeClass,
                storage.lockedClass,
                storage.showClass
            ];

            if ($ornament && $ornament.length) {

                $.each(positionClasses, function() {
                    $ornament.removeClass("" + this);
                });

                storage.$activeWidgetOrnament = false;
            }
        };

        var prepareToDropNewWidget = function(event, $widgetImage) {
            var $newWidget = $widgetImage.closest(".widget-item-wrapper"),
                widget_size_data = $newWidget.data("size").split("x"),
                backend_url = storage.getListWrapper().data("backend-url"),
                app_id = $newWidget.data("app_id");

            storage.newDraggedWidget = {
                widget_app_id: app_id,
                widget_id: false,
                widget_name: $newWidget.data("widget"),
                widget_href: backend_url + "" + app_id + "/?widget=",
                widget_sort: "0",
                widget_group_index: "0",
                widget_size: {
                    width: parseInt(widget_size_data[0]),
                    height: parseInt(widget_size_data[1])
                }
            };
        };

        var onWidgetListDrop = function(event, $group) {
            var draggedWidget = storage.newDraggedWidget;

            // Cancel drop on clocked Group
            if ($group && $group.length) {
                if ($group.hasClass(storage.lockedGroupClass)) {
                    return false;
                }

                var dropArea = getDropArea(event, $group);
                if (!dropArea.side) {
                    return false;
                }
            }

            // Cancel drop if wlist is locked
            if (storage.is_widget_list_locked) {
                return false;
            } else {
                storage.is_widget_list_locked = true;
            }

            var $deferred = addNewWidget();

            $deferred.done( function(response) {

                storage.is_widget_list_locked = false;

                if (response.status === "ok") {
                    var widget_id = response.data.id,
                        is_drop_on_group = ($group && $group.length);

                    // Set Data
                    draggedWidget['widget_id'] = widget_id;
                    draggedWidget['widget_href'] = draggedWidget['widget_href'] + "" + widget_id;

                    // Need for Group replace
                    if (is_drop_on_group) {
                        storage.target_group_offset = $group.offset();
                    }

                    // Render HTML
                    var $widget = $(response.data.html);
                    var $new_group = addNewGroup( storage.getWidgetGroups().eq(0) );
                    $new_group.append($widget);

                    // Init new Widget
                    DashboardWidgets[widget_id] = new DashboardWidget(draggedWidget);

                    // Replace Widget in Target Group
                    if (is_drop_on_group) {
                        storage.draggedWidget = DashboardWidgets[widget_id];
                        storage.$widget_group = $new_group;

                        replaceWidgetAfterCreate(event, $group);
                    }
                }
            });
        };

        var replaceWidgetAfterCreate = function(event, $group) {
            storage.is_new_widget = true;

            prepareDrop(event, $group);

            storage.draggedWidget = false;
        };

        var addNewWidget = function() {
            var $deferred = $.Deferred(),
                href = storage.getNewWidgetHref(),
                dataArray = {
                    app_id: storage.newDraggedWidget.widget_app_id,
                    widget: storage.newDraggedWidget.widget_name,
                    block: 0,
                    sort: 0,
                    size: storage.newDraggedWidget.widget_size.width + "x" + storage.newDraggedWidget.widget_size.height,
                    dashboard_id: getDashboardID(),
                    new_block: 1
                };

            $.post(href, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            return $deferred;
        };

        // For Custom Dashboard
        var onEmptyGroupDrop = function(event) {
            var $group = storage.getWidgetGroups().last();

            if (storage.draggedWidget) {
                prepareDrop(event, $group);

            } else if (storage.newDraggedWidget) {
                onWidgetListDrop(event, $group);
            }
        };

        var onEmptyGroupOver = function(event, $emptyGroup, $wrapper) {
            var $lastGroupContainer = $wrapper.find(".widget-group-wrapper").last();

            markingGroup("hover", $lastGroupContainer);
        };

        var markingGroup = function( type, $group, callback ) {
            var hoverClass = storage.hoverGroupClass,
                borderHoverClass = storage.showGroupOrnamentClass,
                activeClass;

            if (type == "hover") {
                activeClass = hoverClass;

                if (!$group.hasClass(activeClass)) {
                    $group.addClass(activeClass);
                    storage.$hover_group = $group.addClass(activeClass);
                }
            }

            if (type == "border-hover") {
                activeClass = borderHoverClass;

                if (!$group.hasClass(activeClass)) {
                    $group.addClass(activeClass);
                    storage.$hover_group = $group.addClass(activeClass);
                }
            }

            if (callback && typeof callback == "function") {
                callback();
            }
        };

        // Main Initialize

        $(document).ready( function() {
            bindEvents();
        });

    })();

    // PAGE | Скрипты относящиеся к странице
    ( function() {

        var storage = {
            activeLighterClass: "is-highlighted",
            dashboardEditableClass: "is-editable-mode",
            dashboardCustomEditClass: "is-custom-edit-mode",
            dashboardTvClass: "tv",
            activeEditModeClass: "is-active",
            isLoadingClass: "is-loading",
            hiddenClass: "is-hidden",
            showClass: "is-shown",
            animateClass: "is-animated",
            lazyLoadCounter: 0,
            dashboardSelectData: {
                default: false,
                active: false
            },
            isEditModeActive: false,
            isWidgetListLoaded: false,
            isBottomLazyLoadLocked: false,
            isTopLazyLoadLocked: false,
            isActivityFilterLocked: false,
            is_dialog_shown: false,
            topLazyLoadingTimer: 0,
            activityFilterTimer: 0,
            lazyTime: 15 * 1000,
            scrollData: {
                top: false,
                fixedTop: false,
                fixedBottom: false,
                scrollValue: 0
            },
            is_custom_dashboard: false,
            getPageWrapper: function() {
                return $("#d-page-wrapper");
            },
            getGroupsWrapper: function() {
                return $("#d-widgets-wrapper");
            },
            getShowButton: function() {
                return $("#show-dashboard-editable-mode");
            },
            getHideButton: function() {
                return $("#close-dashboard-editable-mode");
            },
            getWidgetList: function() {
                return $("#widgets-list-wrapper");
            },
            getWidgetActivity: function() {
                return $("#widget-activity");
            },
            getSettingsWrapper: function() {
                return $("#d-settings-wrapper");
            },
            getCloseTutorialHref: function() {
                return "?module=dashboard&action=closeTutorial";
            },
            getNotifications: function() {
                return $("#d-notification-wrapper");
            },
            getFirstNoticeWrapper: function() {
                return $("#d-first-notice-wrapper");
            },
            getDashboardsList: function() {
                return $("#d-dashboards-list-wrapper");
            }
        };

        var initialize = function() {
            // Init Select
            initDashboardSelect();
        };

        var bindEvents = function() {
            var $showLink = storage.getShowButton(),
                $hideLink = storage.getHideButton(),
                $widgetActivity = storage.getWidgetActivity(),
                $dashboardList = storage.getDashboardsList(),
                $closeNoticeLink = storage.getFirstNoticeWrapper().find(".close-notice-link");

            $dashboardList.on("change", "#d-dashboards-select", function() {
                changeDashboard( $(this) );
            });

            $closeNoticeLink.on("click", function() {
                hideFirstNotice();
            });

            $showLink.on("click", function() {
                onShowLinkClick( $(this) );
                return false;
            });

            $hideLink.on("click", function() {
                $showLink.show();
                $hideLink.hide();
                hideEditMode();
                hideWidgetList();
                return false;
            });

            $widgetActivity.on("click", "#d-load-more-activity", function () {
                loadOldActivityContent( $(this), $widgetActivity );
                return false;
            });

            $(".activity-filter-wrapper input:checkbox").on("change", function() {
                if (storage.activityFilterTimer) {
                    clearTimeout(storage.activityFilterTimer);
                }

                if (storage.topLazyLoadingTimer) {
                    clearTimeout(storage.topLazyLoadingTimer);
                }

                showLoadingAnimation($widgetActivity);

                storage.activityFilterTimer = setTimeout( function() {
                    showFilteredData( $widgetActivity );
                }, 2000);

                storage.topLazyLoadingTimer = setTimeout( function() {
                    loadNewActivityContent($widgetActivity);
                }, storage.lazyTime );

                // Change Text
                changeFilterText();

                return false;
            });

            var $notification_wrapper = $(".d-notification-wrapper");
            $notification_wrapper.on("click", ".wa-announcement-close", function() {
                var $notices = $(".d-notification-block");
                if ($notices.length < 2) {
                    // or remove();
                    $("body").append( $notification_wrapper.hide() );
                }
                var app_id = $(this).attr('rel');
                var url = backend_url + "?module=settings&action=save";
                $.post(url, {app_id: app_id, name: 'announcement_close', value: 'now()'});
                return false;
            });

            // Escape close edit-mode
            $(document).on("keyup", function(event) {
                var is_dialog_shown = storage.is_dialog_shown;
                if ( !is_dialog_shown ) {
                    if (event.keyCode == 27 && storage.isEditModeActive) {
                        $hideLink.trigger("click");
                    }
                } else {
                    storage.is_dialog_shown = false;
                }
            });

            $(window).on("scroll", function(event) {
                onPageScroll(event);
            });

            if (!storage.is_custom_dashboard) {
                storage.topLazyLoadingTimer = setTimeout(function () {
                    loadNewActivityContent($widgetActivity);
                }, storage.lazyTime);
            }

            // Development helper: Ctrl + Alt + dblclick on a widget reloads the widget
            $("#d-widgets-block").on("dblclick", ".widget-wrapper", function(e) {
                if (e.altKey && (e.ctrlKey || e.metaKey)) {
                    var id = $(this).data("widget-id");
                    if (id > 0 && DashboardWidgets[id]) {
                        DashboardWidgets[id].$widget.html('<div class="block"><i class="icon16 loading"></i></div>');
                        DashboardWidgets[id].renderWidget(true);
                    }

                    // Clear accidental text selection
                    setTimeout( function() {
                        if(document.selection && document.selection.empty) {
                            document.selection.empty();
                        } else if(window.getSelection) {
                            window.getSelection().removeAllRanges();
                        }
                    }, 0);

                    return false;
                }
            });
            $("#d-widgets-block").on("click", ".widget-wrapper", function(e) {
                if (e.altKey && (e.ctrlKey || e.metaKey)) {
                    return false;
                }
            });

            //$showLink.click();
            //$("body").addClass(storage.dashboardCustomEditClass);

            storage.getPageWrapper().on("click", ".d-delete-dashboard-wrapper a", function() {
                var $link = $(this),
                    do_delete = confirm( $link.data("confirm-text") );

                if (do_delete) {
                    deleteCustomDashboard( $link.data("dashboard-id") );
                }
                return false;
            });
        };

        var onShowLinkClick = function($showLink) {
            var $hideLink = storage.getHideButton(),
                $firstNotice = storage.getFirstNoticeWrapper(),
                notice_is_shown = ( $firstNotice.css("display") !== "none" );

            // Change Buttons
            $showLink.hide();
            $hideLink.show();

            // Hide First Notice
            if (notice_is_shown) {
                $firstNotice.find(".close-notice-link").trigger("click");
            }

            //
            showEditMode();
            //
            showWidgetList();
        };

        var showFirstNotice = function() {
            var $wrapper = storage.getFirstNoticeWrapper(),
                $activity = storage.getWidgetActivity(),
                showNotice = $wrapper.data("show-notice"),
                $notifications = storage.getNotifications();

            if (showNotice) {
                $activity.addClass(storage.hiddenClass);
                $notifications.addClass(storage.hiddenClass);
                $wrapper.show();
            }
        };

        var hideFirstNotice = function() {
            var $wrapper = storage.getFirstNoticeWrapper(),
                $activity = storage.getWidgetActivity(),
                $notifications = storage.getNotifications();

            // hide DOM
            $wrapper.hide();

            $activity
                .removeClass(storage.hiddenClass)
                .addClass(storage.animateClass);

            $notifications
                .removeClass(storage.hiddenClass)
                .addClass(storage.animateClass);

            setTimeout( function() {
                $activity.addClass(storage.showClass);
                $notifications.addClass(storage.showClass);
            }, 4);

            // set data
            $.post(storage.getCloseTutorialHref(), {});
        };

        var changeFilterText = function() {
            var $filterText = $("#activity-select-text"),
                full_text = $filterText.data("full-text"),
                not_full_text = $filterText.data("not-full-text"),
                $form = $("#activity-filter"),
                check_count = 0,
                full_checked = true;

            $form.find("input:checkbox").each( function() {
                var $input = $(this),
                    is_checked = ( $input.attr("checked") == "checked" );

                if (!is_checked) {
                    full_checked = false;
                } else {
                    check_count++;
                }
            });

            if (full_checked) {
                $filterText.text(full_text);
            } else {
                not_full_text += " (" + check_count + ")";
                $filterText.text(not_full_text);
            }
        };

        var onPageScroll = function() {
            var scrollTop = getScrollTop(),
                is_edit_mode = isEditMode(),
                $activityBlock = $("#d-activity-wrapper"),
                activity_height = $activityBlock.outerHeight(),
                $window = $(window),
                displayArea = {
                    width: $window.width(),
                    height: $window.height()
                };

            //
            scrollingWidgetContent({
                scrollTop: scrollTop,
                displayArea: displayArea,
                is_edit_mode: is_edit_mode,
                activity_height: activity_height
            });

            //
            initActivityLazyLoading({
                scrollTop: scrollTop,
                displayArea: displayArea,
                is_edit_mode: is_edit_mode,
                $activityBlock: $activityBlock,
                activity_height: activity_height
            });
        };

        var scrollingWidgetContent = function(options) {
            var scrollData = {
                    top: storage.scrollData.top,
                    fixedTop: storage.scrollData.fixedTop,
                    fixedBottom: storage.scrollData.fixedBottom,
                    scrollValue: storage.scrollData.scrollValue
                },
                scrollTop = options.scrollTop,
                old_scroll_data = scrollData.scrollValue,
                direction = ( old_scroll_data > scrollTop ) ? 1 : -1,
                displayArea = options.displayArea,
                is_edit_mode = options.is_edit_mode,
                activity_height = options.activity_height,
                $widgetsWrapper = $("#d-widgets-wrapper"),
                $widgetsBlock = $widgetsWrapper.find(".d-widgets-block"),
                widgets_height = $widgetsBlock.outerHeight(),
                widgets_top = $widgetsWrapper.offset().top,
                dynamic_widgets_top = $widgetsBlock.offset().top,
                display_height = displayArea.height,
                display_width = displayArea.width,
                bottom_fixed_class = "fixed-to-bottom",
                top_fixed_class = "fixed-to-top",
                is_mobile = false,
                min_width = 720,
                delta;

            var activateScroll = ( !is_edit_mode && !is_mobile && (activity_height > widgets_height) && ( display_width > min_width ) );

            if (activateScroll) {
                delta = scrollTop - widgets_top;

                // Если высота виджетов меньше размера дисплея, то всё просто
                if (widgets_height < display_height) {
                    if (delta > 0) {

                        if (scrollData.top || !scrollData.fixedTop || scrollData.fixedBottom) {
                            $widgetsBlock.removeAttr("style");
                            $widgetsBlock.addClass(top_fixed_class);

                            scrollData.top = false;
                            scrollData.fixedTop = true;
                            scrollData.fixedBottom = false;
                        }

                    } else {

                        if (scrollData.top || scrollData.fixedTop || scrollData.fixedBottom) {
                            $widgetsBlock.removeAttr("style");
                            $widgetsBlock.removeClass(bottom_fixed_class);
                            $widgetsBlock.removeClass(top_fixed_class);

                            scrollData.top = false;
                            scrollData.fixedTop = false;
                            scrollData.fixedBottom = false;
                        }
                    }

                // Если высота больще экрана
                } else {

                    // Если меньше чем изначальное положение отключаем
                    if (scrollTop <= widgets_top) {

                        if (scrollData.top || scrollData.fixedTop || scrollData.fixedBottom) {
                            $widgetsBlock
                                .removeAttr("style")
                                .removeClass(bottom_fixed_class)
                                .removeClass(top_fixed_class);

                            scrollData.top = false;
                            scrollData.fixedTop = false;
                            scrollData.fixedBottom = false;
                        }

                    // Если выше начала после скролла фиксируем к верху
                    } else if (scrollTop <= dynamic_widgets_top && dynamic_widgets_top >= widgets_top) {

                        if (direction > 0) {

                            if (scrollData.top || !scrollData.fixedTop || scrollData.fixedBottom) {
                                $widgetsBlock
                                    .removeAttr("style")
                                    .removeClass(bottom_fixed_class)
                                    .addClass(top_fixed_class);

                                scrollData.top = false;
                                scrollData.fixedTop = true;
                                scrollData.fixedBottom = false;
                            }

                        } else {

                            if (!scrollData.top || scrollData.fixedTop || scrollData.fixedBottom) {
                                $widgetsBlock
                                    .css("top", dynamic_widgets_top - widgets_top)
                                    .removeClass(top_fixed_class)
                                    .removeClass(bottom_fixed_class);

                                scrollData.top = true;
                                scrollData.fixedTop = false;
                                scrollData.fixedBottom = false;
                            }

                        }

                    // Если ниже конца
                    } else if (scrollTop + display_height >= dynamic_widgets_top + widgets_height) {

                        // Если направление скролла вверх
                        if (direction > 0) {

                            if (!scrollData.top || scrollData.fixedTop || scrollData.fixedBottom) {
                                $widgetsBlock
                                    .css("top", dynamic_widgets_top - widgets_top)
                                    .removeClass(top_fixed_class)
                                    .removeClass(bottom_fixed_class);

                                scrollData.top = true;
                                scrollData.fixedTop = false;
                                scrollData.fixedBottom = false;
                            }

                        // Если направление скролла вниз
                        } else {

                            if (scrollData.top || scrollData.fixedTop || !scrollData.fixedBottom) {
                                $widgetsBlock
                                    .removeAttr("style")
                                    .removeClass(top_fixed_class)
                                    .addClass(bottom_fixed_class);

                                scrollData.top = false;
                                scrollData.fixedTop = false;
                                scrollData.fixedBottom = true;
                            }
                        }

                    // Во всех других случаях
                    } else {

                        if (!scrollData.top || scrollData.fixedTop || scrollData.fixedBottom) {
                            $widgetsBlock
                                .css("top", dynamic_widgets_top - widgets_top)
                                .removeClass(top_fixed_class)
                                .removeClass(bottom_fixed_class);

                            scrollData.top = true;
                            scrollData.fixedTop = false;
                            scrollData.fixedBottom = false;
                        }
                    }
                }
            }

            // Save New Data
            scrollData.scrollValue = scrollTop;
            storage.scrollData = scrollData;
        };

        var showFilteredData = function( $widgetActivity) {
            if (!storage.isActivityFilterLocked) {
                storage.isActivityFilterLocked = true;

                var $wrapper = $widgetActivity.find(".activity-list-block"),
                    $form = $("#activity-filter"),
                    $deferred = $.Deferred(),
                    ajaxHref = "?module=dashboard&action=activity",
                    dataArray = $form.serializeArray();

                dataArray.push({
                    name: "save_filters",
                    value: 1
                });

                $.post(ajaxHref, dataArray, function (response) {
                    $deferred.resolve(response);
                });

                $deferred.done( function(response) {
                    var html = "<div class=\"empty-activity-text\">" + $wrapper.data("empty-text") + "</div>";
                    if ( $.trim(response).length ) {
                        html = response;
                    }
                    $wrapper.html(html);

                    hideLoadingAnimation($widgetActivity);

                    storage.isActivityFilterLocked = false;
                });
            }
        };

        var showEditMode = function() {
            var $dashboard = storage.getPageWrapper();

            $dashboard.addClass(storage.dashboardEditableClass);

            toggleHighlighterGroups();

            storage.isEditModeActive = true;
        };

        var hideEditMode = function() {
            var $dashboard = storage.getPageWrapper(),
                $settings = storage.getSettingsWrapper(),
                dashboard_id = getDashboardID(),
                is_custom_dashboard = ( dashboard_id !== "default_dashboard" && dashboard_id !== "new_dashboard" );

            // if we in Custom Dashboard
            if (is_custom_dashboard) {
                reloadDashboard();

            } else {

                if ($settings.css("display") != "none") {
                    $settings.find(".hide-settings-link").trigger("click");
                }

                $dashboard.removeClass(storage.dashboardEditableClass);

                toggleHighlighterGroups();

                storage.isEditModeActive = false;
            }
        };

        var toggleHighlighterGroups = function() {
            var $wrapper = storage.getGroupsWrapper(),
                activeClass = storage.activeLighterClass;

            // Groups
            $wrapper.toggleClass(activeClass);

            // Body
            $("body").toggleClass(activeClass);
        };

        var showWidgetList = function() {
            var $widgetList = storage.getWidgetList(),
                $deferred = $.Deferred(),
                href = "?module=dashboard&action=sidebar",
                is_loaded = storage.isWidgetListLoaded;

            // Show block
            $widgetList.removeClass(storage.hiddenClass);

            // Render
            if (!is_loaded) {
                // Start Animation
                startAnimateWidgetList();

                storage.isWidgetListLoaded = true;

                $.post(href, function(request) {
                    $deferred.resolve(request);
                });

                $deferred.done( function(response) {
                    // Stop animation
                    stopAnimateWidgetList();

                    // Adding html to wrapper
                    $widgetList.prepend(response);
                });
            }

        };

        var hideWidgetList = function() {
            var $widgetList = storage.getWidgetList();

            $widgetList.addClass(storage.hiddenClass);
        };

        var startAnimateWidgetList = function() {
            var $widgetList = storage.getWidgetList(),
                activeClass = storage.isLoadingClass;

            $widgetList.addClass(activeClass);
        };

        var stopAnimateWidgetList = function() {
            var $widgetList = storage.getWidgetList(),
                activeClass = storage.isLoadingClass;

            $widgetList.removeClass(activeClass);
        };

        var loadOldActivityContent = function($link, $widgetActivity) {
            // Save data
            if (!storage.isBottomLazyLoadLocked) {
                storage.isBottomLazyLoadLocked = true;

                showLoadingAnimation($widgetActivity);

                var $linkWrapper = $link.closest(".show-more-activity-wrapper"),
                    $wrapper = $widgetActivity.find(".activity-list-block"),
                    max_id = $wrapper.find(".activity-item:last").data('id'),
                    $deferred = $.Deferred(),
                    ajaxHref = "?module=dashboard&action=activity",
                    dataArray = {
                        max_id: max_id
                    };

                $.post(ajaxHref, dataArray, function (response) {
                    $deferred.resolve(response);
                });

                $deferred.done( function(response) {
                    // Remove Link
                    $linkWrapper.remove();

                    // Render
                    $wrapper.append(response);

                    storage.isBottomLazyLoadLocked = false;
                    storage.lazyLoadCounter++;

                    hideLoadingAnimation($widgetActivity);
                });
            }
        };

        var loadNewActivityContent = function($widgetActivity) {
            // Save data
            if (!storage.isTopLazyLoadLocked) {
                storage.isTopLazyLoadLocked = true;

                showLoadingAnimation($widgetActivity);

                var $wrapper = $widgetActivity.find(".activity-list-block"),
                    min_id = $wrapper.find(".activity-item:first").data('id'),
                    $deferred = $.Deferred(),
                    ajaxHref = "?module=dashboard&action=activity",
                    dataArray = {
                        min_id: min_id
                    };

                $.post(ajaxHref, dataArray, function (response) {
                    $deferred.resolve(response);
                });

                $deferred.done( function(response) {
                    if ( $.trim(response).length ) {
                        // Render
                        $wrapper.find(".empty-activity-text").remove();
                        $wrapper.prepend(response);
                    }

                    storage.isTopLazyLoadLocked = false;

                    if (!storage.is_custom_dashboard) {
                        storage.topLazyLoadingTimer = setTimeout( function() {
                            loadNewActivityContent($widgetActivity);
                        }, storage.lazyTime );
                    }

                    hideLoadingAnimation($widgetActivity);
                });
            }
        };

        var initActivityLazyLoading = function(options) {
            var scrollTop = options.scrollTop,
                displayArea = options.displayArea,
                is_edit_mode = options.is_edit_mode,
                $activityBlock = options.$activityBlock,
                activity_height = options.activity_height,
                $link = $("#d-load-more-activity"),
                lazyLoadCounter = storage.lazyLoadCounter,
                correction = 47;

            if ($link.length && !is_edit_mode) {
                var isScrollAtEnd = ( scrollTop >= ( $activityBlock.offset().top + activity_height - displayArea.height - correction ) );
                if (isScrollAtEnd) {
                    if (lazyLoadCounter >=2) {

                        var $wrapper = $link.closest(".show-more-activity-wrapper"),
                            loadingClass = "is-loading";

                        $wrapper.removeClass(loadingClass);

                    } else {

                        // Trigger event
                        $link.trigger("click");
                    }
                }
            }
        };

        var showLoadingAnimation = function($widgetActivity) {
            $widgetActivity.find(".activity-header .loading").show();
        };

        var hideLoadingAnimation = function($widgetActivity) {
            $widgetActivity.find(".activity-header .loading").hide();
        };

        var initDashboardSelect = function() {
            var $dashboardList = storage.getDashboardsList(),
                $select = getDashboardSelect(),
                default_value = $select.find("option").first().val();

            storage.dashboardSelectData.default = default_value;
            storage.dashboardSelectData.active = default_value;

            $select.val(default_value);

            $dashboardList.prepend($select);
        };

        var changeDashboard = function() {
            var $body = $('body'),
                customEditClass = storage.dashboardCustomEditClass,
                tvClass = storage.dashboardTvClass,
                value = getDashboardID();

            if (value == "new_dashboard") {

                var $select = getDashboardSelect(),
                    last_active_val = storage.dashboardSelectData.active;

                // Set Select data
                $select.val(last_active_val);

                createNewDashboard();

            } else if (value == "default_dashboard") {
                reloadDashboard();

            } else {
                // Set var (needed for intervals)
                storage.is_custom_dashboard = true;

                // Set custom ornament
                $body
                    .addClass(customEditClass)
                    .addClass(tvClass);

                // Load Widgets
                initCustomDashboard(value);

                storage.dashboardSelectData.active = value;
            }
        };

        var initCustomDashboard = function( dashboard_id) {
            var $deferred = $.Deferred(),
                $dashboardArea = $("#d-widgets-block"),
                dashboard_href = "?module=dashboard&action=editPublic&dashboard_id=" + dashboard_id,
                dashboard_data = {};

            $dashboardArea.html("");

            $.post(dashboard_href, dashboard_data, function(response) {
                $deferred.resolve(response);
            });

            $deferred.done( function(html) {
                $dashboardArea.html(html);

                var $link = $dashboardArea.find(".d-dashboard-link-wrapper"),
                    $deleteLink = $dashboardArea.find(".d-delete-dashboard-wrapper");

                renderDashboardLinks( $link, $deleteLink );
            });

        };

        var renderDashboardLinks = function( $link, $deleteLink ) {
            var $groupsWrapper = storage.getGroupsWrapper(),
                $linkWrapper = $("#d-dashboard-link-wrapper"),
                $linkHTML = $link.html();

            // Link
            $linkWrapper.html( $linkHTML );

            // Delete
            $("#d-delete-dashboard-wrapper").remove();
            $deleteLink.attr("id", "d-delete-dashboard-wrapper").slideDown(200);
            $groupsWrapper.after( $deleteLink );
        };

        var createNewDashboard = function() {
            var $dialogWrapper = $("#dashboard-editor-dialog"),
                $loading = '<i class="icon16 loading"></i>';

            storage.is_dialog_shown = true;

            $dialogWrapper.waDialog({
                onLoad: function() {
                    var $input = $dialogWrapper.find("input:text:first");

                    $input.focus();
                },
                onSubmit: function($dialog) {
                    var $form = $dialog.find("form"),
                        $deferred = $.Deferred();

                    // Load
                    $dialog.find(':submit:first').parent().append($loading);

                    $.post( $form.attr('action'), $form.serialize(), function(responce) {
                        $deferred.resolve(responce);
                    }, 'json');

                    $deferred.done( function(responce) {

                        // Remove Load
                        $form.find('.loading').remove();

                        if (responce.status == 'ok') {
                            var $select = getDashboardSelect(),
                                $newOption = $('<option value="'+responce.data.id+'">' + responce.data.name +'</option>');

                            $select.find("[value=\"new_dashboard\"]:first").before($newOption);

                            $dialog.trigger('close');

                            $select
                                .val(responce.data.id)
                                .trigger("change");

                            $form[0].reset();
                        } else {
                            alert(responce.errors);
                        }

                        storage.is_dialog_shown = false;
                    });

                    return false;
                }
            });
        };

        var reloadDashboard = function() {
            var $select = getDashboardSelect();

            // Set for clear Browser form saver
            $select.css("visibility", "hidden");

            // Reload
            location.reload();
        };

        var deleteCustomDashboard = function( dashboard_id ) {
            var $deferred = $.Deferred(),
                delete_href = "?module=dashboard&action=dashboardDelete",
                delete_data = {
                    id: dashboard_id
                };

            if (dashboard_id) {
                $.post(delete_href, delete_data, function(responce) {
                    $deferred.resolve(responce);
                }, "json");

                $deferred.done( function(responce) {
                    location.reload();
                    return false;
                });
            }
        };

        $(document).ready( function() {
            initialize();
            //
            bindEvents();
            //
            showFirstNotice();
        });

    })();

})(jQuery, backend_url);