/**
 * @class FixedBlock
 * @description used for fixing form buttons
 * */
class FixedBlock {

    constructor(options) {
        let that = this;

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
    }

    initClass() {
        let that = this,
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
            let is_exist = $.contains($window[0].document, that.$wrapper[0]);
            if (is_exist) {
                that.onScroll($window.scrollTop());
            } else {
                $window.off("scroll", watcher);
            }
        }

        that.$wrapper.data("block", that);
    }

    init() {
        let that = this;

        if (!that.$clone) {
            let $clone = $("<div />").css("margin", "0");
            that.$wrapper.after($clone);
            that.$clone = $clone;
        }

        that.$clone.hide();

        let offset = that.$wrapper.offset();

        that.offset = {
            left: offset.left,
            top: offset.top,
            width: that.$wrapper.outerWidth(),
            height: that.$wrapper.outerHeight()
        };
    }

    resize() {
        let that = this;

        switch (that.type) {
            case "top":
                that.fix2top(false);
                break;
            case "bottom":
                that.fix2bottom(false);
                break;
        }

        let offset = that.$wrapper.offset();
        that.offset = {
            left: offset.left,
            top: offset.top,
            width: that.$wrapper.outerWidth(),
            height: that.$wrapper.outerHeight()
        };

        that.$window.trigger("scroll");
    }

    /**
     * @param {Number} scroll_top
     * */
    onScroll(scroll_top) {
        let that = this,
            window_w = that.$window.width(),
            window_h = that.$window.height();

        // update top for dynamic content
        that.offset.top = (that.$clone && that.$clone.is(":visible") ? that.$clone.offset().top : that.$wrapper.offset().top);

        switch (that.type) {
            case "top":
                let use_top_fix = (that.offset.top - that.lift < scroll_top);

                that.fix2top(use_top_fix);
                break;
            case "bottom":
                let use_bottom_fix = (that.offset.top && scroll_top + window_h < that.offset.top + that.offset.height);
                that.fix2bottom(use_bottom_fix);
                break;
        }
    }

    /**
     * @param {Boolean|Object} set
     * */
    fix2top(set) {
        let that = this,
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
    }

    /**
     * @param {Boolean|Object} set
     * */
    fix2bottom(set) {
        let that = this,
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
    }
}

class WASettingsEmail {
    constructor(options) {
        let that = this;

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

        that.locales = options.locales;

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    }

    initClass() {
        let that = this;
        //
        let $sidebar = $('#js-sidebar-wrapper');
        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="email"]').addClass('selected');
        //
        that.initChangeTransport();
        //
        that.initDkim();
        //
        that.initAddRemoveItem();
        //
        that.initSubmit();
    }

    initChangeTransport() {
        let that = this;

        that.$wrapper.on('change', that.transport_class, function () {
            let $item = $(this).parents(that.item_class),
                transport = $item.find(that.transport_class).val();

            $item.find('.js-params').hide(); // Hide all params
            $item.find('.js-transport-description').css('display', 'none'); // Hide all descriptions
            $item.find('.js-'+ transport +'-description').css('display', 'inline-block'); // Show needed description
            $item.find('.js-'+ transport +'-params').show(); // Show needed params
        });
    }

    initDkim() {
        let that = this;


        that.$wrapper.on('change', that.dkim_checkbox_class, function () {
            let $dkim_checkbox = $(this),
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
            let $item = $(this).parents(that.item_class);
            if (!$.trim($(this).val())) {
                dkim($item, 'showNeedEmail');
            } else {
                dkim($item, 'hideNeedEmail');
            }
        });


        function dkim($item, action) {
            let $dkim_checkbox = $item.find('.js-dkim-checkbox'),
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
                let email = $.trim($dkim_sender_input.val()),
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
    }

    initAddRemoveItem () {
        let that = this;

        // Add item
        that.$item_add.on('click', function (e) {
            e.preventDefault();
            const $item = that.$item_template.clone().removeClass('js-template').addClass('js-item');
            const $itemNameInput = $item.find('.js-key');
            $itemNameInput.val('');
            that.$items_wrapper.prepend($item);
            that.$form.trigger('input');

            $item.find(that.transport_class).trigger('change');

            $itemNameInput.on('keyup', function() {
                $(this).removeClass('state-error');
                $(this).siblings('.js-error').remove();
            });
        });

        // Remove item
        that.$wrapper.on('click', that.item_remove_class, function (e) {
            e.preventDefault();
            let $item = $(this).parents(that.item_class);
            $item.remove();
            that.$form.trigger('input');
        });
    }

    initSubmit () {
        let that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();

            // Set attribute name for all item fields
            // by data-name attribute
            let $all_items = that.$form.find('.js-item');
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

            let href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow');
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
                let item_key = $item.find(that.key_class).val(),
                    item_fields = $item.find('[data-name]');

                if (!item_key.length) {
                    // prevent form sending if have no value in input
                    const $error = $(`<div class="state-error js-error">${that.locales.required}</div>`);
                    $item.find(that.key_class).addClass('state-error').after($error);
                    $item.find(that.key_class)[0].scrollIntoView({block: "center", behavior: "smooth"})
                    that.is_locked = true;
                    return;
                }

                that.is_locked = false;

                if (typeof item_key !== 'string' || !item_key) {
                    return;
                }

                $.each(item_fields, function (i, field) {
                    let $field = $(field);
                    $field.attr('name', 'data['+ item_key +']['+ $field.data('name') +']');
                });
            }
        });

        function fieldError(error) {
            let $field = that.$form.find('input[name='+error.field+']'),
                $hint = $field.parent('.value').find('.js-error-place');

            $field.addClass('shake animated').focus();
            $hint.text(error.message);
            setTimeout(function(){
                $field.removeClass('shake animated').focus();
                $hint.text('');
            }, 1000);
        }

        that.$form.on('input change', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.addClass('yellow');
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
        });
    }
}

class WASettingsEmailTemplate {
    constructor(options) {
        let that = this;

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
    }

    initClass() {
        let that = this;

        //
        let $sidebar = $('#js-sidebar-wrapper');
        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="email-template"]').addClass('selected');

        if (that.$template_text.length) {
            that.initAce();
            //
            that.initCheatSheet();
            //
            //that.initFixedActions();
            //
            that.initReset();
        }
        //
        that.initInlineSettingsLink();
        //
        that.initCheck();
        //
        that.initSubmit();
    }

    initAce() {
        let that = this,
            div = $('<div></div>');

        // Init Ace
        that.$template_text.parent().prepend($('<div class="ace"></div>').append(div));
        that.$template_text.hide();
        that.ace = ace.edit(div.get(0));
        // Set options
        that.ace.commands.removeCommand('find');
        ace.config.set("basePath", window.wa_url + 'wa-content/js/ace/');

        setEditorTheme();
        document.documentElement.addEventListener('wa-theme-change', setEditorTheme);

        function setEditorTheme() {
            const theme = document.documentElement.dataset.theme;

            if (theme === 'dark') {
                that.ace.setTheme("ace/theme/monokai");
            } else {
                that.ace.setTheme("ace/theme/eclipse");
            }
        }

        that.ace.renderer.setShowGutter(false);
        let session = that.ace.getSession();
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
        that.ace.setOption("minLines", 10);
        that.ace.setOption("maxLines", 100);
        session.on('change', function () {
            that.$template_text.val(that.ace.getValue());
        });
    }

    initCheatSheet() {
        let that = this,
            cheat_sheet_name = that.cheat_sheet_name;

        let getViewRight = function() {
            return ($(window).width() - (that.$wrapper.offset().left + that.$wrapper.outerWidth()));
        };

        $(document).on('wa_cheatsheet_init.' + cheat_sheet_name, function () {
            $.cheatsheet[cheat_sheet_name].insertVarEvent = function () {
                $("#wa-editor-help-" + cheat_sheet_name).on('click', ".js-var", function () {
                    if (that.ace) {
                        that.ace.insert($(this).text());
                        that.$button.addClass('yellow');
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
                    let $help = $("#wa-editor-help-" + cheat_sheet_name);


                    let getHelpRight = function() {
                        return $(window).width() - ($help.offset().left + $help.outerWidth());
                    };

                    let adjustHelpOffset = function () {
                        if ($help.length) {
                            $help.css('right', 0);
                            let diff = getHelpRight() - getViewRight();
                            $help.css('right', (-diff) + 'px');
                        }
                    };

                    let watcher = function() {
                        let timer = setInterval(function () {
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
    }

    initInlineSettingsLink() {
        let that = this,
            $inline_settings_link = that.$wrapper.find('.js-inline-settings-link');

        $inline_settings_link.on('click', function () {
            let $current_channel = $(document).find('.js-channel[data-id="'+ that.channel_id +'"]');
            $current_channel.find('.js-channel-edit').click();
        });
    }

    initCheck() {
        let that = this,
            $dialog_wrapper = that.$email_check_dialog,
            is_locked = false;

        that.$wrapper.on('click', '.js-check-button', function () {
            if (that.$button.hasClass('yellow')) {
                $.waDialog({
                    $wrapper: that.$requirement_to_save
                });
            } else {
                $.waDialog({
                    $wrapper: $dialog_wrapper,
                    onOpen($dialog, dialog) {
                        const $form = $dialog.find('form'),
                            $dialog_buttons = $form.find('.dialog-footer'),
                            $button = $dialog_buttons.find('.js-submit-button'),
                            $loading = $dialog_buttons.find('.loading');

                        $form.on('submit', function (e) {
                            e.preventDefault();
                            if (is_locked) {
                                return;
                            }
                            is_locked = true;
                            $button.prop('disabled', true);
                            $loading.show();
                            $form.find('.js-field-error').text('');

                            let href = $form.attr('action'),
                                data = $form.serialize();

                            $.post(href, data, function (res) {
                                if (res.status === 'ok') {
                                    $button.removeClass('yellow');
                                    setTimeout(function(){
                                        $loading.hide();
                                        dialog.close();
                                    },2000);
                                } else {
                                    if (res.errors) {
                                        $.each(res.errors, function (i, error) {
                                            let field = error.field,
                                                message = error.message;

                                            let $input = $form.find('input[name="data' + field + '"]'),
                                                $input_parent = $input.parent();
                                            $input_parent.addClass('shake animated');
                                            $input.after('<p class="js-field-error state-error-hint">'+ message +'</p>');
                                            setTimeout(function(){
                                                $input_parent.removeClass('shake animated');
                                                $input_parent.find('.js-field-error').remove();
                                            },2000);
                                        })
                                    }
                                    $loading.hide();
                                    is_locked = false;
                                    $button.prop('disabled', false);
                                }
                            });
                        });
                    }
                });
            }
        });
    }

    initSubmit() {
        let that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            that.is_locked = true;
            that.$button.prop('disabled', true);
            that.$loading.removeClass('yes').addClass('loading').show();

            let href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow');
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

        that.$form.on('input change', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.addClass('yellow');
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
        });
    }

    /**
     * @deprecated
     */
    initFixedActions() {
        let that = this;
        new FixedBlock({
            $wrapper: that.$wrapper,
            $section: that.$wrapper.find(".js-footer-actions"),
            type: "bottom"
        });
    }

    initReset() {
        let that = this,
            $link = that.$form.find('.js-reset'),
            ace = that.ace.getSession(),
            $subject = that.$wrapper.find('.js-subject'),
            subject = $('<div />').text(that.default_template["subject"]).html();

        $link.on('click', function () {
            $subject.val(subject);
            ace.setValue(that.default_template["text"]);
            that.$button.addClass('yellow');
        });
    }
}

class WASettingsEmailTemplateSidebar {
    constructor(options) {
        let that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$new_templates_group_dialog = options["$new_templates_group_dialog"];
        that.$edit_channel_dialog = options["$edit_channel_dialog"];
        that.$delete_confirm_dialog = options["$delete_confirm_dialog"];
        that.$add_new = that.$wrapper.find('.js-new-templates');

        // VARS
        that.channel_id = options["channel_id"];
        that.path_to_template = options["path_to_templates"];

        // DYNAMIC VARS

        // INIT
        that.initClass();
    }

    initClass() {
        let that = this;

        //
        that.initExpandCollapseChannelTemplates();
        //
        that.initNewTemplatesGroup();
        //
        that.initEditChannel();
    }

    initExpandCollapseChannelTemplates() {
        let that = this,
            $wrapper = that.$wrapper[0],
            storage_name = 'wa/settings/expand-email-templates',
            expand_templates = getExpandTemplates();

        if (!expand_templates.some((id) => id == that.channel_id)) {
            expand_templates.push(that.channel_id);
        }

        // Expand on start template groups
        expand_templates.forEach((channel_id) => {
            let $channel = $wrapper.querySelector('.js-channel[data-id="'+ channel_id +'"]');
            if ($channel) {
                expandOrCollapse($channel);
            }
        })

        // On click
        $($wrapper).on('click', '.js-expand-collapse', function (e) {
            e.preventDefault();
            let $channel = this.closest('.js-channel');
            expandOrCollapse($channel);
        });

        function expandOrCollapse($channel) {
            let channel_id = $channel.dataset.id,
                expand_collapse_icon_class = $channel.querySelector('.js-expand-collapse-icon').classList,
                $settings_icon = $channel.querySelector('.js-channel-edit'),
                $channel_templates = $channel.querySelectorAll('.js-template[data-channel-id="'+ channel_id +'"]'),
                action;

            if (expand_collapse_icon_class.contains('rarr')) {
                action = 'show';
                expand_collapse_icon_class.remove('rarr')
                expand_collapse_icon_class.add('darr');
                $settings_icon.style.display = 'block';
                $channel_templates.forEach(function (template) {
                    template.style.display = 'block';
                });
            } else {
                action = 'hide';
                expand_collapse_icon_class.remove('darr')
                expand_collapse_icon_class.add('rarr');
                $settings_icon.style.display = 'none';
                $channel_templates.forEach(function (template) {
                    template.style.display = 'none';
                });
            }

            if (action === 'show' && !expand_templates.some((id) => id == channel_id)) {
                expand_templates.push(parseInt(channel_id, 10));
            } else if (action === 'hide') {
                expand_templates = expand_templates.filter((id) => id != channel_id)
            }

            setExpandTemplates(expand_templates);
        }

        function getExpandTemplates() {
            let local_storage = ( localStorage.getItem(storage_name) || "[]" );
            return JSON.parse(local_storage);
        }

        function setExpandTemplates(expand_templates) {
            localStorage.setItem(storage_name, JSON.stringify(expand_templates));
        }
    }

    initNewTemplatesGroup() {
        let that = this,
            $dialog_wrapper = that.$new_templates_group_dialog,
            is_locked = false;

        that.$add_new.on('click', function (e) {
            e.preventDefault();
            $.waDialog({
                $wrapper: $dialog_wrapper,
                onOpen($dialog, dialog) {
                    const $form = $dialog.find('form'),
                        $dialog_buttons = $dialog.find('.dialog-footer'),
                        $button = $dialog_buttons.find('.js-submit-button'),
                        $loading = $dialog_buttons.find('.loading');

                    $form.on('submit', function (e) {
                        e.preventDefault();
                        if (is_locked) {
                            return;
                        }

                        is_locked = true;
                        $button.prop('disabled', true);
                        $form.find('.s-error-message-wrapper').text('');
                        $loading.show();

                        let href = $form.attr('action'),
                            data = $form.serialize();

                        $.post(href, data, function (res) {
                            if (res.status === 'ok') {
                                $button.removeClass('yellow');
                                setTimeout(function () {
                                    $loading.hide();
                                    $.wa.content.load(that.path_to_template + res.data.id + '/');
                                    dialog.close();
                                }, 2000);
                            } else {
                                if (res.errors) {
                                    $.each(res.errors, function (i, error) {
                                        let $filed = $form.find('[name="data[' + error.field + ']"]'),
                                            $error_message = $form.find('.js-error-' + error.field);

                                        $filed.addClass('error shake animated');
                                        $error_message.text(error.message);
                                        setTimeout(function () {
                                            $error_message.text('');
                                            $filed.removeClass('error shake animated');
                                        }, 2000);
                                    });
                                }
                                $loading.hide();
                                is_locked = false;
                                $button.prop('disabled', false);
                            }
                        });
                    })
                        .on('input', function () {
                            $button.addClass('yellow');
                        });
                }
            });
        });
    }

    initEditChannel() {
        let that = this;

        that.$wrapper.on('click', '.js-channel-edit', function (e) {
            e.preventDefault();
            let $channel = $(this).parents('.js-channel'),
                channel_id = $channel.data('id'),
                channel_name = $channel.data('name'),
                channel_email = $channel.data('email'),
                channel_system = $channel.data('system'),
                $dialog_wrapper = that.$edit_channel_dialog.clone(),
                is_locked = false;

            $.waDialog({
                html: $dialog_wrapper,
                onOpen($dialog, dialog) {
                    const $form = $dialog.find('form'),
                        $dialog_buttons = $dialog.find('.dialog-footer'),
                        $button = $dialog_buttons.find('.js-submit-button'),
                        $loading = $dialog_buttons.find('.loading');

                    $dialog.find('.js-channel-name').text(channel_name);
                    $dialog.find('.js-email-select').val(channel_email);
                    if (channel_system) {
                        $dialog.find('.js-name-text').text(channel_name).show();
                    } else {
                        $dialog.find('.js-delete').show();
                        $dialog.find('.js-name-input').val(channel_name).show();
                    }

                    $form.on('submit', function (e) {
                        e.preventDefault();
                        if (is_locked) {
                            return;
                        }
                        is_locked = true;
                        $button.prop('disabled', true);
                        $form.find('.s-error-message-wrapper').text('');
                        $loading.addClass('loading').show();

                        let href = '?module=settingsTemplateEmailEdit&id='+ channel_id,
                            data = $form.serialize();

                        $.post(href, data, function (res) {
                            if (res.status === 'ok') {
                                $button.removeClass('yellow');
                                setTimeout(function(){
                                    $loading.hide();
                                    $.wa.content.reload();
                                    dialog.close();
                                },2000);
                            } else {
                                if (res.errors) {
                                    $.each(res.errors, function (i, error) {
                                        let $filed = $form.find('[name="data['+ error.field +']"]'),
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
                                is_locked = false;
                                $button.prop('disabled', false);
                            }
                        });
                    })
                        .on('input', function () {
                        $button.addClass('yellow');
                    });

                    // Duplicate and Delete channel
                    let $duplicate_link = $dialog_buttons.find('.js-duplicate'),
                        $delete_link = $dialog_buttons.find('.js-delete');

                    $duplicate_link.on('click', function () {
                        let href = '?module=settingsTemplateDuplicate',
                            data = {id: channel_id};

                        $.post(href, data, function (res) {
                            if (res.status === 'ok') {
                                $.wa.content.load(that.path_to_template + res.data.id +'/');
                                dialog.close();
                            } else {
                                $.wa.content.reload();
                            }
                        });
                    });

                    $delete_link.on('click', function () {
                        let href = '?module=settingsTemplateDelete',
                            data = {id: channel_id};

                        $.waDialog({
                            $wrapper:that.$delete_confirm_dialog,
                            onOpen($del_dialog, del_dialog) {
                                dialog.hide();
                                let $form = $del_dialog.find('form');
                                $form.on('submit', function (e) {
                                    e.preventDefault()
                                    $.post(href, data, function () {
                                        $.wa.content.load(that.path_to_template);
                                        $.wa.content.reload();
                                       // location.reload();
                                        del_dialog.close();
                                        dialog.close();
                                    });
                                });
                            },
                            onClose() {
                                dialog.show();
                            }
                        });
                    });
                }
            });
        });
    }
}
