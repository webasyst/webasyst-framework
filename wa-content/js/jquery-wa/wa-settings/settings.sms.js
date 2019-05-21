var WASettingsSMS = ( function($) {

    WASettingsSMS = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('form');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');
        that.is_locked = false;

        // VARS

        // DYNAMIC VARS

        // INIT
        that.init();
    };

    WASettingsSMS.prototype.init = function() {
        var that = this;
        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="sms"]').addClass('selected');

        that.initSubmit();
    };

    WASettingsSMS.prototype.initSubmit = function () {
        var that = this,
            $form = that.$form;

        $form.on('change', function () {
            that.clearValidateErrors();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            that.is_locked = true;
            that.$button.prop('disabled', true);
            that.$loading.removeClass('yes').addClass('loading').show();

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            that.clearValidateErrors();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow').addClass('green');
                    that.$loading.removeClass('loading').addClass('yes');
                    that.$footer_actions.removeClass('is-changed');
                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                } else {
                    that.showValidateErrors(res.errors);
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$form.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.removeClass('green').addClass('yellow');
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
            return;
        });
    };

    WASettingsSMS.prototype.showValidateErrors = function (errors) {
        var that = this,
            $form = that.$form;
        $.each(errors || {}, function (field_name, error) {
            var $field = $form.find('[name="' + field_name + '"]').addClass('error');
            $field.after('<div class="errormsg">' + $.wa.encodeHTML(error) + '</div>')
        });
    };

    WASettingsSMS.prototype.clearValidateErrors = function () {
        var that = this,
            $form = that.$form;
        $form.find('.error').removeClass('error');
        $form.find('.errormsg').remove();
    };

    return WASettingsSMS;

})(jQuery);

var WASettingsSMSTemplate = ( function($) {

    WASettingsSMSTemplate = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('form');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');
        that.$delete_confirm_dialog = options["$delete_confirm_dialog"];
        that.$sms_preview_dialog = options["$sms_preview_dialog"];
        that.$sms_check_dialog = options["$sms_check_dialog"];
        that.$requirement_to_save = options["$requirement_to_save"];

        // VARS
        that.template_areas = that.$form.find('textarea');
        that.channel_id = options["channel_id"];
        that.path_to_template = options["path_to_templates"];
        that.cheat_sheet_name = options["cheat_sheet_name"];
        that.default_templates = options["default_templates"];
        that.locales = options["locales"];

        // DYNAMIC VARS
        that.is_locked = false;
        that.ace = {};
        that.selected_template = null;

        // INIT
        that.initClass();
    };

    WASettingsSMSTemplate.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="sms-template"]').addClass('selected');

        // Init Ace
        if (that.template_areas.length) {
            that.initAce();
            //
            that.initCheatSheet();
            //
            that.initFixedActions();
            //
            that.initReset();
        }

        //
        that.initPreview();
        //
        that.initCheck();
        //
        that.initChannelActions();
        //
        that.initSubmit();
    };

    WASettingsSMSTemplate.prototype.initAce = function() {
        var that = this,
            sessions = {};

        that.template_areas.each(function (i, textarea) {
            var template_id = $(textarea).data('template'),
                div = $('<div></div>');

            that.selected_template = template_id;

            // Init Ace
            $(textarea).parent().prepend($('<div class="ace"></div>').append(div));
            $(textarea).hide();
            that.ace[template_id] = ace.edit(div.get(0));
            // Set options
            that.ace[template_id].commands.removeCommand('find');
            ace.config.set("basePath", window.wa_url + 'wa-content/js/ace/');
            that.ace[template_id].setTheme("ace/theme/eclipse");
            that.ace[template_id].renderer.setShowGutter(false);
            sessions[template_id] = that.ace[template_id].getSession();
            sessions[template_id].setMode("ace/mode/smarty");
            if (navigator.appVersion.indexOf('Mac') != -1) {
                that.ace[template_id].setFontSize(13);
            } else if (navigator.appVersion.indexOf('Linux') != -1) {
                that.ace[template_id].setFontSize(16);
            } else {
                that.ace[template_id].setFontSize(14);
            }
            if ($(textarea).val().length) {
                sessions[template_id].setValue($(textarea).val());
            } else {
                sessions[template_id].setValue('');
            }
            that.ace[template_id].setOption("minLines", 5);
            that.ace[template_id].setOption("maxLines", 5);
            sessions[template_id].on('change', function () {
                $(textarea).val(that.ace[template_id].getValue());
            });
            that.ace[template_id].on('focus', function () {
                that.selected_template = template_id;
            });
        });
    };

    WASettingsSMSTemplate.prototype.initCheatSheet = function () {
        var that = this,
            cheat_sheet_name = that.cheat_sheet_name;

        var getViewRight = function() {
            return ($(window).width() - (that.$wrapper.offset().left + that.$wrapper.outerWidth()));
        };

        $(document).bind('wa_cheatsheet_init.' + cheat_sheet_name, function () {
            $.cheatsheet[cheat_sheet_name].insertVarEvent = function () {
                $("#wa-editor-help-" + cheat_sheet_name).on('click', "div.fields a.inline-link", function () {
                    var el = $(this).find('i');
                    if (el.children('b').length) {
                        el = el.children('b');
                    }
                    if (that.ace[that.selected_template]) {
                        that.ace[that.selected_template].insert(el.text());
                        that.$button.removeClass('green').addClass('yellow');
                    }
                    $("#wa-editor-help-" + cheat_sheet_name).hide();
                    return false;
                });
            }
        });

        $(".js-cheat-sheet-wrapper").load('?module=backendCheatSheet&action=button',
            {
                options: {
                    name: cheat_sheet_name,
                    app: 'webasyst',
                    key: 'sms_templates',
                    need_cache: 1
                }
            }, function () {

                $(document).one('wa_cheatsheet_load.' + cheat_sheet_name, function() {
                    var $help = $("#wa-editor-help-" + cheat_sheet_name);


                    var getHelpRight = function() {
                        return $(window).width() - ($help.offset().left + $help.outerWidth());
                    };

                    var adjustHelpOffset = function () {
                        if ($help.length) {
                            $help.css('right', 0);
                            var diff = getHelpRight() - getViewRight();
                            $help.css('right', (-diff) + 'px');
                        }
                    };

                    var watcher = function() {
                        var timer = setInterval(function () {
                            if (!$.contains(document, $help.get(0))) {
                                $(window).off('resize.' + cheat_sheet_name);
                                clearInterval(timer);
                                timer = null;
                            }
                        }, 500);
                    };

                    adjustHelpOffset();

                    $(window).on('resize.' + cheat_sheet_name, function () {
                        adjustHelpOffset();
                    });

                    watcher();

                });
            }
        );

    };

    WASettingsSMSTemplate.prototype.initPreview = function() {
        var that = this,
            $dialog_wrapper = that.$sms_preview_dialog,
            is_locked = false;

        that.$wrapper.on('click', '.js-preview-link', function () {
            if (is_locked) {
                return;
            }

            is_locked = true;

            var $template_field = $(this).parents('.field'),
                template_id = $template_field.data('template'),
                template_value = that.ace[template_id].getValue(),
                href = '?module=settingsTemplateSMSPreview',
                data = {data: {template_id: template_id, preview: template_value}};

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    $dialog_wrapper.find('.js-template-name').text(res.data.template);
                    $dialog_wrapper.find('.js-message-place').text(res.data.preview);
                    $dialog_wrapper.find('.js-time').text(res.data.time);
                    $dialog_wrapper.waDialog({
                        width: '400px',
                        height: '220px'
                    });
                } else {
                    console.log(res);
                }
            });
            is_locked = false;
        });
    };

    WASettingsSMSTemplate.prototype.initCheck = function() {
        var that = this,
            $dialog_wrapper = that.$sms_check_dialog,
            $form = $dialog_wrapper.find('form'),
            $dialog_buttons = $dialog_wrapper.find('.dialog-buttons'),
            $button = $dialog_buttons.find('.js-submit-button'),
            $loading = $dialog_buttons.find('.s-loading'),
            is_locked = false;

        that.$wrapper.on('click', '.js-check-button', function () {
            if (that.$button.hasClass('yellow')) {
                that.$requirement_to_save.waDialog({
                    width: '400px',
                    height: '110px'
                });
            } else {
                $dialog_wrapper.waDialog({
                    width: '400px',
                    height: '266px'
                });
            }
        });

        // Update templates counter
        $form.on('change', '.js-template-item-checkbox', function () {
            var $checked_templates = $form.find('.js-template-item-checkbox:checked'),
                checked_templates_count = $checked_templates.length;

            $button.val(that.locales.send_nan_sms.replace('%s', checked_templates_count));
            if (!checked_templates_count) {
                $button.prop('disabled', true);
            } else {
                $button.prop('disabled', false);
            }
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            if (is_locked) {
                return;
            }
            is_locked = true;
            $button.prop('disabled', true);
            $loading.removeClass('yes').addClass('loading').show();
            $form.find('.js-field-error').remove();

            var href = $form.attr('action'),
                data = $form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    $button.removeClass('yellow').addClass('green');
                    $loading.removeClass('loading').addClass('yes');
                    setTimeout(function(){
                        $loading.hide();
                        $dialog_buttons.find('.cancel').click();
                    },2000);
                } else {
                    if (res.errors) {
                        $.each(res.errors, function (i, error) {
                            var field = error.field,
                                message = error.message;

                            if (field == 'template') {
                                var $input = $dialog_wrapper.find('.js-templates-list');
                            } else {
                                var $input = $form.find('input[name="data' + field + '"]').parent();
                            }
                            $input.addClass('shake animated');
                            $input.after('<div class="s-field-error js-field-error">'+ message +'</div>');
                            setTimeout(function(){
                                $input.removeClass('shake animated');
                                //$form.find('.js-field-error').remove();
                            },2000);
                        })
                    }
                    $loading.hide();
                }
                is_locked = false;
                $button.prop('disabled', false);
            });
        });
    };

    WASettingsSMSTemplate.prototype.initChannelActions = function() {
        var that = this,
            $duplicate_link = that.$footer_actions.find('.js-duplicate'),
            $delete_link = that.$footer_actions.find('.js-delete');

        $duplicate_link.on('click', function () {
            var href = '?module=settingsTemplateDuplicate',
                data = {id: that.channel_id};

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    $.wa.content.load(that.path_to_template + res.data.id +'/');
                } else {
                    $.wa.content.reload();
                }
            });
        });

        $delete_link.on('click', function () {
            var href = '?module=settingsTemplateDelete',
                data = {id: that.channel_id};

            that.$delete_confirm_dialog.waDialog({
                width: '400px',
                height: '100px',
                onSubmit: function () {
                    $.post(href, data, function () {
                        $.wa.content.load(that.path_to_template);
                    });
                    return false;
                }
            });
        });


    };

    WASettingsSMSTemplate.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            that.is_locked = true;
            that.$button.prop('disabled', true);
            that.$loading.removeClass('yes').addClass('loading').show();

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow').addClass('green');
                    that.$loading.removeClass('loading').addClass('yes');
                    that.$footer_actions.removeClass('is-changed');


                    // UI update
                    if (res.data && res.data.channel && res.data.channel.id > 0) {

                        // Reload sidebar
                        $('#s-sms-templates-page .s-sms-template-sidebar-wrapper')
                            .load('?module=settingsTemplateSMS&action=sidebar&id=' + res.data.channel.id, function () {
                                // Update header, but after reload sidebar, cause we need UI updating looks like it does it at once, not alternately
                                that.$wrapper.find('.s-template-name').text(res.data.channel.name);
                            });

                    }

                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                } else {
                    if (res.errors) {
                        $.each(res.errors, function (field, message) {
                            var $input = that.$form.find(':input[name="data['+field+']"]');
                            $input.addClass('shake animated');
                            $input.after('<span style="color: red; margin-left: 12px;">'+ message +'</span>');
                            setTimeout(function(){
                                $input.removeClass('shake animated');
                                $input.next().remove();
                            },2000);
                        });
                        $("html, body").scrollTop(that.$wrapper.offset().top);
                    }
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$form.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.removeClass('green').addClass('yellow');
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
            return;
        });
    };

    WASettingsSMSTemplate.prototype.initFixedActions = function() {
        var that = this;

        /**
         * @class FixedBlock
         * @description used for fixing form buttons
         * */
        var FixedBlock = ( function($) {

            FixedBlock = function(options) {
                var that = this;

                // DOM
                that.$window = $(window);
                that.$wrapper = options["$section"];
                that.$wrapperW = options["$wrapper"];
                that.$form = that.$wrapper.parents('form');

                // VARS
                that.type = (options["type"] || "bottom");
                that.lift = (options["lift"] || 0);

                // DYNAMIC VARS
                that.offset = {};
                that.$clone = false;
                that.is_fixed = false;

                // INIT
                that.initClass();
            };

            FixedBlock.prototype.initClass = function() {
                var that = this,
                    $window = that.$window,
                    resize_timeout = 0;

                $window.on("resize", function() {
                    clearTimeout(resize_timeout);
                    resize_timeout = setTimeout( function() {
                        that.resize();
                    }, 100);
                });

                $window.on("scroll", watcher);

                that.$wrapper.on("resize", function() {
                    that.resize();
                });

                that.$form.on("input", function () {
                    that.resize();
                });

                that.init();

                function watcher() {
                    var is_exist = $.contains($window[0].document, that.$wrapper[0]);
                    if (is_exist) {
                        that.onScroll($window.scrollTop());
                    } else {
                        $window.off("scroll", watcher);
                    }
                }

                that.$wrapper.data("block", that);
            };

            FixedBlock.prototype.init = function() {
                var that = this;

                if (!that.$clone) {
                    var $clone = $("<div />").css("margin", "0");
                    that.$wrapper.after($clone);
                    that.$clone = $clone;
                }

                that.$clone.hide();

                var offset = that.$wrapper.offset();

                that.offset = {
                    left: offset.left,
                    top: offset.top,
                    width: that.$wrapper.outerWidth(),
                    height: that.$wrapper.outerHeight()
                };
            };

            FixedBlock.prototype.resize = function() {
                var that = this;

                switch (that.type) {
                    case "top":
                        that.fix2top(false);
                        break;
                    case "bottom":
                        that.fix2bottom(false);
                        break;
                }

                var offset = that.$wrapper.offset();
                that.offset = {
                    left: offset.left,
                    top: offset.top,
                    width: that.$wrapper.outerWidth(),
                    height: that.$wrapper.outerHeight()
                };

                that.$window.trigger("scroll");
            };

            /**
             * @param {Number} scroll_top
             * */
            FixedBlock.prototype.onScroll = function(scroll_top) {
                var that = this,
                    window_w = that.$window.width(),
                    window_h = that.$window.height();

                // update top for dynamic content
                that.offset.top = (that.$clone && that.$clone.is(":visible") ? that.$clone.offset().top : that.$wrapper.offset().top);

                switch (that.type) {
                    case "top":
                        var use_top_fix = (that.offset.top - that.lift < scroll_top);

                        that.fix2top(use_top_fix);
                        break;
                    case "bottom":
                        var use_bottom_fix = (that.offset.top && scroll_top + window_h < that.offset.top + that.offset.height);
                        that.fix2bottom(use_bottom_fix);
                        break;
                }

            };

            /**
             * @param {Boolean|Object} set
             * */
            FixedBlock.prototype.fix2top = function(set) {
                var that = this,
                    fixed_class = "is-top-fixed";

                if (set) {
                    that.$wrapper
                        .css({
                            position: "fixed",
                            top: that.lift,
                            left: that.offset.left
                        })
                        .addClass(fixed_class);

                    that.$clone.css({
                        height: that.offset.height
                    }).show();

                } else {
                    that.$wrapper.removeClass(fixed_class).removeAttr("style");
                    that.$clone.removeAttr("style").hide();
                }

                that.is_fixed = !!set;
            };

            /**
             * @param {Boolean|Object} set
             * */
            FixedBlock.prototype.fix2bottom = function(set) {
                var that = this,
                    fixed_class = "is-bottom-fixed";

                if (set) {
                    that.$wrapper
                        .css({
                            position: "fixed",
                            bottom: 0,
                            left: that.offset.left,
                            width: that.offset.width
                        })
                        .addClass(fixed_class);

                    that.$clone.css({
                        height: that.offset.height
                    }).show();

                } else {
                    that.$wrapper.removeClass(fixed_class).removeAttr("style");
                    that.$clone.removeAttr("style").hide();
                }

                that.is_fixed = !!set;
            };

            return FixedBlock;

        })(jQuery);

        new FixedBlock({
            $wrapper: that.$wrapper,
            $section: that.$wrapper.find(".js-footer-actions"),
            type: "bottom"
        });

    };

    WASettingsSMSTemplate.prototype.initReset = function () {
        var that = this,
            $link = that.$footer_actions.find('.js-reset');

        $link.on('click', function () {
            that.template_areas.each(function () {
                var template_id = $(this).data('template'),
                    ace = that.ace[template_id];

                ace.setValue(that.default_templates[template_id]);
            });

            that.$button.removeClass('green').addClass('yellow');
        });

    };

    return WASettingsSMSTemplate;

})(jQuery);

var WaSettingsSMSNewTemplateDialog = ( function($) {

    WaSettingsSMSNewTemplateDialog = function(id, options) {
        var that = this;
        that.id = id;
        that.path_to_template = options.path_to_template;
    };


    WaSettingsSMSNewTemplateDialog.prototype.open = function() {
        var that = this,
            $dialog_wrapper = that.getDialogWrapper();

        if (!$dialog_wrapper.data('init')) {
            that.init();
            $dialog_wrapper.data('init', true);
        }

        $dialog_wrapper.waDialog({
            width: '400px',
            height: '190px'
        });
    };

    /**
     * Init on first open of dialog
     */
    WaSettingsSMSNewTemplateDialog.prototype.init = function() {
        var that = this,
            $dialog_wrapper = that.getDialogWrapper(),
            $form = $dialog_wrapper.find('form'),
            $dialog_buttons = $dialog_wrapper.find('.dialog-buttons'),
            $button = $dialog_buttons.find('.js-submit-button'),
            $loading = $dialog_buttons.find('.s-loading'),
            is_locked = false;

        // Submit
        $form.on('submit', function (e) {
            e.preventDefault();
            if (is_locked) {
                return;
            }
            is_locked = true;
            $button.prop('disabled', true);
            $form.find('.s-error-message-wrapper').text('');
            $loading.removeClass('yes').addClass('loading').show();

            var href = $form.attr('action'),
                data = $form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    $button.removeClass('yellow').addClass('green');
                    $loading.removeClass('loading').addClass('yes');
                    setTimeout(function(){
                        $loading.hide();
                        $.wa.content.load(that.path_to_template + res.data.id +'/');
                    },2000);
                } else {
                    if (res.errors) {
                        $.each(res.errors, function (i, error) {
                            var $filed = $form.find('[name="data['+ error.field +']"]'),
                                $error_message = $form.find('.js-error-'+error.field);

                            $filed.addClass('error shake animated');
                            $error_message.text(error.message);
                            setTimeout(function(){
                                $error_message.text('');
                                $filed.removeClass('error shake animated');
                            }, 2000);
                        });
                    }
                    $loading.hide();
                }
                is_locked = false;
                $button.prop('disabled', false);
            });
        });
        $form.on('input', function () {
            $button.removeClass('green').addClass('yellow');
        });
    };

    WaSettingsSMSNewTemplateDialog.prototype.getDialogWrapper = function() {
        var that = this,
            id = that.id;
        return $("#" + id);
    };

    return WaSettingsSMSNewTemplateDialog;

})(jQuery);

var WASettingsSMSTemplateSidebar = ( function($) {

    WASettingsSMSTemplateSidebar = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$add_new = that.$wrapper.find('.js-new-templates');

        // VARS
        that.dialog = options.dialog;

        that.initLink();
    };


    WASettingsSMSTemplateSidebar.prototype.initLink = function() {
        var that = this;
        that.$add_new.on('click', function () {
            that.dialog && that.dialog.open();
        });
    };

    return WASettingsSMSTemplateSidebar;

})(jQuery);
