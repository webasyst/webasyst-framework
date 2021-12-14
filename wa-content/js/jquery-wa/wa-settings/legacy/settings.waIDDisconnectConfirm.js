var WASettingsWaIDDisconnectConfirm = ( function($) {

    WASettingsWaIDDisconnectConfirm = function(options) {
        var that = this;

        // DOM
        that.$dialog = options.$dialog;
        that.$button = that.$dialog.find('.js-disconnect');
        that.$success_block = that.$dialog.find('.js-success-block');
        that.$fail_block = that.$dialog.find('.js-fail-block');

        // VARS
        that.connect_url = options.connect_url || '';

        // DYNAMIC VARS

        // INIT
        that.init();
    };

    WASettingsWaIDDisconnectConfirm.prototype.init = function() {
        var that = this;
        that.initDialog();
    };

    WASettingsWaIDDisconnectConfirm.prototype.initDialog = function () {
        var that = this;
        $.waDialog({
            html: that.$dialog,
            animate: false,
            onOpen: function () {
                that.$button.on('click', function (e) {
                    e.preventDefault();
                    that.disconnect();
                });
            },
            onClose: function () {
                if (that.$success_block.is(':visible')) {
                    window.location.reload();
                }
            }
        });
    };

    WASettingsWaIDDisconnectConfirm.prototype.disconnect = function() {
        var that = this,
            $loading = that.$dialog.find('.js-loading'),
            $button = that.$button,
            disconnect_url = '?module=settings&action=waIDDisconnect';

        that.$fail_block.find('.errormsg').remove();

        var request = $.post(disconnect_url);

        var onDone = function(r) {

            if (r && r.status === 'ok') {
                that.$success_block.show();
                that.$dialog.find('.wa-dialog-footer').hide();
                that.$dialog.find('.js-success-dialog-footer').show();
                return;
            }

            that.$fail_block.show();

            if (r && r.errors) {
                $.each(r.errors, function (key, error_msg) {
                    var $error = $('<p class="errormsg">').text(error_msg);
                    that.$fail_block.append($error);
                });
                $button.removeAttr('disabled');
            }
        };

        var onFail = function() {
            that.$fail_block.show();
            $button.removeAttr('disabled');
        };

        var onAlways = function() {
            $loading.hide();
        };

        $loading.show();
        $button.attr('disabled', true);

        request.done(onDone).fail(onFail).always(onAlways);

    };

    return WASettingsWaIDDisconnectConfirm;

})(jQuery);
