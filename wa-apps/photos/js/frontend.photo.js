$(function() {
    var max_availabe_rest_count = 15,
        is_end = false, is_start = false,
        duration = 400;

    $('#photo-stream').photoStreamSlider({
        backwardLink: '#photo-stream .rewind',
        forwardLink: '#photo-stream .ff',
        photoStream: 'ul',
        duration: duration,
        onForward: function f() {
            if (is_end) {
                return;
            }
            var self = this,
                list = self.find('ul').find('li'),
                visible_list = list.filter('.visible'),
                last = list.filter(':last'),
                last_visible = visible_list.filter(':last'),
                next = last_visible.nextAll(),
                next_count = next.length;
            if (next_count < max_availabe_rest_count) {
                if (last.hasClass('dummy')) {
                    is_end = true;
                    return;
                }
                var photo_url = last.find('a').attr('href'),
                    url = photo_url + 'loadList?direction=1';
                if (typeof f.xhr === 'object') {
                    return;
                }
                f.xhr = $.get(url, 
                    function(html) {
                        if (html) {
                            var rendered = $('<div></div>').html(html);
                            if ($.Retina) {
                                rendered.find('.stream-wrapper .photostream li:not(.dummy) img').retina();
                            }
                            html = rendered.find('.stream-wrapper ul').children();
                            rendered.remove();
                            self.trigger('append', html);
                            $.photos.onLoadTail();
                        } else {
                            return is_end = true;
                        }
                        delete f.xhr;
                    }, 
                'html');
            }
        },
        onBackward: function f() {
            if (is_start) {
                return;
            }
            var self = this,
                list = self.find('ul').find('li'),
                visible_list = list.filter('li.visible'),
                first = list.filter(':first'),
                first_visible = visible_list.filter(':first'),
                prev = first_visible.prevAll(),
                prev_count = prev.length;

            if (prev_count < max_availabe_rest_count) {
                if (first.hasClass('dummy')) {
                    is_first = true;
                    return;
                }
                var photo_url = first.find('a').attr('href'),
                    url = photo_url + 'loadList?direction=-1';
                if (typeof f.xhr === 'object') {
                    return;
                }
                f.xhr = $.get(url, 
                    function(html) {
                        if (html) {
                            var rendered = $('<div></div>').html(html);
                            if ($.Retina) {
                                rendered.find('.stream-wrapper .photostream li:not(.dummy) img').retina();
                            }
                            html = rendered.find('.stream-wrapper ul').children();
                            rendered.remove();
                            self.trigger('prepend', html);
                            $.photos.onLoadHead();
                        } else {
                            return is_start = true;
                        }
                        delete f.xhr;
                    }, 
                'html');
            }
        }
    });

    // common functionality
    $.photos = $.extend($.photos || {}, {
        options: {},
        init: function(options) {
            this.options = options;
            $.photos.hotkey_manager.set();
            $.photos.renderMap();
            if ($.Retina) {
                $('#photo').retina();
            }
            this.onInit();
        },
        onInit: function() {},
        onLoadTail: function() {},
        onLoadHead: function() {},
        hotkey_manager: (function() {
            function arrowsHandlerDown(e) {
                var target_type = e.target.type,
                    code = e.keyCode;
                if ( arrowsHandlerDown.hold ||
                     target_type == 'text' || target_type == 'textarea' ||
                     (code != 37 && code != 39)
                   ) 
                { 
                    return; 
                }
                if (code == 39 && !e.altKey) {
                    $.photos.goToNextPhoto();
                    arrowsHandlerDown.hold = true;
                }
                if (code == 37 && !e.altKey) {
                    $.photos.goToPrevPhoto();
                    arrowsHandlerDown.hold = true;
                }
            }
            function arrowsHandlerUp(e) {
                arrowsHandlerDown.hold = false;
            }
            return {
                set: function() {
                    $(document).bind('keydown', arrowsHandlerDown).bind('keyup', arrowsHandlerUp);
                },
                unset: function() {
                    $(document).unbind('keydown', arrowsHandlerDown).unbind('keyup', arrowsHandlerUp);
                }
            };
        })(),
        goToNextPhoto: function() {
            var next = this.options.photo_stream.find('li.selected').next();
            if (next.length) {
                location.href = next.find('a:first').attr('href');
            }
        },
        goToPrevPhoto: function() {
            var prev = this.options.photo_stream.find('li.selected').prev();
            if (prev.length) {
                location.href = prev.find('a:first').attr('href');
            }
        },
        goToNextPhotoInStack: function() {
            var next = this.options.stack_stream.find('li.selected').next();
            if (next.length) {
                location.href = next.find('a:first').attr('href');
            }
        },
        goToPrevPhotoInStack: function() {
            var prev = this.options.stack_stream.find('li.selected').prev();
            if (prev.length) {
                location.href = prev.find('a:first').attr('href');
            }
        },
        renderMap: function() {
            var photo_map = $('#photo-map'),
                lat = photo_map.attr('data-lat'),
                lng = photo_map.attr('data-lng');
            if (lat && lng) {
                photo_map.show();
                var latLng = new google.maps.LatLng(lat, lng),
                    options = {
                        zoom: 11,
                        center: latLng,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        disableDefaultUI: true,
                        zoomControlOptions: {
                            position: google.maps.ControlPosition.TOP_LEFT,
                            style: google.maps.ZoomControlStyle.SMALL
                        }
                    };
                map = new google.maps.Map(photo_map.get(0), options);
                var marker = new google.maps.Marker({
                    position: latLng,
                    map: map,
                    title: $('#photo-name').val()
                });
            } else {
                photo_map.hide();
            }
        },
        setTitle: function(title) {
            if (title) {
                document.title = title;
            }
        }
    });

    var is_history_api_supported = typeof history.pushState === 'undefined' ? false : true;
    if (is_history_api_supported) {

        var popped = !!window.history.state,
            initial_url = location.href,
            slide_back_needed = false;
        
        window.addEventListener('popstate', function(e) {
            // ignore inital popstate that some browsers fire on page load
            var initial_pop = !popped && location.href == initial_url;
            popped = true;
            if (initial_pop) {
                return;
            }
            slide_back_needed = true;
            if (e.state && e.state.id) {
                $.photos.loadPhoto(e.state.id, false);
            }
        }, false);

        // specific functionality (in case History API supported)
        $.photos = $.extend($.photos || {}, {
            photo_stream_cache: {},
            photo_stack_cache: {},  // not empty when now in stack
            onInit: function() {
                /*
                 * init cache structures
                 */
                // photo-stream
                this.photo_stream_cache = (function() {
                    var stream = new PhotoStream(),
                        append = stream.append,
                        prepend = stream.prepend,
                        photo_id = $('#photo').attr('data-photo-id');
                    
                    function workup(photos) {
                        var size = $('#photo').attr('data-size'),
                            size_info = parseSize(size),
                            photo_container_width = $.photos.options.photo_container.width();
                        for (var i = 0, n = photos.length; i < n; ++i) {
                            var photo = photos[i],
                                real_size = getRealSizesOfThumb(photo, size_info);
                            if (typeof real_size !== 'object' || !real_size || real_size.width > photo_container_width) {
                                real_size = {
                                    width: photo_container_width, height: ''
                                };
                            }
                            photo.thumb_custom = {
                                size: real_size,
                                bound: size_info,
                                url: photo.thumb_custom.url.replace('%size%', size)
                            };
                        }
                        return photos;
                    }
                    stream.append = function(photos) {
                        photos = workup(photos);
                        return append.call(this, photos);
                    };
                    stream.prepend = function(photos) {
                        photos = workup(photos);
                        return prepend.call(this, photos);
                    };
                    
                    stream.append(__photo_stream_data);  // __photo_stream_data comes with photo-stream html block
                    stream.setCurrentById(photo_id);
                    return stream;
                })(),
                // photo-stack
                this.photo_stack_cache = (function() {
                    var stream = new PhotoStream();
                    
                    stream.init = function() {
                        if (typeof __photo_stack_data !== 'undefined') {
                            var size = $('#photo').attr('data-size'),
                                size_info = parseSize(size),
                                photo_id = $('#photo').attr('data-photo-id'),
                                photos = __photo_stack_data[photo_id],
                                photo_container_width = $.photos.options.photo_container.width();
                            for (var i = 0, n = photos.length; i < n; ++i) {
                                var photo = photos[i],
                                    real_size = getRealSizesOfThumb(photo, size_info);
                                if (typeof real_size !== 'object' || !real_size || real_size.width > photo_container_width) {
                                    real_size = {
                                        width: photo_container_width, height: ''
                                    };
                                }
                                photo.thumb_custom = {
                                    size: real_size,
                                    bound: size_info,
                                    url: photo.thumb_custom.url.replace('%size%', size)
                                };
                            }
                            this.set(photos);
                        }
                        return this;
                    }
                    // init setting new items
                    stream.init();
                    if (!stream.isEmpty()) {
                        var photo_id = $('#photo').attr('data-photo-id');
                        stream.setCurrentById(photo_id);
                    }
                    
                    return stream;
                })();
                
                if (!this.photo_stack_cache.isEmpty() && !this.photo_stream_cache.getCurrent()) {
                    this.photo_stream_cache.setCurrentById(this.photo_stack_cache.getFirst().id);
                }
                
                // replace first state
                var photo = this.photo_stack_cache.getCurrent();
                if (!photo) {
                    photo = this.photo_stream_cache.getCurrent();
                }
                window.history.replaceState({id: photo.id}, document.title, location.href);
                
                // correct photo size depending on real-container size
                var photo_tag = $('#photo'),
                    photo_width = parseInt(photo_tag.get(0).style.width, 10),  // width of tag
                    photo_container_width = this.options.photo_container.width();
                if (photo_width > photo_container_width) {
                    photo_tag.width(photo_container_width).height('');
                }
                
                //preloading next photo
                $('<img id="preload-photo" src="" data-photo-id="" style="display:none;">').appendTo('body');
                var next_photo = $.photos.photo_stream_cache.getNext();
                if (next_photo) {
                    $.photos.preloadPhoto(next_photo);
                }
            },
            slideBack: function(id) {
                var photo = $.photos.photo_stack_cache.getById(id),
                    photo_stream_photo_id,
                    photo_stream = this.options.photo_stream,
                    stack_stream = this.options.stack_stream;
                // found in stackcache
                if (photo) {
                    photo_stream_photo_id = $.photos.photo_stack_cache.getFirst().id;
                    // move in hidden stackstream
                    stack_stream.find('li.selected').removeClass('selected');
                    stack_stream.find('li[data-photo-id="' + photo.id + '"]').addClass('selected');
                } else {
                    photo = $.photos.photo_stream_cache.getById(id);
                    // found in streamcache
                    if (photo) {
                        photo_stream_photo_id = photo.id;
                    }
                }
                // photo found in some cache
                if (photo_stream_photo_id) {
                    // move in photostream slider
                    photo_stream = this.options.photo_stream;
                    photo_stream.find('li.selected').removeClass('selected');
                    photo_stream.find('li[data-photo-id="' + photo_stream_photo_id + '"]').addClass('selected');
                    photo_stream.trigger('home', false);
                }
            },
            abortPrevLoading: function() {
                if (typeof $.photos.loadPhotoInStack.xhr === 'object' && 
                    typeof $.photos.loadPhotoInStack.xhr.abort === 'function') 
                {
                    $.photos.loadPhotoInStack.xhr.abort(); 
                }
                if (typeof $.photos.loadPhotoCompletly.xhr === 'object' && 
                    typeof $.photos.loadPhotoCompletly.xhr.abort === 'function') 
                {
                    $.photos.loadPhotoCompletly.xhr.abort();
                }
            },
            loadPhoto: function(photo_id, add_history) {
                var photo = $.photos.photo_stack_cache.getById(photo_id);
                if (photo) {
                    $.photos.loadPhotoInStack(photo, add_history); return;
                } else {
                    photo = $.photos.photo_stream_cache.getById(photo_id);
                    if (photo) {
                        $.photos.loadPhotoCompletly(photo, add_history); return;
                    }
                }
                if (!photo) {
                    $.photos.loadNewPhoto(photo_id, add_history);
                }
            },
            loadNewPhoto: function f(photo_id, add_history) {
                $.photos.abortPrevLoading();
                $.photos.beforeLoadPhoto(photo_id);
                f.xhr = $.get('loadPhoto?size=' + this.options.size,
                    function(r) {
                        if (r.status == 'ok') {
                            
                            // correct size of thumb
                            var photo_container_width = $.photos.options.photo_container.width(),
                                real_size = r.data.photo.thumb_custom.size;
                            if (typeof real_size !== 'object' || !real_size || real_size.width > photo_container_width) {
                                real_size = {
                                    width: photo_container_width, height: ''
                                };
                            }
                            r.data.photo.thumb_custom.size = real_size;
                            
                            $.photos.renderViewPhoto(r.data);
                            // there is new stack - refresh cache
                            if (r.data.stack_nav) {
                                $.photos.photo_stack_cache.init();
                            }
                            var photo = r.data.photo,
                                url = $.photos.formFullPhotoUrl(photo.url);

                            if (add_history !== false) {
                                window.history.pushState({id: photo.id}, '', url);
                            }
                            if (!r.data.stack_nav) {
                                photo = $.photos.photo_stream_cache.updateById(photo.id, photo);
                                $.photos.photo_stream_cache.setCurrent(photo);
                                $.photos.photo_stack_cache.clear();
                            } else {
                                photo = $.photos.photo_stack_cache.updateById(photo.id, photo);
                                $.photos.photo_stack_cache.setCurrent(photo);
                                $.photos.photo_stream_cache.setCurrentById($.photos.photo_stack_cache.getFirst().id);
                            }
                            $.photos.setNextPhotoLink();
                            $.photos.afterLoadPhoto(r.data.photo);
                            $.photos.setTitle(r.data.photo.name);
                            
                            // preloading next photo
                            var next_photo = $.photos.photo_stream_cache.getNext();
                            if (next_photo) {
                                $.photos.preloadPhoto(next_photo);
                            }
                        }
                    },
                    'json'
                );
            },
            loadPhotoInStack: function f(photo, add_history) {
                $.photos.abortPrevLoading();
                var url = $.photos.formFullPhotoUrl(photo.private_url || photo.url);
                if (add_history !== false) {
                    window.history.pushState({id: photo.id}, '', url);
                }
                var parent_id = $.photos.photo_stack_cache.getFirst().id;  // parent of stack
                $.photos.photo_stream_cache.setCurrentById(parent_id);
                $.photos.photo_stack_cache.setCurrent(photo);
                
                $.photos.setNextPhotoLink();
                $.photos.setTitle(photo.name);
                var stack_nav = $('#stack-nav'),
                    offset_indicator = stack_nav.find('.offset');
                offset_indicator.text($.photos.photo_stack_cache.getCurrentIndex() + 1);

                $.photos.beforeLoadPhoto(photo.id);
                // at the beginning cover with thumb photo
                if (photo.thumb_custom.size !== null) {
                    $('#photo').width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
                }
                replaceImg(
                    $('#photo'), 
                    photo.thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                    function() {
                        if ($.Retina) {
                            $(this).retina();
                        }
                    }
                );
                // than load rest part of photo's info 
                f.xhr = $.get('loadPhoto',
                    function(r) {
                        if (r.status == 'ok') { 
                            // update photo info in cache and render page
                            r.data.photo = $.photos.photo_stack_cache.updateById(photo.id, r.data.photo);
                            $.photos.renderViewPhoto(r.data);
                            $.photos.afterLoadPhoto(r.data.photo);
                            delete f.xhr;
                        }
                    },
                'json');
            },
            loadPhotoCompletly: function f(photo, add_history) {
                $.photos.abortPrevLoading();
                var url = $.photos.formFullPhotoUrl(photo.url);
                if (add_history !== false) {
                    window.history.pushState({id: photo.id}, '', url);
                }
                $.photos.photo_stream_cache.setCurrent(photo);
                $.photos.setNextPhotoLink();
                $.photos.setTitle(photo.name);
                $('#stack-nav').hide();
                $.photos.photo_stack_cache.clear();

                $.photos.beforeLoadPhoto(photo.id);
                
                var is_preloaded = $.photos.isPhotoPreloaded(photo);
                if (is_preloaded) {
                    $.photos.renderPhotoImg(photo);
                } else {
                    if (photo.thumb_custom.size && typeof photo.thumb_custom.size === 'object') {
                        $('#photo').width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
                    }
                    // cover with thumb photo
                    replaceImg(
                        $('#photo'), 
                        photo.thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                        function() {
                            if ($.Retina) {
                                $(this).retina();
                            }
                        }
                    );
                }
                

                // than load rest part of photo's info 
                f.xhr = $.get('loadPhoto',
                    function(r) {
                        if (r.status == 'ok') { 
                            // update photo info in cache and render page
                            r.data.photo = $.photos.photo_stream_cache.updateById(photo.id, r.data.photo);
                            $.photos.renderViewPhoto(r.data, is_preloaded);
                            // there is new stack - refresh cache
                            if (r.data.stack_nav) {
                                $.photos.photo_stack_cache.init();
                            }
                            $.photos.afterLoadPhoto(r.data.photo);
                            delete f.xhr;
                        }
                    },
                'json');
                
                // preloading next photo
                var next_photo = $.photos.photo_stream_cache.getNext();
                if (next_photo) {
                    $.photos.preloadPhoto(next_photo);
                }
            },
            beforeLoadPhoto: function(photo_id) {
                $(window).trigger('photos.changeurl', [location.href]);
            },
            afterLoadPhoto: function(photo) {
                if (slide_back_needed) {
                    $.photos.slideBack(photo.id);
                }
            },
            renderViewPhoto: function(data, is_preloaded) {
                var photo = data.photo;
                
                is_preloaded = typeof is_preloaded === 'undefined' ? false : is_preloaded;
                if (!is_preloaded) {
                    $.photos.renderPhotoImg(photo);
                }
                $('#photo').attr('data-photo-id', photo.id);
                
                $('#photo-name').html(photo.name);
                $('#photo-description').html(photo.description);

                // insert rendered html-blocks: albums, tags, author info, exif
                var photo_exif = $('#photo-exif');
                if (!data.exif) {
                    photo_exif.hide();
                } else {
                    photo_exif.html(data.exif).show();
                    $.photos.renderMap();
                }
                var photo_albums = $('#photo-albums');
                if (!data.albums) {
                    photo_albums.hide();
                } else {
                    photo_albums.find('span.photo-info').html(data.albums).end().show();
                }
                var photo_tags = $('#photo-tags');
                if (!data.tags) {
                    photo_tags.hide();
                } else {
                    photo_tags.find('span.photo-info').html(data.tags).end().show();
                }
                var photo_author = $('#photo-author');
                if (!data.author) {
                    photo_author.hide();
                } else {
                    photo_author.find('span.photo-info').html(data.author);
                }
                var stack_nav = $('#stack-nav');
                if (!data.stack_nav) {
                    stack_nav.hide();
                    $('.photo').removeClass('stack');
                } else {
                    stack_nav.html(data.stack_nav).show();
                    $('.photo').addClass('stack');
                }
                // hooks' render html results
                var html = '';
                if (data.frontend_photo) {
                    var frontend_photo = data.frontend_photo;
                    for (var plugin_id in frontend_photo) {
                       if (frontend_photo[plugin_id].bottom) {
                           html += frontend_photo[plugin_id].bottom;
                       }
                    }
                }
                if (!html) {
                    $('#photo-hook-bottom').hide();
                } else {
                    $('#photo-hook-bottom').html(html).show();
                }
                
                // hooks' render top left results
                html = '';
                if (data.frontend_photo) {
                    frontend_photo = data.frontend_photo;
                    for (var plugin_id in frontend_photo) {
                       if (frontend_photo[plugin_id].top_left) {
                           html += frontend_photo[plugin_id].top_left;
                       }
                    }
                }
                if (!html) {
                    $('.corner.top.left').hide();
                } else {
                    $('.corner.top.left').html(html).show();
                }
                
                // hooks' render sidebar
                html = '';
                if (data.frontend_photo) {
                    frontend_photo = data.frontend_photo;
                    for (var plugin_id in frontend_photo) {
                       if (frontend_photo[plugin_id].sidebar) {
                           html += frontend_photo[plugin_id].sidebar;
                       }
                    }
                }
                if (!html) {
                    $('#photo-hook-sidebar').hide();
                } else {
                    $('#photo-hook-sidebar').html(html).show();
                }
            },
            setNextPhotoLink: function() {
                // set a-link to next
                var a = $('#photo').parents('a:first'),
                    next = $.photos.photo_stream_cache.getNext();
                if (next) {
                    a.attr('title', this.options.next_link_title);
                    a.attr('href', location.href.replace(/\/[^\/]+(\/)*$/, '/' + next.url + '/'));
                } else {
                    a.attr('title', '');
                    a.attr('href', 'javascript:void(0);')
                }
                
            },
            onLoadTail: function() {
                $.photos.photo_stream_cache.append(__photo_stream_data);
            },
            onLoadHead: function() {
                $.photos.photo_stream_cache.prepend(__photo_stream_data);
            },
            
            goToNextPhoto: function(try_stack_first) {
                
                var nextInPhotoStream = function() {
                    slide_back_needed = false;
                    var item = this.options.photo_stream;
                    item.trigger('home', [function() {
                        item.trigger('forward', [{
                            steps: 1,
                            selected_stick: true,
                            animate: false,
                            fn: function() {
                                var next_photo = null;
                                if (try_stack_first) {
                                    next_photo = $.photos.photo_stack_cache.getNext();
                                    if (next_photo) {
                                        $.photos.loadPhotoInStack(next_photo);
                                    } else {
                                        next_photo = $.photos.photo_stream_cache.getNext();
                                        if (next_photo) {
                                            $.photos.loadPhotoCompletly(next_photo);
                                        }
                                    }
                                } else {
                                    next_photo = $.photos.photo_stream_cache.getNext();
                                    if (next_photo) {
                                        $.photos.loadPhotoCompletly(next_photo);
                                    }                                
                                }
                            }
                        }]);
                    }, false]);
                };
                
                if (try_stack_first) {
                    var next_photo = $.photos.photo_stack_cache.getNext();
                    if (next_photo) {
                        $.photos.loadPhotoInStack(next_photo);
                    } else {
                        nextInPhotoStream.call(this);
                    }
                } else {
                    nextInPhotoStream.call(this);
                }
                
            },
            goToPrevPhoto: function() {
                var item = this.options.photo_stream;
                slide_back_needed = false;
                item.trigger('home', [function() {
                    item.trigger('backward', [{
                        steps: 1,
                        selected_stick: true,
                        animate: false,
                        fn: function() {
                            var prev_photo = $.photos.photo_stream_cache.getPrev();
                            if (prev_photo) {
                                $.photos.loadPhotoCompletly(prev_photo);
                            }
                        }
                    }]);
                }, false]);
            },
            formFullPhotoUrl: function(photo_url) {
                return location.href.replace(/\/[^\/]+(\/)*$/, '/' + photo_url + '/');
            },
            preloadPhoto: function(photo) {
                var preload_photo_img = $('#preload-photo');
                preload_photo_img.attr('data-photo-id', '');
                replaceImg(
                    preload_photo_img, 
                    photo.thumb_custom.url, 
                    function() {
                        preload_photo_img.attr('data-photo-id', photo.id);
                        if ($.Retina) {
                            $(this).retina();
                        }
                    }
                );
            },
            isPhotoPreloaded: function(photo) {
                return $('#preload-photo').attr('data-photo-id') == photo.id;
            },
            renderPhotoImg: function(photo) {
                replaceImg(
                    $('#photo'),
                    photo.thumb_custom.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                    function() {
                        if (photo.thumb_custom.size && typeof photo.thumb_custom.size === 'object') {
                            $(this).width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
                        }
                        if ($.Retina) {
                            $(this).retina();
                        }
                    }
                );
            }
        });
        // click to photo for next
        $('#photo').parents('a:first').click(function() {
            
            $.photos.goToNextPhoto(true);
            return false;
        });

        // click to photo in photo-stream
        var photo_stream = $('#photo-stream');
        photo_stream.find('ul.photostream li').live('click', function() {
            var self = $(this);
            if (self.hasClass('dummy')) {
                return false;
            } 
            photo_stream.find('li.selected').removeClass('selected');
            self.addClass('selected');
            slide_back_needed = false;
            var photo_id = self.attr('data-photo-id');
            photo_stream.trigger('home', function() {
                var photo = $.photos.photo_stream_cache.getById(photo_id);
                $.photos.loadPhotoCompletly(photo);
            });
            return false;
        });
        
        if ($.Retina) {
            photo_stream.find('ul.photostream li:not(.dummy) img').retina();
        }

        // click to arrows in stack-navigation parenl
        var stack_nav = $('#stack-nav');
        stack_nav.find('.rewind,.ff').live('click', function(e) {
            var self = $(this);
            if (self.hasClass('ff')) {
                var next_photo = $.photos.photo_stack_cache.getNext();
                if (next_photo) {
                    $.photos.loadPhotoInStack(next_photo);
                }
            } else if (self.hasClass('rewind')) {
                var prev_photo = $.photos.photo_stack_cache.getPrev();
                if (prev_photo) {
                    $.photos.loadPhotoInStack(prev_photo);
                }
            }
            return false;
        });
    } // is_history_api_supported

    $.photos.init({
        size: $('#photo').attr('data-size'),
        photo_container: $('.image'),
        photo_stream: $('#photo-stream'),
        stack_stream: $('#stack-nav ul.photostream'),
        next_link_title: $('#photo').parents('a:first').attr('title')
    });
});