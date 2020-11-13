var WASettingsWaID = ( function($) {

    WASettingsWaID = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$connect_button = that.$wrapper.find('.js-connect-to-waid');
        that.$upgrade_all_checkbox = that.$wrapper.find('.js-upgrade-all');
        that.$connect_youself = that.$wrapper.find('.js-connect-yourself');
        that.$disconnect_button = that.$wrapper.find('.js-disconnect-to-waid');
        that.$force_auth_toggle = that.$wrapper.find('.js-force-auth-toggle');
        that.$sidebar_wrapper = $('#s-sidebar-wrapper');

        // VARS
        that.wa_backend_url = options.wa_backend_url || '';
        that.current_page_url = '';
        that.upgrade_all = options.upgrade_all || false;   // start upgrading process (invite all not connected users)

        that.oauth_modal = options.oauth_modal || false;
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';

        that.locale = options.locale || {};

        // INIT
        that.init();
    };

    WASettingsWaID.prototype.init = function() {
        var that = this;

        that.$connect_button.on('click', function (e) {
            e.preventDefault();
            that.connect(that.$upgrade_all_checkbox.is(':checked'));
        });
        
        that.$disconnect_button.on('click', function (e) {
            e.preventDefault();
            that.disconnect();
        });

        that.$sidebar_wrapper.find('ul li').removeClass('selected');

        var $current_sidebar_item = that.$sidebar_wrapper.find('[data-id="waid"]');
        $current_sidebar_item.addClass('selected');

        that.current_page_url = $current_sidebar_item.find('a').attr('href');

        that.initForceAuthToggle();

        that.initWebasystIDHelpLink();

        that.initReInviteLinks();
        that.initConnectYourselfLink();

        // run automatically invitation process
        if (that.upgrade_all) {
            that.runBulkInviting();
        }
    };

    WASettingsWaID.prototype.initForceAuthToggle = function() {
        var that = this,
            $toggle = that.$force_auth_toggle,
            $status = that.$wrapper.find('.js-force-save-status');

        $toggle.iButton({
            labelOn: "",
            labelOff: "",
            className: "s-waid-force-auth-toggle",
            classContainer: 'ibutton-container mini'
        });

        var timer_id = null;

        $toggle.on('change', function () {
            var url = that.wa_backend_url + "?module=settingsWaID&action=save";
            $.post(url, $toggle.serialize())
                .done(function () {
                    timer_id && clearTimeout(timer_id);
                    $status.show();
                    timer_id = setTimeout(function () {
                        $status.fadeOut(500);
                        timer_id = null;
                    }, 2000);
                });
        });

        if ($toggle.attr('disabled')) {
            that.$wrapper.find('.s-waid-force-auth-toggle').attr('title', that.locale.disabled_toggle_reason || '');
        }
    };

    WASettingsWaID.prototype.initWebasystIDHelpLink = function() {
        var that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.js-webasyst-id-help-link');

        $link.on('click', function (e) {
            e.preventDefault();
            var url = that.wa_backend_url + "?module=backend&action=webasystIDHelp&caller=webasystSettings";
            $.get(url, function (html) {
                $('body').append(html);
            });
        });
    };

    WASettingsWaID.prototype.initConnectYourselfLink = function() {
        var that = this,
            $link = that.$connect_youself;
        $link.on('click', function (e) {
            e.preventDefault();
            that.auth(that.webasyst_id_auth_url);
        });
    };

    WASettingsWaID.prototype.initReInviteLinks = function() {
        var that = this,
            $wrapper = that.$wrapper,
            is_loading = {};

        $wrapper.on('click', '.js-send-email-invitation', function (e) {
            e.preventDefault();

            var $link = $(this),
                id = $link.data('id');

            if (is_loading[id]) {
                return;
            }

            var url = that.wa_backend_url + "?module=settings&action=waIDInviteUser",
                $loading = $link.find('.js-loading');

            $link.parent().find('.js-error').hide();

            $loading.show();
            is_loading[id] = true;

            $.post(url, { id : id })
                .done(function (r) {
                    if (r && r.errors) {
                        $link.parent().find('.js-error').show().html(r.errors);
                        return;
                    }
                    if (r && r.data) {
                        $link.addClass('hidden');
                        $link.parent().find('.js-sent-email-ok').removeClass('hidden');

                        $link.closest('tr')
                            .find('.js-await-user-confirmation')
                            .removeClass('hidden').end()
                            .find('.js-last-send-datetime')
                            .text(r.data.sent);
                    }
                })
                .always(function () {
                    is_loading[id] = false;
                    $loading.hide();
                });
        });
    };

    WASettingsWaID.prototype.runBulkInviting = function() {
        var that = this,
            $wrapper = that.$wrapper;

        $wrapper.find('.js-waid-invite-progressbar-wrapper').show();

        var progress = new WASettingsWaIDInviteProgress({
            $wrapper: $wrapper.find('.s-waid-description-block'),
            url: that.wa_backend_url + "?module=settings&action=waIDInviteUsers",
            onStepDone: function (response) {
                if (response && !$.isEmptyObject(response.sent)) {
                    $.each(response.sent, function (id, datetime_formatted) {
                        var $link = $wrapper.find('.js-send-email-invitation[data-id="' + id + '"]');
                        $link.addClass('hidden');
                        $link.parent().find('.js-sent-email-ok').removeClass('hidden');

                        $link.closest('tr')
                            .find('.js-await-user-confirmation')
                                .removeClass('hidden').end()
                            .find('.js-last-send-datetime')
                                .text(datetime_formatted);
                    });
                }
            }
        });

        progress.run();
    };

    WASettingsWaID.prototype.connect = function(upgrade_all) {
        var that = this,
            $wrapper = that.$wrapper;

        $.get('?module=settings&action=waIDConnectDialog', function (html) {
            $wrapper.append(html);
            $wrapper.one('connected', function (e, data, dialog) {

                dialog.close();

                if (upgrade_all) {
                    $.wa.content.load(that.current_page_url + '?upgrade_all=1', true);
                } else {
                    window.location.reload();
                }
            });
        });
    };

    WASettingsWaID.prototype.disconnect = function () {
        var that = this,
            $wrapper = that.$wrapper;
        $.get('?module=settings&action=waIDDisconnectConfirm', function (html) {
            $wrapper.append(html);
        });
    };

    WASettingsWaID.prototype.auth = function (href) {
        var that = this,
            oauth_modal = that.oauth_modal;

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
    };

    return WASettingsWaID;

})(jQuery);
