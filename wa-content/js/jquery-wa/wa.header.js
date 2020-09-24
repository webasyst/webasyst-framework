$(function () {

    $(window).resize(function() {
        var list_width = $('#wa-applist ul').width() - 1,
            icon_width = 75, // 72px + space symbol
            icons_count = $('#wa-applist li[id]').length,
            max_icons = parseInt(list_width / icon_width);

        if (max_icons-- < icons_count) {
            if ( !$("#wa-moreapps").hasClass('uarr') && $('#wa-applist li:eq('+ max_icons +')').attr('id')) {
                if ($('#wa-applist li[id]:eq(' + (max_icons - 1) + ')').length) {
                    $('#wa-moreapps').show().parent().insertAfter($('#wa-applist li[id]:eq(' + (max_icons - 1) + ')'));
                } else {
                    $('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
                }
            }
        } else if ($('#wa-applist li:last').attr('id')) {
            $('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
        } else {
            if ($('#wa-moreapps').hasClass('uarr')) {
                $('#wa-header').css('height', '83px');
                $('#wa-moreapps').removeClass('uarr');
            }
            $('#wa-moreapps').hide();
        }
    }).resize();

    var sortableApps = function () {
        $("#wa-applist ul").sortable({
            distance: 5,
            helper: 'clone',
            items: 'li[id]',
            opacity: 0.75,
            tolerance: 'pointer',
            stop: function () {
            var data = $(this).sortable("toArray", {attribute: 'data-app'});
            var apps = [];
            for (var i = 0; i < data.length; i++) {
                var id = $.trim(data[i]);
                if (id) {
                    apps.push(id);
                }
            }
            var url = backend_url + "?module=settings&action=save";
            $.post(url, {name: 'apps', value: apps});
        }});
    };

    if ($.fn.sortable) {
        sortableApps();
    } else if (!$('#wa').hasClass('disable-sortable-header')) {

        var urls = [];
        if (!$.browser) {
            urls.push('wa-content/js/jquery/jquery-migrate-1.2.1.min.js');
        }
        if (!$.ui) {
            urls.push('wa-content/js/jquery-ui/jquery.ui.core.min.js');
            urls.push('wa-content/js/jquery-ui/jquery.ui.widget.min.js');
            urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
        } else if (!$.ui.mouse) {
            urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
        }
        urls.push('wa-content/js/jquery-ui/jquery.ui.sortable.min.js');

        var $script = $("#wa-header-js");
        var path = $script.attr('src').replace(/wa-content\/js\/jquery-wa\/wa.header.js.*$/, '');
        $.when.apply($, $.map(urls, function(file) {
            return $.ajax({
                cache: true,
                dataType: "script",
                url: path + file
            });
        })).done(sortableApps);

        // Determine user timezone when "Timezone: Auto" is saved in profile
        if ($script.data('determine-timezone') && !document.cookie.match(/\btz=/)) {
            var version = $script.attr('src').split('?', 2)[1];
            $.ajax({
                cache: true,
                dataType: "script",
                url: path + "wa-content/js/jquery-wa/wa.core.js?" + version,
                success: function() {
                    $.wa.determineTimezone(path);
                }
            });
        }
    }

/*
    $('#wa-header').on('mousemove', function () {
        if ($('#wa-moreapps').is(':visible') && !$('#wa-moreapps').hasClass('uarr')) {
            var self = this;
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            this.timeout = setTimeout(function () {
                if (!$('#wa-moreapps').hasClass('uarr')) {
                    $('#wa-moreapps').click();
                }
                self.timeout = null;
            }, 2000);
        }
    }).on('blur', function () {
        if (this.timeout) {
            clearTimeout(this.timeout)
            this.timeout = null;
        }
    }).on('mouseleave', function () {
        if (this.timeout) {
            clearTimeout(this.timeout)
            this.timeout = null;
        }
    });
*/

    // Webasyst ID auth announcement :: click on auth link

    // Bind contact with Webasyst ID contact
    var bindWithWebasystID = function(href, oauth_modal) {
        if (!oauth_modal) {
            var referrer_url = window.location.href;
            window.location = href + '&referrer_url=' + referrer_url;
            return;
        }
        var width = 600;
        var height = 500;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        window.open(href,'oauth', "width=" + 600 + ",height=" + height + ",left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
    };

    $('.js-webasyst-id-connect-announcement .js-webasyst-id-connect').on('click', function (e) {
        e.preventDefault();
        var in_webasyst_settings_page = location.href.indexOf(webasyst_id_settings_url) !== -1;
        if (!in_webasyst_settings_page) {
            location.href = webasyst_id_settings_url;
        }
    });

    $('.js-webasyst-id-auth-announcement .js-webasyst-id-auth').on('click', function (e) {
        e.preventDefault();
        bindWithWebasystID($(this).attr('href'));
    });

    var showWebasystIDHelp = function() {
        var help_url = backend_url + "?module=backend&action=webasystIDHelp",
            is_now_in_settings_page = (location.pathname || '').indexOf('webasyst/settings/waid/') !== -1;

        if (is_now_in_settings_page) {
            help_url += '&caller=webasystSettings'
        }

        $.get(help_url, function (html) {
            $('body').append(html);
        });
    };

    $('.js-webasyst-id-connect-announcement .js-webasyst-id-helplink').on('click', function (e) {
        e.preventDefault();
        showWebasystIDHelp();
    });

    $('.js-webasyst-id-auth-announcement .js-webasyst-id-helplink').on('click', function (e) {
        e.preventDefault();
        showWebasystIDHelp();
    });

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
        if ($(this).hasClass('uarr'))
        {
            $('#wa-header').css('height', '83px');
            $('#wa-moreapps').removeClass('uarr');
            $('#wa-header').removeClass('wa-moreapps');
            $(window).resize();
        } else {
            if ($('#wa-applist li:last').attr('id')) {
                $('#wa-moreapps').parent().insertAfter($('#wa-applist li:last'));
            }
            $('#wa-header').css('height', 'auto');
            $('#wa-moreapps').addClass('uarr');
            $('#wa-header').addClass('wa-moreapps');
        }
        return false;
    });

    $('#wa').on('click', 'a.wa-announcement-close', function (e) {
        e.preventDefault();

        var $link = $(this),
            name = $link.data('name') || 'announcement_close',
            app_id = $link.attr('rel');

        if ($link.closest('.d-notification-block').length) {
            $link.closest('.d-notification-block').remove();
            if (!$('.d-notification-wrapper').children().length) {
                $('.d-notification-wrapper').hide();
            }
        } else {
            $link.next('p').remove();
            $link.remove();
        }

        var url = backend_url + "?module=settings&action=save";
        $.post(url, {app_id: app_id, name: name, value: 'now()'});

        return false;
    });

    var is_idle = true;

    $(document).on("mousemove keyup scroll", function() {
        is_idle = false;
    });

    document.addEventListener("touchmove", function () {
        is_idle = false;
    }, false);

    var updateCount = function() {

        var data = {
            background_process: 1
        };

        if (is_idle) {
            data.idle = "true";
        } else {
            is_idle = true;
        }

        $.ajax({
            url: backend_url + "?action=count",
            data: data,
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
                            var a = $('#wa-applist li[data-app="'+ app_id +'"] a');
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
                            $('#wa-applist li[data-app="'+ app_id +'"] a span.indicator').remove();
                        }
                    }
                    $(document).trigger('wa.appcount', response.data);
                }
                setTimeout(updateCount, 60000);
            },
            error: function () {
                setTimeout(updateCount, 60000);
            },
            dataType: "json",
            async: true
        });
    };

    // update counts immidiately if there are no cached counts; otherwise, update later
    if (!$('#wa-applist').is('.counts-cached')) {
        updateCount();
    } else {
        setTimeout(updateCount, 60000);
    }
});
