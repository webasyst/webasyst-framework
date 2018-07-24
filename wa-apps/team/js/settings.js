// Pages

var SettingsPage = ( function($) {

    SettingsPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$calendarToggle = that.$wrapper.find("#t-calendar-settings");
        that.$form = that.$wrapper.find("form");
        that.$submitButton = that.$form.find("input[type=\"submit\"]");

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
                that.$submitButton.removeClass("green").addClass("yellow");
            }
        }
    };

    SettingsPage.prototype.initSortable = function() {
        var that = this,
            item_index;

        that.$calendarToggle.find("ul").sortable({
            handle: ".t-toggle",
            items: "> .t-calendar-item",
            axis: "y",
            start: function(event,ui) {
                item_index = ui.item.index();
                if (that.$notice) {
                    that.$notice.remove();
                    that.$notice = false;
                }
            },
            stop: function(event,ui) {
                if (item_index != ui.item.index()) {
                    that.saveCalendarsSort(ui);
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
            that.dialog = new TeamDialog({
                html: html
            });
        });
    };

    SettingsPage.prototype.saveCalendarsSort = function(ui) {
        var that = this,
            $item = ui.item,
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
                $notice = $('<span class="t-notice"><i class="icon16 loading"></i>' + text + '</span>');

            $notice.appendTo( $item );

            return $notice;
        }

        function showSavedNotice( $item ) {
            var text = ( that.locales["saved"] || ""),
                $notice = $('<span class="t-notice"><i class="icon16 yes"></i>' + text + '</span>');

            $notice.appendTo( $item );

            return $notice;
        }
    };

    SettingsPage.prototype.save = function( $form ) {
        var that = this,
            url = $.team.app_url + "?module=settings&action=save",
            data = $form.serializeArray();

        var $loading = $("<i class=\"icon16 loading\" style=\"margin: 0 4px;\"></i>");
        $loading.insertAfter( that.$submitButton );

        if (!that.is_locked) {
            that.is_locked = true;
            $.post(url, data, function(r) {
                that.is_form_changed = false;
                that.$submitButton.removeClass("yellow").addClass("green");

                if (r.status === 'ok') {
                    if (r.data.map_info.adapter === 'google') {
                        $.getScript('https://maps.googleapis.com/maps/api/js?sensor=false&key=' +
                            (r.data.map_info.settings.key || '') + '&lang=' + r.data.lang);
                    } else if (r.data.map_info.adapter === 'yandex') {
                        $.getScript('https://api-maps.yandex.ru/2.1/?lang=' + r.data.lang);
                    }
                }

            }).always( function() {
                $loading.remove();
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
        that.$block = that.$wrapper.find(".t-dialog-block");
        that.$form = that.$block.find("form");
        that.$styleWrapper = that.$block.find(".t-style-wrapper");
        that.$limitedToggle = that.$block.find(".t-limited-toggle");
        that.$nameField = that.$block.find('input[name="data[name]"]');

        // VARS
        that.calendar_id = options["calendar_id"];
        that.selected_class = "is-selected";
        that.hidden_class = "is-hidden";
        that.has_error_class = "error";
        that.locales = options["locales"];
        that.teamDialog = that.$wrapper.data("teamDialog");

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
                    .find(".t-error").remove();
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
            font_color = $button.css("color");

        if (that.$selectedStyleButton.length) {
            that.$selectedStyleButton.removeClass(that.selected_class);
        }

        that.setStyleData(bg_color, font_color);

        $button.addClass(that.selected_class);
        that.$selectedStyleButton = $button;
    };

    CalendarEditDialog.prototype.setStyleData = function(bg_color, font_color) {
        var that = this;

        bg_color = rgbToHex(bg_color);
        font_color = rgbToHex(font_color);

        that.$form.find('[name="data[bg_color]"]').val(bg_color).trigger("change");
        that.$form.find('[name="data[font_color]"]').val(font_color).trigger("change");

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
            new TeamDialog({
                html: html
            });

            that.is_locked = false;

            that.$wrapper.trigger("close");
        });
    };

    CalendarEditDialog.prototype.initColorPicker = function() {
        var dialog = this;

        var ColorPicker = ( function($) {

            ColorPicker = function(options) {
                var that = this;

                // DOM
                that.$wrapper = options["$wrapper"];
                that.$field = that.$wrapper.find(".t-color-field");
                that.$icon = that.$wrapper.find(".js-show-color-picker");
                that.$colorPicker = that.$wrapper.find(".t-color-picker");

                // VARS

                // DYNAMIC VARS
                that.is_opened = false;
                that.farbtastic = false;

                // INIT
                that.initClass();
            };

            ColorPicker.prototype.initClass = function() {
                var that = this;

                that.farbtastic = $.farbtastic(that.$colorPicker, function(color) {
                    that.$field.val( color ).change();
                });

                that.$wrapper.data("colorPicker", that);

                that.bindEvents();
            };

            ColorPicker.prototype.bindEvents = function() {
                var that = this;

                that.$field.on("change keyup", function() {
                    var color = $(this).val();
                    //
                    that.$icon.css("background-color", color);
                    that.farbtastic.setColor(color);
                });

                that.$icon.on("click", function(event) {
                    event.preventDefault();
                    // close others opened
                    closeOthersColorPickers();
                    // show current
                    that.displayToggle( !that.is_opened );
                });

                that.$wrapper.on("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                });

                that.$field.on("focus", function() {
                    if (!that.is_opened) {
                        closeOthersColorPickers();
                        that.displayToggle( true );
                    }
                });

                var $background = dialog.$wrapper.find(".t-dialog-background");
                $background.on("click", function() {
                    if (that.is_opened) {
                        that.displayToggle( false );
                    }
                });
                dialog.$block.on("click", function() {
                    if (that.is_opened) {
                        that.displayToggle( false );
                    }
                });

                function closeOthersColorPickers() {
                    that.$wrapper.siblings().each( function() {
                        var colorPicker = $(this).data("colorPicker");
                        if (colorPicker && colorPicker.is_opened) {
                            colorPicker.displayToggle( false );
                        }
                    });
                }
            };

            ColorPicker.prototype.displayToggle = function( show ) {
                var that = this,
                    hidden_class = "is-hidden",
                    $colorPicker = that.$colorPicker;

                if (show) {
                    $colorPicker.removeClass(hidden_class);
                    that.is_opened = true;
                } else {
                    $colorPicker.addClass(hidden_class);
                    that.is_opened = false;
                }
            };

            return ColorPicker;

        })(jQuery);

        dialog.$styleWrapper.find(".t-color-toggle .t-toggle").each( function() {
            new ColorPicker({
                $wrapper: $(this)
            });
        });

    };

    CalendarEditDialog.prototype.setPreviewName = function(value) {
        var that = this;
        that.$styleWrapper.find(".t-style-item").text( value );
    };

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
                that.$form.find(".t-error").remove();

                // Display new errors
                $.each(errors, function(index, item) {
                    var $field = that.$form.find("[name='" + item.field + "']");
                    if ($field.length) {
                        $field
                            .addClass(that.has_error_class)
                            .after('<span class="t-error">' + that.locales[item.locale] + '</span>')
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
        that.$block = that.$wrapper.find(".t-dialog-block");

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
                that.$wrapper.trigger("close");
            }
        }, "json");
    };

    return CalendarDeleteDialog;

})(jQuery);
