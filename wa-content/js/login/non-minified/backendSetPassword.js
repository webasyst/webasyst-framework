var WaBackendSetPassword = ( function($) {

    var WaBackendSetPassword = function(options) {
        this.init(options);
    };

    var Self = WaBackendSetPassword;
    Self.className = 'WaBackendSetPassword';

    var Parent = WaLoginAbstractSetPasswordForm;

    WaLoginAbstractForm.inherit(Self, Parent);

    Self.prototype.initVars = function(options) {
        var that = this;
        that.className = Self.className;
        Parent.prototype.initVars.call(that, options);
        that.env = 'backend';
    };

    return Self;


})(jQuery);
