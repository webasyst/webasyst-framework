(function($) {

    $.photos_dragndrop = {
        helper_shift: 20,

        init: function() {
            this._extendJqueryUIDragAndDrop();

            this._initDragPhotos();
            this._initDropPhotos();
            this._initDragAlbums();
            this._initDropAlbums();

        },

        _initDragPhotos: function() {
            var draggable_common_opts = {
                opacity: 0.75,
                zIndex: 9999,
                distance: 5,
                appendTo: 'body',
                cursor: 'move',
                refreshPositions: true,
                start: function(event, ui) {
                    // prevent default-browser drag-and-drop action
                    document.ondragstart = function() {
                        return false;
                    };
                    // scroll fix. See helperScroll
                    ui.helper.data('scrollTop', $(document).scrollTop());
                    $(document).bind('scroll', $.photos_dragndrop._scrolHelper);
                    // mark target of dragging
                    $('#album-list-container').addClass('p-drag-active');
                },
                stop: function(event, ui) {
                    document.ondragstart = null;
                    $(document).unbind('scroll', $.photos_dragndrop._scrolHelper);
                    $('#album-list-container').removeClass('p-drag-active');
                    hideSortHint();
                },
                drag: function(event, ui) {
                    var e = event.originalEvent;
                    ui.position.left = e.pageX - $.photos_dragndrop.helper_shift;
                    ui.position.top = e.pageY;
                }
            };

            var onStart = draggable_common_opts.start,
                onStop  = draggable_common_opts.stop;

            // drag photo to albums or sort
            $("img", $('#photo-list')).liveDraggable($.extend({}, draggable_common_opts, {
                containment: [
                    0,
                    0,
                    $(window).width(),
                    {
                        toString: function() {
                            return $(document).height();  // we have lengthened document, so make flexible calculating (use typecast method toString)
                        }
                    }
                ],
                helper: function(event) {
                    var self = $(this).parents('li:first'),
                        selected = $('#photo-list li.selected'),
                        count = selected.length ? selected.length : 1,
                        photo_ids = [self.attr('data-photo-id')],
                        included = false;

                    var li = self.get(0);
                    selected.each(function() {
                        if (this != li) {
                            photo_ids.push($(this).attr('data-photo-id'));
                        } else {
                            included = true;
                        }
                    });

                    // if we have selected list, but drag start with unselected item than inclue this item (and select)
                    if (!included && selected.length) {
                        self.addClass('selected').find('input:first').trigger('select', true);
                        ++count;
                    }
                    self.data('photo_ids', photo_ids);
                    return '<div id="helper"><span class="indicator red">' + count + '</span><i class="icon10 no-bw" style="display:none;"></i></div>';
                },
                handle: '.p-image',
                start: function(event, ui) {
                    onStart.apply(this, [event, ui]);
                    var self = $(this).parents('li:first');
                    if (self.data('photo_ids').length == 1) {
                        self.addClass('selected');
                    }
                },
                stop: function(event, ui) {
                    onStop.apply(this, [event, ui]);
                    var self = $(this).parents('li:first');
                    if (self.data('photo_ids').length == 1) {
                        self.removeClass('selected');
                    }
                }
            }));

            // drag one photo (big img in photo-card)
            $('#photo').liveDraggable($.extend({}, draggable_common_opts, {
                containment: 'body',
                start: function(event, ui) {
                    if (!$(event.target).hasClass('ui-draggable')) {
                        return false;
                    }
                },
                helper: function() {
                    return '<div id="helper"><span class="indicator red">1</span><i class="icon10 no-bw" style="display:none;"></i></div>';
                }
            }));
        },

        _initDropPhotos: function() {
            // dropping process in photo-list itself. Dropping process is trying sorting
            $("li", $('#photo-list')).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                over: function(event, ui) {
                    // sorting in not static album is illegal

                    if (ui.draggable.hasClass('dr')) {
                        return false;
                    }

                    var sort_enable = isSortEnable();
                    if (!sort_enable) {
                        showSortHint();
                    } else {
                        hideSortHint();
                    }
                    $.photos_dragndrop._activatePhotoListItem.call(this);
                },
                out: function(event, ui) {
                    $.photos_dragndrop._unactivatePhotoListItem.call(this);
                },
                drop: function(event, ui) {
                    var sort_enable = isSortEnable();

                    // sorting in not static album is illegal
                    var album = $.photos.getAlbum();

                    // drop into itself is illegal
                    var draggable = ui.draggable.parents('li:first'),
                        self = $(this);


                    if (draggable.get(0) == this || self.hasClass('selected')) {
                        $.photos_dragndrop._unactivatePhotoListItem.call(this);
                        return false;
                    }

                    // define selected item
                    var selected = $('#photo-list li.selected');
                    if (!selected.length) {
                        selected = draggable;
                    }

                    $.photos_dragndrop._unactivatePhotoListItem.call(this);
                    $.photos_dragndrop._unactivatePhotoListItem.call(draggable);

                    if (!sort_enable) {
                        return false;
                    }

                    // visually sorting and some clear actions
                    var before_id = parseInt(self.attr('data-photo-id'));
                    if (self.hasClass('last')) {
                        before_id = null;
                        self.after(selected);
                        self.removeClass('last');
                        $('#photo-list li:last').addClass('last');
                    } else {
                        self.before(selected);
                        if (draggable.hasClass('last')) {
                            draggable.removeClass('last')
                            $('#photo-list li:last').addClass('last');
                        }
                    }
                    // clear visuall hightlights
                    selected.trigger('select', false);

                    // sorting on server
                    var photo_id = [];
                    selected.each(function() {
                        photo_id.push(parseInt($(this).attr('data-photo-id')));
                    });
                    $.post('?module=album&action=photoMove', {
                        photo_id: photo_id,
                        album_id: album.id,
                        before_id: before_id
                    }, function(r) {
                        if (r.status == 'ok') {
                            $.photos.photo_stream_cache.move(photo_id, before_id);
                        }
                    }, 'json');
                }
            });
        },

        _initDragAlbums: function() {
            var containment = $('#wa-app > .sidebar'),
                containment_pos = containment.position(),
                containment_metrics = { width: containment.width(), height: containment.height() };

            $("li.dr", $('#album-list')).liveDraggable({
                containment: [
                      containment_pos.left,
                      containment_pos.top,
                      containment_pos.left + containment_metrics.width + containment_metrics.width*0.25,
                      containment_pos.top + containment_metrics.height
                ],
                refreshPositions: true,
                revert: "invalid",
                helper: function() {
                    var self = $(this),
                        clone = self.clone().addClass('ui-draggable').css({
                            position: 'absolute'
                        }).prependTo('#album-list > ul');
                    clone.find('a:first').append('<i class="icon10 no-bw" style="margin-left: 0; margin-right: 0; display:none;"></i>');
                    return clone;
                },
                cursor: "move",
                cursorAt: { left: 5 },
                opacity: 0.75,
                zIndex: 9999,
                distance: 5,
                start: function(event, ui) {
                    document.ondragstart = function() {
                        return false;
                    };
                },
                stop: function() {
                    document.ondragstart = null;
                }
            });
        },

        _initDropAlbums: function() {
            this._initDropBetweenAlbums();
            this._initDropInsideAlbums();
        },

        _initDropBetweenAlbums: function() {
            // drop between albums
            $("li.drag-newposition", $('#album-list')).liveDroppable({
                accept: 'li.dr',
                greedy: true,
                tolerance: 'pointer',
                over: function(event, ui) {
                    // legal only for album (li)
                    if (ui.draggable.get(0).tagName == 'IMG') {
                        return false;
                    }
                    $(this).addClass('active').parent().parent().addClass('drag-active');
                },
                out: function(event, ui) {
                    $(this).removeClass('active').parent().parent().removeClass('drag-active');
                },
                deactivate: function(event, ui) {
                    var self = $(this);
                    if (self.is(':animated') || self.hasClass('dragging')) {
                        self.stop().animate({height: '0px'}, 300, null, function(){self.removeClass('dragging');});
                    }
                    $(this).removeClass('active').parent().parent().removeClass('drag-active');
                },
                drop: function (event, ui) {
                    // legal only for album (li)
                    if (ui.draggable.get(0).tagName == 'IMG') {
                        return false;
                    }
                    var list = $(this).parent('ul');
                    var dr = $(ui.draggable);
                    var id = dr.attr('rel');
                    var prev = $(this).prev('li');

                    if (prev.length && prev.attr('rel') == id && !prev.hasClass('ui-draggable')) {
                        return false;
                    }
                    if (this == dr.next().get(0)) {
                        return false;
                    }
                    var parent_id = list.parent('li').length ? list.parent('li').attr('rel') : 0;
                    var before = $(this).next(),
                        before_id = null;
                    if (before.length) {
                        before_id = before.attr('rel');
                    }
                    $.post('?module=album&action=move', {
                        id: id,
                        before_id: before_id,
                        parent_id: parent_id
                    }, function(r) {
                        var current_album = $.photos.getAlbum(),
                            album = r.data.album,
                            counters = r.data.counters;

                        if (album.status <= 0 &&
                            $.photos_dragndrop.privateDescendants(album.id))
                        {
                            $.photos.dispatch('album/'+album.id+'/');
                        }

                        if (current_album && current_album.id == album.id) {
                            var frontend_link = r.data.frontend_link;
                            if (frontend_link) {
                                $('#photo-list-frontend-link').attr('href', frontend_link).text(frontend_link);
                            }
                            if (album.type == Album.TYPE_DYNAMIC) {
                                $.photos.load("?module=album&action=photos&id=" + album.id, $.photos.onLoadPhotoList);
                            }
                        }
                        if (!$.isEmptyObject(counters)) {
                            for (var album_id in counters) {
                                if (counters.hasOwnProperty(album_id)) {
                                    album_list.find('li[rel='+album_id+']').find('.count:first').text(counters[album_id]);
                                }
                            }
                        }
                    }, 'json');

                    var $parent_list = dr.parent('ul');
                    var li_count = $parent_list.children('li.dr[rel!='+id+']').length;

                    dr.next().insertAfter($(this));
                    dr.insertAfter($(this));

                    if (!li_count) {
                        $parent_list.parent('li').children('i').remove();
                        $parent_list.remove();
                    }
                }
            });
        },

        privateDescendants: function(album_id, include_parent) {
            include_parent = typeof include_parent === 'boolean' ? include_parent : true;
            var album_list = $('#album-list');
            var li = album_list.find('li[rel='+album_id+']');
            var list;
            if (include_parent) {
                list = li;
            } else {
                list = $();
            }
            changed = false;
            list.add(li.find('li.dr')).each(function() {
                var self = $(this).find('>a');
                if (!self.find('i.lock-bw').length) {
                    var next = self.find('.pictures').next();
                    var html = '<i class="icon10 lock-bw no-overhanging"></i>';
                    if (next.length) {
                        changed = true;
                        next.before(html);
                    } else {
                        self.append(html);
                    }
                }
            });
            return changed;
        },

        _initDropInsideAlbums: function() {
            // drop inside album
            $("li.dr a", $('#album-list')).liveDroppable({
                accept: function(el) {
                    // Albums in sidebar, image from single photo page
                    if (el.is('li.dr, img#photo')) {
                        return true;
                    }
                    // Images from photo lists
                    if (el.closest('li[data-photo-id]').length) {
                        return true;
                    }
                    return false;
                },
                tolerance: 'custom',
                greedy: true,
                out: function(event, ui) {
                    $(this).parent().removeClass('drag-newparent');
                    ui.helper.find('span').show().end().find('i').hide();       // show 'circle'-icon
                },
                over: function(event, ui) {
                    var self = $(this).parent(),  // li
                        is_photo = false;
                    ui.draggable = $.photos_dragndrop._fixUiDraggable(ui.draggable);

                    // photo
                    if (ui.draggable.parents('#photo-list').length || ui.draggable.is('#photo')) {
                        if (!self.hasClass('static')) {
                            ui.helper.find('span').hide().end().find('i').show();   // show 'cross'-icon
                        } else {
                            ui.helper.find('span').show().end().find('i').hide();       // show 'circle'-icon
                        }
                        is_photo = true;
                    }
                    self.addClass('drag-newparent');

                    if (is_photo) {
                        return false;
                    }

                    // album
                    if (ui.draggable.hasClass('static') && !self.hasClass('static'))
                    {
                        ui.helper.find('i.no-bw').show();
                        return false;
                    } else {
                        ui.helper.find('i.no-bw').hide();
                    }

                    var dr = $(ui.draggable);
                    var drSelector = '.dr[rel!="'+dr.attr('rel')+'"]';
                    var nearby = $();

                    // helper to widen all spaces below the current li and above next li (which may be on another tree level, but not inside current)
                    var addBelow = function(nearby, current) {
                        if (current.length <= 0) {
                            return nearby;
                        }
                        nearby = nearby.add(current.nextUntil(drSelector).filter('li.drag-newposition'));
                        if (current.nextAll(drSelector).length > 0) {
                            return nearby;
                        }
                        return addBelow(nearby, current.parent().closest('li'));
                    };

                    // widen all spaces above the current li and below the prev li (which may be on another tree level)
                    var above = self.prevAll(drSelector).first();
                    if(above.length > 0) {
                        var d = above.find(drSelector);
                        if (d.length > 0) {
                            nearby = addBelow(nearby, d.last());
                        } else {
                            nearby = addBelow(nearby, above);
                        }
                    } else {
                        nearby = nearby.add(self.prevUntil(drSelector).filter('li.drag-newposition'));
                    }

                    // widen all spaces below the current li and above the next li (which may be on another tree level)
                    if (self.children('ul').children(drSelector).length > 0) {
                        nearby = nearby.add(self.children('ul').children('li.drag-newposition:first'));
                    } else {
                        nearby = addBelow(nearby, self);
                    }

                    var old = $('.drag-newposition:animated, .drag-newposition.dragging').not(nearby);

                    old.stop().animate({height: '0px'}, 300, null, function(){old.removeClass('dragging');});
                    nearby.stop().animate({height: '10px'}, 300, null, function(){nearby.addClass('dragging');});
                },
                drop: function( event, ui ) {
                    var self = $(this).parent().removeClass('drag-newparent'),   // li
                        list;

                    ui.draggable = $.photos_dragndrop._fixUiDraggable(ui.draggable);

                    // copy photo to album (only static is legal)
                    if (ui.draggable.parents('#photo-list').length || ui.draggable.is('#photo')) {
                        var m = this.href.match(/.*#\/album\/([\d]+)/);
                        if (m === null || !parseInt(m[1], 10)) {
                            if (console) {
                                console.log("Link: " + this.href + " is not correct");
                            }
                            return;
                        }
                        var album_id = parseInt(m[1], 10),
                            photo_ids = null;
                        if (self.hasClass('static')) {
                            if (ui.draggable.is('#photo')) {
                                photo_ids = [$.photos.photo_stream_cache.getCurrent().id];
                            } else {
                                photo_ids = ui.draggable.data('photo_ids');
                            }
                        }
                        if (photo_ids) {
                            $.photos.addToAlbums({
                                photo_id: photo_ids,
                                album_id: album_id
                            });
                            $('#photo-list li.selected').trigger('select', false);
                        }
                        return false;
                    }

                    // album
                    if (ui.draggable.hasClass('static') && !self.hasClass('static'))
                    {
                        return false;
                    }

                    var dr = $(ui.draggable);
                    if (self.attr('rel') == dr.attr('rel')) {
                        return false;
                    }

                    if (self.hasClass('drag-newposition')) {
                        list = self.parent('ul');
                    } else {
                        if (self.children('ul').length) {
                            list =  self.children('ul');
                        } else {
                            list = $('<ul class="menu-v with-icons dr"><li class="drag-newposition"></li></ul>').appendTo(self);
                            list.find('.drag-newposition').mouseover(); // init droppable
                            $('<i class="icon16 darr overhanging"></i>').insertBefore(self.children('a'));
                        }
                    }

                    var id = dr.attr('rel');
                    var parent_id = self.attr('rel');
                    if (parent_id == dr.parent('ul').parent('li.dr').attr('rel')) {
                        return false;
                    }

                    $.post('?module=album&action=move', {
                        id: id,
                        parent_id: parent_id
                    }, function(r) {
                        var current_album = $.photos.getAlbum(),
                            album = r.data.album,
                            counters = r.data.counters;

                        if (album.status <= 0 &&
                            $.photos_dragndrop.privateDescendants(album.id))
                        {
                            $.photos.dispatch('album/'+album.id+'/');
                        }

                        if (current_album && current_album.id == album.id) {
                            var frontend_link = r.data.frontend_link;
                            if (frontend_link) {
                                $('#photo-list-frontend-link').attr('href', frontend_link).text(frontend_link);
                            }
                            if (album.type == Album.TYPE_DYNAMIC) {
                                $.photos.load("?module=album&action=photos&id=" + album.id, $.photos.onLoadPhotoList);
                            }
                        }
                        var album_list = $('#album-list');
                        if (!$.isEmptyObject(counters)) {
                            for (var album_id in counters) {
                                if (counters.hasOwnProperty(album_id)) {
                                    album_list.find('li[rel='+album_id+']').find('.count:first').text(counters[album_id]);
                                }
                            }
                        }
                    }, 'json');

                    var $parent_list = dr.parent('ul');
                    var li_count = $parent_list.children('li.dr[rel!='+id+']').length;

                    var sep = dr.next();
                    dr.appendTo(list);
                    sep.appendTo(list);

                    if (!li_count) {
                        $parent_list.parent('li').children('i').remove();
                        $parent_list.remove();
                    }
                }
            });
        },

        // when scrolling page drag-n-drop helper must moving too with cursor
        _scrolHelper: function(e) {
            var helper = $('#helper'),
                prev_scroll_top = helper.data('scrollTop'),
                scroll_top = $(document).scrollTop(),
                shift = prev_scroll_top ? scroll_top - prev_scroll_top : 50;

            helper.css('top', helper.position().top + shift + 'px');
            helper.data('scrollTop', scroll_top);
        },

        _dropAnimation: function(items, done) {
            var duration = 300;
            var deferreds = [];
            items.each(function() {
                var item = $(this);
                var item_offset = item.offset();
                var item_clone = item.clone().css({
                    'z-index': 10,
                    position: 'absolute',
                    top: item_offset.top,
                    left: item_offset.left
                }).insertAfter(item);
                item.css({
                    opacity: 0
                });
                deferreds.push(
                    item.hide(duration).promise()
                );
                deferreds.push(item_clone.animate({
                    top: item_offset.top,
                    left: item_offset.left
                }, duration).promise().done(function() {
                    $(this).remove();
                }));
            });
            $.when.apply($, deferreds).done(function() {
                if (typeof done === 'function') {
                    done();
                }
            });
        },

        _shiftToLeft: function(item) {
            if (item.data('shifted') !== 'left') {
                var wrapper = item.find('.p-wrapper');
                if (!wrapper.length) {
                    var children = item.children();
                    var wrapper = $("<div class='p-wrapper' style='position:relative;'></div>").appendTo(item);
                    wrapper.append(children);
                }
                wrapper.stop().animate({
                    left: -15
                }, 200);
                item.data('shifted', 'left');
            }
        },
        _shiftToRight: function(item) {
                if (item.data('shifted') !== 'right') {
                    var wrapper = item.find('.p-wrapper');
                    if (!wrapper.length) {
                        var children = item.children();
                        var wrapper = $("<div class='p-wrapper' style='position:relative;'></div>").appendTo(item);
                        wrapper.append(children);
                    }
                    wrapper.stop().animate({
                        left: 15
                    }, 200);
                    item.data('shifted', 'right');
                }
        },
        _shiftAtPlace: function(item) {
            if (item.data('shifted')) {
                var wrapper = item.find('.p-wrapper');
                if (wrapper.length) {
                    var children = wrapper.children();
                    wrapper.stop().css({
                        left: 0
                    });
                    item.append(children);
                    wrapper.remove();
                }
                item.data('shifted', '');
            }
        },

        _bindExtDragActivate: function(item, className) {
            $(document).bind('mousemove.ext_drag_activate', function (e) {
                $.photos_dragndrop._extDragActivate(
                    e,  item, className
                );
            });
        },

        _unbindExtDragActivate: function() {
            $(document).unbind('mousemove.ext_drag_activate');
        },

        _activatePhotoListItem: function() {
                var self = $(this);
                var sort_enable = isSortEnable();
                var className = sort_enable ? 'drag-active' : 'drag-active-disabled';
                if (sort_enable) {
                    $.photos_dragndrop._shiftToRight(self);
                }
                if (self.hasClass('last')) {
                    $.photos_dragndrop._bindExtDragActivate(self, className);
                } else {
                    self.addClass(className);
                }
        },

        // clear (unactive) action
        _unactivatePhotoListItem: function(ui) {
            var self = $(this);
            var sort_enable = isSortEnable();
            var className =  sort_enable ? 'drag-active' : 'drag-active-disabled';
            var classNameOfLast = className + '-last';
            if (self.hasClass('last')) {
                $.photos_dragndrop._unbindExtDragActivate();
            }
            self.removeClass(className + ' ' + classNameOfLast);
            if (sort_enable) {
                $.photos_dragndrop._shiftAtPlace(self);
            }
        },

        // active/inactive drop-li both left and right
        _extDragActivate: function(e, self, className) {
            var classNameOfLast = className + '-last';
            if (!self.hasClass('last')) {
                self.addClass(className);
                return;
            }
            var pageX = e.pageX,
                pageY = e.pageY,
                self_width = self.width(),
                self_height = self.height(),
                self_position = self.position();

            if ($.photos.list_template == 'template-photo-thumbs') {
                if (pageX > self_position.left + self_width*0.5 && pageX <= self_position.left + self_width) {
                    self.removeClass(className).addClass(classNameOfLast);
                    $.photos_dragndrop._shiftToLeft(self);
                } else if (pageX > self_position.left && pageX <= self_position.left + self_width*0.5) {
                    self.removeClass(classNameOfLast).addClass(className);
                    $.photos_dragndrop._shiftToRight(self);
                } else {
                    $.photos_dragndrop._shiftAtPlace(self);
                }
            } else if ($.photos.list_template == 'template-photo-descriptions') {
                if (pageY > self_position.top + self_height*0.5) {
                    self.removeClass(className).addClass(classNameOfLast);
                } else if (pageY > self_position.top) {
                    self.removeClass(classNameOfLast).addClass(className);
                }
            }
            if (pageY < self_position.top || pageY > self_position.top + self_height ||
                    pageX < self_position.left || pageX > self_position.left + self_width)
            {
                self.removeClass(className).removeClass(classNameOfLast);
            }
        },

        // standardize draggable because it must be different
        _fixUiDraggable: function(draggable) {
            if (draggable.is('#photo')) {
                return draggable;
            }
            if (draggable.get(0).tagName == 'IMG') {
                draggable = draggable.parents('li:first');
            }
            return draggable;
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
                var h;
                this.live("mouseover", h = function() {
                    var self = $(this);
                    if (!self.data("init_draggable")) {
                        self.data("init_draggable", h).draggable(opts);
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
                        self.data("init_droppable", init).droppable(opts);
                        self.mouseover();
                    }
                };
                init.call(this);
                this.die("mouseover", init).live("mouseover", init);
                this.live('mouseover', init);
            };

            // Custom tolerance-mode realization because of jquery.ui doesn't have necessary functionality
            // This functionality extension need when dragging "big-photo" in one photo-page: intersection
            // have to take into account small helper icon and NOT big photo.
            // Because left corner of big photo can rest against left bound of window, so x-coordinate is frozen and not recalculated
            var parent_ui_intersect = $.ui.intersect;
            $.ui.intersect = function(draggable, droppable, toleranceMode) {
                if (!$(draggable.element).is('#photo') && toleranceMode == 'custom') {
                    toleranceMode = 'pointer';
                }
                if (toleranceMode != 'custom') {
                    return parent_ui_intersect.call($.ui, draggable, droppable, toleranceMode);
                }
                var left = droppable.offset.left,
                    top = droppable.offset.top,
                    helper_position = draggable.helper.position(),
                    helper_left = helper_position.left,
                    helper_top = helper_position.top;

              return $.ui.isOver(helper_top, helper_left + $.photos_dragndrop.helper_shift, top, left, droppable.proportions.height, droppable.proportions.width);
            };
        }

    };

    function isSortEnable() {
        var album = $.photos.getAlbum();
        if (!album || album.type !== Album.TYPE_STATIC || album.edit_rights === false) {
            return false;
        }
        return true;
    }

    function showSortHint() {
        var sort_method = $.photos.getOption('sort_method');
        if (sort_method) {
            var block = $('#hint-menu-block').show();
            block.children().hide();
            block.find('.' + sort_method).show();
        }
    }

    function hideSortHint() {
            var block = $('#hint-menu-block').hide();
            block.children().hide();
    }

})(jQuery);