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
            var offset = $('#photo-list img.photo').length;
            if (!options.total_count || offset >= options.total_count) {
                return;
            }
            $(window).lazyLoad({
                container: '#photo-list',
                load: function() {
                    $(window).lazyLoad('sleep');
                    $(".lazyloading-wrapper .lazyloading-progress").show();
                    $(".lazyloading-wrapper .lazyloading-link").hide();
                    $.get(
                        '?lazy&offset='+offset,
                        function (html) {
                            html = $('<div></div>').html(html);
                            var list = html.find('#photo-list');
                            var length = $('img.photo', list).length;
                            $('.lazyloading-wrapper').html(html.find('.lazyloading-wrapper:first').html());
                            if (!length) {
                                stop();
                                return;
                            }
                            $('#photo-list').append(list.html());
                            $('.lazyloading-wrapper').show();

                            offset += length;
                            if (offset >= options.total_count) {
                                stop();
                                return;
                            }
                            $.photos.onLoadData();
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
            $('.lazyloading-wrapper a.lazyloading-link').die('click.lazyloading').live('click.lazyloading',function(){
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
                            size = $('#photo-list img.photo[data-photo-id='+photo_id+']').attr('data-size'),
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
        },

        shiftPhotoInStack: function f(toward, stack_nav) {
            if (typeof f.xhr === 'object' && typeof f.xhr.abort === 'function')
            {
                if(console) {
                    console.log('abort');
                }
                f.xhr.abort();
            }
            toward = toward || 'next';

            var photo_id = stack_nav.attr('data-photo-id'),
                photo_img = $('#photo-list img.photo[data-photo-id='+photo_id+']'),
                one_photo_link_tag = photo_img.parents('a:first'),
                one_photo_page_url = one_photo_link_tag.attr('href'),
                stack_cache = $.photos.photo_stack_cache_table[photo_id],
                size = photo_img.attr('data-size'),
                photo;

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
                one_photo_link_tag.attr('href', one_photo_page_url.replace(/\/[^\/]+(\/)*$/, '/' + photo.url + '/'));
                $.photos.current_photo = photo;
                if (photo.thumb_custom.size !== null) {
                    photo_img.width(photo.thumb_custom.size.width).height(photo.thumb_custom.size.height);
                }
                replaceImg(
                    photo_img,
                    photo.thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                    null
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
                                photo.thumb_custom.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '')
                            );
                        }
                        delete f.xhr;
                    },
                'json');

                // correcting offset label value
                var offset_label = stack_nav.find('.offset'),
                    offset = parseInt(offset_label.text());
                offset = toward == 'next' ? offset + 1 : offset - 1;
                offset_label.text(offset);
            }
        }
    });
    $.photos.init();
});