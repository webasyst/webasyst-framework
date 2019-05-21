var WaLoginAbstractLoginForm = ( function($) {

    // Abstract class
    var Self = WaLoginAbstractLoginForm = function () {};
    Self.className = 'WaLoginAbstractLoginForm';
    var Parent = WaLoginAbstractForm;

    var def = WaLoginAbstractForm.def;

    // "inherit"
    Self.prototype = Object.create(Parent.prototype);
    Self.prototype.constructor = Self;

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);
    };

    Self.prototype.initVars = function (options) {
        var that = this;
        options = options || {};
        that.auth_type = def(that.auth_type, options.auth_type, '');
        that.className = def(that.className, Self.className);
        Parent.prototype.initVars.call(that, options);

        that.timeout = def(that.timeout, 0);
        that.timeout = isNaN(that.timeout) || that.timeout <= 0 ? 60 : that.timeout;

        that.form_type = 'login';
    };

    Self.prototype.setFocus = function () {
        var that = this,
            $login = that.getFormInput('login'),
            $password = that.getFormInput('password'),
            login = $.trim($login.val() || '');
        if (login) {
            $password.focus();
        } else {
            $login.focus();
        }
    };

    Self.prototype.validate = function (send_onetime_password) {
        var that = this,
            data = that.getSerializedFormData(),
            errors = {};
        $.each(data, function (index, item) {
            var name = item.name,
                value = $.trim(item.value || '');
            if (name === that.buildFormInputName('login') && !value) {
                errors['login'] = [that.locale.login_required || that.locale.required || ''];
            }
            if (name === that.buildFormInputName('captcha') && !value) {
                errors['captcha'] = [that.locale.captcha_required || that.locale.required || ''];
            }
            if (!send_onetime_password) {
                if (name === that.buildFormInputName('password') && !value) {
                    errors['password'] = [that.locale.password_required || that.locale.required || ''];
                }
            }
        });
        return errors;
    };

    Self.prototype.prepareErrorItem = function (error_namespace, error, error_code) {
        var that = this,
            $error = Parent.prototype.prepareErrorItem.call(that, error_namespace, error, error_code);
        if (that.isOneTimePasswordMode() && error_namespace === 'password' && error_code === 'out_of_tries') {
            // OUT of tries error case
            // UX/UI thing: "Disable" next attempt
            var $input = that.getFormInput('password'),
                $form = that.getFormItem();
            $input.attr('readonly', true);
            $form.find('.wa-login-submit').attr('disabled', true);
        }
        return $error;
    };

    Self.prototype.isOneTimePasswordMode = function () {
        var that = this,
            auth_type = that.auth_type;
        return auth_type === 'onetime_password';
    };

    Self.prototype.onSentOnetimePassword = function (data) {
        var that = this;
        data = data || {};
        that.showInfoMessages(data.messages || {});
    };

    Self.prototype.initOnetimePasswordLink = function (options) {
        var that = this,
            options = options || {},
            $link = options.$link,
            $loading = options.$loading;

        if (!$link.length || !that.isOneTimePasswordMode()) {
            return;
        }

        var xhr = null;

        // ignore submit emulation on this button
        $link.data('ignore', '1');

        $link.on('click', function (e) {

            e.preventDefault();

            that.clearErrors();

            if (that.js_validate) {
                var errors = that.validate(true);
                if (!$.isEmptyObject(errors)) {
                    that.showErrors(errors);
                    return;
                }
            }

            if (xhr) {
                return;
            }

            var url = $link.attr('href') || $link.data('href');

            $loading.show();
            $link.attr('disabled', true);

            var data = that.getSerializedFormData();

            that.clearErrors();

            xhr = that.jsonPost(url, data)
                .done(function (r) {
                    $link.attr('disabled', false);
                    if (r && r.status === 'ok') {
                        that.onSentOnetimePassword(r.data);
                    } else {
                        that.showErrors(r ? r.errors || {} : {});
                    }
                })
                .fail(function () {
                    $link.attr('disabled', false);
                })
                .always(function () {
                    xhr = null;
                    $loading.hide();
                });
        })
    };

    return Self;

})(jQuery);
