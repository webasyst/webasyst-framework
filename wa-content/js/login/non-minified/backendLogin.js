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

        // Init 'button' for request onetime password
        // On success request new password will be called that.onSentOnetimePassword
        that.initOnetimePasswordLink({
            $link: that.$wrapper.find('.wa-request-onetime-password-button'),
            $loading: that.$wrapper.find('.wa-request-onetime-password-button-loading')
        });

        // Init 'link' for re-request ('sent again') onetime password
        // On success request new password will be called that.onSentOnetimePassword
        that.initOnetimePasswordLink({
            $link: that.$wrapper.find('.wa-request-onetime-password-link'),
            $loading: that.$wrapper.find('.wa-request-onetime-password-link-loading')
        });

        if (that.webasyst_id_auth_url) {
            that.initWebasystIDAuthLink();
            that.initWebasystIDHelpLink();
        }
        if (that.bind_with_webasyst_contact) {
            that.initSignInAndBindWithWebasystID();
            that.initWebasystIDHelpLink();
        }
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
        that.wa_app_url = options.wa_app_url || '';
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';
        that.bind_with_webasyst_contact = options.bind_with_webasyst_contact || false;
    };
    
    Self.prototype.setupOnetimePasswordView = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $password = that.getFormField('password');

        that.makeInputReadonly('login', false);

        that.turnOffBlock($password);
        that.turnOffBlock($wrapper.find('.wa-submit-button-wrapper'));
        that.turnOnBlock($wrapper.find('.wa-request-onetime-password-button-wrapper'));

        that.turnOffBlock($wrapper.find('.field-remember-me'));

        $wrapper.find('.wa-change-login-link').hide();
        $wrapper.find('.wa-request-onetime-password-link-wrapper').hide();

        $password.find('.' + that.classes.message_msg).remove();

        that.triggerEvent('wa_auth_form_change_view');
    };

    /**
     * When onetime password successfully sent to client
     * It is template, overridden method, that will be called in Parent class
     * @see Parent
     * @param data
     */
    Self.prototype.onSentOnetimePassword = function (data) {
        data = data || {};

        var that = this,
            $wrapper = that.$wrapper,
            $password_block = that.getFormField('password'),
            $password_input = that.getFormInput('password');


        // "Disable" login input
        that.makeInputReadonly('login');

        // Show password-input to type onetime_password we requested
        that.turnOnBlock($password_block);

        // see Parent.prepareErrorItem for password out_of_tries error in onetime time password mode
        $password_input.removeAttr('readonly').val('');

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
        var $timer_message = that.getInfoMessageItem('password', 'timeout');

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

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);
        handlers.redirect = function (redirect_url) {
            var current_url = window.location.href || '',
                is_prefix = current_url.indexOf(redirect_url) === 0,
                has_hash = current_url.indexOf('#') !== -1;

            // absolute redirect_url is prefix for current location.href
            // and location.href HAS '#'
            //  => Make a conclusion that all we need it is location.reload()
            if (is_prefix && has_hash) {
                window.location.reload();
            } else {
                window.location.href = redirect_url;
            }
        };
        return handlers;
    };

    Self.prototype.initWebasystIDAuthLink = function (oauth_modal) {
        var that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.js-webasyst-auth-link'),
            $remember_me_field = $wrapper.find('.field-remember-me'),
            $remember_me_checkbox = $remember_me_field.find(':checkbox');

        $link.on('click', function (e) {
            e.preventDefault();

            var href = $(this).attr('href') || '';

            // remember me hack for webasyst ID auth into backend
            if ($remember_me_checkbox.is(':checked')) {
                href = href.replace('backend_auth=1', 'backend_auth=2');
            }

            if (!oauth_modal) {
                window.location = href;
                return;
            }

            var width = 600;
            var height = 500;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;

            window.open(href,'oauth', "width=" + 600 + ",height=" + height + ",left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
        });
    };

    /**
     * Init special mode when we sign in and bind with webasyst ID at the same time
     */
    Self.prototype.initSignInAndBindWithWebasystID = function () {
        var that = this,
            $back_to_simple = $('.js-back-to-simple-login')
        $back_to_simple.on('click', function (e) {
            e.preventDefault();
            $.post(that.wa_app_url + '?module=login&action=reset', function () {
                window.location.reload();
            });
        });
    };

    Self.prototype.initWebasystIDHelpLink = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.js-waid-hint');

        $link.on('click', function (e) {
            e.preventDefault();
            var url = that.wa_app_url + "?module=backend&action=webasystIDHelp";
            $.get(url, function (html) {
                $('body').append(html);
            });
        });
    };

    return Self;

})(jQuery);
