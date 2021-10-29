// Pages

var SettingsPage = ( function($) {

    SettingsPage = function($wrapper, options) {
        var that = this;

        // DOM
        that.$wrapper = $wrapper;
        that.$calendarToggle = that.$wrapper.find('#t-calendar-settings');
        that.$form = that.$wrapper.find('#calendar_settings');
        that.$submitButton = that.$wrapper.siblings('.bottombar').find('[type="submit"]');

        that.options = options;

        // DYNAMIC VARS
        that.$notice = false;
        that.is_locked = false;
        that.is_form_changed = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    SettingsPage.prototype.initClass = function() {
        const that = this;

        that.bindEvents();
        that.initSortable();
    };

    SettingsPage.prototype.bindEvents = function() {
        var that = this;

        that.$calendarToggle.on('click', '.js-edit-calendar, .js-add-calendar', $.proxy(that.showEditDialog, that));

        that.$form.on('change', 'input, select, textarea', $.proxy(that.checkFormChanges, that));

        that.$form.on('submit', $.proxy(that.save, that));
    };

    SettingsPage.prototype.initSortable = function() {
        const that = this;

        that.$calendarToggle.sortable({
            animation: 150,
            handle: '.t-toggle',
            direction: 'vertical',
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

    SettingsPage.prototype.checkFormChanges = function() {
        const that = this;

        if (that.is_form_changed) {
            return;
        }

        that.is_form_changed = true;
        that.$submitButton.addClass('yellow');
    }

    SettingsPage.prototype.showEditDialog = function(event) {
        event.preventDefault();

        const that = this;
        let data = {};

        const calendarId = parseInt($(event.target).closest('.t-calendar-item').data('id'));

        if (calendarId) {
            data['id'] = calendarId;
        }

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.post(that.options.api.editDialog, data, function( html ) {
            that.dialog = $.waDialog({
                html
            });
        });
    };

    SettingsPage.prototype.saveCalendarsSort = function(item) {
        const that = this;

        const $item = $(item);
        const href = $.team.app_url + '?module=settings&action=calendarsSortSave';
        const data = {
            calendars: getIndexArray()
        };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.$notice = showLoadingNotice($item);

        that.xhr = $.post(href, data, function() {
            that.$notice.remove();
            that.$notice = showSavedNotice($item);

            setTimeout( function() {
                if (that.$notice && $.contains(document, that.$notice[0])) {
                    that.$notice.remove();
                }
            }, 1000);

            that.xhr = false;
        });

        function getIndexArray() {
            const result = [];
            const $calendars = that.$calendarToggle.find('.t-calendar-item');

            $calendars.each( function() {
                const $calendar = $(this);
                const id = $calendar.data('id');

                if (id && id > 0) {
                    result.push(id);
                }
            });

            return result;
        }

        function showLoadingNotice($item) {
            const $notice = $(`<span class="t-notice"><i class="fas fa-spin fa-spinner wa-animation-spin speed-1000"></i></span>`);
            $notice.appendTo($item);
            return $notice;
        }

        function showSavedNotice($item) {
            const $notice = $(`<span class="t-notice state-success"><i class="fas fa-check-circle"></i></span>`);
            $notice.appendTo($item);
            return $notice;
        }
    };

    SettingsPage.prototype.save = function(event) {
        event.preventDefault();

        const that = this;

        if (!that.is_form_changed || that.is_locked) {
            return;
        }

        const data = that.$form.serializeArray();

        that.$submitButton.append('<i class="fas fa-spinner wa-animation-spin speed-1000 custom-ml-4 js-profile-settings-spinner"></i>');

        that.is_locked = true;

        $.post(that.options.api.save, data, function(r) {
            if (r.status !== 'ok') {
                return;
            }

            that.is_form_changed = false;
            that.is_locked = false;
            that.$submitButton.removeClass('yellow');
            that.$submitButton.find('.js-profile-settings-spinner').remove();
        });
    };

    return SettingsPage;

})(jQuery);

// Dialogs

var CalendarEditDialog = ( function($) {

    CalendarEditDialog = function($wrapper, options) {
        var that = this;

        // DOM
        that.$wrapper = $wrapper;
        that.$block = that.$wrapper.find('.dialog-body');
        that.$form = that.$block;
        that.$styleWrapper = that.$block.find('.t-style-wrapper');
        that.$iconsWrapper = that.$block.find('.t-calendar-icons');
        that.$limitedToggle = that.$block.find('.t-limited-toggle');
        that.$limitedGroups = that.$block.find('.t-hidden-content');
        that.$colorToggle = that.$block.find('.t-color-toggle');
        that.$nameField = that.$block.find('input[name="data[name]"]');
        that.$iconField = that.$block.find('input[name="data[icon]"]');
        that.$inputStatusBg = that.$block.find('input[name="data[status_bg_color]"]');
        that.$inputStatusFont = that.$block.find('input[name="data[status_font_color]"]');
        that.$inputEventBg = that.$block.find('input[name="data[bg_color]"]');
        that.$inputEventFont = that.$block.find('input[name="data[font_color]"]');
        that.$pickerBgColor = that.$block.find('.js-bg-color');
        that.$badge_status = that.$block.find('.js-badge-preview.is-status');
        that.$badge_event = that.$block.find('.js-badge-preview.is-event');
        that.$badgeIcon = that.$block.find('.js-badge-icon');
        that.$badgeName = that.$block.find('.js-badge-name');
        that.$fields = that.$form.find('input, textarea');

        // VARS
        that.calendar_id = options['calendar_id'];
        that.teamDialog = that.$wrapper.data('dialog');
        that.options = options;

        // CSS CLASSES
        that.classes = {
            selected_class  : 'is-selected',
            hidden_class    : 'is-hidden',
            has_error_class : 'state-error'
        };

        that.$selectedStyleButton = that.$styleWrapper.find('.' + that.classes.selected_class);

        // DYNAMIC VARS
        that.is_locked = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    CalendarEditDialog.prototype.initClass = function() {
        const that = this;

        that.bindEvents();
        that.initColorPicker();
        that.checkCustomColor();
    };

    CalendarEditDialog.prototype.bindEvents = function() {
        const that = this;

        that.$block.on('click', '.js-delete-calendar', $.proxy(that.showDeleteConfirm, that));

        that.$styleWrapper.on('click', '.t-style-item', $.proxy(that.setStyleToggle, that));

        that.$styleWrapper.on('click', '.js-custom-color', $.proxy(that.toggleCustomColor, that));

        that.$iconsWrapper.on('click', '.t-calendar-icons--item', $.proxy(that.setBadgeIcon, that));

        that.$form.on('submit', $.proxy(that.save, that));

        that.$fields.on('mousedown', $.proxy(that.removeErrors, that));

        that.$nameField.on('change keyup', $.proxy(that.setPreviewName, that));

        that.$limitedToggle.on('change', 'input:radio', $.proxy(that.toggleLimitSection, that));
    };

    CalendarEditDialog.prototype.checkCustomColor = function() {
        const that = this;

        if (that.$selectedStyleButton.length) {
            return;
        }

        that.$styleWrapper.find('.js-custom-color-check').removeClass('hidden');
        that.$colorToggle.removeClass('hidden');
    }

    CalendarEditDialog.prototype.setBadgeIcon = function(event) {
        event.preventDefault();

        const that = this;

        const $icon = $(event.target);
        const $svg = $icon.find('svg');
        const icon = `${$svg.attr('data-prefix')} fa-${$svg.attr('data-icon')}`;

        $icon.addClass(that.classes.selected_class).siblings().removeClass(that.classes.selected_class);
        that.$badgeIcon.html(`<i class="${icon}"></i>`);
        that.$iconField.val(icon);
    }

    CalendarEditDialog.prototype.toggleCustomColor = function(event) {
        event.preventDefault();

        const that = this;

        $(event.target).find('.js-custom-color-caret').toggleClass('fa-caret-down fa-caret-up');
        that.$colorToggle.toggleClass('hidden');
        that.teamDialog.resize();
    }

    CalendarEditDialog.prototype.toggleLimitSection = function(event) {
        const that = this;

        if (event.target.value.length) {
            that.$limitedGroups.show();
        } else {
            that.$limitedGroups.hide();
        }

        that.teamDialog.resize();
    }

    CalendarEditDialog.prototype.removeErrors = function(event) {
        const that = this;

        const $field = $(event.target);
        const has_error = $field.hasClass(that.classes.has_error_class);

        if (has_error) {
            $field
              .removeClass(that.classes.classes.has_error_class)
              .closest('.value')
              .find('.state-error-hint').remove();
        }
    }

    CalendarEditDialog.prototype.setStyleToggle = function(event) {
        const that = this;

        const $button = $(event.target);

        const font_color_status = $button.css('color');
        const bg_color_status = $button.css('background-color');
        const font_color = that.colorConvert(bg_color_status, -15).hex;
        const bg_color = that.colorConvert(bg_color_status, +40).hex;

        that.$selectedStyleButton.removeClass(that.classes.selected_class);
        that.setStyleData(font_color_status, bg_color_status);
        $button.addClass(that.classes.selected_class);
        that.$selectedStyleButton = $button;

        that.$styleWrapper.find('.js-custom-color-check').addClass('hidden');

        that.$colorToggle.addClass('hidden');

        that.$badge_status.css({
            'color': font_color_status,
            'background-color': bg_color_status,
        });

        that.$badge_event.css({
            'color': font_color,
            'background-color': bg_color,
        });
    };

    CalendarEditDialog.prototype.setStyleData = function(font_color, bg_color) {
        const that = this;

        if (!font_color.includes('#')) {
            font_color = rgbToHex(font_color);
        }

        if (!bg_color.includes('#')) {
            bg_color = rgbToHex(bg_color);
        }

        that.$badge_event[0].style.removeProperty('box-shadow');

        that.$inputStatusBg.val(bg_color).trigger('change');
        that.$inputStatusFont.val(font_color).trigger('change');
        that.$inputEventBg.val(that.colorConvert(bg_color, +40).hex).trigger('change');
        that.$inputEventFont.val(that.colorConvert(bg_color, -15).hex).trigger('change');
        that.$pickerBgColor.css('background-color', bg_color);

        function rgbToHex( color_string ) {
            let a;
            let b;

            a = color_string.split('(')[1].split(')')[0];
            a = a.split(',').splice(0,3);
            b = a.map(function(x){
                x = parseInt(x).toString(16);
                return (x.length === 1) ? '0'+x : x;
            });

            return '#' + b.join('');
        }
    };

    CalendarEditDialog.prototype.showDeleteConfirm = function(event) {
        event.preventDefault();

        const that = this;

        if (that.is_locked) {
            return;
        }

        const data = {
            id: that.calendar_id
        };

        that.is_locked = true;

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.get(that.options.api.showDeleteConfirm, data, function(html) {
            $.waDialog({
                html
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
                        dialog.$badge_status.css('background-color', color_hex);
                        dialog.$badge_event.css('background-color', dialog.colorConvert(color_hex, +40).hex);
                        dialog.$badge_event.css('color', color_hex);

                        dialog.$form.find('[name="data[status_bg_color]"]').val(color_hex);
                        dialog.$form.find('[name="data[bg_color]"]').val(dialog.colorConvert(color_hex, +40).hex);
                        dialog.$form.find('[name="data[font_color]"]').val(color_hex);
                    }

                    if (that.$pick_color_btn.classList.contains('js-font-color')) {
                        dialog.$badge_status.css('color', color_hex);
                        dialog.$form.find('[name="data[status_font_color]"]').val(color_hex);
                    }

                    dialog.$styleWrapper.find('.js-custom-color-check').removeClass('hidden');
                    dialog.$selectedStyleButton.removeClass(dialog.classes.selected_class);
                }

                that.$field.addEventListener('keyup', eventHandler, false)

            };

            return ColorPicker;

        })();

        dialog.$styleWrapper.find('.t-color-toggle .t-toggle').each(function () {
            new ColorPicker({
                $wrapper: this
            })
        });
    }

    CalendarEditDialog.prototype.setPreviewName = function(event) {
        const that = this;

        const finalValue = event.target.value.length ? event.target.value : that.options.locales.preview;

        that.$badgeName.text(finalValue);
    };

    CalendarEditDialog.prototype.colorConvert = function(hex, brightness = 0) {
        let r = 0;
        let g = 0;
        let b = 0;

        if (hex.includes('#')) {
            // Convert hex to RGB first
            if (hex.length === 4) {
                r = '0x' + hex[1] + hex[1];
                g = '0x' + hex[2] + hex[2];
                b = '0x' + hex[3] + hex[3];
            } else if (hex.length === 7) {
                r = '0x' + hex[1] + hex[2];
                g = '0x' + hex[3] + hex[4];
                b = '0x' + hex[5] + hex[6];
            }
        }else{
            // Parse RGB
            let rgb = hex.split('(')[1].split(')')[0].split(',').splice(0,3);
            r = rgb[0];
            g = rgb[1];
            b = rgb[2];
        }
        r /= 255;
        g /= 255;
        b /= 255;

        let cmin = Math.min(r,g,b);
        let cmax = Math.max(r,g,b);
        let delta = cmax - cmin;
        let h = 0;
        let s = 0;
        let l = 0;

        if (delta === 0) {
            h = 0;
        }else if (cmax === r) {
            h = ((g - b) / delta) % 6;
        }else if (cmax == g) {
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

            if (l >= 50) {
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
        const a = s * Math.min(l, 1 - l) / 100;
        const f = n => {
            const k = (n + h / 30) % 12;
            const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
            return Math.round(255 * color).toString(16).padStart(2, '0');   // convert to Hex and prefix "0" if needed
        };

        result_obj.hex = `#${f(0)}${f(8)}${f(4)}`;

        return result_obj;
    }

    CalendarEditDialog.prototype.save = function(event) {
        event.preventDefault();

        const that = this;

        if (that.is_locked) {
            return;
        }

        const data = prepareData( that.$form.serializeArray() );

        if (!data) {
            console.warn('have no data to save');
            that.is_locked = false;
            that.teamDialog.close();
            return;
        }

        that.is_locked = true;

        $.post(that.options.api.save, data, function(response) {
            if (response.status !== 'ok') {
                console.warn(response);
                return;
            }

            that.is_locked = false;
            $.team.content.reload();
            $.team.sidebar.reload();
            that.teamDialog.close();
        });

        function prepareData(data) {
            let result = {};
            const errors = [];

            $.each(data, function(index, item) {
                result[item.name] = item.value;
            });

            if (!result['data[is_limited]']) {
                delete result['data[is_limited]'];
            }

            if (!$.trim(result['data[name]']).length) {
                errors.push({
                    field: 'data[name]',
                    locale: 'empty'
                });
            }

            if (errors.length) {
                showErrors(errors);
                return false;
            }

            return result;

            function showErrors(errors) {
                // Remove old errors
                that.$form.find('.state-error-hint').remove();

                // Display new errors
                $.each(errors, function(index, item) {
                    const $field = that.$form.find(`[name="${item.field}"]`);

                    if ($field.length) {
                        $field
                            .addClass(that.classes.has_error_class)
                            .after(`<div class="state-error-hint custom-mt-4">${that.options.locales[item.locale]}</div>`);
                    }
                });
            }
        }
    }

    return CalendarEditDialog;

})(jQuery);

var CalendarDeleteDialog = ( function($) {

    CalendarDeleteDialog = function(wrapper, options) {
        var that = this;

        // DOM
        that.$wrapper = wrapper;
        that.$block = that.$wrapper.find('.dialog-body');
        that.$deleteButton = that.$block.find('.js-delete-calendar');

        // VARS
        that.options = options;

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    CalendarDeleteDialog.prototype.initClass = function() {
        var that = this;

        that.$deleteButton.on('click', $.proxy(that.deleteCalendar, that));
    };

    CalendarDeleteDialog.prototype.deleteCalendar = function(event) {
        event.preventDefault();

        const that = this;

        if (that.is_locked) {
            return;
        }

        const data = {
            id: that.options['calendar_id']
        };

        that.is_locked = true;

        $.post(that.options.api.delete, data, function(response) {
            if (response.status !== 'ok') {
                console.warn(response);

                return;
            }

            $.team.content.reload();
            that.$wrapper.data('dialog').close();
        }, 'json');
    };

    return CalendarDeleteDialog;

})(jQuery);
