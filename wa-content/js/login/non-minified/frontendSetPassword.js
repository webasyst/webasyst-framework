var WaFrontendSetPassword = ( function($) {

    var WaFrontendSetPassword = function(options) {
        this.init(options);
    };

    var Self = WaFrontendSetPassword;
    Self.className = "WaFrontendSetPassword";

    var Parent = WaLoginAbstractSetPasswordForm;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.initVars = function(options) {
        var that = this;
        that.className = Self.className;
        Parent.prototype.initVars.call(that, options);
        that.initHash(options.hash);
        that.env = 'frontend';
    };

    Self.prototype.initHash = function(hash) {
        var that = this;
        that.hash = hash || '';
        if (that.hash) {
            return;
        }

        // hash not passed to form

        var url = location.href;

        // first try extract param 'key' from url
        var captured = /key=([^&]+)/.exec(url)[1];
        that.hash = captured ? captured : '';
        if (that.hash) {
            return;
        }

        // than try extract param 'hash' from url
        captured = /hash=([^&]+)/.exec(url)[1]
        that.hash = captured ? captured : '';
    };

    Self.prototype.beforeJsonPost = function (url, data) {
        var that = this;
        data = Parent.prototype.beforeJsonPost.call(that, url, data);
        data = that.mixinVarsInData({ hash: that.hash }, data);
        return data;
    };

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this,
            handlers = Parent.prototype.onDoneSubmitHandlers.call(that);
        handlers.rest = function (r) {

            // Trigger events
            if (r.data.set_password === true) {
                that.triggerEvent('wa_auth_reset_password');
                that.triggerEvent('wa_auth_contact_logged', r.data.contact || {});
            }

            if (r.data.generated_password_sent === true) {
                that.triggerEvent('wa_auth_resent_password');
            }

        };
        return handlers;
    };

    return Self;

})(jQuery);
