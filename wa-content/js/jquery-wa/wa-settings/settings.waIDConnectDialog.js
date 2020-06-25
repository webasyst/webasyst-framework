var WASettingsWaIDConnectDialog = ( function($) {

    WASettingsWaIDConnectDialog = function(options) {
        var that = this;

        // DOM
        that.$dialog = options.$dialog;
        that.$process_block = that.$dialog.find('.js-process-block');
        that.$success_block = that.$dialog.find('.js-success-block');
        that.$fail_block = that.$dialog.find('.js-fail-block');

        // VARS
        that.connect_url = options.connect_url || '';
        that.wa_url = options.wa_url || '';
        that.reload_after_close = typeof options.reload_after_close === 'undefined' ? true : options.reload_after_close;
        that.oauth_modal = options.oauth_modal || false;

        // DYNAMIC VARS

        // INIT
        that.init();
    };

    WASettingsWaIDConnectDialog.prototype.init = function() {
        var that = this;
        that.initDialog();
    };

    WASettingsWaIDConnectDialog.prototype.initDialog = function () {
        var that = this;

        $.waDialog({
            html: that.$dialog,
            onOpen: function () {
                that.connect();
                that.initAuthLink();
            },
            onClose: function () {
                if (that.$success_block.is(':visible') && that.reload_after_close) {
                    window.location.reload();
                }
            }
        });

    };

    WASettingsWaIDConnectDialog.prototype.connect = function() {
        var that = this,
            connect_url = '?module=settings&action=waIDConnect';

        var request = $.post(connect_url);

        var onDone = function(r) {

            if (r && r.status === 'ok') {
                that.$success_block.show();
                that.$process_block.hide();

                if (r.data && r.data.webasyst_id_auth_url) {
                    that.$dialog.find('.js-webasyst-id-auth').show().attr('href', r.data.webasyst_id_auth_url);
                }

                that.$dialog.trigger('connected', [r.data]);

                return;
            }

            that.$fail_block.show();
            that.$process_block.hide();

            if (r && r.errors) {
                $.each(r.errors, function (key, error_msg) {
                    var $error = $('<p class="errormsg">').text(error_msg);
                    that.$fail_block.append($error);
                });
            }
        };

        var onFail = function() {
            that.$fail_block.show();
            that.$process_block.hide();
        };

        var onAlways = function() {
            that.$dialog.find('.js-close-dialog').removeAttr('disabled');
        };

        request.done(onDone).fail(onFail).always(onAlways);
    };

    WASettingsWaIDConnectDialog.prototype.initAuthLink = function () {
        var that = this,
            $dialog = that.$dialog,
            $link = $dialog.find('.js-webasyst-id-auth'),
            oauth_modal = that.oauth_modal;

        $link.on('click', function (e) {
            e.preventDefault();

            var href = $(this).attr('href');
            if (!oauth_modal) {
                var referrer_url = window.location.href;
                window.location = href + '&referrer_url=' + referrer_url;
                return;
            }

            var width = 600;
            var height = 500;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;

            window.open(href,'oauth', "width=" + 600 + ",height=" + height + ",left="+left+",top="+top+",status=no,toolbar=no,menubar=no");


            return false;
        });
    };

    return WASettingsWaIDConnectDialog;

})(jQuery);
