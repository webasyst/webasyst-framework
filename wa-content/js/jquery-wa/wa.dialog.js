jQuery.fn.waDialog = function (options) {
    options = jQuery.extend({
        loading_header: '',
        title: '',
        esc: true,
        buttons: null,
        url: null,
        url_reload: true,
        'class': null, // className is a synonym
        content: null,
        'width': 0,
        'height': 0,
        'min-width': 0,
        'min-height': 0,
        offsetTop: null,
        offsetLeft: null,
        disableButtonsOnSubmit: false,
        onLoad: null,
        onCancel: null,
        onSubmit: null
    }, options || {});

    var d = $(this);

    var id = d.attr('id');
    if (id && !d.hasClass('dialog')) {
        d.removeAttr('id');
        if ($("#" + id).length) {
            if (options.url) {
                d = $("#" + id);
                if (!options.url_reload) {
                    options.url = null;
                }
            } else {
                $("#" + id).remove();
            }
        }
    }

    var cl = (options['class'] || options['className']) ? (options['class'] || options['className']) : (d.attr('class') || '');

    if (!d.hasClass('dialog')) {
        var content = $(this);
        var d = $('<div ' + (id ? 'id = "' + id + '"' : '') + ' class="dialog ' + cl + '" style="display: none">'+
                    '<div class="dialog-background"></div>'+
                    '<div class="dialog-window"></div>'+
              '</div>').appendTo('body');
        if (content.find('.dialog-content').length || content.find('.dialog-buttons').length) {
            $('.dialog-window', d).append(content.show());
            var dc = content.find('.dialog-content');
            if (dc.length) {
                var tmp = $('<div class="dialog-content-indent"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
            dc = content.find('.dialog-buttons');
            if (dc.length) {
                var tmp = $('<div class="dialog-buttons-gradient"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
        } else {
            $('.dialog-window', d).append(
                    (options.onSubmit ? '<form method="post" action="">' : '') +
                    '<div class="dialog-content">'+
                        '<div class="dialog-content-indent">'+
                            // content goes here
                        '</div>'+
                    '</div>'+
                    '<div class="dialog-buttons">'+
                        '<div class="dialog-buttons-gradient">'+
                            // buttons go here
                        '</div>'+
                    '</div>'+
                    (options.onSubmit ? '</form>' : '')
            );
            d.find('.dialog-content-indent').append(content.show());
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
        if (options.url) {
            d.find('.dialog-content-indent').append('<h1>'+(options.loading_header || '')+'<i class="icon16 loading"></i></h1>');
        } else if (options.content) {
            d.find('.dialog-content-indent').append(options.content);
        }
        if (options.title) {
            d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
        }
    } else {
        if (options.content) {
            d.find('.dialog-content-indent').html(options.content);
            if (options.title) {
                d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
            }
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
    }

    if (!d.find('.dialog-background').length) {
        d.prepend('<div class="dialog-background"> </div>');
    }

    d.unbind('close').bind('close', function () {
        if (options.onClose) {
            options.onClose.call($(this));
        }
        $(this).hide();
    });

    var css = ['width', 'height', 'min-width', 'min-height'];
    for (var k = 0; k < css.length; k++) {
        if (options[css[k]]) {
            if ((css[k] == 'height' && options[css[k]] < '300px') || (css[k] == 'width' && options[css[k]] < '400px')) {
                d.find('div.dialog-window').css('min-' + css[k], options[css[k]]);
            }
            d.find('div.dialog-window').css(css[k], options[css[k]]);
        }
    }

    if (options.disableButtonsOnSubmit) {
        d.find("input[type=submit]").removeAttr('disabled');
    }

    if (!d.parent().length) {
        d.appendTo('body');
    }


    d.show();

    if (options.url) {
        jQuery.get(options.url, function (response) {
            var el = $(response);
            if (el.find('.dialog-content').length || el.find('.dialog-buttons').length) {
                if (el.find('.dialog-content').length) {
                    d.find('.dialog-content-indent').empty().append(el.find('.dialog-content').contents());
                }
                if (el.find('.dialog-buttons').length) {
                    d.find('.dialog-buttons-gradient').empty().append(el.find('.dialog-buttons').contents());
                }
            } else {
                d.find('.dialog-content-indent').html(response);
            }
            d.trigger('wa-resize');
            if (options.onLoad) {
                options.onLoad.call(d.get(0));
            }
        });
    } else {
        if (options.onLoad) {
            options.onLoad.call(d.get(0));
        }
    }

    d.find('.dialog-buttons').delegate('.cancel', 'click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (options.onCancel) {
            options.onCancel.call(d.get(0));
        }
        d.trigger('close');
        return false;
    });


    if (options.onSubmit) {
        d.find('form').unbind('submit').submit(function () {
            if (options.disableButtonsOnSubmit) {
                d.find("input[type=submit]").attr('disabled', 'disabled');
            }
            return options.onSubmit.apply(this, [d]);
        });
    }

    d.unbind('wa-resize').bind('wa-resize', function () {
        var el = jQuery(this).find('.dialog-window');
        var dw = el.width();
        var dh = el.height();

        jQuery("body").css('min-height', dh+'px');

        var ww = jQuery(window).width();
        var wh = jQuery(window).height()-60;

        //centralize dialog
        var w = (ww-dw)/2 / ww;
        var h = (wh-dh-60)/2 / wh; //60px is the height of .dialog-buttons div
        if (h < 0) h = 0;
        if (w < 0) w = 0;

        el.css({
            'left': options.offsetLeft || (Math.round(w*100)+'%'),
            'top': options.offsetTop || (Math.round(h*100)+'%')
        });
    }).trigger('wa-resize');

    if (options.esc) {
        d.unbind('esc').bind('esc', function () {
            d.trigger('close');
        });
    }
    return d;
}

jQuery(window).resize(function () {
    jQuery(".dialog:visible").trigger('wa-resize');
});

jQuery(document).keyup(function(e) {
    //all dialogs should be closed when Escape is pressed
    if (e.keyCode == 27) {
        jQuery(".dialog:visible").trigger('esc');
    }
});