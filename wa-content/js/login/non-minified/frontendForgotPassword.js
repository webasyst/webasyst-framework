var WaFrontendForgotPassword = ( function($) {

    var WaFrontendForgotPassword = function(options) {
        this.init(options);
    };

    var Self = WaFrontendForgotPassword;
    Self.className = 'WaFrontendForgotPassword';

    var Parent = WaLoginAbstractForgotPasswordForm;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.initVars = function (options) {
        var that = this;
        that.className = Self.className;
        that.classes = {
            field: 'wa-field'
        };
        Parent.prototype.initVars.call(that, options);
        that.env = 'frontend';
    };

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);

        // Init View of form - hide latter needed blocks
        var initView = function () {
            // Confirmation code input not show
            that.turnOffBlock(that.getFormField('confirmation_code'));

            // Messages on the top not show
            that.$wrapper.find('.' + that.classes.message_msg).hide();

            // Login input must be editable
            that.getFormInput('login').removeAttr('readonly');

            // Submit button must be shown
            that.turnOnBlock(that.$wrapper.find('.wa-forgotpassword-button'));

            // Links must be hidden
            that.$wrapper.find('.wa-edit-login-link-wrapper').hide();
            that.$wrapper.find('.wa-send-again-confirmation-code-link-wrapper').hide();
        };

        initView();

        // Edit link
        that.$wrapper.find('.wa-edit-login-link-wrapper').click(function () {
            // Re-init View
            initView();
        });

        // Submit input code button

        that.$wrapper.find('.wa-confirmation-code-input-submit').click(function (e) {
            e.preventDefault();
            that.submit({
                $button: $(this),
                $loading: that.$wrapper.find('.wa-confirmation-code-input-submit-loading')
            });
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

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);

        var showSentMessage = function (r) {
            // Clean prev messages on the top
            that.$wrapper.find('.' + that.classes.message_msg).html('')

            // Show status message on the top
            that.showInfoMessages({
                sent: r.data.sent_message
            });
        };

        var onSentSMS = function (r) {

            var $confirmation_code_block = that.getFormField('confirmation_code'),
                $confirmation_code_input = that.getFormInput('confirmation_code');

            // see Parent.prepareErrorItem for out_of_tries error
            $confirmation_code_input.removeAttr('readonly');

            // Show input for confirmation code
            that.turnOnBlock($confirmation_code_block);

            // clean prev value of input
            that.getFormInput('confirmation_code').val('');

            // Hide main Submit button
            that.turnOffBlock(that.$wrapper.find('.wa-forgotpassword-button')); 

            showSentMessage(r);

            // Show timeout message
            var $timeout_message = that.formatInfoMessage(r.data.timeout_message, false);

            // Render in proper place
            that.$wrapper.find('.wa-confirmation-code-input-message').html($timeout_message);

            // "Disable" login input
            that.getFormInput('login').attr('readonly', 1);

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
            showSentMessage(r);
            // Remove all UI blocks
            that.$wrapper.find('.js-forgotpassword-form-fields').remove();
            that.$wrapper.find('.js-forgotpassword-form-actions').remove();
        };

        handlers.rest = function (r) {
            if (r.data.generated_password_sent === true) {
                that.triggerEvent('wa_auth_resent_password');
            } else if (r.data.code_confirmed) {
                that.triggerEvent('wa_auth_set_password', [ r.data.hash || '' ])
            } else if (r.data.channel_type === 'sms') {
                onSentSMS(r);
            } else if (r.data.channel_type === 'email') {
                onSentEmail(r);
            }
        };
        return handlers;
    };

    return Self;

})(jQuery);
