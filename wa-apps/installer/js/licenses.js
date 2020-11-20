var InstallerLicenses = ( function($) {

    InstallerLicenses = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // VAR
        that.licenses = options.licenses || {}; // license_id => license
        that.app_url = options.app_url;
        that.is_auto_install = options.is_auto_install || false;

        that.init();
    };

    InstallerLicenses.prototype.init = function() {
        var that = this;

        // remove from history 'install' parameter, so when user click back we returns to url without install parameter
        var href = window.location.href || '';
        if (href.indexOf('install=')) {
            href = href.replace(/install=(.*?)(&|$)/, '');
            href = href.replace(/\?$/, '');
            window.history.pushState({}, '', href)
        }

        that.initInstallButtons();
    };

    InstallerLicenses.prototype.initInstallButtons = function () {
        var that = this,
            licenses = that.licenses;

        var install = function (license_id) {
            var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)")),
                csrf = matches ? decodeURIComponent(matches[1]) : '',
                url = that.app_url + '?module=update&action=manager',
                license = licenses[license_id],
                fields = [];

            fields.push({ name: 'install', value: 1 });
            fields.push({ name: '_csrf', value: csrf });
            fields.push({ name: 'app_id[' + license.slug + ']', value: license.vendor });

            that.postByForm(url, fields);
        };

        var bindLicense = function (license_id, onDone, onError) {
            var url = that.app_url + '?module=licenses&action=bind';
            $.post(url, { id: license_id })
                .done(function (r) {
                    if (r && r.status === 'ok') {
                        onDone();
                    } else if (r && r.errors) {
                        onError(r.errors);
                    }
                });
        }

        var onClickInstallButton = function ($button) {
            var $item = $button.closest('.js-license-item'),
                $loading = $item.find('.js-loading'),
                license_id = $item.data('id');

            $item.find('.js-bind-error').hide();

            $loading.show();

            bindLicense(license_id,
                function () {
                    install(license_id);
                    $loading.hide();
                },
                function (errors) {
                    var error_msg = $.isArray(errors) ? errors.join('<br>', errors) : errors;
                    $item.find('.js-bind-error').show().text(error_msg);
                    $loading.hide();
                });
        };

        that.$wrapper.on('click', '.js-install-button', function () {
            var $button = $(this);
            onClickInstallButton($button);
        });

        // make sense only for one license list
        if (that.is_auto_install) {
            onClickInstallButton(that.$wrapper.find('.js-install-button:first'));
        }
    };

    InstallerLicenses.prototype.postByForm = function (url, fields) {
        var $form = $('<form>', {
            action: url,
            method: 'post'
        });

        $.each(fields, function (i, field) {
            $('<input>').attr({
                type: "hidden",
                name: field.name,
                value: field.value
            }).appendTo($form);
        });

        $form.appendTo('body').submit();
    };

    return InstallerLicenses;

})(jQuery);
