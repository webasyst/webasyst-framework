// Pages

var SettingsPage = ( function($) {

    SettingsPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$calendarToggle = that.$wrapper.find("#t-calendar-settings");
        that.$form = that.$wrapper.find("form");
        that.$submitButton = $(document).find('.bottombar [type="submit"]');
        // VARS
        that.locales = options["locales"];

        // DYNAMIC VARS
        that.$notice = false;
        that.is_locked = false;
        that.is_form_changed = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    SettingsPage.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        that.initSortable()
    };

    SettingsPage.prototype.bindEvents = function() {
        var that = this;

        that.$calendarToggle.on("click", ".js-edit-calendar", function(event) {
            event.preventDefault();
            var calendar_id = parseInt( $(this).closest(".t-calendar-item").data("id") );
            if (calendar_id) {
                that.showEditDialog( calendar_id );
            }
        });

        that.$calendarToggle.on("click", ".js-add-calendar", function(event) {
            event.preventDefault();
            that.showEditDialog();
        });

        that.$form.on("submit", function(event) {
            event.preventDefault();
            if (that.is_form_changed) {
                that.save( that.$form );
            }
        });

        that.$form.on("change", "input, select, textarea", setChanged);

        function setChanged() {
            if (!that.is_form_changed) {
                that.is_form_changed = true;
                that.$submitButton.addClass("yellow");
            }
        }
    };

    SettingsPage.prototype.initSortable = function() {
        var that = this,
            item_index;

        that.$calendarToggle.sortable({
            animation: 150,
            handle: ".t-toggle",
            direction: "vertical",
            filter: '.t-actions',
            onMove(event) {
                if(event.related.classList.contains('t-actions')) {
                    return -1
                }
            },
            onStart() {
                if (that.$notice) {
                    that.$notice.remove();
                    that.$notice = false;
                }
            },
            onEnd(event) {
                if (event.oldIndex !== event.newIndex) {
                    that.saveCalendarsSort(event.item);
                }
            }
        });
    };

    SettingsPage.prototype.showEditDialog = function( id ) {
        var that = this,
            href = $.team.app_url + "?module=calendar",
            data = {};

        if (id) {
            data["id"] = id;
        }

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post(href, data, function( html ) {
            that.dialog = $.waDialog({
                html: html
            });
        });
    };

    SettingsPage.prototype.saveCalendarsSort = function(item) {
        var that = this,
            $item = $(item),
            href = $.team.app_url + "?module=settings&action=calendarsSortSave",
            data = {
                calendars: getIndexArray()
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.$notice = showLoadingNotice( $item );

        that.xhr = $.post(href, data, function(response) {
            that.$notice.remove();
            that.$notice = showSavedNotice( $item );

            setTimeout( function() {
                if (that.$notice && $.contains(document, that.$notice[0])) {
                    that.$notice.remove();
                }
            }, 1000);

            that.xhr = false;
        });

        //

        function getIndexArray() {
            var result = [],
                $calendars = that.$calendarToggle.find(".t-calendar-item");

            $calendars.each( function() {
                var $calendar = $(this),
                    id = $calendar.data("id");

                if (id && id > 0) {
                    result.push(id);
                }
            });

            return result;
        }

        function showLoadingNotice( $item ) {
            var text = ( that.locales["saving"] || ""),
                $notice = $('<span class="t-notice">&nbsp;<i class="fas fa-spin fa-spinner"></i>&nbsp;' + text + '</span>');

            $notice.appendTo( $item );

            return $notice;
        }

        function showSavedNotice( $item ) {
            var text = ( that.locales["saved"] || ""),
                $notice = $('<span class="t-notice state-success">&nbsp;<i class="fas fa-check-circle"></i>&nbsp;' + text + '</span>');

            $notice.appendTo( $item );

            return $notice;
        }
    };

    SettingsPage.prototype.save = function( $form ) {
        var that = this,
            url = $.team.app_url + "?module=settings&action=save",
            data = $form.serializeArray(),
            btn_text = that.$submitButton.text();

        that.$submitButton.html(`${btn_text}&nbsp;<i class="fas fa-spin fa-spinner"></i>`);

        if (!that.is_locked) {
            that.is_locked = true;
            $.post(url, data, function(r) {
                that.is_form_changed = false;
                that.$submitButton.removeClass("yellow");

                if (r.status === 'ok') {
                    if (r.data.map_info.adapter === 'google') {
                        $.getScript('https://maps.googleapis.com/maps/api/js?sensor=false&key=' +
                            (r.data.map_info.settings.key || '') + '&lang=' + r.data.lang);
                    } else if (r.data.map_info.adapter === 'yandex') {
                        $.getScript('https://api-maps.yandex.ru/2.1/?apikey=' +
                            (r.data.map_info.settings.apikey || '') + '&lang=' + r.data.lang);
                    }
                }

            }).always( function() {
                that.$submitButton.text(btn_text);
                that.is_locked = false;
            });
        }
    };

    return SettingsPage;

})(jQuery);

// Dialogs

var CalendarEditDialog = ( function($) {

    CalendarEditDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".dialog-body");
        that.$form = that.$block;
        that.$styleWrapper = that.$block.find(".t-style-wrapper");
        that.$iconsWrapper = that.$block.find(".t-calendar-icons");
        that.$limitedToggle = that.$block.find(".t-limited-toggle");
        that.$colorToggle = that.$block.find(".t-color-toggle");
        that.$nameField = that.$block.find('input[name="data[name]"]');
        that.$iconField = that.$block.find('input[name="data[icon]"]');
        that.$badge_status = that.$block.find(".js-badge-preview.is-status");
        that.$badge_event = that.$block.find(".js-badge-preview.is-event");

        // VARS
        that.calendar_id = options["calendar_id"];
        that.selected_class = "is-selected";
        that.hidden_class = "is-hidden";
        that.has_error_class = "state-error";
        that.locales = options["locales"];
        that.teamDialog = that.$wrapper.data("dialog");

        // DYNAMIC VARS
        that.$selectedStyleButton = that.$styleWrapper.find("." + that.selected_class);
        that.is_locked = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    CalendarEditDialog.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        that.initColorPicker();
    };

    CalendarEditDialog.prototype.bindEvents = function() {
        var that = this;

        that.$block.on("click", ".js-delete-calendar", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.showDeleteConfirm();
            }
        });

        that.$styleWrapper.on("click", ".t-style-item", function(event) {
            event.preventDefault();
            that.setStyleToggle( $(this) );
        });

        that.$styleWrapper.on("click", ".js-custom-color", function(event) {
            event.preventDefault();
            $(this).find('svg').toggleClass('fa-caret-down fa-caret-up')
            that.$colorToggle.toggleClass('hidden')
        });

        that.$iconsWrapper.on("click", ".t-calendar-icons--item", function(event) {
            event.preventDefault();
            let $icon = $(this),
                $svg = $icon.find('svg'),
                icon = `${$svg.attr('data-prefix')} fa-${$svg.attr('data-icon')}`

            $icon.addClass(that.selected_class).siblings().removeClass(that.selected_class);
            that.$badge_status.find('*:not(span)').remove().end().prepend(`<i class="${icon}"></i>`)
            that.$badge_event.find('*:not(span)').remove().end().prepend(`<i class="${icon}"></i>`)
            that.$iconField.val(icon);
        });

        that.$block.on("click", ".js-custom-icon", function(event) {
            event.preventDefault();
            $(this).find('svg').toggleClass('fa-caret-down fa-caret-up')
            that.$iconField.attr('type', (i, type) => type == 'hidden' ? 'text' : 'hidden');
        });

        that.$form.on("submit", function(event) {
            event.preventDefault();
            if (!that.locked) {
                that.save();
            }
        });

        that.$form.on("change", ".t-color-field", function() {
            if (that.$selectedStyleButton.length) {
                that.$selectedStyleButton.removeClass(that.selected_class);
                that.$selectedStyleButton = false;
            }
        });

        // Remove errors hints
        var $fields = that.$form.find("input, textarea");
        $fields.on("mousedown", function() {
            var $field = $(this),
                has_error = $field.hasClass( that.has_error_class );

            if (has_error) {
                $field
                    .removeClass(that.has_error_class)
                    .closest(".value")
                    .find(".state-error-hint").remove();
            }
        });

        that.$nameField.on("change keyup", function() {
            var value = $(this).val();
            value = value.length ? value : that.locales.preview;
            that.setPreviewName( value );
        });

        that.$limitedToggle.on("change", "input:radio", function() {
            var $hint = that.$limitedToggle.find(".t-hidden-content");
            if ($(this).val().length) {
                $hint.show();
            } else {
                $hint.hide();
            }
            that.teamDialog.resize();
        });
    };

    CalendarEditDialog.prototype.setStyleToggle = function( $button ) {
        var that = this,
            bg_color = $button.css("background-color"),
            font_color = $button.css("color"),
            bg_color_status = that.colorConvert(bg_color, +40).hex,
            font_color_status = that.colorConvert(bg_color, -15).hex;

        if (that.$selectedStyleButton.length) {
            that.$selectedStyleButton.removeClass(that.selected_class);
        }

        that.setStyleData(bg_color, font_color);

        $button.addClass(that.selected_class);
        that.$selectedStyleButton = $button;

        that.$badge_status.css({
            'background-color': bg_color_status,
            'color': font_color_status,
        })
        that.$badge_event.css({
            'background-color': bg_color,
            'color': font_color,
        })

        that.$styleWrapper.find('[name="data[status_bg_color]"]').val(bg_color_status)
        that.$styleWrapper.find('[name="data[status_font_color]"]').val(font_color_status)

    };

    CalendarEditDialog.prototype.setStyleData = function(bg_color, font_color) {
        var that = this;

        if (!bg_color.includes('#')) {
            bg_color = rgbToHex(bg_color);
        }
        if (!font_color.includes('#')) {
            font_color = rgbToHex(font_color);
        }

        that.$form.find('[name="data[bg_color]"]').val(bg_color).trigger("change");
        that.$form.find('[name="data[font_color]"]').val(font_color).trigger("change");
        that.$form.find('[name="data[status_bg_color]"]').val(that.colorConvert(bg_color, +40).hex).trigger("change");
        that.$form.find('[name="data[status_font_color]"]').val(that.colorConvert(bg_color, -15).hex).trigger("change");

        function rgbToHex( color_string ) {
            var a, b;

            a = color_string.split("(")[1].split(")")[0];
            a = a.split(",").splice(0,3);
            b = a.map(function(x){
                x = parseInt(x).toString(16);
                return (x.length==1) ? "0"+x : x;
            });

            return "#" + b.join("");
        }
    };

    CalendarEditDialog.prototype.showDeleteConfirm = function() {
        var that = this,
            href = "?module=calendar&action=deleteConfirm",
            data = {
                id: that.calendar_id
            };

        that.is_locked = true;

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.get(href, data, function(html) {
            $.waDialog({
                html: html
            });

            that.is_locked = false;

            that.teamDialog.close();
        });
    };

    CalendarEditDialog.prototype.initColorPicker = function() {
        let dialog = this;

        var ColorPicker = ( function() {

            ColorPicker = function(options) {
                let that = this;

                // DOM
                that.$wrapper = options["$wrapper"];
                that.$field = that.$wrapper.querySelector(".t-color-field");
                that.$pick_color_btn = that.$wrapper.querySelector(".js-show-color-picker");

                // VARS
                that.pickr_options = {
                    el: that.$pick_color_btn,
                    theme: 'classic',
                    appClass: 'wa-pcr-app small',
                    lockOpacity: true,
                    position: 'right-start',
                    useAsButton: true,
                    container: dialog.$wrapper[0],
                    default: that.$field.value || '#42445a',
                    components: {
                        palette: true,
                        hue: true,
                    }
                }

                // DYNAMIC VARS

                // INIT
                that.initClass();
            };

            ColorPicker.prototype.initClass = function() {
                let that = this;

                const color_picker = Pickr.create(that.pickr_options)
                    .on('change', color =>  eventHandler(color))
                    .on('changestop', (event, pickr) => pickr.hide());

                that.$wrapper.dataset['colorPicker'] = that;

                function eventHandler(color) {
                    let color_hex;
                    if (color.hasOwnProperty('toHEXA')) {
                        color_hex = color.toHEXA().toString(0);
                    }else{
                        color_hex = color.target.value;
                    }

                    that.$field.value = color_hex;
                    that.$pick_color_btn.style.backgroundColor = color_hex;

                    if (that.$pick_color_btn.classList.contains('js-bg-color')) {
                        dialog.$badge_event[0].style.backgroundColor = color_hex;
                        dialog.$badge_status[0].style.backgroundColor = dialog.colorConvert(color_hex, +40).hex;
                        dialog.$badge_status[0].style.color = color_hex;

                        dialog.$form[0].querySelector('[name="data[status_bg_color]"]').value = dialog.colorConvert(color_hex, +40).hex;
                        dialog.$form[0].querySelector('[name="data[status_font_color]"]').value = dialog.colorConvert(color_hex, -15).hex;
                    }
                    if (that.$pick_color_btn.classList.contains('js-font-color')) {
                        dialog.$badge_event[0].style.color = color_hex;
                    }
                }

                that.$field.addEventListener('keyup', eventHandler, false)

            };

            return ColorPicker;

        })();

        dialog.$styleWrapper.find(".t-color-toggle .t-toggle").each(function () {
            new ColorPicker({
                $wrapper: this
            })
        });
    }

    CalendarEditDialog.prototype.setPreviewName = function(value) {
        var that = this;
        that.$block.find(".js-badge-preview > span").text( value );
    };

    CalendarEditDialog.prototype.colorConvert = function(hex, brightness = 0) {
        let r = 0, g = 0, b = 0;
        if (hex.includes('#')) {
            // Convert hex to RGB first
            if (hex.length == 4) {
                r = "0x" + hex[1] + hex[1];
                g = "0x" + hex[2] + hex[2];
                b = "0x" + hex[3] + hex[3];
            } else if (hex.length == 7) {
                r = "0x" + hex[1] + hex[2];
                g = "0x" + hex[3] + hex[4];
                b = "0x" + hex[5] + hex[6];
            }
        }else{
            // Parse RGB
            let rgb = hex.split("(")[1].split(")")[0].split(",").splice(0,3);
            r = rgb[0];
            g = rgb[1];
            b = rgb[2];
        }
        r /= 255;
        g /= 255;
        b /= 255;

        let cmin = Math.min(r,g,b),
            cmax = Math.max(r,g,b),
            delta = cmax - cmin,
            h = 0,
            s = 0,
            l = 0;

        if (delta == 0) {
            h = 0;
        }else if (cmax == r) {
            h = ((g - b) / delta) % 6;
        }else if (cmax == g){
            h = (b - r) / delta + 2;
        }else {
            h = (r - g) / delta + 4;
        }
        h = Math.round(h * 60);

        if (h < 0) {
            h += 360;
        }

        l = (cmax + cmin) / 2;
        s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
        s = +(s * 100).toFixed();
        l = +(l * 100).toFixed();

        if (brightness !== 0) {
            let ratio = 20;
            if(l >= 50) {
                l = l - ratio;
            }
            if (brightness > 0) {
                l = l + parseInt(brightness, 10);
                if (l >= 100) {
                    l = l - 10
                }
            }else if(brightness < 0) {
                l = l + parseInt(brightness, 10)
            }
        }

        let result_obj = {
            hsl: `hsl(${h},${s}%,${l}%)`
        }

        // convert hsl to hex
        l /= 100;
        const a = s * Math.min(l, 1 - l) / 100,
            f = n => {
                const k = (n + h / 30) % 12,
                    color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
                return Math.round(255 * color).toString(16).padStart(2, '0');   // convert to Hex and prefix "0" if needed
            };

        result_obj.hex = `#${f(0)}${f(8)}${f(4)}`;

        return result_obj;
    }

    CalendarEditDialog.prototype.save = function() {
        var that = this,
            href = "?module=calendar&action=save",
            data = prepareData( that.$form.serializeArray() );

        if (!that.is_locked) {
            that.is_locked = true;

            if (data) {
                $.post(href, data, function(response) {
                    if (response.status == "ok") {
                        $.team.content.reload();
                        $.team.sidebar.reload();
                        that.is_locked = false;
                        that.teamDialog.close();
                    }
                });
            } else {
                that.is_locked = false;
            }
        }

        function prepareData(data) {
            var result = {},
                errors = [];

            $.each(data, function(index, item) {
                result[item.name] = item.value;
            });

            if (!result["data[is_limited]"]) {
                delete result["data[is_limited]"];
            }

            if (!$.trim(result["data[name]"]).length) {
                errors.push({
                    field: "data[name]",
                    locale: "empty"
                });
            }

            if (errors.length) {
                showErrors(errors);
                return false;
            }

            return result;

            function showErrors( errors ) {
                // Remove old errors
                that.$form.find(".state-error-hint").remove();

                // Display new errors
                $.each(errors, function(index, item) {
                    var $field = that.$form.find("[name='" + item.field + "']");
                    if ($field.length) {
                        $field
                            .addClass(that.has_error_class)
                            .after('<span class="state-error-hint">' + that.locales[item.locale] + '</span>')
                    }
                });
            }
        }
    };

    return CalendarEditDialog;

})(jQuery);

var CalendarDeleteDialog = ( function($) {

    CalendarDeleteDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".dialog-body");

        // VARS
        that.calendar_id = options["calendar_id"];

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    CalendarDeleteDialog.prototype.initClass = function() {
        var that = this;

        that.$block.on("click", ".js-delete-calendar", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that['delete']();
            }
        });
    };

    CalendarDeleteDialog.prototype['delete'] = function() {
        var that = this,
            href = "?module=calendar&action=delete",
            data = {
                id: that.calendar_id
            };

        that.is_locked = true;

        $.post(href, data, function(response) {
            if (response.status == "ok") {
                $.team.content.reload();
                that.$wrapper.data("dialog").close();
            }
        }, "json");
    };

    return CalendarDeleteDialog;

})(jQuery);
