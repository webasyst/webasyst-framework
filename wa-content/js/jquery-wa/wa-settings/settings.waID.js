var WASettingsWaID = ( function($) {

    WASettingsWaID = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$connect_button = that.$wrapper.find('.js-connect-to-waid');
        that.$disconnect_button = that.$wrapper.find('.js-disconnect-to-waid');

        // VARS
        that.wa_backend_url = options.wa_backend_url || '';

        // DYNAMIC VARS

        // INIT
        that.init();
    };

    WASettingsWaID.prototype.init = function() {
        var that = this;

        that.$connect_button.on('click', function (e) {
            e.preventDefault();
            that.connect();
        });

        that.$disconnect_button.on('click', function (e) {
            e.preventDefault();
            that.disconnect();
        });

        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="waid"]').addClass('selected');

        that.initWebasystIDHelpLink();

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

    WASettingsWaID.prototype.connect = function() {
        var that = this,
            $wrapper = that.$wrapper;
        $.get('?module=settings&action=waIDConnectDialog', function (html) {
            $wrapper.append(html);
        });
    };

    WASettingsWaID.prototype.disconnect = function () {
        var that = this,
            $wrapper = that.$wrapper;
        $.get('?module=settings&action=waIDDisconnectConfirm', function (html) {
            $wrapper.append(html);
        });
    };

    return WASettingsWaID;

})(jQuery);
