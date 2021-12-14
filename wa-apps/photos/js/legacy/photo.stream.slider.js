(function($) {
    $.fn.photoStreamSlider = function(options) {
        var settings = $.extend({
            duration: 200
        }, options || {});

        var self = this;

        function init()
        {
            var photo_stream = self.find(settings.photoStream),
                visible_list = $('li.visible', photo_stream),
                list = $('li', photo_stream),
                visible_width = visible_list.filter(':first').outerWidth() * visible_list.length,
                width = list.filter(':first').outerWidth() * list.length,
                height = list.filter(':first').outerHeight(),
                wrapper = photo_stream.parent();

            wrapper.css({
                overflow: 'hidden',
                height: height,
                position: 'relative',
                padding: '4px 0',
                margin: 0,
                width: visible_width
            });

            var li = photo_stream.find('li:first'),
                first_visible = visible_list.filter(':first'),
                delta = (first_visible.outerWidth() - first_visible.width()) / 2,
                offset = 0,
                li_width = li.outerWidth();

            first_visible = first_visible.get(0);
            while (li.length) {
                if (li.get(0) == first_visible) {
                    break;
                }
                offset += li_width;
                li = li.next();
            }

            offset -= delta;
            photo_stream.css({
                position: 'absolute',
                left: -offset,
                width: width
            });

            $(settings.forwardLink).click(function() {
                slide('forward');
                return false;
            });

            $(settings.backwardLink).click(function() {
                slide('backward');
                return false;
            });

            self.bind('append prepend', function(e, html) {
                if (e.type == 'append') {
                    var last_dummy = photo_stream.find('li:last'),
                        li = last_dummy,
                        dummies_tail = $();
                    while (li.hasClass('dummy')) {
                        dummies_tail = dummies_tail.add(li);
                        li = li.prev('li')
                    }
                    dummies_tail.remove();
                    photo_stream.append(html);
                } else {
                    // because we prepend and use css-left for shifting, so calc shift (width of prepended items)
                    var rendered = $('<div></div>').html(html),
                        new_list = rendered.find('li'),
                        shift = new_list.length * li_width;
                    rendered.remove();

                    var first_dummy = photo_stream.find('li:first'),
                        li = first_dummy,
                        dummies_head = $();

                    while (li.hasClass('dummy')) {
                        dummies_head = dummies_head.add(li);
                        li = li.next('li')
                    }
                    dummies_head.remove();
                    photo_stream.prepend(new_list);
                }
                // update closure vars
                list = photo_stream.find('li');
                // recalc width
                width = list.filter(':first').outerWidth() * list.length;
                photo_stream.css('width', width);

                if (e.type == 'prepend') {
                    // prepend - take into account shifting (see before)
                    var f = function() {
                        photo_stream.css('left', parseInt(photo_stream.css('left')) - shift);
                    };
                    if (photo_stream.is(':animated')) {
                        photo_stream.stop(false, true);
                        f();
                    } else {
                        f();
                    }
                }
 
                var selected_li = photo_stream.find('li.selected');
                if (selected_li.hasClass('visible')) {
                    self.trigger('home', [null, false]);
                }
            });

            self.bind('forward backward', function(e, options) {
                slide({
                    direction: e.type,
                    steps: options.steps,
                    animate: typeof options.animate !== 'undefined' ? options.animate : true,
                    fn: options.fn
                });
                var selected_li = photo_stream.find('li.selected'),
                    candidate_li = e.type == 'forward' ? selected_li.next('li:not(.dummy)') : selected_li.prev('li:not(.dummy)');
                if (candidate_li.length) {
                    selected_li.removeClass('selected');
                    candidate_li.addClass('selected');
                }
            });

            self.bind('home', function(e, fn, animate) {
                var middle = parseInt(visible_list.length / 2),
                    current = visible_list.filter(':eq('+middle+')'),
                    next_selected = current.nextAll('.selected:first'),
                    prev_selected = current.prevAll('.selected:first'),
                    cnt = 0,
                    direction = '';

                if (typeof fn !== 'function') {
                    animate = fn;
                }
                animate = typeof animate !== 'undefined' ? animate : true;
                if (next_selected.length) {
                    direction = 'forward';
                    current.nextAll().each(function() {
                        ++cnt;
                        if ($(this).hasClass('selected')) {
                            return false;
                        }
                    });
                } else if (prev_selected.length) {
                    direction = 'backward';
                    current.prevAll().each(function() {
                        ++cnt;
                        if ($(this).hasClass('selected')) {
                            return false;
                        }
                    });
                }
                if (direction && cnt) {
                    slide({
                        direction: direction,
                        steps: cnt,
                        fn: fn,
                        animate: animate
                    });
                } else {
                    if (typeof fn == 'function') {
                        fn();
                    }
                }
                return false;
            });

            function slide(options)
            {
                if (typeof options == 'string') {
                    options = {
                        direction: options,
                        animate: true
                    };
                }
                var direction = options['direction'] || 'forward',
                    count = options['steps'] || visible_list.length;

                if (slide.execution) {
                    return;
                }
                slide.execution = true;

                var shift = visible_list.filter(':first').outerWidth() * count,
                    visible_count = visible_list.length;

                if (direction == 'forward') {
                    var last = visible_list.filter(':last'),
                        next = last.nextAll(':lt(' + count + ')'),
                        next_count = next.length,
                        last_in_next = next.filter(':last');
                    if (next_count) {
                        visible_list.removeClass('visible');
                        last_in_next.prevAll(':lt(' + (visible_count - 1) + ')').addClass('visible');
                        last_in_next.addClass('visible');
                    }
                } else {
                    var first = visible_list.filter(':first'),
                        prev = first.prevAll(':lt(' + count + ')'),
                        prev_count = prev.length,
                        first_in_prev = prev.filter(':last');       // prevAll return list in right-to-left order, so first is last
                    if (prev_count) {
                        visible_list.removeClass('visible');
                        first_in_prev.nextAll(':lt(' + (visible_count - 1) + ')').addClass('visible').show();
                        first_in_prev.addClass('visible');
                    }
                }
                // update closure
                visible_list = $('li.visible', photo_stream);
                // sliding itself
                var left = photo_stream.position()['left'];
                if (direction == 'forward') {
                    var bound = width - visible_width - delta;
                    left = left - shift;
                    left = left > -bound ? left : -bound;
                } else {
                    left = left + shift;
                    left = left < delta ? left : delta;
                }

                function afterAnimate() {
                    slide.execution = false;
                    var callback = 'on' + direction.charAt(0).toUpperCase() + direction.slice(1);
                    callback = settings[callback];
                    if (typeof callback == 'function') {
                        callback.call(self);
                    }
                    callback = options.fn;
                    if (typeof callback == 'function') {
                        callback.call(self);
                    }
                }
                if (options.animate) {
                    photo_stream.animate({
                        left: left
                    }, settings.duration, afterAnimate);
                } else {
                    photo_stream.css({
                        left: left
                    });
                    afterAnimate();
                }
            }
        }

        init();

    }
})(jQuery);