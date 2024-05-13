(function($) {
    $.wa_blog.calendar = {
        options: {},
        init: function(options) {
            this.options = options || {};
            this._initDateSelecter();
            if (options.allow_add) {
                $(".b-calendar td").click(function(event){
                    if (event.target.tagName == $(this).get(0).tagName ) {
                        window.location.href = "?module=post&action=edit&date="+$(this).attr("id").replace(/^date-/,'');
                    }
                });
                var title = this.options.td_title;
                $('.b-calendar td').each(function(){
                    $(this).attr('title', title);
                });
            }
            this.initDragnDrop();
            $(document).bind('selectstart', function(e) {
                var target_name = e.target.tagName;
                if (target_name == 'TD' || 
                    target_name == 'TH' ||
                    target_name == 'TR' ||
                    target_name == 'TBODY' ||
                    target_name == 'TABLE'
                ) 
                {
                    return false;
                }
            });
        },
        
        _initDateSelecter: function() {
            $('select.month,select.year').change(function() {
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
            $(items.join(','), '.b-calendar td').liveDraggable({
                containment: $('.b-calendar'),
                distance: 5,
                helper: 'clone'
            });
            $('.b-calendar td').liveDroppable({
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
                                    cell.append('<br><br>');
                                    item.nextAll('br:lt(2)').remove().end().remove();
                                    // change icon
                                    item_clone.find('.icon10.edit-bw').
                                        removeClass('edit-bw').
                                        addClass('exclamation').
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
                        self.die("mouseover", self.data('init_draggable'));
                    }
                });
                this.live("mouseover", function() {
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
                        self.die("mouseover", self.data('init_droppable'));
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
                this.die("mouseover", init).live("mouseover", init);
                this.live('mouseover', init);
            };
        }
    }
})(jQuery);