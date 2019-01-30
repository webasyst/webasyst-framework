var WaBackendLogin = ( function($) {

    var WaBackendLogin = function(options) {
        var that = this;
        that.init(options);
    };

    var Self = WaBackendLogin;
    Self.className = 'WaBackendLogin';
    var Parent = WaLoginAbstractLoginForm;

    // "inherit"
    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);

        that.initCancelButton();

        that.initOnetimePasswordLink({
            $link: that.$wrapper.find('.wa-request-onetime-password-button'),
            $loading: that.$wrapper.find('.wa-request-onetime-password-button-loading')
        });

        that.initOnetimePasswordLink({
            $link: that.$wrapper.find('.wa-request-onetime-password-link'),
            $loading: that.$wrapper.find('.wa-request-onetime-password-link-loading')
        });

    };

    Self.prototype.initCancelButton = function () {
        var that = this,
            $form = that.getFormItem(),
            $cancel_link = $form.find('.wa-login-cancel');

        $cancel_link.on('click', function (e) {
            e.preventDefault();
            that.is_json_mode = false;
            $form.append('<input type="hidden" name="cancel" value="1" />');
            $form.submit();
        });
    };

    Self.prototype.initVars = function (options) {
        var that = this;
        that.className = Self.className;
        Parent.prototype.initVars.call(that, options);
        that.$wrapper.data('WaAuthForm', that);
        that.is_json_mode = true;
        that.env = 'backend';
    };
    
    Self.prototype.setupOnetimePasswordView = function () {
        var that = this,
            $wrapper = that.$wrapper;

        that.makeInputReadonly('login', false);

        that.turnOffBlock(that.getFormField('password'));
        that.turnOffBlock($wrapper.find('.wa-submit-button-wrapper'));
        that.turnOnBlock($wrapper.find('.wa-request-onetime-password-button-wrapper'));

        that.turnOffBlock($wrapper.find('.field-remember-me'));

        $wrapper.find('.wa-change-login-link').hide();
        $wrapper.find('.wa-request-onetime-password-link-wrapper').hide();


        that.getFormField('password').find('.' + that.classes.message_msg).remove();

        that.triggerEvent('wa_auth_form_change_view');
    };

    Self.prototype.onSentOnetimePassword = function (data) {
        data = data || {};

        var that = this,
            $wrapper = that.$wrapper;


        // "Disable" login input
        that.makeInputReadonly('login');

        // Show password-input to type onetime_password we requested
        that.turnOnBlock(that.getFormField('password'));

        // Submit button show
        that.turnOnBlock($wrapper.find('.wa-submit-button-wrapper'));

        // Button for request onetime_password now hidden
        that.turnOffBlock($wrapper.find('.wa-request-onetime-password-button-wrapper'));

        // Show remember-me input
        that.turnOnBlock($wrapper.find('.field-remember-me'));

        // Link to edit login value
        that.initChangeLoginLink();

        // Remove prev messages
        that.clearInfoMessages('password');

        // Snow message about sending & message with timer
        // All of these place under password
        that.showInfoMessages({
            password: {
                timeout: data.onetime_password_timeout_message,
                sent_message: data.onetime_password_sent_message,
            }
        });

        // Link(s) must be hidden until timer finished
        $wrapper.find('.wa-change-login-link').hide();
        $wrapper.find('.wa-request-onetime-password-link-wrapper').hide();

        // Message about timeout to re-request onetime_password
        var $timer_message = that.$wrapper.find('.' + that.classes.message_msg + '[data-name="password"][data-index="timeout"]');


        // go timer
        that.runTimeoutMessage($timer_message, {
            timeout: data.onetime_password_timeout,
            onFinish: function () {
                $wrapper.find('.wa-change-login-link').show();
                $wrapper.find('.wa-request-onetime-password-link-wrapper').show();
                $timer_message.remove();
            }
        });
    };

    Self.prototype.initChangeLoginLink = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.wa-change-login-link');
        $link.one('click', function () {
            that.setupOnetimePasswordView();
        });
    };

    Self.prototype.makeInputReadonly = function (name, readonly) {
        readonly = readonly !== undefined ? !!readonly : true;
        var that = this,
            $input = that.getFormInput(name);
        $input.attr('disabled', readonly);
        if (readonly) {
            var input_name = $input.attr('name'),
                input_val = $input.val(),
                $hidden = $('<input type="hidden">').attr('name', input_name).val(input_val);
            $hidden.insertAfter($input);
            $input.data('hidden_clone', $hidden);
        } else {
            var $hidden = $input.data('hidden_clone');
            if ($hidden.length) {
                $hidden.remove();
            }
        }
    };

    return Self;

})(jQuery);
