var WaLoginAbstractSetPasswordForm = ( function($) {

    // Abstract class
    var Self = WaLoginAbstractSetPasswordForm = function () {};
    Self.className = 'WaLoginAbstractSetPasswordForm';
    var Parent = WaLoginAbstractForm;

    var def = WaLoginAbstractForm.def;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.initVars = function (options) {
        var that = this;
        that.className = def(that.className, Self.className);
        Parent.prototype.initVars.call(that, options);
        that.form_type = 'setpassword';
    };

    Self.prototype.validate = function () {
        var that = this,
            data = that.getSerializedFormData(),
            errors = {};
        $.each(data, function (index, item) {
            var name = item.name,
                value = $.trim(item.value || '');
            if (name === that.buildFormInputName('login') && !value) {
                errors['login'] = [that.locale.login_required || that.locale.required || ''];
                return;
            }
            if (name === that.buildFormInputName('password') && !value) {
                errors['password'] = [that.locale.password_required || that.locale.required || ''];
                return;
            }
            if (name === that.buildFormInputName('captcha') && !value) {
                errors['captcha'] = [that.locale.captcha_required || that.locale.required || ''];
                return;
            }
        });

        var $password = that.getFormInput('password'),
            $confirm_password = that.getFormInput('password_confirm'),
            password_val = $.trim($password.val() || ''),
            confirm_password_val = $.trim($confirm_password.val() || '');

        if (password_val !== confirm_password_val) {
            errors['password_confirm'] = [that.locale.not_match || '']
        }

        return errors;
    };

    return Self;

})(jQuery);
