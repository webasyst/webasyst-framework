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
                    let settings = this.data('rateWidgetSettings') || {};
                    $.extend(settings, ext);
                    if (typeof ext.hold !== 'undefined' && typeof ext.hold !== 'function') {
                        settings.hold = _scalarToFunc(settings.hold);
                    }
                }
            }
            return this;        // means that widget is installed already
        }

        this.data('rateWidgetSettings', $.extend({
            onUpdate() { },
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
            if (self.data('inited')) {  // has inited already. Don't init again
                return;
            }

            if (settings.rate != null) {
                self.attr('data-rate', settings.rate);
            }

            self.find('svg:lt(' + self.attr('data-rate') + ')').attr('data-prefix', 'fas');

            self
                .mouseover(function (e) {
                    if (settings.hold.call(self)) {
                        return;
                    }

                    let target = e.target;

                    if (target.tagName === 'svg') {

                        target = $(target);
                        target
                            .prevAll()
                            .addBack()
                            .removeClass('fa-star-half-alt')
                            .attr('data-prefix', 'fas');

                        target
                            .nextAll()
                            .removeClass('fa-star-half-alt')
                            .attr('data-prefix', 'far');
                    }
                })
                .mouseleave(function () {
                    if (settings.hold.call(self)) {
                        return;
                    }
                    update.call(self, self.attr('data-rate'));
                });


            self.on('click', function (e) {

                if (settings.hold.call(self)) {
                    return;
                }

                let target = e.target;
                if (target.tagName === 'path') {
                    target = target.ownerSVGElement;
                }

                let prev_rate = self.attr('data-rate'),
                    rate = $(target).attr('data-rate-value');

                if (prev_rate == rate) {
                    return;
                }

                self
                    .find('svg')
                    .removeClass('fa-star-half-alt')
                    .attr('data-prefix', 'far')
                    .each(function () {
                        $(this).attr('data-prefix', 'fas');
                        if ($(this).attr('data-rate-value') == rate) {
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
                let clear_link_id = `clear-${$(this).attr('id')}`,
                    clear_link = $(`#${clear_link_id}`);

                if (!clear_link.length) {
                    self.after(`<a href="javascript:void(0);" class="p-rate-clear" id="${clear_link_id}" style="display:none;">${$_('clear')}</a>`);
                    clear_link = $('#' + clear_link_id);
                }

                clear_link.on('click', function (e) {
                    e.preventDefault();

                    if (settings.hold.call(self)) {
                        return;
                    }

                    let prev_rate = self.attr('data-rate');

                    update.call(self, 0);

                    if (prev_rate != 0) {
                        settings.onUpdate(0);
                    }
                });

                let timer_id;

                self
                    .parent()
                    .mousemove(function () {
                        if (settings.hold.call(self)) {
                            return;
                        }
                        if (timer_id) {
                            clearTimeout(timer_id);
                        }
                        clear_link.show(0);
                    })
                    .mouseleave(function () {
                        timer_id = setTimeout(function () {
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
            let rate = 0;
            this.find('svg')
                .removeClass('fa-star-half-alt')
                .attr('data-prefix', 'far').each(function () {
                if (rate == new_rate) {
                    return false;
                }
                rate++;
                if (rate > new_rate) {
                    if (rate - new_rate == 0.5) {
                        $(this).attr('data-prefix', 'fas').addClass('fa-star-half-alt');
                    }
                } else {
                    $(this).attr('data-prefix', 'fas');
                }
            });
            this.attr('data-rate', new_rate);
        }

        function _scalarToFunc(scalar) {
            return function () {
                return scalar;
            };
        }

        return this;

    };
})(jQuery);