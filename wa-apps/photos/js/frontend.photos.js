$(function() {
    // photo-list page
    $.photos = $.extend($.photos || {}, {
        photo_stack_cache_table: {},
        current_photo: null,
        options: {},
        init: function(options) {
            this.options = options || {};
            this.onLoadData();
            $('.rewind,.ff').live('click', function(e) {
                var self = $(this);
                if (self.hasClass('ff')) {
                    $.photos.shiftPhotoInStack('next', self.parents('.stack-nav'));
                } else if (self.hasClass('rewind')) {
                    $.photos.shiftPhotoInStack('prev', self.parents('.stack-nav'));
                }
                return false;
            });
        },
        setLazyLoad: function(options) {
            var offset = $('#photo-list img.photo_img').length;
            if (!options.total_count || offset >= options.total_count) {
                return;
            }
            options.auto = typeof options.auto === 'undefined' ? true : options.auto;
            $(window).lazyLoad({
                container: '#photo-list',
                state: options.auto ? 'wake' : 'stop',
                load: function() {
                    $(window).lazyLoad('sleep');
                    $(".lazyloading-wrapper .lazyloading-progress").show();
                    $(".lazyloading-wrapper .lazyloading-link").hide();
                    $.get(
                        '?lazy&offset='+offset,
                        function (html) {
                            html = $('<div></div>').html(html);
                            var list = html.find('#photo-list');
                            var length = $('img.photo_img', list).length;
                            $('.lazyloading-wrapper').html(html.find('.lazyloading-wrapper:first').html());
                            if (!length) {
                                stop();
                                return;
                            }
                            
                            if ($.Retina) {
                                list.find('#photo-list img').retina();
                            }
                            
                            $('#photo-list').append(list.html());
                            $('#photo-list').trigger('append_photo_list');
                            $('.lazyloading-wrapper').show();

                            $.photos.onLoadData();
                            offset += length;
                            if (offset >= options.total_count) {
                                stop();
                                return;
                            }
                            $(window).lazyLoad('wake');
                        },
                        "html"
                    );
                }
            });
            
            function stop() {
                $(window).lazyLoad('stop');
                $(".lazyloading-wrapper .lazyloading-progress").hide();
                $(".lazyloading-wrapper .lazyloading-link").hide();
            }
            $('.lazyloading-wrapper a.lazyloading-link').die('click.lazyloading').live('click.lazyloading', function() {
                $(window).lazyLoad('force');
                return false;
            });
            $('.lazyloading-paging').hide();
            $('.lazyloading-wrapper').show();
        },
        onLoadData: function() {
            if (typeof __photo_stack_data === 'object') {
                for (var photo_id in __photo_stack_data) {
                    if (__photo_stack_data.hasOwnProperty(photo_id)) {
                        var stream = new PhotoStream(),
                            photos = __photo_stack_data[photo_id],
                            size = $('#photo-list img.photo_img[data-photo-id='+photo_id+']').attr('data-size'),
                            size_info = parseSize(size);
                        for (var i = 0, n = photos.length; i < n; ++i) {
                            var photo = photos[i];
                            photo.thumb_custom = {
                                size: getRealSizesOfThumb(photo, size_info),
                                bound: size_info,
                                url: photo.thumb_custom.url.replace('%size%', size)
                            };
                        }
                        stream.append(photos).setCurrentById(photo_id);
                        this.photo_stack_cache_table[photo_id] = stream;
                        delete __photo_stack_data[photo_id];
                    }
                }
            }
            $(window).trigger('photosLoaded');
        },

        shiftPhotoInStack: function f(toward, stack_nav) {

            toward = toward || 'next';

            var photo_id = stack_nav.attr('data-photo-id'),
                photo_img = $('#photo-list img.photo_img[data-photo-id='+photo_id+']'),
                one_photo_link_tag = photo_img.parents('a:first'),
                stack_cache = $.photos.photo_stack_cache_table[photo_id],
                size = photo_img.attr('data-size'),
                photo,
                one_photo_page_url;
           
            if (one_photo_link_tag.length) {
                one_photo_page_url = one_photo_link_tag.attr('href');
            } else if (console) {
                console.log('link to one photo page is undefined');
            }

            if (typeof stack_cache === 'undefined' && console) {
                console.log('Stack cache is empty for photo_id=' + photo_id);
                return;
            }


            if (toward == 'next') {
                photo = stack_cache.getNext();
                var next = stack_cache.getNext(photo);
                stack_nav.find('.ff').attr('href', next ? next.full_url : 'javascript:void(0);');
            } else {
                photo = stack_cache.getPrev();
                var prev = stack_cache.getPrev(prev);
                stack_nav.find('.rewind').attr('href', prev ? prev.full_url : 'javascript:void(0);');
            }

            if (photo) {
                $.photos.current_photo = photo;
                if (one_photo_page_url) {
                    one_photo_link_tag.attr('href', one_photo_page_url.replace(/\/[^\/]+(\/)*$/, '/' + photo.url + '/'));
                }
                stack_cache.setCurrent(photo);
                if (!photo_img.attr('id')) {
                    photo_img.attr('id', 'photo-' + photo.id);
                }
                $.photos.loadPhoto({
                    photo: photo,
                    size: size,
                    photo_img: photo_img,
                    stack_nav: stack_nav,
                    stack_cache: stack_cache
                });
            }
        },
        
        loadPhoto: function f(options) {
            var photo = options.photo,
                size = options.size,
                photo_img = options.photo_img,
                stack_cache = options.stack_cache,
                stack_nav = options.stack_nav;
            if (typeof f.xhr === 'object' && typeof f.xhr.abort === 'function')
            {
                if(console) {
                    console.log('abort');
                }
                f.xhr.abort();
            }
            // correcting offset label value
            stack_nav.find('.offset').text(stack_cache.getCurrentIndex() + 1);
            
            if (photo.thumb_custom.size !== null) {
                photo_img.width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
            }
            replaceImg(
                photo_img,
                photo.thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                function() {
                    if ($.Retina) {
                        $(this).retina();
                    }
                }
            );
            
            var url = photo.full_url + 'loadPhoto?size=' + size + '&mini=1';
            f.xhr = $.get(url,
                function(r) {
                    if (r.status == 'ok') {
                        var photo = r.data.photo;
                        photo = stack_cache.updateById(photo.id, photo);
                        stack_cache.setCurrent(photo);
                        if (photo.thumb_custom.size !== null) {
                            photo_img.width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
                        }
                        replaceImg(
                            photo_img,
                            photo.thumb_custom.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                            function() {
                                if ($.Retina) {
                                    $(this).retina();
                                }
                            }
                        );
                    }
                    delete f.xhr;
                },
            'json');
        }
    });
    $.photos.init();
});