// Включаем дополнительные параметры в jQuery Event
jQuery.event.props.push("dataTransfer");
jQuery.event.props.push("pageX");
jQuery.event.props.push("pageY");

// Контейнер для всех виджетов
const DashboardWidgets = {};

// Контейнер для контроллеров виджетов
const DashboardControllers = {};

const WidgetSort = ( function($) {
    return class WidgetSort {
        $root_widget_groups;
        $grouped_widgets;

        constructor() {
            let that = this;

            that.nested_selector = '.js-nested-sortable';
            that.root_selector = '#d-widgets-block';
            that.$grouped_widgets = $(that.nested_selector);
            that.$root_widget_groups = $(that.root_selector);
            that.$empty_group = that.$root_widget_groups.find(':last-of-type.js-empty-group');
            that.options = {};
            that.$dragged= null;

            that.init();
            //wa_widget_changed_type
            $(document).on('wa_new_widget_create', function (event) {
                that.init();
            })
        }

        init() {
            this.groupedWidgets();
            this.rootWidgetGroups();
        }

        groupedWidgets() {
            const that = this;
            let options = {};
            that.$grouped_widgets.each(function () {
                let element = this;

                options = {
                    group: {
                        name: 'nested',
                        put(to, from) {
                            let $element = $(to.el),
                                count_small_widgets = $element.children('.widget-1x1').length,
                                count_middle_widgets = $element.children('.widget-2x1').length,
                                is_middle = that.$dragged?.classList.contains('widget-2x1'),
                                is_widgets_list_block = from.el.classList.contains('list-wrapper');

                            return !(count_small_widgets === 4
                                || count_middle_widgets === 2
                                || (count_small_widgets === 2 && count_middle_widgets)
                                || from.el.getAttribute('id') === 'd-widgets-block'
                                || (count_middle_widgets && is_middle)
                                || (count_small_widgets === 3 && is_middle)
                                || is_widgets_list_block);
                        }
                    },
                    handle: '.widget-wrapper',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    ghostClass: 'widget-ghost',
                    chosenClass: 'widget-chosen',
                    dragClass: 'widget-drag',
                    filter: '.is-removed, .js-empty-group',
                    onAdd(event) {
                        // Если положили виджет в пустую группу, то создаем новую пустую группу
                        if (event.to.classList.contains('js-empty-group') && !event.from.classList.contains('list-wrapper')) {
                            event.to.classList.remove('js-empty-group')
                            event.to.parentElement.insertAdjacentHTML('beforeend', '<div class="widget-group-wrapper js-nested-sortable js-empty-group"></div>');
                            Sortable.create(document.querySelector('.js-empty-group'), options)
                        }

                        let widget_id = event.item.dataset.widgetId;
                        let group_index = [].indexOf.call(event.to.parentElement?.children, event.to);

                        if (widget_id) {
                            that.saveWidget({
                                widget_id,
                                widget_sort: event.newIndex,
                                group_index,
                                is_new: false
                            });
                        }
                    },
                    onRemove(event) {
                        // Удаляем пустую группу, когда из нее был перенесен последний виджет
                        if (event.from.children.length === 0) {
                            Sortable.get(event.from).destroy()
                            event.from.remove();
                        }
                    },
                    onMove(event) {

                        if (event.originalEvent.type === 'dragenter' && event.originalEvent.target.classList.contains('widget-group-wrapper')) {
                            event.originalEvent.target.classList.toggle('hover')
                        }
                        if (event.originalEvent.type === 'dragleave' && event.originalEvent.target.classList.contains('widget-group-wrapper')) {
                            event.originalEvent.target.classList.toggle('hover')
                        }

                    },
                    onStart(event){
                        that.$dragged = event.item;
                        that.$empty_group.toggleClass('is-active', true)
                    },
                    onEnd(event) {
/*                        let widget_id = event.item.dataset.widgetId;
                        let group_index = [].indexOf.call(event.to.parentElement?.children, event.to);
                        if (widget_id) {
                            that.saveWidget({
                                widget_id,
                                widget_sort: event.newIndex,
                                group_index,
                                is_new: false
                            });
                        }*/
                        that.setGroupSortable(event.to)
                        that.setGroupSortable(event.from)

                        that.$empty_group.toggleClass('is-active', false)

                        // Reinit widget sets
                        that.init();
                    }
                };

                $(element).sortable(options);
            })
            that.options = options;
        }

        rootWidgetGroups() {
            const that = this;
            that.$root_widget_groups.sortable({
                animation: 150,
                group: {
                    name: 'root',
                },
                ghostClass: 'widget-ghost',
                chosenClass: 'widget-chosen',
                dragClass: 'widget-drag',
                forceFallback: true,
                onChoose(event) {
                    event.item.classList.remove('zoomIn');
                },
                onEnd(event) {
                    let $item = $(event.item),
                        $widget = $item.find('.widget-wrapper'),
                        widget_id = $widget.data('widget-id');

                    if (widget_id) {
                        that.saveWidget({
                            widget_id,
                            widget_sort: 0,
                            group_index: event.newIndex,
                            is_new: true
                        });
                    }
                }
            });
        }

        setGroupSortable(node) {
            // Запрещаем сортировку, если в группе есть виджеты 2x1 и несколько 1x1 (иначе имеем баг с позиционированием)
            let children = Array.from(node.childNodes),
                small_widget = children.filter(el => el.classList.contains('widget-1x1')).length,
                middle_widget = children.filter(el => el.classList.contains('widget-2x1')).length;
            Sortable.get(node).options.sort = !(middle_widget > 1 || (middle_widget && small_widget > 1));
        }

        saveWidget(options) {
            let $deferred = $.Deferred(),
                href = "?module=dashboard&action=widgetMove&id=" + options.widget_id,
                dataArray = {
                    block: options.group_index,
                    sort: options.widget_sort
                };

            if (options.is_new) {
                dataArray.new_block = 1;
            }

            $.post(href, dataArray, function (request) {
                $deferred.resolve(request);
            }, "json");
        }

        sortingState(state) {
            let _state = state === 'disable' || false;
            this.$root_widget_groups.each(function () {
                Sortable.get(this)?.option('disabled', _state);
                let nested = Array.from(this.children).filter(el => !el.firstElementChild?.classList.contains('widget-2x2'))
                nested.forEach(function(el){
                    let _sortable = Sortable.get(el)
                    if(_sortable) {
                        _sortable.option('disabled', _state);
                    }
                })
            })
        }
    }
})(jQuery);

const Dashboard = ( function($) {
    return class Dashboard {

        // Проверка на пустую группу
        static checkEmptyGroup( $currentGroup, $groups, is_new_widget ) {
            let is_empty = !( $currentGroup.find(".widget-wrapper").length ),
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
                this.lockWidgetsToggle();

                // Delete Group
                this.deleteEmptyGroup( $currentGroup, ( animation_time - 1 ) );
            }

            return result_time;
        }

        // Удаляем пустую группу
        static deleteEmptyGroup( $group, time ) {
            let that = this
            if (time > 0) {
                $group.addClass("is-removed");
            }

            setTimeout( function () {
                // Remove Lock
                that.lockWidgetsToggle();

                // Remove Group
                $group.remove();
            }, time );
        }

        // Получаем данные виджетов в группе
        static getGroupData($group) {
            let $widgets_in_group = $group.find(".widget-wrapper"),
                groupData = {
                    group_index: parseInt( $group.index() ),
                    group_area: 0,
                    widgetsArray: []
                };

            $widgets_in_group.each( function(index) {
                let widget_id = $(this).data("widget-id"),
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
        }

        // Блокировка области виджетов, чтобы пользователь не делал много запросов одновременно (параллельно)
        static lockWidgetsToggle() {
            let $wrapper = $("#wa_widgets"),
                activeClass = "is-locked";

            $wrapper.toggleClass(activeClass);
        }

        // Проверка на наличии любых виджетов
        static checkEmptyWidgets() {
            let that = this,
                result = false;

            for (let widget_id in DashboardWidgets) {
                if (DashboardWidgets.hasOwnProperty(widget_id)) {
                    result = true;
                    break;
                }
            }

            that.toggleEmptyWidgetNotice(result);
        }

        // Показ/скрытие сообщения о пустом дэшборде
        static toggleEmptyWidgetNotice(hide) {
            let $wrapper = $("#empty-widgets-wrapper"),
                activeClass = "is-shown";

            if (hide) {
                $wrapper.removeClass(activeClass);
            } else {
                $wrapper.addClass(activeClass);
            }
        }

        // Определяем СпроллТоп
        static getScrollTop() {
            return $(window)['scrollTop']();
        }

        static isEditMode() {
            let $pageWrapper = $("#wa_widgets"),
                activeClass = "is-editable-mode";
            return $pageWrapper.hasClass(activeClass);
        }

        static getDashboardList() {
            return ( $(".js-dashboards-list") || false )
        }

        // @deprecated
        static _getDashboardID() {
            let dashboard_id = localStorage.getItem('dashboard_id'),
                value = (dashboard_id) ? dashboard_id : false;

            // If Default Dashboard
            if (value == 0) {
                value = false
            }

            return value;
        }

        static getDashboardID() {
            let $dashboard_list = $('.js-dashboards-list'),
                id;

            if ($dashboard_list.length) {
                id = $dashboard_list.find('.selected > a').data('dashboard')
            }else{
                id = localStorage.getItem('dashboard_id')
            }

            // If Default Dashboard
            if (id == 0 || id === undefined) {
                return false
            }

            return id;
        }
    }
})(jQuery);

const Group = ( function($, backend_url) {
    return class Group {
        constructor() {
            let that = this
            $(document).ready(function () {
                that.storage = {
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
                        return $(document).find("#wa_widgets");
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
                        return $(".widgets-list-wrapper");
                    }
                    //getDropOrnament: function() {
                    //    return $("#d-drop-ornament");
                    //}
                };

                this.nested_selector = '.js-nested-sortable';
                this.root_selector = '#d-widgets-block';
                this.$grouped_widgets = $(this.nested_selector);
                this.$root_widget_groups = $(this.root_selector);

                // Main Initialize

                that.bindEvents();

            });
        }

        bindEvents() {
            let that = this,
                $groups_wrapper = that.storage.getGroupsWrapper(),
                $widgetList = that.storage.getListWrapper();

            $widgetList.on("change", "#widgets-list-filter", function() {
                that.onSortWidgetList( $(this) );
            });

            $widgetList.on("click", ".widget-item-wrapper .add-widget-link", function(event) {
                if(!that.is_widget_list_page) {
                    that.onWidgetListClick(event, $(this));
                }
            });

            /*$widgetList.on("dragstart", ".widget-item-wrapper .image-block"", function(event) {
                that.onWidgetListDragStart(event, $(this));
            });

            $widgetList.on("dragend", ".widget-item-wrapper .image-block", function() {
                that.onWidgetListDragEnd($(this));
            });

            $groups_wrapper.on("dragstart", ".widget-group-wrapper .widget-draggable-block", function(event) {
                that.onDragStart(event, $(this));
            });

            $groups_wrapper.on("dragend", ".widget-group-wrapper .widget-draggable-block", function() {
                that.onDragEnd($(this));
            });

            // Hack, иначе Drop не будет срабатывать
            $groups_wrapper.on("dragover", ".widget-group-wrapper", function(event) {
                that.prepareDragOver(event, $(this));
                event.preventDefault();
            });

            // Custom Dashboard, подцветка при наведении на пустую группу
            $groups_wrapper.on("dragover", ".ornament-widget-group", function(event) {
                that.onEmptyGroupOver(event, $(this), $groups_wrapper );
                event.preventDefault();
            });

            // Навели на группу
            $groups_wrapper.on("dragenter", ".widget-group-wrapper", function() {
                that.onDragEnter( $(this) );
            });

            $groups_wrapper.on("drop", ".widget-group-wrapper", function(event) {
                if (that.storage.draggedWidget) {
                    that.prepareDrop(event, $(this));

                } else if (that.storage.newDraggedWidget) {
                    that.onWidgetListDrop(event, $(this));
                }
                event.preventDefault();
            });

            // Custom Dashboard
            $groups_wrapper.on("drop", ".ornament-widget-group", function(event) {
                that.onEmptyGroupDrop(event);
                event.preventDefault();
            });*/
        }

        // Group Functions

        onDragEnter($group) {

        }

        onDragStart(event, $target) {
            let that = this,
                $dragged_widget_wrapper = $target.closest(".widget-wrapper"),
                dragged_widget_id = $dragged_widget_wrapper.data("widget-id");

            // Add class
            $dragged_widget_wrapper.addClass(that.storage.isDraggedClass);

            // Save widget to storage
            that.storage.draggedWidget = (typeof DashboardWidgets[dragged_widget_id] !== "undefined") ? DashboardWidgets[dragged_widget_id] : false;
            that.storage.$widget_group = $dragged_widget_wrapper.closest(".widget-group-wrapper");

            // Hack. In FF D&D doesn't work without dataTransfer
            event.originalEvent.dataTransfer.setData("text/html", "<div class=\"anything\"></div>");

            let $ornament = $dragged_widget_wrapper.find(".widget-draggable-ornament"),
                ornament_width = parseInt($ornament.width()/2),
                ornament_height = parseInt($ornament.height()/2);

            event.dataTransfer.setDragImage(
                $ornament[0],
                ornament_width,
                ornament_height
            )
        }

        prepareDragOver(event, $group) {
            let that = this,
                time = 150;
            // Flag
            if (that.storage.doDragOver) {
                that.storage.doDragOver = false;
                setTimeout( function() {
                    that.storage.doDragOver = true;
                }, time);

                that.onDragOver(event, $group);
            }
        }

        onDragOver(event, $group) {
            let that = this,
                draggedWidget = ( that.storage.newDraggedWidget || that.storage.draggedWidget );

            if (!draggedWidget) {
                return false;
            }

            let groupData = Dashboard.getGroupData($group),
                dragged_widget_area = ( parseInt(draggedWidget.widget_size.width) * parseInt(draggedWidget.widget_size.height) ),
                is_group_locked = ( ( dragged_widget_area + groupData.group_area ) > that.storage.max_group_area ),
                $hover_group = that.storage.$hover_group,
                activeClass = that.storage.showGroupOrnamentClass,
                group_offset = $group.offset(),
                mouse_offset = {
                    left: event.pageX,
                    top: event.pageY
                },
                border_width = 10,
                delta = Math.abs(parseInt(group_offset.left - mouse_offset.left));

            // Remove classes from old group
            if ($hover_group && $hover_group.length) {
                $hover_group.removeClass(activeClass);
                $hover_group.removeClass(that.storage.hoverGroupClass);
                $hover_group.removeClass(that.storage.lockedGroupClass);
                that.storage.$hover_group = false;
            }

            // Marking new group
            if (delta < border_width) {

                that.markingGroup("border-hover", $group);

                that.clearWidgetOrnament();

            } else {

                if ( !$group.hasClass(that.storage.hoverGroupClass) ) {

                    // Lock
                    if (is_group_locked) {
                        $group.addClass(that.storage.lockedGroupClass);
                    }

                    that.markingGroup("hover", $group);
                }

                if (!is_group_locked) {
                    that.renderDropArea(event, $group);
                }
            }
        }

        onDragEnd() {
            // Clear
            let that = this;
            that.clearGroupMarkers();
        }

        prepareDrop(event, $group) {
            let that = this,
                $target_group = $group,
                border_space = that.storage.border_space,
                group_offset = $group.offset(),
                is_new_widget = ( that.storage.target_group_offset ),
                mouse_offset = {
                    left: parseInt(event.pageX),
                    top: parseInt(event.pageY)
                },
                drop_delta;

            // If Adding new widget from widget list
            if (is_new_widget) {
                group_offset = that.storage.target_group_offset;
                that.storage.target_group_offset = false;
            }

            drop_delta = {
                left: mouse_offset.left - group_offset.left,
                top: mouse_offset.top - group_offset.top
            };

            if (drop_delta.left <= border_space) {

                //console.log("Новый виджет", is_new_widget);

                // Отменяем перемещение если дропаем на полоску новой группы справа или слева от таргета.
                if (!is_new_widget) {

                    let $widget_group = that.storage.$widget_group,
                        widget_count = $widget_group.find(".widget-wrapper").length,
                        widget_group_index = $widget_group.index(),
                        target_group_index = $group.index();

                    let is_solo_widget = (widget_count === 1),
                        is_before_group = (target_group_index === widget_group_index),
                        is_next_group = (target_group_index - 1 === widget_group_index);

                    //console.log(that.storage.draggedWidget, is_solo_widget, target_group_index, widget_group_index, is_next_group);

                    if ( is_solo_widget && ( is_before_group || is_next_group ) ) {
                        return false;
                    }
                }

                // Добавляем новую группу
                $target_group = that.addNewGroup($group);
            }

            //
            that.onDrop(event, $target_group);
        }

        onDrop(event, $group) {
            let that = this,
                is_available = that.checkAvailability($group);

            if (is_available) {

                let dropArea = that.getDropArea(event, $group);
                if (dropArea.side) {
                    that.renderWidget($group, dropArea);
                }

            }
        }

        getSegment(event, $target) {
            let target_offset = $target.offset(),
                mouse_offset = {
                    left: parseInt(event.pageX),
                    top: parseInt(event.pageY)
                },
                left_percent,
                top_percent,
                segment,
                left,
                top,
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
        }

        getDropArea(event, $group) {
            let that = this,
                $target = $(event.target),
                draggedWidget = ( that.storage.newDraggedWidget || that.storage.draggedWidget ),
                is_widget = ( $target.closest(".widget-wrapper").length ),
                group_segment = that.getSegment(event, $group),
                groupData = Dashboard.getGroupData($group),
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
                widget_segment = that.getSegment(event, $widget);
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
            let moving_inside_bloc = ( draggedWidget.widget_group_index === groupData.group_index ),
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
        }

        checkAvailability($group) {
            let that = this,
                draggedWidget = that.storage.draggedWidget,
                current_widget_area = 0,
                new_group_area = 0,
                groupData = Dashboard.getGroupData( $group );

            // Check for movement inside group
            let drop_group_index = $group.index(),
                widget_group_index = draggedWidget.$widget_wrapper.closest(".widget-group-wrapper").index();

            if (drop_group_index === widget_group_index) {
                that.storage.is_new_group = false;
                return true;
            }

            // Current Widget Area
            if (draggedWidget) {
                current_widget_area = ( parseInt(draggedWidget.widget_size.width) * parseInt(draggedWidget.widget_size.height) );
                new_group_area = current_widget_area + groupData.group_area;

                return (new_group_area <= that.storage.max_group_area);
            }
        }

        renderWidget($group, drop_place) {
            let that = this,
                widget = that.storage.draggedWidget,
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
            that.prepareSaveWidget($group);
        }

        prepareSaveWidget($group) {
            let that = this,
                widget = that.storage.draggedWidget,
                $before_widget_group = that.storage.$widget_group,
                new_widget_group_index = parseInt($group.index()),
                new_widget_sort = parseInt(widget.$widget_wrapper.index()),
                is_changed = !( ( new_widget_sort === widget.widget_sort ) && (new_widget_group_index === that.storage.$widget_group.index() ) ),
                is_last_group = that.storage.getLastGroupIndex(),
                is_new_group = ( $group.find(".widget-wrapper").length < 2 ),
                create_new_group = ( is_last_group == new_widget_group_index );

            $.each([$group,$before_widget_group], function() {
                let $group = $(this);
                if ($group.length) {
                    $group.find(".widget-wrapper").each( function() {
                        let id = $(this).data("widget-id"),
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
                let is_new_widget = that.storage.is_new_widget,
                    animation_time = Dashboard.checkEmptyGroup($before_widget_group, that.storage.getWidgetGroups(), is_new_widget);
                that.storage.is_new_widget = false;

                // Создаём новый блок для перемещений
                if (create_new_group) {
                    that.addNewGroup();
                }

                // Сохраняем виджет, после того пустая группа удалиться
                setTimeout( function() {
                    // Переопределяем индекс блока, на случай если он был удалён
                    new_widget_group_index = parseInt( $group.index() );

                    that.saveWidget({
                        widget: widget,
                        widget_id: widget.widget_id,
                        widget_sort: new_widget_sort,
                        group_index: new_widget_group_index,
                        is_new: is_new_group
                    });
                }, animation_time);

            }
        }

        saveWidget(options) {
            let that = this,
                widget = options.widget,
                $deferred = $.Deferred(),
                href = that.storage.getSaveHref(options.widget_id),
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
        }

        addNewGroup($group) {
            let that = this,
                $wrapper = that.storage.getGroupsBlock(),
                $new_group = that.storage.getNewGroupHTML();

            // Создаём группу перед группой
            if ($group && $group.length) {
                $group.before($new_group.addClass('zoomIn'));
                return $new_group;

                // Иначе создаём группу в конце
            } else {
                //$wrapper.append($new_group);

                // After last group
                let $lastGroup = $wrapper.find(".widget-group-wrapper").last();
                $lastGroup.after($new_group.addClass('zoomIn'));
            }

        }

        renderDropArea(event, $group) {
            let that = this,
                dropArea = that.getDropArea(event, $group),
                is_widget = ( dropArea.$widget && dropArea.$widget.length ),
                positionClass = "",
                positionClasses = [
                    "segment-1",
                    "segment-2",
                    "segment-3",
                    "segment-4"
                ],
                $ornament;

            that.clearWidgetOrnament();

            // Показывает подцветку на виджете
            if (is_widget) {
                $ornament = dropArea.$widget.find(".widget-drop-ornament");

                positionClass = positionClasses[dropArea.widget_segment - 1];
                $ornament.addClass(positionClass);

                $ornament.addClass(that.storage.showClass);

                that.storage.$activeWidgetOrnament = $ornament;

                if (dropArea.side) {
                    $ornament.addClass(that.storage.activeClass);
                } else {
                    $ornament.addClass(that.storage.lockedClass);
                }

            } else {
                //$ornament = that.storage.getDropOrnament(),
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
        }

        // Widget List Functions

        onWidgetListClick(event, $target) {
            let that = this;
            that.prepareToDropNewWidget(event, $target);

            that.onWidgetListDrop(event);
        }

        onSortWidgetList( $select ) {
            let that = this,
                value = $select.val(),
                $groups = that.storage.getListWrapper().find(".list-group-wrapper"),
                $targetGroup = that.storage.getListWrapper().find("." + value + "-list-group");

            if (value) {
                // Hide all
                $groups.hide();

                // Show current
                $targetGroup.show();
            } else {
                $groups.show();
            }
        }

        onWidgetListDragStart(event, $widgetImage) {
            let that = this
            that.prepareToDropNewWidget(event, $widgetImage);
            // Hack. In FF D&D doesn't work without dataTransfer
            //event.originalEvent.dataTransfer.setData("text/html", "<div class=\"anything\"></div>");
        }

        onWidgetListDragEnd() {
            let that = this
            // Clear
            that.clearGroupMarkers();
        }

        clearGroupMarkers() {
            // Clear Group
            let that = this,
                $hoverGroup = that.storage.$hover_group;
            if ($hoverGroup && $hoverGroup.length) {
                $hoverGroup.removeClass(that.storage.hoverGroupClass);
                $hoverGroup.removeClass(that.storage.lockedGroupClass);
                $hoverGroup.removeClass(that.storage.showGroupOrnamentClass);
                that.storage.$hover_group = false;
                that.storage.$widget_group = false;
            }

            that.clearWidgetOrnament();

            // Clear Widget
            let draggedWidget = that.storage.draggedWidget;
            if (draggedWidget) {
                draggedWidget.$widget_wrapper.removeClass(that.storage.isDraggedClass);
                that.storage.draggedWidget = false;
            }

            let newWidget = that.storage.newDraggedWidget;
            if (newWidget) {
                that.storage.newDraggedWidget = false;
            }
        }

        clearWidgetOrnament() {
            let that = this,
                $ornament = (that.storage.$activeWidgetOrnament || false),
                positionClasses = [
                    "segment-1",
                    "segment-2",
                    "segment-3",
                    "segment-4",
                    that.storage.activeClass,
                    that.storage.lockedClass,
                    that.storage.showClass
                ];

            if ($ornament && $ornament.length) {

                $.each(positionClasses, function() {
                    $ornament.removeClass("" + this);
                });

                that.storage.$activeWidgetOrnament = false;
            }
        }

        prepareToDropNewWidget(event, $widgetImage) {
            let that = this,
                $newWidget = $widgetImage.closest(".widget-item-wrapper"),
                widget_size_data = $newWidget.data("size").split("x"),
                backend_url = that.storage.getListWrapper().data("backend-url"),
                app_id = $newWidget.data("app_id");

            that.storage.newDraggedWidget = {
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
        }

        onWidgetListDrop(event, $group) {
            let that = this,
                draggedWidget = that.storage.newDraggedWidget;

            // Cancel drop on clocked Group
            if ($group && $group.length) {
                if ($group.hasClass(that.storage.lockedGroupClass)) {
                    return false;
                }

                let dropArea = that.getDropArea(event, $group);
                if (!dropArea.side) {
                    return false;
                }
            }

            // Cancel drop if wlist is locked
            if (that.storage.is_widget_list_locked) {
                return false;
            } else {
                that.storage.is_widget_list_locked = true;
            }

            let $deferred = that.addNewWidget();

            $deferred.done( function(response) {

                that.storage.is_widget_list_locked = false;

                if (response.status === "ok") {
                    let widget_id = response.data.id,
                        is_drop_on_group = ($group && $group.length);

                    // Set Data
                    draggedWidget['widget_id'] = widget_id;
                    draggedWidget['widget_href'] = draggedWidget['widget_href'] + "" + widget_id;

                    // Need for Group replace
                    if (is_drop_on_group) {
                        that.storage.target_group_offset = $group.offset();
                    }

                    // Render HTML
                    let $widget = $(response.data.html),
                        $new_group = that.addNewGroup( that.storage.getWidgetGroups().eq(0) );

                    $new_group.append($widget);

                    // Init new Widget
                    DashboardWidgets[widget_id] = new DashboardWidget(draggedWidget);

                    // Replace Widget in Target Group
                    if (is_drop_on_group) {
                        that.storage.draggedWidget = DashboardWidgets[widget_id];
                        that.storage.$widget_group = $new_group;

                        that.replaceWidgetAfterCreate(event, $group);
                    }

                }
            });
        }

        replaceWidgetAfterCreate(event, $group) {
            let that = this
            that.storage.is_new_widget = true;

            that.prepareDrop(event, $group);

            that.storage.draggedWidget = false;

            $(document).trigger('wa_new_widget_create');
        }

        addNewWidget() {
            let that = this,
                $deferred = $.Deferred(),
                href = that.storage.getNewWidgetHref(),
                dataArray = {
                    app_id: that.storage.newDraggedWidget.widget_app_id,
                    widget: that.storage.newDraggedWidget.widget_name,
                    block: 0,
                    sort: 0,
                    size: that.storage.newDraggedWidget.widget_size.width + "x" + that.storage.newDraggedWidget.widget_size.height,
                    dashboard_id: Dashboard.getDashboardID(),
                    new_block: 1
                };

            $.post(href, dataArray, function(request) {
                $deferred.resolve(request);
            }, "json");

            return $deferred;
        }

        // For Custom Dashboard
        onEmptyGroupDrop(event) {
            let that = this,
                evt = event.originalEvent,
                index = event.newIndex,
                $group = that.storage.getWidgetGroups().eq(index);

            if (that.storage.draggedWidget) {
                that.prepareDrop(evt, $group);

            } else if (that.storage.newDraggedWidget) {
                that.onWidgetListDrop(evt, $group);
            }
        }

        onEmptyGroupOver(event, $emptyGroup, $wrapper) {
            let that = this,
                $lastGroupContainer = $wrapper.find(".widget-group-wrapper").last();

            that.markingGroup("hover", $lastGroupContainer);
        }

        markingGroup( type, $group, callback ) {
            let that = this,
                hoverClass = that.storage.hoverGroupClass,
                borderHoverClass = that.storage.showGroupOrnamentClass,
                activeClass;

            if (type == "hover") {
                activeClass = hoverClass;

                if (!$group.hasClass(activeClass)) {
                    $group.addClass(activeClass);
                    that.storage.$hover_group = $group.addClass(activeClass);
                }
            }

            if (type == "border-hover") {
                activeClass = borderHoverClass;

                if (!$group.hasClass(activeClass)) {
                    $group.addClass(activeClass);
                    that.storage.$hover_group = $group.addClass(activeClass);
                }
            }

            if (callback && typeof callback == "function") {
                callback();
            }
        }
    }
})(jQuery, backend_url);

const Page = ( function($, backend_url) {
    return class Page {

        constructor() {
            this.storage = {
                activeLighterClass: "is-highlighted",
                dashboardEditableClass: "is-editable-mode",
                dashboardCustomEditClass: "is-custom-edit-mode",
                dashboardTvClass: "tv",
                activeEditModeClass: "is-active",
                isLoadingClass: "is-loading",
                hiddenClass: "hidden",
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
                    return $("#wa_widgets");
                },
                getGroupsWrapper: function() {
                    return $("#wa_widgets");
                },
                getShowButton: function() {
                    return $(".js-dashboard-edit");
                },
                getHideButton: function() {
                    return $(".js-dashboard-edit-close");
                },
                getWidgetList: function() {
                    return $(".widgets-list-wrapper")
                },
                getWidgetActivity: function() {
                    return $("#wa_activity");
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
                    return $(".js-dashboards-list");
                },
                getNewDashboard: function() {
                    return $(".js-new-dashboard");
                }
            };

            this.sortable = {};

            this.$sortable_grouped_widgets = {};
            this.$sortable_root_widget_groups = {};

            this.new_dashboard_dialog = $("#dashboard-editor-dialog") || false
            // todo @deprecated
            /*const initialize = function() {
                // Init Select
                initDashboardSelect();
            };*/

            this.bindEvents();
            //
            this.showFirstNotice();

        }

        bindEvents() {
            let that = this,
                $showLink = that.storage.getShowButton(),
                $hideLink = that.storage.getHideButton(),
                $widgetActivity = that.storage.getWidgetActivity(),
                $delete_dashboard = $('.js-dashboard-delete'),
                $edit_dashboard = $('.js-dashboard-edit'),
                $new_dashboard = that.storage.getNewDashboard(),
                $closeNoticeLink = that.storage.getFirstNoticeWrapper().find(".close-notice-link");

            // add new dashboard
            $new_dashboard.on('click', function (e) {
                e.preventDefault()
                if (!that.new_dashboard_dialog.length) {
                    that.new_dashboard_dialog = $("#dashboard-editor-dialog")
                }
                that.createNewDashboard();
            });

            // delete dashboard
            $delete_dashboard.on('click', function (e) {
                e.preventDefault();
                let id = $(this).parent('a').data("dashboard")
                let $wrapper = $('#dashboard-delete-dialog')
                $.waDialog({
                    $wrapper,
                    onOpen: function ($dialog) {
                        let $submit = $dialog.find('[type="submit"]')
                        $submit.on('click', function (e) {
                            that.deleteCustomDashboard(id);
                        });
                    }
                });
            });

            // edit dashboard
            $edit_dashboard.on('click', function (e) {
                e.preventDefault()
                that.onShowLinkClick();
            });

            // $dashboardList.on("click", 'a', function() {
            //     let $link = $(this),
            //         $li = $link.parent(),
            //         dashboard = $link.data('dashboard');
            //
            //     localStorage.setItem('dashboard_id', dashboard);
            //
            //     $li.toggleClass('selected').siblings().removeClass('selected');
            //
            //     that.changeDashboard( $(this) );
            // });

            $closeNoticeLink.on("click", function(e) {
                e.preventDefault()
                that.hideFirstNotice();
            });

            $hideLink.on("click", function(e) {
                e.preventDefault()
                $showLink.show();
                $hideLink.hide();
                that.hideEditMode();
            });

            $widgetActivity.on("click", "#d-load-more-activity", function () {
                that.loadOldActivityContent( $(this), $widgetActivity );
                return false;
            });

            $("#activity-filter input:checkbox").on("change", function() {
                if (that.storage.activityFilterTimer) {
                    clearTimeout(that.storage.activityFilterTimer);
                }
                if (that.storage.topLazyLoadingTimer) {
                    clearTimeout(that.storage.topLazyLoadingTimer);
                }

                that.showLoadingAnimation($widgetActivity);

                that.storage.activityFilterTimer = setTimeout( function() {
                    that.showFilteredData( $widgetActivity );
                }, 2000);

                that.storage.topLazyLoadingTimer = setTimeout( function() {
                    that.loadNewActivityContent($widgetActivity);
                }, that.storage.lazyTime );

                // Change Text
                that.changeFilterText();

                return false;
            });


            // Escape close edit-mode
            $(document).on("keyup", function(event) {
                let is_dialog_shown = that.storage.is_dialog_shown;
                if ( !is_dialog_shown ) {
                    if (event.keyCode == 27 && that.storage.isEditModeActive) {
                        $hideLink.trigger("click");
                    }
                } else {
                    that.storage.is_dialog_shown = false;
                }
            });

            $(window).on("scroll", function(event) {
                that.onPageScroll(event);
            });

            if (!that.storage.is_custom_dashboard) {
                that.storage.topLazyLoadingTimer = setTimeout(function () {
                    that.loadNewActivityContent($widgetActivity);
                }, that.storage.lazyTime);
            }

            // Development helper: Ctrl + Alt + dblclick on a widget reloads the widget
            let $widgets_block = $("#d-widgets-block");
            $widgets_block.on("dblclick", ".widget-wrapper", function(e) {
                if (e.altKey && (e.ctrlKey || e.metaKey)) {
                    let id = $(this).data("widget-id");
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
            $widgets_block.on("click", ".widget-wrapper", function(e) {
                if (e.altKey && (e.ctrlKey || e.metaKey)) {
                    return false;
                }
            });

            //$showLink.click();
            //$("body").addClass(that.storage.dashboardCustomEditClass);

            that.storage.getPageWrapper().on("click", ".d-delete-dashboard-wrapper a", function() {
                let $link = $(this),
                    do_delete = confirm( $link.data("confirm-text") );

                if (do_delete) {
                    //that.deleteCustomDashboard( $link.data("dashboard-id") );
                }
                return false;
            });
        }

        onShowLinkClick() {
            let that = this,
                $showLink = that.storage.getShowButton(),
                $hideLink = that.storage.getHideButton(),
                $firstNotice = that.storage.getFirstNoticeWrapper(),
                notice_is_shown = ( $firstNotice.css("display") !== "none" );

            // Change Buttons
            $showLink.hide();
            $hideLink.show();

            // Hide First Notice
            if (notice_is_shown) {
                $firstNotice.find(".close-notice-link").trigger("click");
            }

            // Define Sortable variable if it not yet
            if(Object.getOwnPropertyNames(that.sortable).length === 0) {
                that.sortable = new WidgetSort();
            }

            that.$sortable_grouped_widgets = that.sortable.$grouped_widgets;
            that.$sortable_root_widget_groups = that.sortable.$root_widget_groups;

            // Включаем сортировку
            that.sortable.sortingState('enable');

            that.showEditMode();
            //
            that.showWidgetList();
        }

        showFirstNotice() {
            let that = this,
                $wrapper = that.storage.getFirstNoticeWrapper(),
                $activity = that.storage.getWidgetActivity(),
                showNotice = $wrapper.data("show-notice"),
                $notifications = that.storage.getNotifications();

            if (showNotice) {
                $activity.addClass(that.storage.hiddenClass);
                $notifications.addClass(that.storage.hiddenClass);
                $wrapper.show();
            }
        }

        hideFirstNotice() {
            let that = this,
                $wrapper = that.storage.getFirstNoticeWrapper(),
                $activity = that.storage.getWidgetActivity(),
                $notifications = that.storage.getNotifications();

            // hide DOM
            $wrapper.hide();

            $activity
                .removeClass(that.storage.hiddenClass)
                .addClass(that.storage.animateClass);

            $notifications
                .removeClass(that.storage.hiddenClass)
                .addClass(that.storage.animateClass);

            setTimeout( function() {
                $activity.addClass(that.storage.showClass);
                $notifications.addClass(that.storage.showClass);
            }, 4);

            // set data
            $.post(that.storage.getCloseTutorialHref(), {});
        }

        changeFilterText() {
            let $filterText = $("#activity-select-text"),
                text = $filterText.data("text"),
                $form = $("#activity-filter"),
                check_count = 0,
                full_checked = true;

            $form.find("input:checkbox").each( function() {
                let $input = $(this),
                    is_checked = ( $input.attr("checked") == "checked" );

                if (!is_checked) {
                    full_checked = false;
                } else {
                    check_count++;
                }
            });

            if (full_checked) {
                $filterText.text(text);
            } else {
                text += " (" + check_count + ")";
                $filterText.text(text);
            }
        }

        onPageScroll() {
            let that = this,
                scrollTop = Dashboard.getScrollTop(),
                is_edit_mode = Dashboard.isEditMode(),
                $activityBlock = $("#wa_activity, .js-dashboard-activity"),
                activity_height = $activityBlock.outerHeight(),
                $window = $(window),
                displayArea = {
                    width: $window.width(),
                    height: $window.height()
                },
                $widgets_wrapper = $('.js-dashboard-widgets');

            const stickyWidgetContent = function() {
                let $widgets_wrapper = $('.js-dashboard-widgets'),
                    $widgets_block = $widgets_wrapper.find('.d-widgets-block'),
                    content_top_offset = $widgets_wrapper.offset().top || 0,
                    top = 0;

                $(window).on('resize', function () {
                    let widgets_wrapper_height = $widgets_block.outerHeight(),
                        vh = window.innerHeight;

                    if (vh - widgets_wrapper_height >= content_top_offset ) {
                        top = `${content_top_offset}px`
                    }else{
                        top = `${vh - widgets_wrapper_height}px`
                    }

                    $widgets_block.css({
                        position: 'sticky',
                        top: top
                    })
                }).resize();
            };
            if ($widgets_wrapper.length) {
                stickyWidgetContent();
            }

            //
            that.initActivityLazyLoading({
                scrollTop: scrollTop,
                displayArea: displayArea,
                is_edit_mode: is_edit_mode,
                $activityBlock: $activityBlock,
                activity_height: activity_height
            });
        }

        showFilteredData( $widgetActivity) {
            let that = this;
            if (!that.storage.isActivityFilterLocked) {
                that.storage.isActivityFilterLocked = true;

                let $wrapper = $widgetActivity.find(".activity-list-block"),
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
                    let html = "<div class=\"empty-activity-text\">" + $wrapper.data("empty-text") + "</div>";
                    if ( $.trim(response).length ) {
                        html = response;
                    }
                    $wrapper.html(html);

                    that.hideLoadingAnimation($widgetActivity);

                    that.storage.isActivityFilterLocked = false;
                });
            }
        }

        showEditMode() {
            let that = this,
                $dashboard = that.storage.getPageWrapper();

            $dashboard.addClass(that.storage.dashboardEditableClass);

            that.toggleHighlighterGroups();

            that.storage.isEditModeActive = true;
        }

        hideEditMode() {
            let that = this,
                $dashboard = that.storage.getPageWrapper(),
                $settings = that.storage.getSettingsWrapper(),
                dashboard_id = Dashboard.getDashboardID(),
                is_custom_dashboard = ( dashboard_id !== "default_dashboard" && dashboard_id !== "new_dashboard" );

            // if we in Custom Dashboard
            if (!is_custom_dashboard) {
                that.reloadDashboard();

            } else {

                if ($settings.css("display") != "none") {
                    $settings.find(".hide-settings-link").trigger("click");
                }

                $dashboard.removeClass(that.storage.dashboardEditableClass);
                $('body').removeClass(that.storage.dashboardCustomEditClass);

                that.toggleHighlighterGroups();

                // Отключаем сортировку
                that.sortable.sortingState('disable');

                that.storage.isEditModeActive = false;
            }
        }

        toggleHighlighterGroups() {
            let that = this,
                $wrapper = that.storage.getGroupsWrapper(),
                activeClass = that.storage.activeLighterClass;

            // Groups
            $wrapper.toggleClass(activeClass);

            // Body
            $("body").toggleClass(activeClass);
        }

        showWidgetList() {
            let that = this,
                $widgetList = that.storage.getWidgetList(),
                $deferred = $.Deferred(),
                href = "?module=dashboard&action=sidebar",
                is_loaded = that.storage.isWidgetListLoaded;

            // Show block
            $("body").addClass(that.storage.dashboardCustomEditClass);

            // Render
            if (!is_loaded) {

                that.storage.isWidgetListLoaded = true;

                $.post(href, function(request) {
                    $deferred.resolve(request);
                });

                $deferred.done( function(response) {
                    // Adding html to wrapper
                    setTimeout(()=> {
                        $widgetList.empty().html(response);
                    }, 1000)
                });
            }

        }

        loadOldActivityContent($link, $widgetActivity) {
            let that = this;
            // Save data
            if (!that.storage.isBottomLazyLoadLocked) {
                that.storage.isBottomLazyLoadLocked = true;

                that.showLoadingAnimation($widgetActivity);

                let $linkWrapper = $link.closest(".show-more-activity-wrapper"),
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

                    if ( $.trim(response).length && !response.includes('activity-empty-today')) {
                        // Render
                        $wrapper.append(response);
                    }

                    that.storage.isBottomLazyLoadLocked = false;
                    that.storage.lazyLoadCounter++;

                    that.hideLoadingAnimation($widgetActivity);
                });
            }
        }

        loadNewActivityContent($widgetActivity) {
            let that = this;
            // Save data
            if (!that.storage.isTopLazyLoadLocked) {
                that.storage.isTopLazyLoadLocked = true;

                that.showLoadingAnimation($widgetActivity);

                let $wrapper = $widgetActivity.find(".activity-list-block"),
                    min_id = $wrapper.find(".activity-item:not(.activity-empty-today):first").data('id'),
                    $deferred = $.Deferred(),
                    ajaxHref = "?module=dashboard&action=activity",
                    dataArray = {
                        min_id: min_id
                    };

                $.post(ajaxHref, dataArray, function (response) {
                    $deferred.resolve(response);
                });

                $deferred.done( function(response) {
                    if ( $.trim(response).length && !response.includes('activity-empty-today')) {
                        // Render
                        $wrapper.find(".empty-activity-text").remove();
                        $wrapper.prepend(response);
                    }

                    that.storage.isTopLazyLoadLocked = false;

                    if (!that.storage.is_custom_dashboard) {
                        that.storage.topLazyLoadingTimer = setTimeout( function() {
                            that.loadNewActivityContent($widgetActivity);
                        }, that.storage.lazyTime );
                    }

                    that.hideLoadingAnimation($widgetActivity);
                });
            }
        };

        initActivityLazyLoading(options) {
            let that = this,
                scrollTop = options.scrollTop,
                displayArea = options.displayArea,
                is_edit_mode = options.is_edit_mode,
                $activityBlock = options.$activityBlock,
                activity_height = options.activity_height,
                $link = $("#d-load-more-activity"),
                lazyLoadCounter = that.storage.lazyLoadCounter,
                correction = 47;

            if ($link.length && !is_edit_mode) {
                let isScrollAtEnd = ( scrollTop >= ( $activityBlock.offset().top + activity_height - displayArea.height - correction ) );
                if (isScrollAtEnd) {
                    if (lazyLoadCounter >=2) {

                        let $wrapper = $link.closest(".show-more-activity-wrapper"),
                            loadingClass = "is-loading";

                        $wrapper.removeClass(loadingClass);

                    } else {

                        // Trigger event
                        $link.trigger("click");
                    }
                }
            }
        };

        showLoadingAnimation($widgetActivity) {
            $widgetActivity.find(".activity-filter-wrapper .loading").show();
        }

        hideLoadingAnimation($widgetActivity) {
            $widgetActivity.find(".activity-filter-wrapper .loading").hide();
        }

        // todo deprecated
        /*initDashboardSelect() {
            let that = this,
             $dashboardList = that.storage.getDashboardsList(),
                $select = that.getDashboardSelect(),
                default_value = $select.find("option").first().val();

            that.storage.dashboardSelectData.default = default_value;
            that.storage.dashboardSelectData.active = default_value;

            $select.val(default_value);

            $dashboardList.prepend($select);
        }*/

        changeDashboard() {
            let that = this,
                $body = $('body'),
                customEditClass = that.storage.dashboardCustomEditClass,
                tvClass = that.storage.dashboardTvClass,
                value = Dashboard.getDashboardID();

            if (value == "new_dashboard") {

                let $tabs = Dashboard.getDashboardList(),
                    last_active_val = that.storage.dashboardSelectData.active;

                // Set Select data
                $tabs.val(last_active_val);

                //that.createNewDashboard();

            } else if (value == "default_dashboard") {
                that.reloadDashboard();

            } else {
                // Set var (needed for intervals)
                that.storage.is_custom_dashboard = true;

                // Set custom ornament
                $body
                    .addClass(customEditClass)
                    .addClass(tvClass);

                // Load Widgets
                //that.initCustomDashboard(value);

                that.storage.dashboardSelectData.active = value;
            }
        }

        // initCustomDashboard( dashboard_id) {
        //     let that = this,
        //         $deferred = $.Deferred(),
        //         $dashboardArea = $("#d-widgets-block"),
        //         dashboard_href = "?module=dashboard&action=editPublic&dashboard_id=" + dashboard_id,
        //         dashboard_data = {};
        //
        //     $dashboardArea.html("");
        //
        //     $.post(dashboard_href, dashboard_data, function(response) {
        //         $deferred.resolve(response);
        //     });
        //
        //     $deferred.done( function(html) {
        //         $dashboardArea.html(html);
        //
        //         let $link = $dashboardArea.find(".d-dashboard-link-wrapper"),
        //             $deleteLink = $dashboardArea.find(".d-delete-dashboard-wrapper");
        //
        //         that.renderDashboardLinks( $link, $deleteLink );
        //     });
        //
        // }

        renderDashboardLinks( $link, $deleteLink ) {
            let that = this,
                $groupsWrapper = that.storage.getGroupsWrapper(),
                $linkWrapper = $("#d-dashboard-link-wrapper"),
                $linkHTML = $link.html();

            // Link
            $linkWrapper.html( $linkHTML );

            // Delete
            $("#d-delete-dashboard-wrapper").remove();
            $deleteLink.attr("id", "d-delete-dashboard-wrapper").slideDown(200);
            $groupsWrapper.after( $deleteLink );
        }

        createNewDashboard() {
            let that = this,
                $loading = '<i class="fas fa-spinner fa-spin"></i>';

            that.storage.is_dialog_shown = true;

            $.waDialog({
                $wrapper: that.new_dashboard_dialog,
                onOpen: function($dialog, dialog) {
                    let $input = $dialog.find("input:text:first"),
                        $form = $dialog.find('form');

                    $input.focus();

                    $form.on('submit', function (e) {
                        e.preventDefault()
                        let $deferred = $.Deferred(),
                        dashboard_url = $form.data('dashboard-url');
                        // Load
                        $form.find(':submit:first').parent().append($loading);

                        $.post( $form.attr('action'), $form.serialize(), function(response) {
                            $deferred.resolve(response);
                        }, 'json');

                        $deferred.done( function(response) {

                            // Remove Load
                            $form.find('.loading').remove();

                            if (response.status == 'ok') {
                                let id = response.data.id
                                localStorage.setItem('dashboard_id', id);
                                location.href = `${dashboard_url}${id}/`
                            } else {
                                alert(response.errors);
                            }
                            that.storage.is_dialog_shown = false;
                        });
                    })
                }
            });
        }

        reloadDashboard() {
            // Reload
            location.reload();
        }

        deleteCustomDashboard( dashboard_id ) {
            let $deferred = $.Deferred(),
                delete_href = "?module=dashboard&action=dashboardDelete",
                delete_data = {
                    id: dashboard_id
                };
            if (dashboard_id) {
                $.post(delete_href, delete_data, function(response) {
                    $deferred.resolve(response);
                }, "json");

                $deferred.done( function() {
                    location.href = backend_url;
                });
            }
        }
    }
})(jQuery, backend_url);

const DashboardWidget = ( function($) {
    return class DashboardWidget extends Dashboard {
        constructor(options) {
            super();

            let that = this;

            that.storage = {
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
                getWidgetWrapper: function () {
                    return $("#wa_widgets");
                },
                getControlsWrapper: function (that) {
                    return that.$widget_wrapper.find(".widget-controls-wrapper");
                },
                getResizeHref: function (widget_id) {
                    return "?module=dashboard&action=widgetResize&id=" + widget_id;
                },
                getDeleteHref: function () {
                    return "?module=dashboard&action=widgetDelete";
                },
                getSettingsHref: function (widget_id) {
                    return "?module=dashboard&action=widgetSettings&id=" + widget_id;
                },
                getPageWrapper: function () {
                    return $("#wa_widgets");
                },
                getSettingsWrapper: function () {
                    return $("#d-settings-wrapper");
                },
                getSettingsContainer: function () {
                    return $("#d-settings-container");
                },
                getSettingsBlock: function () {
                    return $("#d-settings-block");
                }
            }

            // Settings
            that.widget_id = (options.widget_id || false);
            that.widget_href = (options.widget_href || false);
            that.widget_sort = parseInt((options.widget_sort || false));
            that.widget_group_index = parseInt((options.widget_group_index || false));
            that.widget_size = {
                width: parseInt((options.widget_size.width || false)),
                height: parseInt((options.widget_size.height || false))
            };
            that.widget_size_class = false;

            // DOM
            that.$widget = $("#widget-" + that.widget_id);
            that.$widget_wrapper = $("#widget-wrapper-" + that.widget_id);

            // Functions
            that.renderWidget(true);

            // Functions
            that.widgetBindEvents();

            // Hide Notice after create
            Dashboard.toggleEmptyWidgetNotice(true);

            that.init()
        }

        init(){
            let that = this
            // Ивенты с Настройками Окошка виджетов
            $(document).ready(function () {
                let $settingWrapper = that.storage.getSettingsWrapper(),
                    $settingContainer = that.storage.getSettingsContainer(),
                    settings = that.storage.settingsWidget;

                $settingWrapper.on("click", function (event) {
                    event.preventDefault();
                    that.closeSettings(settings);
                });

                $settingContainer.on("click", function (event) {
                    event.stopPropagation();
                });

                $settingContainer.on("click", ".hide-settings-link", function () {
                    $settingWrapper.trigger("click");
                });

                $settingWrapper.on("submit", "form", function (event) {
                    that.onSaveSettings($(this));
                    event.preventDefault();
                });
            });
        }

        widgetBindEvents() {
            let that = this
            let $widgetControls = that.storage.getControlsWrapper(that);

            $widgetControls.on("click", ".control-item", function () {
                let $link = $(this),
                    $parent_group_wrapper = $link.closest('.widget-group-wrapper'),
                    activeClass = that.storage.activeControlClass,
                    is_click_myself = ($link.hasClass(activeClass));

                if (is_click_myself) {
                    return false;

                } else {

                    // Check and remove old active control
                    that.storage.getControlsWrapper(that).find("." + activeClass).removeClass(activeClass);

                    // Set active control
                    $link.addClass(activeClass);

                    let groupedWidgetsInstance = Sortable.get($parent_group_wrapper[0]);

                    if (!groupedWidgetsInstance && !$link.hasClass('set-big-size')) {
                        let options = {
                            group: 'nested',
                            handle: '.widget-wrapper',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            ghostClass: 'widget-ghost',
                            dragClass: 'widget-drag',
                            onEnd(event) {
                                // Удаляем пустую группу, когда из нее был перенесен последний виджет
                                if($(event.from).children().length === 0) {
                                    $(event.from).remove();
                                }
                            }
                        }
                        groupedWidgetsInstance = Sortable.create($parent_group_wrapper[0], options);
                    }else{
                        //groupedWidgetsInstance.destroy();
                    }

                    let $group = $(groupedWidgetsInstance.el);

                    if($link.hasClass('set-medium-size')) {
                        $(document).on('wa_widget_changed_type', function () {
                            if ($group.children('.widget-1x1').length > 1) {
                                //groupedWidgetsInstance.options.sort = false;
                                //$group.attr('data-sort-disabled', true);
                            } else {
                                //$group.removeAttr('data-sort-disabled');
                                //groupedWidgetsInstance.options.sort = true;
                            }
                        })
                        $parent_group_wrapper.addClass('js-nested-sortable');
                    }

                    if($link.hasClass('set-small-size')) {
                        $(document).on('wa_widget_changed_type', function () {
                            if ($group.children('.widget-2x1').length > 0) {
                                //groupedWidgetsInstance.options.sort = false;
                                //$group.attr('data-sort-disabled', true);
                            } else {
                                //$group.removeAttr('data-sort-disabled');
                                //groupedWidgetsInstance.options.sort = true;
                            }
                        })
                        $parent_group_wrapper.addClass('js-nested-sortable');
                    }

                    if($link.hasClass('set-big-size')) {
                        $parent_group_wrapper.removeClass('js-nested-sortable');
                    }
                }
            });

            $widgetControls.on("click", ".show-settings-link", function (e) {
                e.preventDefault();
                that.prepareShowWidgetSettings();
            });

            $widgetControls.on("click", ".set-small-size", function (e) {
                e.preventDefault();
                that.prepareChangeWidgetType({
                    width: 1,
                    height: 1
                });
            });

            $widgetControls.on("click", ".set-medium-size", function (e) {
                e.preventDefault();
                that.prepareChangeWidgetType({
                    width: 2,
                    height: 1
                });
            });

            $widgetControls.on("click", ".set-big-size", function (e) {
                e.preventDefault();
                that.prepareChangeWidgetType({
                    width: 2,
                    height: 2
                });
            });

            $widgetControls.on("click", ".delete-widget-link", function (e) {
                e.preventDefault();
                e.stopPropagation();
                // Hide button
                $(this).css("visibility", "hidden");
                // Delete
                that.deleteWidget();
            });

            //that.$widget_wrapper.on("mouseenter", function(e) {
            //    var is_edit_mode = Dashboard.isEditMode();
            //    if (is_edit_mode) {
            //        that.initControlsController();
            //    }
            //});
        }

        getWidgetSettings() {
            let that = this,
                $deferred = $.Deferred(),
                href = that.storage.getSettingsHref(that.widget_id);

            $.get(href, function (request) {
                $deferred.resolve(request);
            });

            return $deferred;
        }

        prepareShowWidgetSettings() {
            let that = this,
                $pageWrapper = that.storage.getPageWrapper(),
                is_edit_mode = ($pageWrapper.hasClass(that.storage.isEditableModeClass)),
                $widget = that.$widget_wrapper,
                is_controls_shown = ($widget.hasClass(that.storage.isControlsShownClass));

            if (is_edit_mode && !is_controls_shown) {
                that.showWidgetSettings();
            }
        }

        prepareChangeWidgetType(size) {
            let that = this,
                is_available = that.checkAvailability(size);

            if (is_available) {
                that.changeWidgetType(size);
            }
        }

        changeWidgetType(size) {
            let that = this,
                $deferred = $.Deferred(),
                widget_id = that.widget_id,
                href = that.storage.getResizeHref(widget_id),
                dataArray = {
                    size: size.width + "x" + size.height
                };

            // Ставим на блок
            that.lockToggle();

            $.post(href, dataArray, function (request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done(function (response) {
                if (response.status === "ok") {
                    // Save new size
                    that.widget_size = size;

                    // Render
                    that.renderWidget(true);
                }

                // Снимаем блок
                that.lockToggle();
                $(document).trigger('wa_widget_changed_type');
            })
        }

        checkAvailability(size) {
            // Current Widget Area
            let that = this,
                before_widget_area = that.widget_size.width * that.widget_size.height,
                after_widget_area = size.width * size.height,
                delta_area = after_widget_area - before_widget_area,
                result = false;

            if (delta_area > 0) {

                // Group Area
                let $group = that.$widget.closest(".widget-group-wrapper"),
                    groupData = Dashboard.getGroupData($group);

                // Ситуация 1х1, 1х1 => 2x1, 1х1
                if (groupData.widgetsArray.length === 3 && (parseInt(that.$widget_wrapper.index()) === 1) && (after_widget_area === 2)) {
                    //console.log("Ситуация 1х1, 1х1 => 2x1, 1х1");
                    return false;
                }
                result = ((groupData.group_area + delta_area) <= that.storage.maxGroupArea);
            }

            if (delta_area < 0) {
                result = true
            }

            return result;
        }

        setWidgetType() {
            let that = this,
                widget_width = that.widget_size.width,
                widget_height = that.widget_size.height,
                current_widget_type_class = that.widget_size_class;

            if (widget_width > 0 && widget_height > 0) {
                let widget_type_class = that.storage.widget_type[widget_width][widget_height];

                if (widget_type_class) {

                    // Remove Old Type
                    if (current_widget_type_class) {

                        // Если новый класс равен старому
                        if (current_widget_type_class && (current_widget_type_class == widget_type_class)) {
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

        lockToggle() {
            let that = this;
            that.storage.isLocked = !(that.storage.isLocked);
            Dashboard.lockWidgetsToggle();
        }

        onSaveSettings($form) {
            let that = this,
                $deferred = $.Deferred(),
                saveSettingsHref = "?module=dashboard&action=widgetSave&id=" + that.widget_id,
                dataArray = $form.serializeArray();

            $.post(saveSettingsHref, dataArray, function (request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done(function () {
                // Clear HTML
                that.$widget.html("");

                // Закрываем настройки
                that.closeSettings();

                setTimeout(function () {
                    // Перерисовываем виджет
                    that.renderWidget(true);
                }, that.storage.animateTime);

                //console.log("Успешно сохранен");
            });
        }

        renderSettings() {
            let that = this,
                $widget_block = that.$widget,
                $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                $settingsWrapper = that.storage.getSettingsWrapper(),
                $settingsContainer = that.storage.getSettingsContainer(),
                widgetOffset = $widget_block.offset(),
                scrollTop = Dashboard.getScrollTop(),
                //scrollLeft = 0,
                block_width = 450,
                animate_time = that.storage.animateTime,
                top_position,
                left_position;

            // Save link on widget
            that.storage.settingsWidget = that;

            // Display Area
            let windowArea = {
                width: $(window).width(),
                height: $(window).height()
            };

            // Set start widget position
            let widgetArea = {
                //top: widgetOffset.top,
                //left: widgetOffset.left,
                width: $widget_block.width(),
                height: $widget_block.height()
            };

            // Save start widget position
            that.storage.settingsStartPosition = widgetArea;

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
                //.height($(document).height())
                .show();

            setTimeout(function () {
                // Adding Animate Class
                $settingsContainer.addClass(that.storage.isAnimatedClass);
                $widget_container.addClass(that.storage.isAnimatedClass);
                $widget_wrapper.addClass(that.storage.isWidgetMoveClass);

                setTimeout(function () {
                    $settingsContainer.addClass(that.storage.isRotatedClass);

                    top_position = parseInt((windowArea.height - widgetArea.height) / 2);
                    top_position += scrollTop;

                    left_position = (parseInt(windowArea.width - block_width) / 2);

                    $widget_container.css({
                        top: top_position - widgetOffset.top,
                        left: left_position - widgetOffset.left,
                        width: block_width
                    }).hide();

                    $settingsContainer.css({
                        top: top_position,
                        left: left_position - widgetOffset.left,
                        width: block_width
                    });

                }, 4);

            }, 4);

            setTimeout(function () {
                $settingsContainer.addClass(that.storage.hasShadowClass);
            }, animate_time + 8);

        }

        liftSettings() {
            let that = this,
                $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                $settingsContainer = that.storage.getSettingsContainer(),
                startPosition = that.storage.settingsStartPosition,
                border_height = 10,
                settings_height = $("#d-settings-block").outerHeight() + border_height,
                scrollTop = Dashboard.getScrollTop();

            let lift = parseInt(($(window).height() - settings_height) / 2 + scrollTop);

            $settingsContainer.css({
                top: lift,
                height: settings_height
            });

            $widget_container.css({
                top: lift - startPosition.top,
                height: settings_height
            });
        }

        closeSettings() {
            let that = this,
                $settingsWrapper = that.storage.getSettingsWrapper(),
                $settingsContainer = that.storage.getSettingsContainer(),
                $settings = that.storage.getSettingsBlock(),
                $widget_wrapper = that.$widget_wrapper,
                $widget_container = $widget_wrapper.find(".widget-inner-container"),
                startPosition = that.storage.settingsStartPosition,
                animate_time = that.storage.animateTime;

            $settingsContainer.removeClass(that.storage.isRotatedClass);
            $settingsContainer.removeClass(that.storage.hasShadowClass);

            $widget_container.css({
                top: 0,
                left: 0,
                width: startPosition.width,
                height: startPosition.height
            }).show();

            if (startPosition) {
                $settingsContainer.css(startPosition);
            }

            setTimeout(function () {
                $settingsWrapper.hide();

                $settingsContainer
                    .removeClass(that.storage.isAnimatedClass)
                    .attr("style", "");

                $widget_container
                    .attr("style", "")
                    .removeClass(that.storage.isAnimatedClass);

                $widget_wrapper.removeClass(that.storage.isWidgetMoveClass);

                $settings.html("");

                that.storage.settingsStartPosition = false;
            }, animate_time);

            that.storage.settingsWidget = false;

            that.hideWidgetSettings();
        }

        renderWidget(force) {
            let that = this,
                widget_href = that.widget_href + "&id=" + that.widget_id + "&size=" + that.widget_size.width + "x" + that.widget_size.width,
                $widget = that.$widget;

            if ($widget.length) {

                // Проставляем класс (класс размера виджета)
                that.setWidgetType(that);

                // Загружаем контент
                $.ajax({
                    url: widget_href,
                    dataType: 'html',
                    global: false,
                    data: {}
                }).done(function (r) {
                    $widget.html(r);
                }).fail(function (xhr, text_status, error) {
                    if (xhr.responseText && xhr.responseText.indexOf) {
                        console.log('Error getting widget contents', text_status, error);
                        if (xhr.responseText.indexOf('waException') >= 0 || xhr.responseText.indexOf('id="Trace"') >= 0) {
                            $widget.html('<div style="font-size:40%;">' + xhr.responseText + '</div>');
                            return;
                        }
                    }
                    if (force) {
                        $widget.html("");
                    }
                });
            }
        }

        showWidgetSettings() {
            let that = this,
                $widget = that.$widget_wrapper

            let $deferred = that.getWidgetSettings();

            $deferred.done(function (response) {

                $.waDialog({
                    html: response,
                    onOpen($dialog, dialog_instance) {
                        let $form = $dialog.find('form')

                        $form.on('submit', function(){
                            let saveSettingsHref = "?module=dashboard&action=widgetSave&id=" + $widget.data('widget-id'),
                                dataArray = $form.serializeArray();

                            $.post(saveSettingsHref, dataArray, function (request) {
                                if(request.status === 'ok') {
                                    that.renderWidget(true);
                                }
                                dialog_instance.close();
                            }, "json");
                        })
                    }
                });
            });
        }

        hideWidgetSettings() {
            let that = this,
                $widget = that.$widget_wrapper,
                activeClass = that.storage.isControlsShownClass;

            $widget.removeClass(activeClass);
        }

        deleteWidget() {
            let that = this,
                $deferred = $.Deferred(),
                href = that.storage.getDeleteHref(),
                widget_id = that.widget_id,
                dataArray = {
                    id: widget_id
                };

            $.post(href, dataArray, function (request) {
                $deferred.resolve(request);
            }, "json");

            $deferred.done(function (response) {
                if (response.status === "ok") {
                    let $currentGroup = that.$widget_wrapper.closest(".widget-group-wrapper"),
                        $groups = that.storage.getWidgetWrapper().find(".widget-group-wrapper");

                    // Delete widget body
                    that.$widget_wrapper.remove();

                    // Delete Group if Empty
                    Dashboard.checkEmptyGroup($currentGroup, $groups);

                    // Delete JS
                    delete DashboardWidgets[widget_id];

                    // Check Empty Widgets and Show Notice
                    Dashboard.checkEmptyWidgets();
                }
            });
        }

        initControlsController() {
            let that = this,
                $widgetControls = that.$widget_wrapper.find(".size-controls-wrapper .control-item");

            $widgetControls.each(function () {
                let $control = $(this),
                    is_active = ($control.hasClass("is-active")),
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

                let is_available = that.checkAvailability(size);

                if (is_available || is_active) {
                    $control.show();
                } else {
                    $control.hide();
                }

            });
        }
    }
})(jQuery);