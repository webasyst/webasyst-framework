var WaBackendForgotPassword = ( function($) {

    var WaBackendForgotPassword = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$form = that.$wrapper.find('form');
        that.namespace = options.namespace || '';

        // VARS
        that.$templates = {
            error_msg: $('<em class="wa-error-msg"></em>'),
            info_msg: $('<div class="wa-info-msg"></div>')
        };

        that.classes = {
            error_input: 'wa-error',
            error_msg: 'wa-error-msg',
            uncaught_errors: 'wa-uncaught-errors',
            field: 'field'
        };

        that.errors = options.errors || {};
        that.locale = options.locale || {};

        that.timeout = parseInt(options.timeout, 10);
        that.timeout = isNaN(that.timeout) || that.timeout <= 0 ? 60 : that.timeout;

        // INIT
        that.init(options);

        that.$wrapper.data('WaAuthForm', that);
    };

    var Self = WaBackendForgotPassword;
    Self.className = 'WaBackendForgotPassword';

    var Parent = WaLoginAbstractForgotPasswordForm;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.initVars = function (options) {
        var that = this;
        that.className = Self.className;
        Parent.prototype.initVars.call(that, options);
        that.is_json_mode = true;
        that.env = 'backend';
    };

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);

        that.activateInitialView();

        // Edit link
        that.$wrapper.find('.wa-edit-login-link').click(function (e) {
            e.preventDefault();

            // Re-activate initial View
            that.activateInitialView();
        });

        // Re-request link
        var $link = that.$wrapper.find('.wa-send-again-confirmation-code-link');
        $link.click(function (e) {
            e.preventDefault();

            // IMPORTANT: Protocol detail
            // We must NOT post 'confirmation_code' to re-request it
            // Cause 'confirmation_code' presenting means 'Confirmation step' logic

            // So we temporary disable input...
            that.getFormInput('confirmation_code').attr('disabled', true);

            that.submit({
                $button: $link,
                $loading: that.$wrapper.find('.wa-send-again-confirmation-code-link-loading')
            });

            // ..and then un-disable it
            that.getFormInput('confirmation_code').attr('disabled', false);
        });
    };

    Self.prototype.activateInitialView = function () {
        var that = this;

        // Modificator class
        that.$wrapper.removeClass('wa-with-confirmation-code');

        // IMPORTANT:
        // If there is confirmation_code in POST that we in Confirmation Step
        // Initial state - when input is disabled
        that.getFormInput('confirmation_code').attr('disabled', true);

        // On Confirmation Step login is READONLY
        // So in Initial state - otherwise
        that.getFormInput('login').removeAttr('readonly');

        // Hide edit link
        that.$wrapper.find('.wa-edit-login-link-wrapper').hide();
    };

    Self.prototype.activateConfirmationView = function () {
        var that = this;

        // Modificator class
        that.$wrapper.addClass('wa-with-confirmation-code');

        // IMPORTANT:
        // In Confirmation Step
        // We need now POST confirmation_code - so un-disable
        var $code = that.getFormInput('confirmation_code');
        $code.attr('disabled', false);
        $code.val('');

        // On Confirmation Step login is READONLY
        that.getFormInput('login').attr('readonly', 1);

    };

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);

        var onSentSMS = function (r) {

            that.activateConfirmationView();

            // Show timeout message
            var $timeout_message = that.formatInfoMessage(r.data.timeout_message, false);

            // Render in proper place
            that.$wrapper.find('.wa-confirmation-code-input-message').html($timeout_message);

            // Hide Edit link
            var $edit_link = that.$wrapper.find('.wa-edit-login-link-wrapper').hide();

            // Hide re-request again link
            var $sent_link = that.$wrapper.find('.wa-send-again-confirmation-code-link-wrapper').hide();

            // Run timer
            that.runTimeoutMessage($timeout_message, {
                timeout: r.data.timeout,
                onFinish: function () {
                    // Show links
                    $edit_link.show();
                    $sent_link.show();
                }
            });
        };

        var onSentEmail = function (r) {
            // Clean prev messages on the top
            that.$wrapper.find('.' + that.classes.message_msg).html('');

            // Show status message on the top
            that.showInfoMessages({
                sent: r.data.sent_message
            });
            // Hide login field
            that.turnOffBlock(that.getFormField('login'));

            // Hide captcha field
            that.turnOffBlock(that.getFormField('captcha'));

            // Hide main Submit button
            that.turnOffBlock(that.$wrapper.find('.wa-field-submit'));

            // Show "back" link
            that.$wrapper.find('.wa-back-to-login-page-link-wrapper').show();
        };

        handlers.rest = function (r) {
            if (r.data.channel_type === 'sms') {
                onSentSMS(r);
            } else {
                onSentEmail(r);
            }

        };
        return handlers;
    };

    return Self;

})(jQuery);
