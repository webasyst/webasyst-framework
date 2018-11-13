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

        if (that.isOneTypePasswordMode()) {
            that.initOnetimePasswordView();
        }

        that.initAuthAdapters();
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
                var left = (screen.width-600)/ 2,
                    top = (screen.height-400)/ 2,
                    href = $link.attr("href");

                var new_window = window.open(href, "oauth", "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
            }
        }
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
        that.initOnetimePasswordLink({
            $link: $wrapper.find('.wa-request-onetime-password-button'),
            $loading: $wrapper.find('.wa-request-onetime-password-button-loading')
        });

        // Init 'link' for re-request ('sent again') onetime password
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
     * @param data
     */
    Self.prototype.onSentOnetimePassword = function (data) {
        var that = this,
            $wrapper = that.$wrapper;
        data = data || {};

        // Format message about timeout
        var $timeout_message = that.formatInfoMessage(data.onetime_password_timeout_message, false, 'timeout');

        // Place messages
        $wrapper.find('.wa-onetime-password-input-message').html($timeout_message);

        // Show PASSWORD filed itself
        that.turnOnBlock(that.getFormField('password'));

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

    Self.prototype.prepareErrorText = function (name, error) {
        var that = this;
        if (name === 'confirm_email') {
            return $.trim(error || '');
        } else {
            return Parent.prototype.prepareErrorText.call(that, name, error);
        }
    };

    Self.prototype.getErrorTemplate = function (name) {
        var that = this;
        if (name === 'confirm_email') {
            return that.$templates.confirm_email_error;
        } else {
            return Parent.prototype.getErrorTemplate.call(that, name);
        }
    };

    Self.prototype.prepareErrorItem = function (name, error) {
        var that = this,
            $error = Parent.prototype.prepareErrorItem.call(that, name, error);
        if (name === 'confirm_email') {
            $error.data('notClear', 1).attr('data-not-clear', '1');
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

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);
        handlers.rest = function (r) {
            // Trigger event for further processing successfully authorized contact
            if (r.data.auth_status === 'ok') {
                that.triggerEvent('wa_auth_contact_logged', r.data.contact || {});
            }

        };
        return handlers;
    };

    return Self;

})(jQuery);
