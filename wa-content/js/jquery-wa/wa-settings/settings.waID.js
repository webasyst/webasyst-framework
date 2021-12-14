class WASettingsWaID {

    constructor(options) {
        const that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$connect_button = that.$wrapper.find('.js-connect-to-waid');
        that.$upgrade_all_checkbox = that.$wrapper.find('.js-upgrade-all');
        that.$connect_youself = that.$wrapper.find('.js-connect-yourself');
        that.$disconnect_button = that.$wrapper.find('.js-disconnect-to-waid');
        that.$sidebar_wrapper = $('#js-sidebar-wrapper');

        // VARS
        that.wa_backend_url = options.wa_backend_url || '';
        that.current_page_url = '';
        that.upgrade_all = options.upgrade_all || false;   // start upgrading process (invite all not connected users)

        that.oauth_modal = options.oauth_modal || false;
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';

        // INIT
        that.init();
    }

    init() {
        const that = this;

        that.$connect_button.on('click', function (e) {
            e.preventDefault();
            that.connect(that.$upgrade_all_checkbox.is(':checked'));
        });

        that.$disconnect_button.on('click', function (e) {
            e.preventDefault();
            that.disconnect();
        });

        that.$sidebar_wrapper.find('ul li').removeClass('selected');

        const $current_sidebar_item = that.$sidebar_wrapper.find('[data-id="waid"]');
        $current_sidebar_item.addClass('selected');

        that.current_page_url = $current_sidebar_item.find('a').attr('href');

        that.initWebasystIDHelpLink();

        that.initReInviteLinks();
        that.initConnectYourselfLink();

        // run automatically invitation process
        if (that.upgrade_all) {
            that.runBulkInviting();
        }
    }

    initWebasystIDHelpLink() {
        const that = this,
            $wrapper = that.$wrapper,
            $link = $wrapper.find('.js-webasyst-id-help-link');

        $link.on('click', function (e) {
            e.preventDefault();
            const url = that.wa_backend_url + "?module=backend&action=webasystIDHelp&caller=webasystSettings";
            $.get(url, function (html) {
                $('body').append(html);
            });
        });
    }

    initConnectYourselfLink() {
        const that = this,
            $link = that.$connect_youself;
        $link.on('click', function (e) {
            e.preventDefault();
            that.auth(that.webasyst_id_auth_url);
        });
    }

    initReInviteLinks() {
        const that = this,
            $wrapper = that.$wrapper,
            is_loading = {};

        $wrapper.on('click', '.js-send-email-invitation', function (e) {
            e.preventDefault();

            const $link = $(this),
                id = $link.data('id');

            if (is_loading[id]) {
                return;
            }

            const url = that.wa_backend_url + "?module=settings&action=waIDInviteUser",
                $loading = $link.find('.js-loading');

            $link.parent().find('.js-error').hide();

            $loading.show();
            is_loading[id] = true;

            $.post(url, {id: id})
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
    }

    runBulkInviting() {
        const that = this,
            $wrapper = that.$wrapper,
            $progressbar_wrapper = $wrapper.find('.js-waid-invite-progressbar-wrapper');

        $progressbar_wrapper.removeClass('hidden');

        const progress = new WASettingsWaIDInviteProgress({
            $wrapper: $progressbar_wrapper,
            url: that.wa_backend_url + "?module=settings&action=waIDInviteUsers",
            onStepDone: function (response) {
                if (response && !$.isEmptyObject(response.sent)) {
                    $.each(response.sent, function (id, datetime_formatted) {
                        const $link = $wrapper.find('.js-send-email-invitation[data-id="' + id + '"]');
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
    }

    connect(upgrade_all) {
        const that = this,
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
    }

    disconnect() {
        const that = this,
            $wrapper = that.$wrapper;
        $.get('?module=settings&action=waIDDisconnectConfirm', function (html) {
            $wrapper.append(html);
        });
    }

    auth(href) {
        const that = this,
            oauth_modal = that.oauth_modal;

        if (!oauth_modal) {
            const referrer_url = window.location.href;
            window.location = href + '&referrer_url=' + referrer_url;
            return;
        }

        const width = 600,
            height = 500,
            left = (screen.width - width) / 2,
            top = (screen.height - height) / 2;

        window.open(href, 'oauth', "width=" + 600 + ",height=" + height + ",left=" + left + ",top=" + top + ",status=no,toolbar=no,menubar=no");
    }
}
