var SitePersonalSettings = ( function($) {

    SitePersonalSettings = function(options) {
        var that = this;

        // DOM
        that.$sidebar = $('#s-content > .sidebar');
        that.$content = $('#s-content > .content');
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find("form");
        that.$form_buttons = that.$form.find('.js-form-buttons');
        that.$footer_actions = that.$form_buttons.find('.js-footer-actions');
        that.$button = that.$footer_actions.find(':submit');
        that.$loading = that.$form.find('.icon16.loading');
        that.$constructor = that.$wrapper.find('.form-constructor');
        that.$enabled_toggle = $("#s-auth-enabled");
        that.$fields = that.$wrapper.find('.js-fields');
        that.$preview = that.$wrapper.find('.js-form-constructor-preview');
        that.$auth_content = that.$wrapper.find('.js-auth-content');
        that.$auth_methods_wrapper = that.$wrapper.find('.js-auth-methods');

        // DIALOGS
        that.$minimum_auth_type_dialog = options["$minimum_auth_type_dialog"];

        // VARS
        that.no_channels = options["no_channels"];
        that.domain_id = options.domain_id || '';
        that.locale = options["locale"];

        // DYNAMIC VARS
        that.fixed_inited = false;
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    SitePersonalSettings.prototype.initClass = function() {
        var that = this;

        // Disabled captcha
        that.$wrapper.find('.wa-captcha-input').prop('disabled', true);

        //
        that.initToggle();
        //
        that.initSelectAuthApp();
        //
        that.initSelectAuthType();
        //
        that.initAuthByLoginToogle();
        //
        that.initRequiredFieldsForMethods();
        //
        that.initAuthAdapters();
        //
        that.initRegisterFormControl();
        //
        that.initLoginFormControl();
        //
        that.initFixedActions();
        //
        that.initErrorsAutoCleaner();
        //
        that.initSubmit();
    };

    SitePersonalSettings.prototype.initToggle = function () {
        var that = this,
            $toggle = that.$enabled_toggle;

        // Init enable toogle
        $toggle.iButton({
            labelOn: "",
            labelOff: "",
            classContainer: 'ibutton-container mini'
        });

        var status_check = function(item) {
            if ($(item).is(':checked')) {
                $('#s-personal-app-enabled-label').removeClass('gray');
            } else {
                $('#s-personal-app-enabled-label').addClass('gray');
            }
        };
        status_check($toggle);

        $toggle.change(function(){
            status_check(this);
            var enabled = $(this).is(':checked') ? 1 : 0,
                href = '?module=personal&action=authEnable&domain_id=' + that.domain_id,
                data = { enabled: enabled };

            $.post(href, data, function () {
                if (enabled) {
                    $('div.s-ibutton-checkbox p.gray').hide();
                    that.$auth_content.show();
                    that.$form_buttons.show();
                    that.$sidebar.animate({ width: 250});
                    that.$content.animate({ marginLeft: 250});
                    if (!that.fixed_inited) {
                        setTimeout(function(){
                            that.initFixedActions();
                        }, 500);
                    }
                } else {
                    $('div.s-ibutton-checkbox p.gray').show();
                    that.$sidebar.css('overflow', 'hidden').animate({ width: 0});
                    that.$content.animate({ marginLeft: 0});
                    that.$auth_content.hide();
                    that.$form_buttons.hide();
                }
            }, "json");
        });

        // Init ALL settings link
        that.$wrapper.on('click', '.js-settings-link', function (e) {
            e.preventDefault();
            var $link = $(this),
                $type_wrapper = $link.parents('tr'),
                $settings_wrapper = $type_wrapper.find('.js-settings-wrapper'),
                visible = $settings_wrapper.is(':visible') ? 1 : 0;

            if (visible) {
                $settings_wrapper.hide();
            } else {
                $settings_wrapper.show();
            }
        });

        // Init verification channel types toogle
        var channel_types_toogle = that.$form.find('.js-auth-method-toogle');
        $.each(channel_types_toogle, function (i, toogle) {
            var $toogle = $(toogle),
                $type_wrapper = $toogle.parents('tr'),
                $settings_link = $type_wrapper.find('.js-settings-link'),
                $disable_hint = $type_wrapper.find('.js-method-disable-hint'),
                $settings_wrapper = $type_wrapper.find('.js-settings-wrapper');

            $toogle.iButton({
                labelOn: "",
                labelOff: "",
                className: 's-inline-toogle',
                classContainer: 'ibutton-container mini'
            });

            $toogle.change(function () {
                setLoginFieldName();
                var used = $toogle.is(':checked') ? 1: 0;
                if (used) {
                    $type_wrapper.removeClass('gray');
                    $settings_link.show();
                    $settings_wrapper.show();
                    $disable_hint.hide();
                } else {
                    $type_wrapper.addClass('gray');
                    $settings_link.hide();
                    $settings_wrapper.hide();
                    $disable_hint.show();
                    checkMinimumMethods();
                }
                that.setFormChanged();
            });

            function checkMinimumMethods() {
                var $active_method_checkbox = that.$auth_methods_wrapper.find('.js-auth-method-toogle:checked'),
                    active_methods = getActiveMethods();

                if ($active_method_checkbox.length === 0 || ($.inArray('email', active_methods) === -1 && $.inArray('sms', active_methods) === -1)) {
                    var $dialog = that.$minimum_auth_type_dialog.clone();
                    $dialog.waDialog({
                        width: '410px',
                        height: '240px',
                        onClose: function () {
                            var $email_methods_toogle = that.$auth_methods_wrapper.find('input[name="used_auth_methods[email]"]');
                            $email_methods_toogle.prop("checked", true).trigger("change");
                        }
                    });
                }
            }

            function setLoginFieldName() {
                var $auth_form_constructor = that.$wrapper.find('.js-login-form-constructor'),
                    $login_wrapper = $auth_form_constructor.find('div[data-field-id="login"]'),
                    $login_name = $login_wrapper.find('.js-editable-item'),
                    $login_name_input = $login_wrapper.find('input[name="login_caption"]'),
                    $login_placeholder_input = $login_wrapper.find('input[name="login_placeholder"]'),
                    active_methods = getActiveMethods(),
                    email = ($.inArray('email', active_methods) !== -1),
                    phone = ($.inArray('sms', active_methods) !== -1),
                    email_and_phone = ($.inArray('email', active_methods) !== -1 && $.inArray('sms', active_methods) !== -1),
                    value = null;

                if (email_and_phone) {
                    value = that.locale.login_names.email_or_phone;
                } else if (email) {
                    value = that.locale.login_names.email;
                } else if (phone) {
                    value = that.locale.login_names.phone;
                }

                if (value) {
                    $login_name.text(value);
                    $login_name_input.val(value);
                    $login_placeholder_input.val(value);
                }
            }

            function getActiveMethods() {
                var $active_method_checkbox = that.$auth_methods_wrapper.find('.js-auth-method-toogle:checked'),
                    active_methods = [];

                $active_method_checkbox.each(function (i, checkbox) {
                    var $checkbox = $(checkbox),
                        method = $checkbox.data('method');

                    active_methods.push(method);
                });

                return active_methods;
            }
        });
    };

    SitePersonalSettings.prototype.initSelectAuthApp = function() {
        var that = this,
            $wrapper = that.$auth_content.find('.js-auth-apps-select'),
            $selected_app_id_input = $wrapper.find('.js-selected-app-id'),
            $selected_app_icon = $wrapper.find('.js-selected-app-icon'),
            $selected_app_name = $wrapper.find('.js-selected-app-name'),
            $endpoin_login_url = $wrapper.find('.js-endpoint-login-url'),
            $endpoin_signup_url = $wrapper.find('.js-endpoint-signup-url');

        $wrapper.on('click', '.js-auth-app', function () {
            var $link = $(this),
                icon = $link.data('icon'),
                name = $link.data('name'),
                route_url = $link.data('route-url'),
                login_url = $link.data('login-url'),
                signup_url = $link.data('signup-url'),
                $menu_v = $link.parents('.menu-v'),
                $li = $link.parent('li');

            $selected_app_id_input.val(route_url);
            $selected_app_name.html($.wa.encodeHTML(name) + ' <span class="hint">(' + $.wa.encodeHTML(route_url) + ')</span>');
            $selected_app_icon.attr('src', icon);
            $endpoin_login_url.text(login_url);
            $endpoin_signup_url.text(signup_url);
            $menu_v.find('li').removeClass('selected');
            $li.addClass('selected');

            // Hide apps list
            $menu_v.hide();
            setTimeout(function() {
                $menu_v.removeAttr('style');
                $menu_v.css({'margin-top': '12px', 'min-width': '300px'});
            }, 200);
        });
    };

    SitePersonalSettings.prototype.initSelectAuthType = function() {
        var that = this,
            $auth_type_wrapper = that.$wrapper.find('.js-auth-type-select'),
            $checkox_signup_confirm = that.$form.find('.js-signup-confirm'),
            $checkbox_in_list = that.$form.find('.js-available-fields-list input[data-field-id="password"]'),
            $required_checkbox = that.$form.find('input[name="fields[password][required]"]'),
            $signup_notify_checkbox = that.$form.find('.js-signup-notify');

        $auth_type_wrapper.on('change', ":radio[name='auth_type']", function () {
            var value = $(this).val(),
                $selected_type_wrapper = $(this).parents('.js-auth-type');

            $auth_type_wrapper.find('.js-auth-type-fields').hide();
            $selected_type_wrapper.find('.js-auth-type-fields').show();

            if (value === 'user_password') {
                $checkbox_in_list.prop('disabled', false).prop('checked', true).prop('disabled', true).trigger('change');
                $required_checkbox.prop('disabled', false).prop('checked', true).prop('disabled', true);
            } else {
                $checkbox_in_list.prop('disabled', false).prop('checked', false).trigger('change').prop('disabled', true);
                $required_checkbox.prop('disabled', false).prop('checked', false).prop('disabled', true);
                $checkox_signup_confirm.prop('checked', false);
            }

            if (value == 'generate_password') {
                $signup_notify_checkbox.prop('disabled', false).prop('checked', true).prop('disabled', true);
            } else if (!that.no_channels) {
                $signup_notify_checkbox.prop('disabled', false);
            }
        }).find(':radio:checked').change();
    };

    SitePersonalSettings.prototype.initAuthByLoginToogle = function () {
        var that = this,
            $toogle = that.$form.find('#s-auth-by-login');

        $toogle.iButton({
            labelOn: "",
            labelOff: "",
            className: 's-inline-toogle',
            classContainer: 'ibutton-container mini'
        });
    };

    SitePersonalSettings.prototype.initRequiredFieldsForMethods = function () {
        var that = this,
            fields_for_methods = [ 'email', 'phone' ],
            $available_fields_list = that.$form.find('.js-available-fields-list'),
            $register_form_constructor = that.$form.find('.js-register-form-constructor'),
            method_toogles = {},
            checkboxes_in_list = {},
            required_checkboxes = {};

        // Build needed checkboxes :]
        $.each(fields_for_methods, function (i, field) {
            method_toogles[field] = that.$form.find('.js-auth-method-toogle[data-registration-linked-field="'+ field +'"]');
            checkboxes_in_list[field] = $available_fields_list.find('.js-available-field[data-id="'+ field +'"]').find(':checkbox');
            required_checkboxes[field] = $register_form_constructor.find('input[name="fields['+ field +'][required]"]');

            // Set on page load
            disablerListCheckbox(field, method_toogles[field]);
        });

        requiredFieldsForUsedMethods();

        $.each(method_toogles, function (field, $toogle) {
            $toogle.on('change', function () {
                disablerListCheckbox(field);
                disablerRequiredCheckbox(field);
                requiredFieldsForUsedMethods();
            });
        });

        function disablerListCheckbox(field) {
            var $field_toogle = method_toogles[field],
                checked = $field_toogle.is(':checked');

            if (checked) {
                checkboxes_in_list[field].prop('checked', true).prop('disabled', true).trigger('change');
            } else {
                checkboxes_in_list[field].prop('disabled', false);
            }
        }

        function disablerRequiredCheckbox(field) {
            var $field_toogle = method_toogles[field],
                checked = $field_toogle.is(':checked');

            if (checked) {
                required_checkboxes[field].prop('checked', true).prop('disabled', true);
            } else {
                required_checkboxes[field].prop('disabled', false);
            }
        }

        function requiredFieldsForUsedMethods() {
            // It is necessary to find out: selected whether both auth methods
            var used_methods = [],
                used_methods_count = 0;
            $.each(method_toogles, function (field, $toogle) {
                if ($toogle.is(':checked')) {
                    used_methods.push(field);
                    used_methods_count++;
                }
            });

            $.each(required_checkboxes, function (field, $required_checkbox) {
                // If both methods are selected, then we remove the disable from the checkboxes "required field"
                if (used_methods_count === 2) {
                    $required_checkbox.prop('disabled', false);

                    // Otherwise, checked and disabled "required field" checkbox in used auth methods
                } else {
                    $.each(used_methods, function (i, field) {
                        required_checkboxes[field].prop('disabled', false).prop('checked', true).prop('disabled', true);
                    });
                }
            });
        }
    };

    SitePersonalSettings.prototype.initAuthAdapters = function() {
        var that = this;

        that.$wrapper.on('click', 'input.adapter', function () {
            var $controls = $(this).parent().find('.js-adapter-controls');
            if ($(this).is(':checked')) {
                $controls.show();
            } else {
                $controls.hide();
            }
        });
    };

    SitePersonalSettings.prototype.initRegisterFormControl = function() {
        var that = this,
            $wrapper = that.$wrapper.find('.js-register-form-wrapper'),
            $data_processing_wrapper = $wrapper.find('.js-data-processing-wrapper'),
            $agreement_text_editor = $data_processing_wrapper.find('.js-text-editor'),
            $agreement_textarea = $agreement_text_editor.find('.js-agreement-text-textarea'),
            $agreement_restore_text = $agreement_text_editor.find('.js-restore-text'),
            $agreement_preview_wrapper = $wrapper.find('.js-preview-agreement-text-wrapper'),
            $agreement_text = that.$wrapper.find('.js-preview-text'),
            $captcha_toogle = $wrapper.find('.js-signup-captcha-toogle'),
            $captcha_status = $wrapper.find('.js-signup-captcha-status'),
            $form_constructor = $wrapper.find('.js-register-form-constructor'),
            $captcha_preview = $form_constructor.find('.js-captcha-preview'),
            $available_fields_wrapper = $wrapper.find('.js-available-fields-list'),
            previous_default_text = null;

        that.initFormConstructor($form_constructor);

        moveTextareaToActiveValue();
        initSortable();

        // Init captcha toogle
        $captcha_toogle.iButton({
            labelOn: "",
            labelOff: "",
            className: "s-captcha-toogle",
            classContainer: 'ibutton-container mini'
        });
        $captcha_toogle.on('change', function () {
            var is_checked = $(this).is(':checked');
            if (is_checked) {
                $captcha_preview.show();
                $captcha_status.text(that.locale.enabled);
            } else {
                $captcha_status.text(that.locale.disabled);
                $captcha_preview.hide();
            }
        });

        // Update agreement preview
        $agreement_textarea.on('keyup keypress change blur', function() {
            $agreement_text.html($agreement_textarea.val());
        });

        // Update textarea and preview visibility when radio is selected
        $data_processing_wrapper.on('change', ':radio', function() {
            if (!$agreement_textarea.val() || previous_default_text == $agreement_textarea.val()) {
                setDefaultText();
            }

            $agreement_textarea.change();

            switch(this.value) {
                case 'notice':
                    $agreement_preview_wrapper.show();
                    $agreement_preview_wrapper.find(':checkbox').hide();
                    $agreement_textarea.closest('.js-text-editor').show();
                    break;
                case 'checkbox':
                    $agreement_preview_wrapper.show();
                    $agreement_preview_wrapper.find(':checkbox').show();
                    $agreement_textarea.closest('.js-text-editor').show();
                    break;
                default:
                    $agreement_preview_wrapper.hide();
                    $agreement_textarea.closest('.js-text-editor').hide();
                    break;
            }

            moveTextareaToActiveValue();
        }).find(':radio:checked').change();

        // Restore default text
        $agreement_restore_text.on('click', function(e) {
            setDefaultText();
            $agreement_textarea.focus();
            return false;
        });

        // Toogle field
        $available_fields_wrapper.on('change', ':checkbox', function () {
            var $checkbox = $(this),
                field_id = $checkbox.data('field-id'),
                is_checked = $checkbox.is(':checked');

            toogleField(field_id, is_checked);
        });

        function moveTextareaToActiveValue() {
            var $active_value = $data_processing_wrapper.find(':radio:checked').closest('.value');
            $agreement_text_editor.appendTo($active_value);
        }

        function setDefaultText() {
            previous_default_text = $data_processing_wrapper.find(':radio:checked').closest('label').data('default-text') || '';
            $agreement_textarea.val(previous_default_text).change();
        }

        function initSortable() {
            var context = $wrapper.find('.js-register-form-constructor');
            context.sortable({
                distance: 5,
                helper: 'clone',
                items: '.js-sortable-field',
                opacity: 0.75,
                handle: '.sort',
                tolerance: 'pointer',
                containment: context
            });
        }

        function toogleField(field_id, is_checked) {
            var $field = getPreviewField(field_id),
                $inputs = $field.find(':input');

            if (is_checked) {
                $field.removeClass('hidden');
                $inputs.attr('disabled', false);
            } else {
                $field.addClass('hidden');
                $inputs.attr('disabled', true);
            }

            function getPreviewField(field_id) {
                return $form_constructor.find('[data-field-id="' + field_id + '"]');
            }
        }
    };

    SitePersonalSettings.prototype.initLoginFormControl = function() {
        var that = this,
            $wrapper = that.$wrapper.find('.js-login-form-wrapper'),
            $form_constructor = $wrapper.find('.js-login-form-constructor'),
            $captcha_wrapper = $wrapper.find('.js-captcha-wrapper'),
            $captcha_preview = $form_constructor.find('.js-captcha-preview'),
            $remember_toogle = $wrapper.find('.js-rememberme-auth-toogle'),
            $remember_status = $wrapper.find('.js-rememberme-auth-status'),
            $remember_preview = $form_constructor.find('.js-remember-me-preview');

        that.initFormConstructor($form_constructor);

        // Init login captcha preview
        $captcha_wrapper.on('change', ':radio', function() {
            if (this.value == 'always') {
                $captcha_preview.show();
            } else {
                $captcha_preview.hide();
            }
        }).find(':radio:checked').change();

        // Init remember me checkbox toogle
        $remember_toogle.iButton({
            labelOn: "",
            labelOff: "",
            className: "s-rememberme-toogle",
            classContainer: 'ibutton-container mini'
        });
        $remember_toogle.on('change', function () {
            var is_checked = $(this).is(':checked');
            if (is_checked) {
                $remember_status.text(that.locale.enabled);
                $remember_preview.show();
            } else {
                $remember_status.text(that.locale.disabled);
                $remember_preview.hide();
            }
        });
    };

    SitePersonalSettings.prototype.showErrors = function(errors) {
        var that = this,
            $form = that.$form;
        $.each(errors, function (name, errormsg) {
            $form.find('[name="' + name + '"]').addClass('error').after('<em class="errormsg">' + $.wa.encodeHTML(errormsg) + '</em>');
        });
    };

    SitePersonalSettings.prototype.clearErrors = function() {
        var that = this,
            $form = that.$form;
        $form.find('.errormsg').remove();
        $form.find('.error').removeClass('error');
    };

    SitePersonalSettings.prototype.initErrorsAutoCleaner = function() {
        var that = this,
            $form = that.$form;
        $form.on('change', ':input', function () {
            that.clearErrors();
        });
    };

    SitePersonalSettings.prototype.initSubmit = function() {
        var that = this,
            $form = that.$form;

        $form.on('input', function () {
            that.setFormChanged();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }

            that.clearErrors();

            var errors = validateFields();
            if (errors) {
                return false;
            }

            that.is_locked = true;
            that.$button.prop('disabled', true);
            that.$loading.removeClass('yes').removeClass('no').addClass('loading').show();

            var href = $form.attr('action'),
                data = $form.serialize();

            $.post(href, data)
                .done(function (res) {
                    if (res.status === 'ok') {
                        that.$loading.removeClass('loading').addClass('yes').show();
                        that.setFormChanged(false);
                    } else {
                        that.$loading.removeClass('loading').addClass('no').show();
                        that.showErrors(res.errors);
                    }
                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                }).always(function () {
                    that.is_locked = false;
                    that.$button.prop('disabled', false);
                });
        });

        function validateFields() {
            var errors = false;

            // Check all template fields
            var $template_selects = that.$auth_methods_wrapper.find('select.js-template');

            $template_selects.each(function (i, select) {
                var $select = $(select),
                    $mathod_wrapper = $select.parents('tr'),
                    $settings_wrapper = $mathod_wrapper.find('.js-settings-wrapper'),
                    $toogle = $mathod_wrapper.find('.js-auth-method-toogle');

                if ($toogle.is(':checked') && !$.trim($select.val())) {
                    errors = true;
                    $settings_wrapper.show();
                    $select.addClass('shake animated');
                    setTimeout(function(){
                        $select.removeClass('shake animated');
                    },500);
                }
            });

            return errors;
        }
    };

    SitePersonalSettings.prototype.setFormChanged = function (status) {
        var that = this;
        status = status !== undefined ? status : true;

        if (status) {
            that.$button.removeClass('green').addClass('yellow');
        } else {
            that.$button.removeClass('yellow').addClass('green');
        }
    };

    SitePersonalSettings.prototype.initFormConstructor = function ($form_constructor) {
        var $enabled_items = $form_constructor.find('.js-editable-item');

        $enabled_items.each(function () {
            initItem($(this));
        });

        function initItem($item) {
            var $input = $item.next();
            if (!$input.is(':input')) {
                return;
            }

            $item.closest('.editable-wrapper').addClass('editor-off');

            var switchEls = function(){
                $item.addClass('hidden');
                $input.removeClass('hidden').focus();
                $item.closest('.editable-wrapper').removeClass('editor-off').addClass('editor-on');
            };

            $item.on('click', function(){
                switchEls();
                return false;
            });

            $input.on('blur', function(){
                $input.addClass('hidden');
                $item.removeClass('hidden');
                $item.closest('.js-editable-wrapper').removeClass('editor-on').addClass('editor-off');
                if ($input.is('.show-when-editable')) {
                    $input.siblings('.show-when-editable').addClass('hidden');
                }
                if ($item.hasClass('js-editable-button')) {
                    $item.val($input.val());
                } else {
                    $item.text($input.val());
                }
            });

            $input.on('keydown', function(e){
                var code = e.keyCode || e.which;

                switch (code) {
                    case 13: // on enter, esc
                    case 27:
                        $(this).trigger('blur');
                        return;
                    default:
                        break;
                }
            });
        }
    };

    SitePersonalSettings.prototype.initFixedActions = function() {
        var that = this;

        if (that.fixed_inited || !that.$form_buttons.is(':visible')) {
            return;
        }

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

        that.fixed_inited = true;
    };

    return SitePersonalSettings;

})(jQuery);
