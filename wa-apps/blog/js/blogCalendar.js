(function($) {
    $.wa_blog.calendar = {
        options: {},
        init: function(options) {
            this.options = options || {};
            this._initDateSelecter();
            if (options.allow_add) {
                $(".b-calendar-table > div:not(.b-calendar-header)").on('click', function(event){
                    if (event.target.tagName == $(this).get(0).tagName ) {
                        window.location.href = "?module=post&action=edit&date="+$(this).attr("id").replace(/^date-/,'');
                    }
                });
                var title = this.options.td_title;
                $('.b-calendar-table > div:not(.b-calendar-header)').each(function(){
                    $(this).attr('title', title);
                });
            }
            this.initDragnDrop();
        },

        _initDateSelecter: function() {
            $('select.month,select.year').on('change', function() {
                var match = location.search.match(/[&\?](month=.*?&|month=.*)/),
                    month_value = $('select.month').val(),
                    year_value = $('select.year').val(),
                    month_date = year_value + '-' + (month_value > 10 ? month_value : '0'+month_value);
                if (match) {
                    var month = match[1],
                        new_month = month.substr(-1) == '&' ? 'month='+month_date+'&' : 'month='+month_date;
                    location.search = location.search.replace(month, new_month);
                } else {
                    location.search += (location.search ? '&' : '?') + 'month=' + month_date;
                }
            });
        },

        initDragnDrop: function() {
            var self = this;
            self._extendJqueryUIDragAndDrop();
            var items = [
                '.status-' + self.options.statuses.deadline,
                '.status-' + self.options.statuses.draft
            ];

            $(items.join(','), '.b-calendar-table > div:not(.b-calendar-header)').liveDraggable({
                appendTo: "body",
                containment: $('.b-calendar-table'),
                distance: 5,
                helper: 'clone'
            });
            $('.b-calendar-table > div:not(.b-calendar-header)').liveDroppable({
                greedy: true,
                tolerance: 'pointer',
                activeClass: 'drop-active',
                hoverClass: 'drag-active',
                drop: function(event, ui) {
                    if (ui.draggable.parent().get(0) != this) {
                        var cell = $(this),
                            item = ui.draggable,
                            item_clone = item.clone(),
                            datetime = cell.attr('id').replace('date-', '');

                        item.hide();
                        cell.append(item_clone);

                        $.post('?module=post&action=saveField', {
                                post_id: item.attr('data-post-id'),
                                data: {
                                    'datetime': datetime,
                                    'status': self.options.statuses.deadline
                                }
                            },
                            function(r) {
                                if (r && r.status == 'ok') {
                                    item.nextAll('br:lt(2)').remove().end().remove();
                                    // change icon
                                    item_clone.find('.fa-pen').
                                        removeClass('fa-pen text-gray').
                                        addClass('fas fa-exclamation-triangle text-orange').
                                        attr('title', $_('Overdue'));
                                    // change status
                                    item_clone.attr(
                                        item_clone.attr('class').replace(/status-\w+/, 'status-'+self.options.statuses.deadline)
                                    );
                                    // change font style
                                    if (r.data.post.overdue) {
                                        item_clone.find('a').removeClass('b-draft').addClass('b-draft-overdue');
                                    } else {
                                        item_clone.find('a').removeClass('b-draft-overdue').addClass('b-draft');
                                    }
                                } else {
                                    item.show();
                                    item_clone.remove();
                                }

                                self.initDragnDrop();
                            },
                            'json'
                        ).error(function() {
                            item.show();
                            item_clone.remove();
                        });
                    }
                }
            });
        },

        _extendJqueryUIDragAndDrop: function() {
            // live draggable and live droppable
            $.fn.liveDraggable = function (opts) {
                this.each(function(i,el) {
                    var self = $(this);
                    if (self.data('init_draggable')) {
                        self.off("mouseover", self.data('init_draggable'));
                    }
                });
                this.on("mouseover", function() {
                    var self = $(this);
                    if (!self.data("init_draggable")) {
                        self.data("init_draggable", arguments.callee).draggable(opts);
                    }
                });
            };

            $.fn.liveDroppable = function (opts) {
                this.each(function(i,el) {
                    var self = $(this);
                    if (self.data('init_droppable')) {
                        self.off("mouseover", self.data('init_droppable'));
                    }
                });

                var init = function() {
                    var self = $(this);
                    if (!self.data("init_droppable")) {
                        self.data("init_droppable", arguments.callee).droppable(opts);
                        self.mouseover();
                    }
                };
                init.call(this);
                this.off("mouseover", init).on("mouseover", init);
                this.on('mouseover', init);
            };
        }
    }
})(jQuery);
