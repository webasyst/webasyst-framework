var WaFrontendLogin = ( function($) {

    var WaFrontendLogin = function(options) {
        this.init(options);
    };

    var Self = WaFrontendLogin;
    Self.className = 'WaFrontendLogin';
    var Parent = WaLoginAbstractLoginForm;

    // "inherit"
    Self.prototype = Object.create(Parent.prototype);
    Self.prototype.constructor = Self;

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);
        that.initView();
    };

    Self.prototype.initVars = function (options) {
        var that = this;
        options = options || {};
        that.$templates = {
            confirm_email_error: $('<div class="wa-error-msg wa-confirm-email-error"></div>')
        };
        that.classes = {
            field: 'wa-field'
        };
        Parent.prototype.initVars.call(that, options);
        that.className = Self.className;
        that.is_onetime_password_auth_type = options.is_onetime_password_auth_type || false;
        that.env = 'frontend';
        that.is_need_confirm = options.is_need_confirm || false;
    };

    Self.prototype.initView = function () {
        var that = this;

        if (that.isOneTimePasswordMode()) {
            that.initOnetimePasswordView();
        }

        if (that.isNeedConfirm()) {
            that.initConfirmView();
        }

        that.initAuthAdapters();
    };

    Self.prototype.initAuthAdapters = function() {
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
                var width = $link.data('width') || 600,
                    height = $link.data('height') || 500,
                    left = (screen.width - width)/ 2,
                    top = (screen.height - height)/ 2,
                    href = $link.attr("href");

                window.open(href, "oauth", "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top +
                    ",status=no,toolbar=no,menubar=no");
            }
        }
    };

    Self.prototype.isNeedConfirm = function () {
        return this.is_need_confirm;
    };

    Self.prototype.initConfirmView = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $edit_link = $wrapper.find('.wa-edit-login-link-wrapper'),
            $sent_link = $wrapper.find('.wa-send-again-confirmation-code-link-wrapper'),
            $confirm_block = $wrapper.find('.wa-signup-form-confirmation-block'),
            $login = that.getFormInput('login');

        // see onConfirmPhoneError

        that.turnOffBlock($confirm_block);
        that.turnOffBlock($edit_link);
        that.turnOffBlock($sent_link);

        $edit_link.click(function () {
            that.turnOffBlock($confirm_block);
            $login.removeAttr('readonly');

            var $form = that.getFormItem(),
                $button = $form.find(':submit:first:not(:disabled)');

            if (!$button.length) {
                // see prepareErrorItem for 'confirmation_code' error_namespace
                $button = $form.find(':submit:first');
                $button.attr('disabled', false);

                var $confirmation_code_input = that.getFormInput('confirmation_code');
                $confirmation_code_input.removeAttr('readonly').val('');
            }

            // when once click on edit link, edit link should be hidden (cause already in edit mode)
            that.turnOffBlock($edit_link);
        });

        $sent_link.click(function () {
            // re-request code - disable confirmation code input
            that.turnOffBlock($confirm_block);

            var $form = that.getFormItem(),
                $button = $form.find(':submit:first:not(:disabled)');

            if (!$button.length) {

                // see prepareErrorItem for 'confirmation_code' error_namespace

                $button = $form.find(':submit:first');
                $button.attr('disabled', false);

                var $confirmation_code_input = that.getFormInput('confirmation_code');
                $confirmation_code_input.removeAttr('readonly').val('');

            }

            // trigger submit
            $button.trigger('click');
        });
    };

    Self.prototype.initOnetimePasswordView = function () {
        var that = this,
            $wrapper = that.$wrapper;

        var initView = function () {
            // Off actual submit button
            that.turnOffBlock($wrapper.find('.wa-buttons-wrapper'));

            // On request onetime password button
            that.turnOnBlock($wrapper.find('.wa-request-onetime-password-button-wrapper'));

            // Off password field
            that.turnOffBlock(that.getFormField('password'));

            // Hide link for re-request password
            $wrapper.find('.wa-send-again-onetime-password-link-wrapper').hide();

            // Hide link for edit login
            $wrapper.find('.wa-send-onetime-password-edit-link-wrapper').hide();

            // Un-Disable for editing email field
            that.getFormInput('login').removeAttr('readonly');
        };

        initView();


        // Init 'button' for request onetime password
        // On success request new password will be called that.onSentOnetimePassword
        that.initOnetimePasswordLink({
            $link: $wrapper.find('.wa-request-onetime-password-button'),
            $loading: $wrapper.find('.wa-request-onetime-password-button-loading')
        });

        // Init 'link' for re-request ('sent again') onetime password
        // On success request new password will be called that.onSentOnetimePassword
        that.initOnetimePasswordLink({
            $link: $wrapper.find('.wa-send-again-onetime-password-link'),
            $loading: $wrapper.find('.wa-send-again-onetime-password-link-loading')
        });

        // Edit link
        $wrapper.on('click', '.wa-send-onetime-password-edit-link', function () {
            // Re-Init UI view
            initView();
        });

    };

    /**
     * When onetime password successfully sent to client
     * It is template, overridden method, that will be called in Parent class
     * @see Parent
     * @param data
     */
    Self.prototype.onSentOnetimePassword = function (data) {
        var that = this,
            $wrapper = that.$wrapper;
        data = data || {};

        // Show sent message
        if (data.used_channel_type === 'email') {
            $wrapper.find('.wa-onetime-password-transport-message').html(that.locale.sent_by_email || '');
        } else if (data.used_channel_type === 'sms') {
            $wrapper.find('.wa-onetime-password-transport-message').html(that.locale.sent_by_sms || '');
        }

        // Format message about timeout
        var $timeout_message = that.formatInfoMessage(data.onetime_password_timeout_message, false, 'timeout');

        // Place messages
        $wrapper.find('.wa-onetime-password-input-message').html($timeout_message);

        var $password_block = that.getFormField('password'),
            $password_input = that.getFormInput('password');

        // Show PASSWORD filed itself
        that.turnOnBlock($password_block);

        // see Parent.prepareErrorItem for password out_of_tries error in onetime time password mode
        $password_input.removeAttr('readonly').val('');

        // Show actual SUBMIT button
        that.turnOnBlock($wrapper.find('.wa-buttons-wrapper'));

        // Hide Request button
        that.turnOffBlock($wrapper.find('.wa-request-onetime-password-button-wrapper'));

        // "Disable" LOGIN input
        that.getFormInput('login').attr('readonly', 1);

        // Hide link(s) for re-request password again AND edit login
        var $sent_link = $wrapper.find('.wa-send-again-onetime-password-link-wrapper').hide(),
            $edit_link = $wrapper.find('.wa-send-onetime-password-edit-link-wrapper').hide();

        // Run timer inside message
        that.runTimeoutMessage($timeout_message, {
            timeout: data.onetime_password_timeout,
            onFinish: function () {
                // Show link(s)
                $sent_link.show();
                $edit_link.show();
            }
        });

    };

    Self.prototype.prepareErrorText = function (error_namespace, error, error_code) {
        var that = this;
        if (error_namespace === 'login' && error_code === 'confirm_email' || error_namespace === 'confirm_email') {
            return $.trim(error || '');
        } else {
            return Parent.prototype.prepareErrorText.apply(that, arguments);
        }
    };

    Self.prototype.getErrorTemplate = function (error_namespace, error, error_code) {
        var that = this;
        if (error_namespace === 'login' && error_code === 'confirm_email' || error_namespace === 'confirm_email') {
            return that.$templates.confirm_email_error;
        } else {
            return Parent.prototype.getErrorTemplate.apply(that, arguments);
        }
    };

    Self.prototype.prepareErrorItem = function (error_namespace, error, error_code) {
        var that = this,
            $error;

        if (error_namespace === 'login' && error_code === 'confirm_email' || error_namespace === 'confirm_email') {

            // This error has javascript inside it, but in earlier versions of jq (e.g. 1.8.2) inserting js in memory DOM item
            // eval js right away - and it is not what we need. Js must executed only when whole error in REAL DOM (not memory)

            var substitute = ':ERROR-' + ('' + Math.random()).slice(2) + ':';
            $error = Parent.prototype.prepareErrorItem.call(that, error_namespace, substitute, error_code);
            $error.attr('data-not-clear', '1');

            var $tmp = $('<div>').append($error),
                error_html = $tmp.html();

            error_html = error_html.replace(substitute, error);

            // delete previous the same error
            that.getErrorItem(error_namespace, error_code).remove();

            return error_html;
        }

        $error = Parent.prototype.prepareErrorItem.call(that, error_namespace, error, error_code);

        if (error_namespace === 'confirmation_code' && error_code === 'out_of_tries') {
            // OUT of tries error case
            // UX/UI thing: "Disable" next attempt
            var $confirmation_code_input = that.getFormInput('confirmation_code'),
                $form = that.getFormItem();
            $confirmation_code_input.attr('readonly', true);
            $form.find('.wa-login-submit').attr('disabled', true);
            return $error;
        }


        return $error;
    };

    Self.prototype.hideOauthAdaptersBlock = function() {
        var that = this;
        that.$wrapper.find('.wa-adapters-section').hide();
    };

    Self.prototype.beforeJsonPost = function (url, data) {
        var that = this;
        that.hideOauthAdaptersBlock();
        return Parent.prototype.beforeJsonPost.call(that, url, data);
    };

    Self.prototype.beforeErrorTimerStart = function(message, timeout, options) {
        var that = this;
        Parent.prototype.beforeErrorTimerStart(message, timeout, options);
        /*
        if (options.error_namespace === 'confirmation_code') {
            var $confirmation_code_input = that.getFormInput('confirmation_code');
            $confirmation_code_input.attr('readonly', 1);
        }*/
    };

    Self.prototype.afterErrorTimerFinish = function(message, timeout, options) {
        var that = this;
        Parent.prototype.afterErrorTimerFinish(message, timeout, options);
        /*
        if (options.error_namespace === 'confirmation_code') {
            var $confirmation_code_input = that.getFormInput('confirmation_code');
            $confirmation_code_input.removeAttr('readonly');
        }*/

        var $wrapper = that.$wrapper,
            $sent_link = $wrapper.find('.wa-send-again-confirmation-code-link-wrapper');

        $sent_link.show();
    };

    Self.prototype.clearTimeoutMessage = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $message_block = $wrapper.find('.wa-confirmation-code-input-message');

        var $timeout_message = $message_block.find('.' + that.classes.message_msg + '[data-name="timeout"]');
        if ($timeout_message.length) {
            var timer = $timeout_message.data('timer');
            if (timer) {
                timer.finish();
            }
        }
    };

    Self.prototype.onConfirmPhoneError = function (response) {
        var that = this,
            $wrapper = that.$wrapper,
            $edit_link = $wrapper.find('.wa-edit-login-link-wrapper'),
            $sent_link = $wrapper.find('.wa-send-again-confirmation-code-link-wrapper'),
            $confirm_block = $wrapper.find('.wa-signup-form-confirmation-block'),
            $confirm_input = $wrapper.find('[name="confirmation_code"]'),
            $login = that.getFormInput('login');

        that.turnOnBlock($edit_link);
        that.turnOnBlock($confirm_block);

        // "Disable" LOGIN input
        $login.attr('readonly', 1);

        if ($confirm_input) {
            $confirm_input.focus();
        }

        var data = response.data,
            errors = response.errors;

        var $message_block = $wrapper.find('.wa-confirmation-code-input-message');

//        $message_block.html('');

        // Code not sent
        if (!data.code_sent) {
            if (errors.confirmation_code && errors.confirmation_code.timeout) {
                that.clearTimeoutMessage(); // stop previous timer on "message" DOM element, and remove DOM element
            }
            return;
        }

        var $code_sent_message = $(),
            $timeout_message = $();

        // Special message about what happens
        if (data.code_sent_message) {
            $code_sent_message = that.formatInfoMessage(data.code_sent_message, false, 'code_sent_message');
            $message_block.append($code_sent_message);
        }

        // Format message about timeout
        if (data.code_sent_timeout_message) {
            that.clearTimeoutMessage(); // stop previous timer on "message" DOM element, and remove DOM element
            $timeout_message = that.formatInfoMessage(data.code_sent_timeout_message, false, 'timeout');
            $message_block.append($timeout_message);
        }

        // Ensure links are hidden
        $sent_link.hide();
        //$edit_link.hide();

        // Run timer inside message
        that.runTimeoutMessage($timeout_message, {
            timeout: data.code_sent_timeout,
            onFinish: function () {
                // Show link(s)
                $sent_link.show();
                //$edit_link.show();
                // hide message
                $code_sent_message.hide();
            }
        });
    };

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);
        handlers.errors = function (errors, response) {

            // case when contact must confirm phone
            if (!$.isEmptyObject(errors.confirmation_code)) {
                that.onConfirmPhoneError(response);
            }

            that.showErrors(errors);

            return true;
        };

        handlers.rest = function (response) {

            // case when contact must confirm phone (or just confirm authorizing) by SMS code
            if (response.data.code_sent) {
                that.onConfirmPhoneError(response);
                return;
            }

            // Trigger event for further processing successfully authorized contact
            if (response.data.auth_status === 'ok') {
                that.triggerEvent('wa_auth_contact_logged', response.data.contact || {});
            }

        };
        return handlers;
    };

    return Self;

})(jQuery);
