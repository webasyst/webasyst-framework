var WaLoginAbstractForgotPasswordForm = ( function($) {

    // Abstract class
    var Self = WaLoginAbstractForgotPasswordForm = function () {};
    Self.className = 'WaLoginAbstractForgotPasswordForm';
    var Parent = WaLoginAbstractForm;

    var def = WaLoginAbstractForm.def;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.init = function (options) {
        var that = this;
        Parent.prototype.init.call(that, options);
    };

    Self.prototype.initVars = function (options) {
        var that = this;

        that.classes = def(that.classes, {});
        that.classes = $.extend({
            hide_wrapper: 'wa-hide-wrapper'
        }, that.classes);

        that.timeout = def(that.timeout, 0);
        that.timeout = isNaN(that.timeout) || that.timeout <= 0 ? 60 : that.timeout;

        that.login_url = def(that.login_url, '');

        that.className = def(that.className, Self.className);

        Parent.prototype.initVars.call(that, options);

        that.form_type = 'forgotpassword';
    };

    Self.prototype.showErrors = function (all_errors) {
        var that = this;
        Parent.prototype.showErrors.call(that, all_errors);
    };

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);
        handlers.messages = function (messages) {
            if (!that.showSentMessages(messages)) {
                that.showInfoMessages(messages);
            }
            return true;
        };
        return handlers;
    };

    Self.prototype.keysCount = function (obj) {
        obj = obj || {};
        if (Object.keys) {
            return Object.keys(obj).length;
        }
        var count = 0;
        $.each(obj, function () {
            count++;
        });
        return count;
    };

    Self.prototype.makeDiv = function (clz) {
        return $('<div>').addClass(clz);
    };

    Self.prototype.makeA = function (href, html) {
        return $('<a>').attr('href', href).html(html);
    };

    Self.prototype.showSentMessages = function (messages) {
        var that = this,
            count = that.keysCount(messages);

        if (!messages.sent || count !== 1) {
            return false;
        }

        that.showInfoMessages(messages);

        var $wrapper = that.$wrapper,
            $messages = $wrapper.find('.' + that.classes.messages),
            $messages_wrapper = that.makeDiv(that.classes.messages_wrapper),
            $hide_wrapper = that.makeDiv(that.classes.hide_wrapper),
            $children = $wrapper.children();

        $wrapper.append($messages_wrapper);
        $wrapper.append($hide_wrapper.hide());
        $hide_wrapper.append($children);
        $messages_wrapper.append($messages);
        $messages_wrapper.append(that.makeA(that.login_url, that.locale.login_page));

        return true;
    };

    Self.prototype.validate = function () {
        var that = this,
            data = that.getSerializedFormData(),
            errors = {};
        $.each(data, function (index, item) {
            var name = item.name,
                value = $.trim(item.value || '');
            if (name === that.buildFormInputName('login') && !value) {
                errors['login'] = [that.locale.login_required || (that.locale.required || '')];
                return;
            }
            if (name === that.buildFormInputName('captcha') && !value) {
                errors['captcha'] = [that.locale.captcha_required || that.locale.required || ''];
                return;
            }
            if (name === that.buildFormInputName('confirmation_code') && !value) {
                errors['confirmation_code'] = [that.locale.confirmation_code_required || that.locale.required || ''];
                return;
            }
        });
        return errors;
    };

    Self.prototype.prepareErrorItem = function (error_namespace, error, error_code) {
        var that = this,
            $error = Parent.prototype.prepareErrorItem.apply(that, arguments);
        if (error_namespace === 'confirmation_code' && error_code === 'out_of_tries') {
            // OUT of tries error case
            // UX/UI thing: "Disable" next attempt
            var $confirmation_code_input = that.getFormInput('confirmation_code'),
                $form = that.getFormItem();
            $confirmation_code_input.attr('readonly', true);
            $form.find('.wa-confirmation-code-input-submit').attr('disabled', true);
        }
        return $error;
    };


    return Self;

})(jQuery);
