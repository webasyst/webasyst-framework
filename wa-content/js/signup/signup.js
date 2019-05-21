var WaSignup = ( function($) {

    WaSignup = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$form = that.$wrapper.find('form');
        that.namespace = options['namespace'] || 'data';

        // DYNAMIC VARS
        that.is_errors = false;

        that.$templates = {
            error_msg: $('<em class="wa-error-msg"></em>'),
            info_msg: $('<div class="wa-info-msg"></div>')
        };

        that.classes = {
            error_input: 'wa-error',
            error_msg: 'wa-error-msg',
            uncaught_errors: 'wa-uncaught-errors',
            field: 'wa-field'
        };

        that.locale = options['locale'] || {};
        that.locale.required = that.locale.required || 'Required';

        that.timeout = parseInt(options.timeout, 10);
        that.timeout = !isNaN(that.timeout) && that.timeout > 0 ? that.timeout : 60;

        that.js_validate = true;

        that.is_onetime_password_auth_type = options.is_onetime_password_auth_type || false;
        that.onetime_password_url = options.onetime_password_url || '';
        that.is_need_confirm = options.is_need_confirm || false;

        // Default value is TRUE
        that.need_redirects = options.need_redirects !== undefined ? !!options.need_redirects : true;
        that.contact_type = options.contact_type || '';

        // INIT
        that.init();
    };

    WaSignup.prototype.init = function() {
        var that = this;
        that.initSubmit();
        that.initErrorsAutoCleaner();
        that.initAuthAdapters();

        if (that.is_onetime_password_auth_type) {
            that.initOnetimePasswordView();
        }

        if (that.is_need_confirm) {
            that.initNeedConfirmView();
        }

        that.initCaptcha();
    };

    WaSignup.prototype.getFormInput = function(name) {
        var that = this,
            namespace = that.namespace,
            $form = that.$form;

        name = that.normalizeFieldId(name);

        var input_name = name;
        if (name !== 'captcha') {
            input_name = namespace + '[' + name + ']';
        }

        return $form.find('[name="' + input_name + '"]');
    };

    WaSignup.prototype.normalizeFieldId = function (id) {
        var that = this,
            namespace = that.namespace;
        id = $.trim(id || '');
        if (namespace.length && id.indexOf(namespace + '[') !== -1) {
            id = id.replace(namespace + '[', '').replace(']', '');
        }
        return id;
    };

    WaSignup.prototype.getFormField = function (id) {
        var that = this,
            $wrapper = that.$wrapper;
        id = that.normalizeFieldId(id);
        return $wrapper.find('.' + that.classes.field + '[data-field-id="' + id +'"]');
    };

    WaSignup.prototype.triggerEvent = function (event_name) {
        var that = this,
            context = {
                env: 'frontend',
                form_type: 'signup',
                form_wrapper_id: that.$wrapper.attr('id')
            },
            args = Array.prototype.slice.call(arguments),
            args = args.slice(1);
        // Trigger a certain event with its arguments and with 'context' object at the and of arguments list
        that.$wrapper.trigger(event_name, args.concat( [ context ] ));
    };

    /**
     * Format message DOM item ready to place it where needed
     * @param {String} message
     * @param {Boolean} escape
     * @param {String} name
     * @return $() - rendered in memory dom-item
     */
    WaSignup.prototype.formatInfoMessage = function (message, escape, name) {

        // Default value for escape is TRUE
        escape = typeof escape === 'undefined' ? true : escape;

        var that = this,
            $info_msg = that.$templates.info_msg,
            $msg = $info_msg.clone();

        if (name !== undefined) {
            $msg.data('name', name).attr('data-name', name);
        }

        if (escape) {
            return $msg.text($.trim('' + message));
        } else {
            return $msg.html($.trim('' + message));
        }
    };

    WaSignup.prototype.prepareTimeoutErrorItem = function (message, timeout) {
        var that = this,
            $error = that.$templates.error_msg.clone();

        $error.html(message);

        that.runTimeoutMessage($error, {
            timeout: timeout,
            onFinish: function () {
                $error.remove();
            }
        });

        return $error;
    };

    WaSignup.prototype.showErrors = function (all_errors) {

        var that = this,
            $wrapper = that.$wrapper,
            $errors = $wrapper.find('.' + that.classes.uncaught_errors);

        var onCodeOutOfTriesError = function($input) {
            // UX/UI thing: "Disable" next attempt
            $input.attr('readonly', 1);
            $wrapper.find('.wa-confirm-signup-button').attr('disabled', true);
        };

        var onOnetimePasswordOutOfTriesError = function($input) {
            // UX/UI thing: "Disable" next attempt
            $input.attr('readonly', 1);
            $wrapper.find('.wa-done-signup-button').attr('disabled', true);
        };

        $.each(all_errors || {}, function (errors_namespace, errors) {
            var $input = that.getFormInput(errors_namespace);
            if (typeof errors === 'string') {
                errors = [errors];
            }

            var $error_msg = that.$templates.error_msg,
                errors_html = [];

            var code_out_of_tries_error_presents = false,
                onetime_password_out_of_tries_error_presents = false;

            if (errors_namespace === 'timeout') {
                errors_html = that.prepareTimeoutErrorItem(errors.message, errors.timeout);
                errors_html = [errors_html]
            } else {
                $.each(errors || [], function (error_code, error_msg) {
                    error_msg = $.trim('' + error_msg);
                    var $error = $error_msg.clone();
                    $error.data('errorCode', error_code).attr('data-error-code', error_code).text(error_msg);
                    errors_html.push($error);

                    code_out_of_tries_error_presents = code_out_of_tries_error_presents ||
                        errors_namespace === 'confirmation_code' && error_code === 'out_of_tries';

                    onetime_password_out_of_tries_error_presents = onetime_password_out_of_tries_error_presents ||
                        errors_namespace === 'onetime_password' && error_code === 'out_of_tries';
                });
            }

            if ($input.length) {
                $input.parent().append(errors_html);
                $input.addClass(that.classes.error_input);
                if (code_out_of_tries_error_presents) {
                    onCodeOutOfTriesError($input);
                }
                if (onetime_password_out_of_tries_error_presents) {
                    onOnetimePasswordOutOfTriesError($input);
                }
            } else {
                $errors.show().append(errors_html);
            }

            if (errors_namespace === 'email,phone') {
                that.getFormInput('email').addClass(that.classes.error_input);
                that.getFormInput('phone').addClass(that.classes.error_input);
            }
        });

        that.is_errors = true;
        that.triggerEvent('wa_auth_form_change_view');

    };

    WaSignup.prototype.initErrorsAutoCleaner = function () {
        var that = this,
            $form = that.$form;

        $form.on('change', ':input', function () {
            that.clearErrors();
        });

        var contexts = {};
        $form.find(':text').each(function () {
            var $input = $(this),
                name = $input.attr('name'),
                val = $input.val();
            contexts[name] = {
                val: $.trim(val || ''),
                timer: null
            };
        });

        $form.on('submit', function () {
            $.each(contexts, function (name) {
                if (contexts[name] && contexts[name].timer) {
                    clearTimeout(contexts[name].timer);
                }
            });
        });

        $form.on('keydown', ':text', function (e) {
            if (e.keyCode === 13) {
                return;
            }
            var $input = $(this),
                name = $input.attr('name'),
                context = contexts[name] || {},
                prev_val = $.trim(context.val || ''),
                timer = context.timer || null;
            timer && clearTimeout(timer);
            context.timer = setTimeout(function () {
                var val = $.trim($input.val() || '');
                if (val !== prev_val) {
                    that.clearErrors();
                }
                context.val = val;
            }, 300);
        });
    };

    WaSignup.prototype.clearErrors = function () {
        var that = this;
        if (!that.is_errors) {
            return;
        }
        var $wrapper = that.$wrapper;

        $wrapper.find('.' + that.classes.error_input).removeClass(that.classes.error_input);
        $wrapper.find('.' + that.classes.error_msg).not('[data-not-clear=1]').remove();

        if ($wrapper.find('.' + that.classes.uncaught_errors).find('.' + that.classes.error_msg).length <= 0) {
            $wrapper.find('.' + that.classes.uncaught_errors).hide();
        }

        that.is_errors = false;
        that.triggerEvent('wa_auth_form_change_view');
    };

    WaSignup.prototype.hideOauthAdaptersBlock = function() {
        var that = this;
        that.$wrapper.find('.wa-adapters-section').hide();
        that.triggerEvent('wa_auth_form_change_view');
    };

    WaSignup.prototype.beforeJsonPost = function(url, data) {
        var that = this;
        that.hideOauthAdaptersBlock();
        var vars = {
            wa_json_mode: 1,
            need_redirects : that.need_redirects ? 1 : 0,
            contact_type: that.contact_type
        };
        data = that.mixinVarsInData(vars, data);
        return data;
    };

    WaSignup.prototype.mixinVarsInData = function (vars, data) {
        if ($.isPlainObject(data)) {
            data = $.extend(data, vars);
        } else if ($.isArray(data)) {
            $.each(vars, function (key, val) {
                data.push({
                    name: key,
                    value: val
                })
            });
        } else if (data) {
            $.each(vars, function (key, val) {
                data += '&' + key + '=' + val;
            });
        }
        return data;
    };

    WaSignup.prototype.jsonPost = function(url, data) {
        var that = this;
        // prepare data
        data = that.beforeJsonPost(url, data);
        return $.post(url, data, 'json').always(function (r) {
            if (!that.isRedirectResponse(r)) {
                // Not need call redundant refresh request
                $('.wa-captcha-refresh').trigger('click');
            }
        });
    };

    /**
     * Check type of response from json server
     * @param {Object} response
     * @returns {boolean}
     */
    WaSignup.prototype.isRedirectResponse = function (response) {
        return this.getRedirectUrl(response) !== null;
    };

    /**
     * @param {Object} response
     * @returns {String|null} Null if not correct redirect url of it isn't presented
     */
    WaSignup.prototype.getRedirectUrl = function (response) {
        var url = response && response.status === 'ok' && response.data && response.data.redirect_url;
        if (typeof url === 'string') {
            return url;
        } else {
            return null;
        }
    };

    WaSignup.prototype.submit = function (options) {
        options = options || {};

        var that = this,
            $form = options.$form || that.$form,
            $button = options.$button || $form.find(':submit'),
            $loading = options.$loading || $form.find('.wa-loading'),
            url = $form.attr('action'),
            data = $form.serializeArray();

        // $button can be <a> link
        if ($button.attr('disabled') || $button.hasClass('wa-is-disabled')) {
            return;
        }

        that.clearErrors();

        if (that.js_validate) {
            var errors = that.validate();
            if (!$.isEmptyObject(errors)) {
                that.showErrors(errors);
                return;
            }
        }

        $loading.show();

        var disableButton = function (disabled) {
            // $button can be <a> link
            disabled = !!disabled;
            $button.attr('disabled', disabled);
            if (disabled) {
                $button.addClass('wa-is-disabled');
            } else {
                $button.removeClass('wa-is-disabled');
            }
        };

        disableButton(true);

        return that.jsonPost(url, data)
            .done(function (r) {

                if (!that.isRedirectResponse(r)) {
                    // On 'redirect' response
                    // DO NOT hide loading and enable button right away
                    // for UI/UX reason
                    disableButton(false);
                    $loading.hide();
                }

                that.onDoneSubmit(r);
            })
            .fail(function () {
                disableButton(false);
            });
    };

    WaSignup.prototype.onCodeSent = function (data) {
        var that = this,
            $wrapper = that.$wrapper;

        $wrapper.find('.js-signup-form-fields').hide();
        $wrapper.find(".js-signup-form-actions").hide();
        $wrapper.find('.wa-send-again-confirmation-code-link-wrapper').hide();

        // Show captcha for this STEP of view
        $wrapper.find('.wa-field-confirmation-code').after($wrapper.find('.wa-captcha-field'));

        // Move block of errors
        $wrapper.find('.wa-confirm-signup-button-wrapper').prepend($wrapper.find('.wa-uncaught-errors'));

        that.turnOnBlock($wrapper.find('.wa-signup-form-confirmation-block'));

        // Render messages
        var $code_sent_message = that.formatInfoMessage(data.code_sent_message, false),
            $code_sent_timeout_message = that.formatInfoMessage(data.code_sent_timeout_message, false);

        $wrapper.find('.wa-confirmation-code-sent-message').html($code_sent_message);
        $wrapper.find('.wa-confirmation-code-input-message').html($code_sent_timeout_message);
        that.triggerEvent('wa_auth_form_change_view');

        // Run timeout on message
        that.runTimeoutMessage($code_sent_timeout_message, {
            timeout: data.code_sent_timeout,
            onFinish: function () {
                // Show link(s) for re-request to send code again
                $wrapper.find('.wa-send-again-confirmation-code-link-wrapper').show();
                that.triggerEvent('wa_auth_form_change_view');
            }
        });
    };

    WaSignup.prototype.onLinkSent = function (data) {
        var that = this;
        // Render message
        var $message = that.formatInfoMessage(data.confirmation_link_sent_message || '', false);

        // Reset all form and leave just one message
        that.$wrapper.html($message);
        that.triggerEvent('wa_auth_form_change_view');
    };

    WaSignup.prototype.onOnetimePasswordSent = function (data) {
        var that = this,
            $wrapper = that.$wrapper;

        // Channel field is Email or Phone field depends which channel has been choose by server
        var $channel_field = null;
        if (data.used_channel_type === 'email') {
            $channel_field = that.getFormField('email');
            $wrapper.find('.wa-onetime-password-transport-message').html(that.locale.sent_by_email || '');
        } else {
            $channel_field = that.getFormField('phone');
            $wrapper.find('.wa-onetime-password-transport-message').html(that.locale.sent_by_sms || '');
        }

        that.turnOffBlock(that.$wrapper.find('.wa-buttons-wrapper'));
        that.turnOnBlock(that.$wrapper.find('.wa-done-signup-buttons-wrapper'));

        // Link for edit channel field value
        var $edit_link = $channel_field.find('.wa-send-onetime-password-edit-link-wrapper');

        // Hide it until message timer finish (see below)
        $edit_link.hide();


        // "Disable" input
        $channel_field.find(':input').attr('readonly', 1);

        // Field (control) where we have input for type onetime_password
        var $password_field = $wrapper.find('.wa-field-onetime-password');

        // Move right under channel field
        $channel_field.after($password_field);
        // And active it
        that.turnOnBlock($password_field);

        // Link for re-request (re-send) 'onetime_password'
        var $send_again_link = $password_field.find('.wa-send-again-onetime-password-link-wrapper');

        // Hide it until message timer finish (see below)
        $send_again_link.hide();

        // Render message
        var $message = that.formatInfoMessage(data.onetime_password_timeout_message, false);
        $wrapper.find('.wa-onetime-password-input-message').html($message);
        that.triggerEvent('wa_auth_form_change_view');

        // Run timeout on message
        that.runTimeoutMessage($message, {
            timeout: data.onetime_password_timeout,
            onFinish: function () {
                // Timer finish - show links
                $edit_link.show();
                $send_again_link.show();
            }
        });
    };

    WaSignup.prototype.onDoneSubmit = function(r) {
        var that = this;

        // onError - just show errors
        if (!r || r.status !== 'ok') {
            that.showErrors((r && r.errors) || {});
            return;
        }

        r = r || {};
        r.data = r.data || {};

        // onOk - dispatch different responses
        if (r.data.code_sent) {
            that.onCodeSent(r.data);
        } else if (r.data.confirmation_link_sent) {
            that.onLinkSent(r.data);
        } else if (r.data.onetime_password_sent) {
            that.onOnetimePasswordSent(r.data);
        }


        if (that.isRedirectResponse(r)) {
            var url = that.getRedirectUrl(r);
            window.location.href = url;
            return;
        }

        // Trigger event for further processing successfully signed contact
        if (r.data.signup_status === 'ok') {
            that.triggerEvent('wa_auth_contact_signed',

                // contact info
                r.data.contact || {},

                // extra params
                {
                    password_sent: r.data.generated_password_sent
                }
            );
        } else if (r.data.signup_status === 'in_process') {
            if (r.data.confirmation_link_sent) {
                that.triggerEvent('wa_auth_link_sent', {
                    message: r.data.confirmation_link_sent_message || '',
                    transport: 'email'
                });
            } else if (r.data.code_sent) {
                that.triggerEvent('wa_auth_code_sent', {
                    message: r.data.code_sent_message || '',
                    transport: 'sms'
                });
            }
        }

        // Trigger event for further processing successfully authorized contact
        if (r.data.auth_status === 'ok' || r.data.is_auth) {
            that.triggerEvent('wa_auth_contact_logged', r.data.contact || {});
        }

    };

    WaSignup.prototype.initSubmit = function () {
        var that = this,
            $form = that.$form;
        $form.submit(function (e) {
            e.preventDefault();
            that.submit();
        });
    };

    WaSignup.prototype.validate = function () {
        var that = this,
            $form = that.$form,
            data = $form.serializeArray(),
            errors = {};
        $.each(data, function (index, item) {
            var name = item.name,
                value = $.trim(item.value || ''),
                $field = that.getFormField(name);
            if ($field.data('isRequired') && value.length <= 0) {
                var msg = that.locale.required;
                if (that.normalizeFieldId(name) === 'onetime_password') {
                    msg = that.locale.onetime_password_required || msg;
                } else if (that.normalizeFieldId(name) === 'confirmation_code') {
                    msg = that.locale.confirmation_code_required || msg;
                }
                errors[name] = msg;
            }
            if (that.normalizeFieldId(name) === 'captcha' && value.length <= 0) {
                errors['captcha'] = [that.locale.captcha_required || that.locale.required || ''];
            }
        });
        return errors;
    };


    /**
     * Hide block and temporary disabled all inputs inside of this block
     * BUT buttons are move to END of the FORM
     *
     * Temporary disabling of inputs need to prevent they influence to server submit-post processing
     *
     * Eg.
     *  If there is confirmation_code in form
     *  Then on submit post SERVER by arrangement protocol MUST verify that confirmation code
     *
     *  If there is NO confirmation_code in form
     *  Then on submit post SERVER by arrangement protocol MUST generate confirmation code and send by verification channel
     *
     * Why move buttons to END to the FORM?
     *
     *   When in form one button hidden and disabled (eg. button for send code) AND placed FIRST in DOM,
     *   but another button shown and enabled (eg. button for login) AND placed SECOND in DOM
     *   then ENTER key hitting can't work correctly
     *
     *
     * @see turnOnBlock
     * @param $block
     */
    WaSignup.prototype.turnOffBlock = function ($block) {
        var that = this;

        $block.hide();
        $block.find(":input").attr('disabled', true);

        // Buttons move to the end - place temporary dummy item
        $block.find("[type=button],:submit").each(function () {
            var $button = $(this),
                $old_place = $('<div class="wa-js-old-button-place"></div>'),
                $new_place = $('<div class="wa-js-new-button-place"></div>');

            $button.after($old_place);
            $old_place.data('button', $button);

            $new_place.hide();
            that.$form.append($new_place);

            // move from old place to new place
            $new_place.html($button);

        });
        that.triggerEvent('wa_auth_form_change_view');
    };


    /**
     * Show block and restore from restore all back
     * @see turnOffBlock for meaning and example
     * @param $block
     */
    WaSignup.prototype.turnOnBlock = function ($block) {
        var that = this;
        // Buttons un-detaching - re-place temporary dummy item but detached button
        $block.find('.wa-js-old-button-place').each(function () {
            var $old_place = $(this),
                $button = $old_place.data('button'),
                $new_place = $button.parent();
            $old_place.after($button);
            $old_place.remove();
            $new_place.remove();
        });

        $block.show().find(":input").attr('disabled', false);
        that.triggerEvent('wa_auth_form_change_view');

    };

    WaSignup.prototype.initOnetimePasswordView = function () {
        var that = this,
            $wrapper = that.$wrapper;

        // Init UI view

        var initView = function () {

            that.turnOffBlock($wrapper.find('.wa-field-onetime-password'));
            that.turnOffBlock($wrapper.find('.wa-send-onetime-password-edit-link-wrapper'));
            that.turnOffBlock($wrapper.find('.wa-send-again-onetime-password-link-wrapper'));
            that.turnOffBlock($wrapper.find('.wa-done-signup-buttons-wrapper'));

            that.turnOnBlock($wrapper.find('.wa-buttons-wrapper'));

            // Un-Disable for editing email field
            that.getFormInput('email').removeAttr('readonly');
            that.getFormInput('phone').removeAttr('readonly');

            // remove readonly attr, see onOnetimePasswordOutOfTriesError() in that.showErrors()
            that.getFormInput('onetime_password').removeAttr('readonly');
        };

        initView();


        // Init trigger (button or link) to send onetime password
        that.initOnetimePasswordTrigger();

        // Edit link
        $wrapper.on('click', '.wa-send-onetime-password-edit-link', function () {
            // Re-Init UI view
            initView();
        });
    };

    WaSignup.prototype.initOnetimePasswordTrigger = function () {
        var that = this,
            $wrapper = that.$wrapper,
            xhr = null;

        $wrapper.on('click', '.js-send-onetime-password-trigger', function (e) {
            e.preventDefault();

            var $button = $(this),
                $loading = $button.parent().find('.js-send-onetime-password-loading'),
                $onetime_password_input = that.getFormInput('onetime_password');

            // remove readonly attr, see onOnetimePasswordOutOfTriesError() in that.showErrors()
            $onetime_password_input.removeAttr('readonly');

            // IMPORTANT:
            // PROTOCOL detail (!)
            // If we post form WITHOUT 'onetime_password' than it means that we request new 'onetime_password'
            $onetime_password_input.attr('disabled', true);

            // Send submit to server
            var res = that.submit({
                $button: $button,
                $loading: $loading
            });

            // restore 'disabled' status
            // see previous protocol detail comment
            $onetime_password_input.attr('disabled', false);

            if (res) {
                xhr && xhr.abort();
                xhr = res;
            }
        });

    };

    WaSignup.prototype.initNeedConfirmView = function () {
        var that = this,
            $wrapper = that.$wrapper;

        that.turnOffBlock($wrapper.find('.wa-signup-form-confirmation-block'));

        that.initSendAgainConfirmationCodeLink();
        that.initConfirmSignupButton();
    };

    WaSignup.prototype.initConfirmSignupButton = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $button = $wrapper.find('.wa-confirm-signup-button'),
            $loading = $wrapper.find('.wa-confirm-signup-button-icon'),
            xhr = null;

        $button.on('click', function (e) {
            e.preventDefault();

            var res = that.submit({
                $button: $button,
                $loading: $loading
            });

            if (res) {
                xhr && xhr.abort();
                xhr = res;
            }
        });
    };

    WaSignup.prototype.initSendAgainConfirmationCodeLink = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.wa-send-again-confirmation-code-link'),
            $loading = $wrapper.find('.wa-send-again-confirmation-code-link-loading'),
            xhr = null;

        $link.click(function (e) {
            e.preventDefault();

            var $confirmation_code_input = that.getFormInput('confirmation_code');

            // remove readonly attr, see onCodeOutOfTriesError() in that.showErrors()
            $confirmation_code_input.removeAttr('readonly');

            // IMPORTANT:
            // PROTOCOL detail (!)
            // If we post form WITHOUT 'confirmation_code' than it means that we request new 'confirmation_code'
            $confirmation_code_input.attr('disabled', true);

            var res = that.submit({
                $button: $link,
                $loading: $loading
            });

            // restore 'disabled' status
            // see previous protocol detail comment
            $confirmation_code_input.attr('disabled', false);

            if (res) {
                xhr && xhr.abort();
                xhr = res;
            }
        });
    };

    WaSignup.prototype.runTimeoutMessage = function ($message, options) {

        options = options || {};

        var that = this,
            ticks = options.timeout,
            msg = $message.html(),
            onFinish = options.onFinish;

        ticks = parseInt(ticks, 10);
        ticks = !isNaN(ticks) && ticks > 0 ? ticks : 60;

        onFinish = typeof onFinish === 'function' ? onFinish : null;

        if (!msg.match(/\d+:\d/)) {
            return;
        }

        that.triggerEvent('wa_auth_form_change_view');

        var timer = setInterval(function () {
            ticks -= 1;
            if (ticks <= 0) {
                clearInterval(timer);
                $message.remove();
                onFinish && onFinish();
                return;
            }

            var msg = $message.html(),
                minutes = parseInt(ticks / 60, 10),
                seconds = ticks % 60,
                minutes_str = (minutes <= 9 ? ('0' + minutes) : minutes),
                seconds_str = (seconds <= 9 ? ('0' + seconds) : seconds);
            msg = msg.replace(/\d+:\d+/, minutes_str + ':' + seconds_str);
            $message.html(msg);

        }, 1000);

    };

    WaSignup.prototype.initAuthAdapters = function() {
        var that = this;

        var $section = that.$wrapper.find(".wa-adapters-section");
        if (!$section.length) { return false; }

        $section.on("click", "a", function(event) {
            event.preventDefault();
            onProviderClick( $(this) );
        });

        function onProviderClick( $link ) {
            var adapter_id = $link.data("id");
            if (adapter_id) {
                var left = (screen.width-600)/ 2,
                    top = (screen.height-400)/ 2,
                    href = $link.attr("href");

                var new_window = window.open(href, "oauth", "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
            }
        }
    };

    WaSignup.prototype.initCaptcha = function () {
        var that = this,
            $wrapper = that.$wrapper;

        // If recaptcha presented and loaded
        if ($wrapper.find('.wa-captcha-field').length) {
            $(window).one('wa_recaptcha_loaded wa_captcha_loaded', function () {
                that.triggerEvent('wa_auth_form_loaded');
                that.triggerEvent('wa_auth_form_change_view');
            });
        } else {
            that.triggerEvent('wa_auth_form_loaded');
        }
    };

    return WaSignup;

})(jQuery);
