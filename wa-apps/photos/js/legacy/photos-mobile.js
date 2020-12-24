(function($) {
    $.photos_mobile = {
        photo_list_string: {},
        photos: [],
        init: function() {
            $.photos_mobile.photosAction();
            $('#p-upload-link').live('click', function() {
                $('#fileupload input').trigger('click');
                return false;
            });
            $('#fileupload input').live('change', function() {
                $('#fileupload').submit();

                $('#fileupload-iframe').load(function() {
                    // log action
                    $.get('?module=backend&action=log&action_to_log=photos_upload');

                    var hash = location.hash.replace(/^[^#]*#\/*/, ''); /* fix syntax highlight*/
                    if (hash == '') {
                        location.reload();
                    } else {
                        location.hash = '';
                    }
                });
                return false;
            });
        },
        photosAction: function() {
            $.photos_mobile.load("?module=photo&action=list", $.photos_mobile.onLoadPhotoList);
        },
        onLoadPhotoList: function() {
            $("#photo-list").html(tmpl('template-photo-list', {
                photos: $.photos_mobile.photos
            }));
            $('#wa-app').page('destroy').page();
            $("#photo-list").listview('refresh');
        },
        load: function (url, data, callback) {
            $.mobile.showPageLoadingMsg();
            var target = $('#content');
            if (typeof data == 'function') {
                target.load(url, function() {
                    $.mobile.hidePageLoadingMsg();
                    data();
                });
            } else {
                target.load(url, data, function() {
                    $.mobile.hidePageLoadingMsg();
                    callback();
                });
            }
        },

        setLazyLoad: function(options) {
            var offset = options.count;
            var target = $('#photo-list');
            var total_count = options.total_count;

            var stop = function() {
                $.mobile.hidePageLoadingMsg();
                $(window).lazyLoad('stop');
            }

            if (offset < total_count) {
                $(window).lazyLoad({
                    container: '#photo-list',
                    state: (typeof options.auto === 'undefined' ? true: options.auto) ? 'wake' : 'stop',
                    load: function() {
                        $(window).lazyLoad('sleep');
                        $.mobile.showPageLoadingMsg();
                        $.post(
                            '?module=photo&action=loadList',
                            { offset : offset, hash: '' },
                            function (r) {
                                if (r.status != 'ok') {
                                    if (console) {
                                        console.log('Error', r);
                                    }
                                    stop();
                                    return;
                                }
                                var photos = r.data.photos;
                                if (!photos.length) {
                                    stop();
                                    return;
                                }
                                if (offset >= total_count) {
                                    stop();
                                    return;
                                }
                                offset = offset + photos.length;
                                $("#photo-list").append(tmpl('template-photo-list', {
                                    photos: r.data.photos
                                }));
                                $(window).lazyLoad('wake');
                                $.mobile.hidePageLoadingMsg();
                            },
                            "json"
                        );
                    }
                });
            }
        }
    };
})(jQuery);

Date.parseISO = function (string) {
    var tried = Date.parse(string);
    if (!isNaN(tried)) {
        return tried;
    }
    var regexp = "([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})";
    var d = string.match(new RegExp(regexp));

    var date = new Date(d[1], 0, 1);

    if (d[2]) { date.setMonth(d[2] - 1); }
    if (d[3]) { date.setDate(d[3]); }
    if (d[4]) { date.setHours(d[4]); }
    if (d[5]) { date.setMinutes(d[5]); }
    if (d[6]) { date.setSeconds(d[6]); }

    return +date;
};