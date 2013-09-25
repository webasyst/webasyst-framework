$(function () {
    $(window).resize(function() {
        var i = parseInt(($('#wa-applist ul').width() - 1) / 75);
        if (i-- < $('#wa-applist li[id!=""]').length) {
            if ($("#wa-moreapps i").hasClass('darr') && $('#wa-applist li:eq('+i+')').attr('id')) {
                $('#wa-moreapps').show().parent().insertAfter($('#wa-applist li[id!=""]:eq(' + (i - 1) + ')'));
            }
        } else if ($('#wa-applist li:last').attr('id')) {
            $('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
        } else {
            if ($('#wa-moreapps i').hasClass('uarr')) {
                $('#wa-header').css('height', '83px');
                $('#wa-moreapps i').removeClass('uarr').addClass('darr');
            }
            $('#wa-moreapps').hide();
        }

        /*
        if ($("#wa-applist ul>li").length * 75 > $('#wa-applist').width()) {
            $('#wa-moreapps').show();
        } else {
            $('#wa-moreapps').hide();
        }
        */
    }).resize();

    var sortableApps = function () {
        $("#wa-applist ul").sortable({
            distance: 5,
            helper: 'clone',
            items: 'li[id!=""]',
            opacity: 0.75,
            tolerance: 'pointer',
            stop: function () {
            var data = $(this).sortable("toArray");
            var apps = [];
            for (var i = 0; i < data.length; i++) {
                var id = data[i].replace(/wa-app-/, '');
                if (id) {
                    apps.push(id);
                }
            }
            var url = backend_url + "?module=settings&action=save";
            $.post(url, {name: 'apps', value: apps});
        }});
    };

    if ($("#wa-applist ul").sortable) {
        sortableApps();
    } else {
        var urls = [];
        if (!$.ui) {
            urls.push('jquery.ui.core.min.js');
            urls.push('jquery.ui.widget.min.js');
            urls.push('jquery.ui.mouse.min.js');
        } else if (!$.ui.mouse) {
            urls.push('jquery.ui.mouse.min.js');
        }
        var path = $("#wa-header-js").attr('src').replace(/jquery-wa\/wa.header.js.*$/, 'jquery-ui/');
        var before = $("#wa-header-js").next();
        for (var i = 0; i < urls.length; i++) {
            $("#wa-header-js").clone().removeAttr('id').attr('src', path+urls[i]).insertBefore(before);
        }
        $.getScript(path + 'jquery.ui.sortable.min.js', function () {
            sortableApps();
        });

    }

    var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
    $(window).on("load", function() {
        if (pixelRatio > 1) {
            $('#wa-applist img').each(function() {
                if ($(this).data('src2')) {
                    $(this).attr('src', $(this).data('src2'));
                }
            });
        }
    });

    $('#wa-moreapps').click(function() {
        var i = $(this).children('i');
        if (i.hasClass('darr')) {
            if ($('#wa-applist li:last').attr('id')) {
                $('#wa-moreapps').parent().insertAfter($('#wa-applist li:last'));
            }
            $('#wa-header').css('height', 'auto');
            i.removeClass('darr').addClass('uarr');
        } else {
            $('#wa-header').css('height', '83px');
            i.removeClass('uarr').addClass('darr');
            $(window).resize();
        }
        return false;
    });

    $("a.wa-announcement-close", $('#wa')[0]).live('click', function () {
        var app_id = $(this).attr('rel');
        $(this).next('p').remove();
        $(this).remove();
        var url = backend_url + "?module=settings&action=save";
        $.post(url, {app_id: app_id, name: 'announcement_close', value: 'now()'});
        return false;
    });

    var updateCount = function () {
        $.ajax({
            url: backend_url + "?action=count",
            data: {'background_process': 1},
            success: function (response) {
                if (response && response.status == 'ok') {
                    // announcements
                    if (response.data.__announce) {
                        $('#wa-announcement').remove();
                        $('#wa-header').before(response.data.__announce);
                        delete response.data.__announce;
                    }

                    // applications
                    $('#wa-header a span.indicator').hide();
                    for (var app_id in response.data) {
                        var n = response.data[app_id];
                        if (n) {
                            var a = $("#wa-app-" + app_id + " a");
                            if (typeof(n) == 'object') {
                                a.attr('href', n.url);
                                n = n.count;
                            }
                            if (a.find('span.indicator').length) {
                                    if(n) {
                                        a.find('span.indicator').html(n).show();
                                    } else {
                                        a.find('span.indicator').remove();
                                    }
                            } else if(n) {
                                a.append('<span class="indicator">' + n + '</span>');
                            }
                        } else {
                            $("#wa-app-" + app_id + " a span.indicator").remove();
                        }
                    }
                    $(document).trigger('wa.appcount', response.data);
                }
                setTimeout(updateCount, 60000);
            },
            error: function () {
                setTimeout(updateCount, 60000);
            },
            dataType: "json"
        });
    };

    // update counts immidiately if there are no cached counts; otherwise, update later
    if (!$('#wa-applist').is('.counts-cached')) {
        updateCount();
    } else {
        setTimeout(updateCount, 60000);
    }
});