var WASettingsEmail = ( function($) {

    WASettingsEmail = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$form = that.$wrapper.find('form');
        that.$items_wrapper = that.$form.find('.js-settings-items');
        that.$item_add = that.$wrapper.find('.js-add-item');
        that.$item_template = that.$wrapper.find('.js-template');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$form.find('.s-loading');
        that.item_class = ".js-item";
        that.item_remove_class = ".js-remove";
        that.key_class = ".js-key";
        that.transport_class = ".js-transport";
        that.dkim_checkbox_class = ".js-dkim-checkbox";

        // VARS

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsEmail.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="email"]').addClass('selected');
        //
        that.initChangeTransport();
        //
        that.initDkim();
        //
        that.initAddRemoveItem();
        //
        that.initSubmit();
    };

    WASettingsEmail.prototype.initChangeTransport = function() {
        var that = this;

        that.$wrapper.on('change', that.transport_class, function () {
            var $item = $(this).parents(that.item_class),
                transport = $item.find(that.transport_class).val();

            $item.find('.js-params').hide(); // Hide all params
            $item.find('.js-transport-description').css('display', 'none'); // Hide all descriptions
            $item.find('.js-'+ transport +'-description').css('display', 'inline-block'); // Show needed description
            $item.find('.js-'+ transport +'-params').show(); // Show needed params
        });
    };

    WASettingsEmail.prototype.initDkim = function () {
        var that = this;


        that.$wrapper.on('change', that.dkim_checkbox_class, function () {
            var $dkim_checkbox = $(this),
                $item = $dkim_checkbox.parents(that.item_class),
                is_on = $dkim_checkbox.is(':checked');

            if (is_on) {
                dkim($item, 'generateDkim');
            } else {
                dkim($item, 'removeDkim');
            }
        });

        // Remove dkim settings if email or domain is changed
        that.$wrapper.on('input', that.key_class, function () {
            var $item = $(this).parents(that.item_class);
            if (!$.trim($(this).val())) {
                dkim($item, 'showNeedEmail');
            } else {
                dkim($item, 'hideNeedEmail');
            }
        });


        function dkim($item, action) {
            var $dkim_checkbox = $item.find('.js-dkim-checkbox'),
                $dkim_sender_input = $item.find('.js-key'),
                $dkim_wrapper = $item.find('.js-dkim-field'),
                $dkim_private_key = $dkim_wrapper.find('.js-dkim-pvt-key'),
                $dkim_public_key = $dkim_wrapper.find('.js-dkim-pub-key'),
                $dkim_selector = $dkim_wrapper.find('.js-dkim-selector'),
                $dkim_info = $item.find('.js-dkim-info'),
                $dkim_one_string_key = $dkim_wrapper.find('.js-one-string-key'),
                $dkim_host_selector = $dkim_wrapper.find('.js-dkim-host-selector'),
                $dkim_domain_0 = $dkim_wrapper.find('.js-sender-domain-0'),
                $dkim_domain = $dkim_wrapper.find('.js-domain'),
                $dkim_needs_email = $dkim_wrapper.find('.js-dkim-needs-email'),
                $dkim_error = $dkim_wrapper.find('.js-dkim-error');

            if (action === "generateDkim") {
                var email = $.trim($dkim_sender_input.val()),
                    href = '?module=settingsGenerateDkim',
                    data = { email: email };

                $dkim_error.slideUp().text('');
                $.post(href, data, function(r) {
                    if (r.status == 'ok') {
                        $dkim_private_key.val(r.data.dkim_pvt_key);
                        $dkim_public_key.val(r.data.dkim_pub_key);
                        $dkim_selector.val(r.data.selector);
                        $dkim_one_string_key.text(r.data.one_string_key);
                        $dkim_host_selector.text(r.data.selector);
                        $dkim_domain_0.text(r.data.domain);
                        $dkim_domain.text(r.data.domain);
                        $dkim_info.slideDown();
                    } else if (r.status == 'fail' && r.errors) {
                        $dkim_error.text(r.errors[0]).slideDown();
                    }
                }, 'json')
                    .error(function() {
                        $dkim_error.text('Failed to create DKIM signature').slideDown();
                    });
            } else if (action === "removeDkim") {
                $dkim_info.slideUp();
                setTimeout(function () {
                    removeDkimData();
                }, 150);
            } else if (action === "hideNeedEmail") {
                $dkim_needs_email.hide();
                $dkim_checkbox.prop('checked', false);
                $dkim_info.slideUp();
                setTimeout(function () {
                    removeDkimData();
                }, 150);
            } else if (action === "showNeedEmail") {
                $dkim_needs_email.show();
                $dkim_checkbox.prop('checked', false);
                $dkim_info.slideUp();
                setTimeout(function () {
                    removeDkimData();
                }, 150);
            }

            function removeDkimData() {
                $dkim_error.slideUp().text('');
                $dkim_private_key.val('');
                $dkim_public_key.val('');
                $dkim_selector.val('');
                $dkim_one_string_key.text('');
                $dkim_host_selector.text('');
                $dkim_domain_0.text('');
                $dkim_domain.text('');
            }
        }
    };

    WASettingsEmail.prototype.initAddRemoveItem = function () {
        var that = this;

        // Add item
        that.$item_add.on('click', function (e) {
            e.preventDefault();
            var $item = that.$item_template.clone().removeClass('js-template').addClass('js-item');
            $item.find('.js-key').val('');
            that.$items_wrapper.append($item);
            that.$form.trigger('input');
        });

        // Remove item
        that.$wrapper.on('click', that.item_remove_class, function (e) {
            e.preventDefault();
            var $item = $(this).parents(that.item_class);
            $item.remove();
            that.$form.trigger('input');
        });
    };

    WASettingsEmail.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();

            // Set attribute name for all item fields
            // by data-name attribute
            var $all_items = that.$items_wrapper.find('.js-item');
            $.each($all_items, function (i, item) {
                setNames($(item));
            });

            // Send post
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
                    setTimeout(function(){
                        that.$loading.hide();
                    }, 2000);
                } else if (res.errors) {
                    $.each(res.errors, function (i, error) {
                        if (error.field) {
                            fieldError(error);
                        }
                    });
                    that.$loading.hide();
                } else {
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });

            function setNames($item) {
                var item_key = $item.find(that.key_class).val(),
                    item_fields = $item.find('[data-name]');

                if (typeof item_key !== 'string' || !item_key) {
                    return;
                }

                $.each(item_fields, function (i, field) {
                    var $field = $(field);
                    $field.attr('name', 'data['+ item_key +']['+ $field.data('name') +']');
                });
            }
        });

        function fieldError(error) {
            var $field = that.$form.find('input[name='+error.field+']'),
                $hint = $field.parent('.value').find('.js-error-place');

            $field.addClass('shake animated').focus();
            $hint.text(error.message);
            setTimeout(function(){
                $field.removeClass('shake animated').focus();
                $hint.text('');
            }, 1000);
        }

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

    return WASettingsEmail;

})(jQuery);

var WASettingsEmailTemplate = ( function($) {

    WASettingsEmailTemplate = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$template_text = that.$wrapper.find('.js-template-text');
        that.$form = that.$wrapper.find('form');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');
        that.$email_check_dialog = options["$email_check_dialog"];
        that.$requirement_to_save = options["$requirement_to_save"];

        // VARS
        that.channel_id = options["channel_id"];
        that.cheat_sheet_name = options["cheat_sheet_name"];
        that.default_template = options["default_template"];
        that.template_id = options.template_id || '';

        // DYNAMIC VARS
        that.is_locked = false;
        that.ace = null;

        // INIT
        that.initClass();
    };

    WASettingsEmailTemplate.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="email-template"]').addClass('selected');

        if (that.$template_text.length) {
            that.initAce();
            //
            that.initCheatSheet();
            //
            that.initFixedActions();
            //
            that.initReset();
        }
        //
        that.initInlineSettingsLink();
        //
        that.initCheck();
        //
        that.initSubmit();
    };

    WASettingsEmailTemplate.prototype.initAce = function() {
        var that = this,
            div = $('<div></div>');

        // Init Ace
        that.$template_text.parent().prepend($('<div class="ace"></div>').append(div));
        that.$template_text.hide();
        that.ace = ace.edit(div.get(0));
        // Set options
        that.ace.commands.removeCommand('find');
        ace.config.set("basePath", window.wa_url + 'wa-content/js/ace/');
        that.ace.setTheme("ace/theme/eclipse");
        that.ace.renderer.setShowGutter(false);
        var session = that.ace.getSession();
        session.setMode("ace/mode/smarty");
        if (navigator.appVersion.indexOf('Mac') != -1) {
            that.ace.setFontSize(13);
        } else if (navigator.appVersion.indexOf('Linux') != -1) {
            that.ace.setFontSize(16);
        } else {
            that.ace.setFontSize(14);
        }
        if (that.$template_text.val().length) {
            session.setValue(that.$template_text.val());
        } else {
            session.setValue(' ');
        }
        that.ace.setOption("minLines", 5);
        that.ace.setOption("maxLines", 100);
        session.on('change', function () {
            that.$template_text.val(that.ace.getValue());
        });
    };

    WASettingsEmailTemplate.prototype.initCheatSheet = function () {
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
                    if (that.ace) {
                        that.ace.insert(el.text());
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
                    key: 'email_template_' + that.template_id,
                    need_cache: 1
                }
            },
            function () {

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

    WASettingsEmailTemplate.prototype.initInlineSettingsLink = function() {
        var that = this,
            $inline_settings_link = that.$wrapper.find('.js-inline-settings-link');

        $inline_settings_link.on('click', function () {
            var $current_channel = $(document).find('.js-channel[data-id="'+ that.channel_id +'"]');
            $current_channel.find('.js-channel-edit').click();
        });
    };

    WASettingsEmailTemplate.prototype.initCheck = function() {
        var that = this,
            $dialog_wrapper = that.$email_check_dialog,
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
                    height: '126px'
                });
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
            $form.find('.js-field-error').text('');

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

                            var $input = $form.find('input[name="data' + field + '"]').parent();
                            $input.addClass('shake animated');
                            $input.after('<div class="js-field-error" style="color: red; margin-left: 12px;">'+ message +'</div>');
                            setTimeout(function(){
                                $input.removeClass('shake animated');
                                $input.next().remove();
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

    WASettingsEmailTemplate.prototype.initSubmit = function () {
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
                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                } else {
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

    WASettingsEmailTemplate.prototype.initFixedActions = function() {
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

    WASettingsEmailTemplate.prototype.initReset = function () {
        var that = this,
            $link = that.$footer_actions.find('.js-reset'),
            ace = that.ace.getSession(),
            $subject = that.$wrapper.find('.js-subject'),
            subject = $('<div />').text(that.default_template["subject"]).html();

        $link.on('click', function () {
            $subject.val(subject);
            ace.setValue(that.default_template["text"]);
            that.$button.removeClass('green').addClass('yellow');
        });
    };

    return WASettingsEmailTemplate;

})(jQuery);

var WASettingsEmailTemplateSidebar = ( function($) {

    WASettingsEmailTemplateSidebar = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$new_templates_group_dialog = options["$new_templates_group_dialog"];
        that.$edit_channel_dialog = options["$edit_channel_dialog"];
        that.$delete_confirm_dialog = options["$delete_confirm_dialog"];
        that.$add_new = that.$wrapper.find('.js-new-templates');
        that.$sidebar_items_wrapper = that.$wrapper.find('.js-sidebar-items');

        // VARS
        that.channel_id = options["channel_id"];
        that.path_to_template = options["path_to_templates"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    WASettingsEmailTemplateSidebar.prototype.initClass = function() {
        var that = this;

        //
        that.initExpandCollapseChannelTemplates();
        //
        that.initNewTemplatesGroup();
        //
        that.initEditChannel();
    };

    WASettingsEmailTemplateSidebar.prototype.initExpandCollapseChannelTemplates = function() {
        var that = this,
            storage_name = 'wa/settings/expand-email-templates',
            expand_templates = getExpandTemplates();

        if ($.inArray(that.channel_id, expand_templates) === -1) {
            expand_templates.push(that.channel_id);
        }

        // Expand on start template groups
        $.each(expand_templates, function (i, channel_id) {
            var $channel = that.$sidebar_items_wrapper.find('.js-channel[data-id="'+ channel_id +'"]');
            expandOrCollapse($channel);
        });

        // On click
        that.$sidebar_items_wrapper.on('click', '.js-expand-collapse', function () {
            var $channel = $(this).parents('.js-channel');
            expandOrCollapse($channel);
        });

        function expandOrCollapse($channel) {
            var channel_id = $channel.data('id'),
                $expand_collapse_icon = $channel.find('.js-expand-collapse-icon'),
                $settings_icon = $channel.find('.js-channel-edit'),
                $channel_templates = that.$sidebar_items_wrapper.find('.js-template[data-channel-id="'+ channel_id +'"]'),
                action = null;

            if ($expand_collapse_icon.hasClass('rarr')) {
                action = 'show';
                $expand_collapse_icon.removeClass('rarr').addClass('darr');
                $settings_icon.show();
                $channel_templates.each(function (i, template) {
                    $(template).show();
                });
            } else {
                action = 'hide';
                $expand_collapse_icon.removeClass('darr').addClass('rarr');
                $settings_icon.hide();
                $channel_templates.each(function (i, template) {
                    $(template).hide();
                });
            }

            if (action == 'show' && $.inArray(channel_id, expand_templates) === -1) {
                expand_templates.push(channel_id);
            } else if (action == 'hide') {
                expand_templates = $.grep(expand_templates, function(id) {
                    return id != channel_id;
                });
            }

            setExpandTemplates(expand_templates);
        }

        function getExpandTemplates() {
            var local_storage = ( localStorage.getItem(storage_name) || "[]" );
            return JSON.parse(local_storage);
        }

        function setExpandTemplates(expand_templates) {
            localStorage.setItem(storage_name, JSON.stringify(expand_templates));
        }
    };

    WASettingsEmailTemplateSidebar.prototype.initNewTemplatesGroup = function() {
        var that = this,
            $dialog_wrapper = that.$new_templates_group_dialog,
            $form = $dialog_wrapper.find('form'),
            $dialog_buttons = $dialog_wrapper.find('.dialog-buttons'),
            $button = $dialog_buttons.find('.js-submit-button'),
            $loading = $dialog_buttons.find('.s-loading'),
            is_locked = false;

        that.$add_new.on('click', function () {
            $dialog_wrapper.waDialog({
                width: '400px',
                height: '190px'
            });
        });

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

    WASettingsEmailTemplateSidebar.prototype.initEditChannel = function () {
        var that = this;

        that.$sidebar_items_wrapper.on('click', '.js-channel-edit', function () {
            var $channel = $(this).parents('.js-channel'),
                channel_id = $channel.data('id'),
                channel_name = $channel.data('name'),
                channel_email = $channel.data('email'),
                channel_system = $channel.data('system'),
                $dialog_wrapper = that.$edit_channel_dialog.clone(),
                $form = $dialog_wrapper.find('form'),
                $dialog_buttons = $dialog_wrapper.find('.dialog-buttons'),
                $button = $dialog_buttons.find('.js-submit-button'),
                $loading = $dialog_buttons.find('.s-loading'),
                is_locked = false;

            $dialog_wrapper.find('.js-channel-name').text(channel_name);
            $dialog_wrapper.find('.js-email-select').val(channel_email);
            if (channel_system) {
                $dialog_wrapper.find('.js-name-text').text(channel_name).show();
            } else {
                $dialog_wrapper.find('.js-delete').show();
                $dialog_wrapper.find('.js-name-input').val(channel_name).show();
            }

            $dialog_wrapper.waDialog({
                width: '400px',
                height: '190px'
            });

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

                var href = '?module=settingsTemplateEmailEdit&id='+ channel_id,
                    data = $form.serialize();

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $button.removeClass('yellow').addClass('green');
                        $loading.removeClass('loading').addClass('yes');
                        setTimeout(function(){
                            $loading.hide();
                            $.wa.content.reload();
                            $dialog_wrapper.trigger('close');
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

            // Duplicate and Delete channel
            var $duplicate_link = $dialog_buttons.find('.js-duplicate'),
                $delete_link = $dialog_buttons.find('.js-delete');

            $duplicate_link.on('click', function () {
                var href = '?module=settingsTemplateDuplicate',
                    data = {id: channel_id};

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $.wa.content.load(that.path_to_template + res.data.id +'/');
                        $dialog_wrapper.trigger('close');
                    } else {
                        $.wa.content.reload();
                    }
                });
            });

            $delete_link.on('click', function () {
                var href = '?module=settingsTemplateDelete',
                    data = {id: channel_id};

                that.$delete_confirm_dialog.waDialog({
                    width: '400px',
                    height: '100px',
                    onLoad: function () {
                        $dialog_wrapper.hide();
                    },
                    onSubmit: function () {
                        $.post(href, data, function () {
                            $.wa.content.load(that.path_to_template);
                        });
                        return false;
                    },
                    onCancel: function () {
                        $dialog_wrapper.show();
                    }
                });
            });
        });
    };

    return WASettingsEmailTemplateSidebar;

})(jQuery);
