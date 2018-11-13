var WASettingsAuth = ( function($) {

    WASettingsAuth = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$form = that.$wrapper.find('form');
        that.$auth_methods = that.$wrapper.find('.js-auth-methods');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');

        that.$backgrounds_wrapper = that.$wrapper.find('.js-background-images');
        that.$preview_wrapper = that.$wrapper.find('.js-custom-preview-wrapper');
        that.$background_input = that.$wrapper.find('input[name="auth_form_background"]');
        that.$upload_preview_background_wrapper = that.$wrapper.find('.js-upload-preview');

        // VARS
        that.locale = options['locale'];

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsAuth.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="auth"]').addClass('selected');

        // Disabled captcha
        that.$wrapper.find('.wa-captcha-input').prop('disabled', true);

        //
        that.initSelectAuthType();
        //
        that.initAuthMethods();
        //
        that.initRemembermeToogle();
        //
        that.initChangeBackground();
        //
        that.initUploadCustomBackground();
        //
        that.initRemoveCustomBackground();
        //
        that.initLoginFormControl();
        //
        that.initSubmit();
    };

    WASettingsAuth.prototype.initSelectAuthType = function () {
        var that = this,
            $auth_type_wrapper = that.$wrapper.find('.js-auth-type-select');

        $auth_type_wrapper.on('change', ":radio[name='auth_type']", function () {
            var $selected_type_wrapper = $(this).parents('.js-auth-type');
            $auth_type_wrapper.find('.js-auth-type-fields').hide();
            $selected_type_wrapper.find('.js-auth-type-fields').show();
        });
    };

    WASettingsAuth.prototype.initAuthMethods = function() {
        var that = this,
            $auth_form_constructor = that.$wrapper.find('.js-login-form-constructor'),
            $login_wrapper = $auth_form_constructor.find('div[data-field-id="login"]'),
            $login_name = $login_wrapper.find('.js-editable-item'),
            $login_name_input = $login_wrapper.find('input[name="login_caption"]'),
            $login_placeholder_input = $login_wrapper.find('input[name="login_placeholder"]'),
            $wrapper = that.$auth_methods;

        $wrapper.on('change', '.js-auth-method-checkbox', function () {
            var active_methods = getActiveMethods(),
                email = ($.inArray('email', active_methods) !== -1),
                phone = ($.inArray('sms', active_methods) !== -1),
                email_and_phone = ($.inArray('email', active_methods) !== -1 && $.inArray('sms', active_methods) !== -1),
                value = null;

            if (!active_methods.length) {
                $wrapper.find('.js-auth-method-checkbox[name="used_auth_methods[email]"]').prop('checked', true).trigger('change'); // default method
                return;
            }

            if (email_and_phone) {
                value = that.locale.login_names.login_or_phone;
            } else if (email) {
                value = that.locale.login_names.login;
            } else if (phone) {
                value = that.locale.login_names.phone;
            }

            if (value) {
                $login_name.text(value);
                $login_name_input.val(value);
                $login_placeholder_input.val(value);
            }
        });

        function getActiveMethods() {
            var $active_method_checkbox = $wrapper.find('.js-auth-method-checkbox:checked'),
                active_methods = [];

            $active_method_checkbox.each(function (i, checkbox) {
                var $checkbox = $(checkbox),
                    method = $checkbox.data('method');

                active_methods.push(method);
            });

            return active_methods;
        }

    };

    WASettingsAuth.prototype.initRemembermeToogle = function () {
        var that = this,
            $remember_toogle = that.$wrapper.find('.js-rememberme-auth-toogle'),
            $remember_status = that.$wrapper.find('.js-rememberme-auth-status');

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
            } else {
                $remember_status.text(that.locale.disabled);
            }
        });
    };

    WASettingsAuth.prototype.initChangeBackground = function() {
        var that = this;

        that.$backgrounds_wrapper.on('click', 'li > a', function () {
            var $image = $(this),
                value = $image.data('value');

            that.$backgrounds_wrapper.find('.selected').removeClass('selected');
            $image.parents('li').addClass('selected');
            that.$background_input.val(value);
            that.$form.trigger('input');

            if (value.match(/^stock:/)) {
                that.$wrapper.find('.js-stretch-checkbox').prop('disabled', true);
            } else {
                that.$wrapper.find('.js-stretch-checkbox').prop('disabled', null);
            }

            return false;
        });
    };

    WASettingsAuth.prototype.initUploadCustomBackground = function () {
        var that = this,
            $upload_error = that.$wrapper.find('.js-error-upload');

        that.$wrapper.on('change', '.js-background-upload', function (e) {
            e.preventDefault();

            if (!$(this).val()) {
                return;
            }

            var href = "?module=settingsUploadCustomBackground",
                image = new FormData();

            image.append("image", $(this)[0].files[0]);

            // Remove all custom image
            // Preview in list
            var old_value = that.$preview_wrapper.find('.js-custom-background-preview').data('value');
            that.$backgrounds_wrapper.find('a[data-value="'+ old_value +'"]').parents('li').remove();
            // Big preview
            that.$preview_wrapper.html('');

            that.$upload_preview_background_wrapper.find('.loading').show();

            $.ajax({
                url: href,
                type: 'POST',
                data: image,
                cache: false,
                contentType: false,
                processData: false
            }).done(function(res) {
                var $preview_template = $(that.$wrapper.find('.js-preview-template').html()),
                    $list_preview_template = that.$wrapper.find('.js-list-preview-template').clone();

                if (res.status == 'ok') {
                    // Set value in hidden field
                    that.$background_input.val(res.data.file_name);

                    // Set big preview
                    $preview_template.find('.js-custom-background-preview').attr('data-value', res.data.file_name);
                    $preview_template.find('.js-image-img').attr('src', res.data.img_path);
                    $preview_template.find('.js-image-width').text(res.data.width);
                    $preview_template.find('.js-image-height').text(res.data.height);
                    $preview_template.find('.js-image-size').text(res.data.file_size_formatted);
                    $preview_template.find('.stretch').removeAttr('style').find('.js-stretch-checkbox').removeAttr('disabled');

                    // Set preview in images list
                    $list_preview_template.find('a').attr('data-value', res.data.file_name);
                    $list_preview_template.find('img').attr('src', res.data.img_path).attr('alt', res.data.file_name);
                    $list_preview_template.removeClass('js-list-preview-template').removeAttr('style');

                    that.$backgrounds_wrapper.find('.selected').removeClass('selected');
                    that.$backgrounds_wrapper.append($list_preview_template);

                    that.$preview_wrapper.html($preview_template);
                } else if (res.errors) {
                    $upload_error.text(res.errors);
                    setTimeout(function(){
                        $upload_error.text('');
                    }, 5000);
                }

            }).always(function () {

            });
            that.$upload_preview_background_wrapper.find('.loading').hide();
            $(this).val('');
        });
    };

    WASettingsAuth.prototype.initRemoveCustomBackground = function () {
        var that = this;

        that.$wrapper.on('click', '.js-remove-custom-backgorund', function (e) {
            var $dialog_text = that.$wrapper.find('.js-remove-text').clone(),
                dialog_buttons = that.$wrapper.find('.js-remove-buttons').clone().html(),
                value = $(this).parents('.js-custom-background-preview').data('value');

            $dialog_text.show();
            e.preventDefault();
            // Show confirm dialog
            $($dialog_text).waDialog({
                'buttons': dialog_buttons,
                'width': '500px',
                'height': '65px',
                'min-height': '65px',
                onSubmit: function (d) {
                    var href = '?module=settingsRemoveCustomBackground';

                    $.get(href, function (res) {
                        that.$backgrounds_wrapper.find('a[data-value="'+ value +'"]').parents('li').remove();
                        that.$preview_wrapper.html('');
                        that.$backgrounds_wrapper.find('a[data-value="stock:bokeh_vivid.jpg"]').click();
                    });

                    d.trigger('close'); // close dialog
                    $('.dialog').remove(); // remove dialog
                    return false;
                }
            });
        });
    };

    WASettingsAuth.prototype.initLoginFormControl = function() {
        var that = this,
            $form_constructor = that.$wrapper.find('.js-login-form-constructor'),
            $captcha_wrapper = that.$wrapper.find('.js-captcha-wrapper'),
            $captcha_preview = $form_constructor.find('.js-captcha-preview'),
            $remember_toogle = that.$wrapper.find('.js-rememberme-auth-toogle'),
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

        // Init remember me preview
        $remember_toogle.on('change', function () {
            var is_checked = $(this).is(':checked');
            if (is_checked) {
                $remember_preview.show();
            } else {
                $remember_preview.hide();
            }
        });
    };

    WASettingsAuth.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            var errors = validateFields();
            if (errors) {
                return false;
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

        function validateFields() {
            var errors = false;

            // Check all template fields
            var $template_selects = that.$auth_methods.find('select.js-template');

            $template_selects.each(function (i, select) {
                var $select = $(select),
                    $method_wrapper = $select.parents('.js-auth-method'),
                    $method_checkbox = $method_wrapper.find('.js-auth-method-checkbox');

                if ($method_checkbox.is(':checked') && !$.trim($select.val())) {
                    errors = true;
                    $select.addClass('shake animated');
                    setTimeout(function(){
                        $select.removeClass('shake animated');
                    },500);
                }
                if (errors) {
                    return errors;
                }
            });

            return errors;
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

    //

    WASettingsAuth.prototype.initFormConstructor = function ($form_constructor) {
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

    return WASettingsAuth;

})(jQuery);