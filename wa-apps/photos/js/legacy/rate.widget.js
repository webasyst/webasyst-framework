(function($) {
    $.fn.rateWidget = function(options, ext, value) {
        if (typeof options == 'string') {
            if (options == 'getOption') {
                if (ext == 'rate') {
                    return parseInt(this.attr('data-rate'));
                }
            }
            if (options == 'setOption') {
                if (ext == 'rate') {
                    var val = parseFloat(value) || 0;
                    update.call(this, Math.round(val * 2) / 2);
                    ext = {
                        rate: value
                    };
                }
                if (typeof ext === 'object' && ext) {
                    var settings = this.data('rateWidgetSettings') || {};
                    $.extend(settings, ext);
                    if (typeof ext.hold !== 'undefined' && typeof ext.hold !== 'function') {
                        settings.hold = _scalarToFunc(settings.hold);
                    }
                }
            }
            return this;        // means that widget is installed already
        }

        this.data('rateWidgetSettings', $.extend({
            onUpdate: function() {},
            rate: null,
            hold: false,
            withClearAction: true,
            alwaysUpdate: false
        }, options || {}));

        var settings = this.data('rateWidgetSettings'),
            self = this;
        if (typeof settings.hold !== 'function') {
            settings.hold = _scalarToFunc(settings.hold);
        }
        init.call(this);
        function init() {
            if (this.data('inited')) {  // has inited already. Don't init again
                return;
            }
            if (settings.rate != null) {
                self.attr('data-rate', settings.rate);
            }
            self.find('i:lt(' + self.attr('data-rate') + ')').removeClass('star-empty').addClass('star');
            self.mouseover(function(e) {
                if (settings.hold.call(self)) {
                    return;
                }
                var target = e.target;
                if (target.tagName == 'I') {
                    target = $(target);
                    target.prevAll()
                        .removeClass('star star-half star-empty').addClass('star-hover').end()
                        .removeClass('star star-half star-empty').addClass('star-hover');
                    target.nextAll().removeClass('star star-hover').addClass('star-empty');
                }
            }).mouseleave(function() {
                if (settings.hold.call(self)) {
                    return;
                }
                update.call(self, self.attr('data-rate'));
            });
            self.click(function(e) {
                if (settings.hold.call(self)) {
                    return;
                }
                var item = e.target;
                var root = this;
                while (item.tagName != 'I') {
                    if (item == root) {
                        return;
                    }
                }
                var prev_rate = self.attr('data-rate');
                var rate = 0;
                self.find('i')
                    .removeClass('star star-hover')
                    .addClass('star-empty')
                    .each(function() {
                        rate++;
                        $(this).removeClass('star-empty').addClass('star');
                        if (this == item) {
                            if (settings.alwaysUpdate || prev_rate != rate) {
                                self.attr('data-rate', rate);
                                settings.onUpdate(rate);
                            }
                            return false;
                        }
                });
            });
            // if withClearAction is setted to true make available near the stars link-area for clear all stars (set rate to zero)
            if (settings.withClearAction) {
                var clear_link_id = 'clear-' + $(this).attr('id'),
                    clear_link = $('#' + clear_link_id);
                if (!clear_link.length) {
                    self.after('<a href="javascript:void(0);" class="inline-link p-rate-clear" id="'+clear_link_id+'" style="display:none;"><b><i>'+$_('clear')+'</b></i></a>');
                    clear_link = $('#' + clear_link_id);
                }
                clear_link.click(function() {
                    if (settings.hold.call(self)) {
                        return;
                    }
                    var prev_rate = self.attr('data-rate');
                    update.call(self, 0);
                    if (prev_rate != 0) {
                        settings.onUpdate(0);
                    }
                });
                var timer_id;
                self.parent().mousemove(function() {
                    if (settings.hold.call(self)) {
                        return;
                    }
                    if (timer_id) {
                        clearTimeout(timer_id);
                    }
                    clear_link.show(0);
                }).mouseleave(function() {
                    timer_id = setTimeout(function() {
                        if (settings.hold.call(self)) {
                            return;
                        }
                        clear_link.hide(0);
                    }, 150);
                });
            }
            this.data('inited', true);
        }

        function update(new_rate) {
            var rate = 0;
            this.find('i')
                .removeClass('star star-empty star-half star-hover')
                .addClass('star-empty').each(function() {
                    if (rate == new_rate) {
                        return false;
                    }
                    rate++;
                    if (rate > new_rate) {
                        if (rate - new_rate == 0.5) {
                            $(this).removeClass('star-empty').addClass('star-half');
                        }
                    } else {
                        $(this).removeClass('star-empty').addClass('star');
                    }
                });
            this.attr('data-rate', new_rate);
        }

        function _scalarToFunc(scalar) {
            return function() {
                return scalar;
            };
        }

        return this;

    };
})(jQuery);