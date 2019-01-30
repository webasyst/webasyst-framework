var WASettingsGeneral = ( function($) {

    WASettingsGeneral = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$form = that.$wrapper.find('form');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');

        that.$backgrounds_wrapper = that.$wrapper.find('.js-background-images');
        that.$preview_wrapper = that.$wrapper.find('.js-custom-preview-wrapper');
        that.$background_input = that.$wrapper.find('input[name="auth_form_background"]');
        that.$upload_preview_background_wrapper = that.$wrapper.find('.js-upload-preview');

        // VARS

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsGeneral.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="general"]').addClass('selected');

        //
        that.initClearCache();
        //
        that.initSubmit();
    };

    WASettingsGeneral.prototype.initClearCache = function () {
        var that = this;

        that.$wrapper.on('click', '.js-clear-cache', function () {
            var href = '?module=settingsClearCache',
                $cache_loading = that.$wrapper.find('.js-cache-loading');

            $cache_loading.removeClass('yes').addClass('loading').show();

            $.get(href, function(r) {
                if (r.status == 'ok') {
                    $cache_loading.removeClass('loading').addClass('yes');
                } else if (r.status == 'fail') {
                    $cache_loading.removeClass('loading').addClass('no');
                }
                setTimeout(function(){
                    $cache_loading.hide();
                },2000);
            }, 'json')
            .error(function() {
                $cache_loading.removeClass('loading').addClass('yes');
                setTimeout(function(){
                    $cache_loading.hide();
                },2000);
            });
        });
    };

    WASettingsGeneral.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            that.is_locked = true;
            that.$button.prop('disabled', true);
            that.$loading.removeClass('yes').addClass('loading').show();

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow').addClass('green');
                    that.$loading.removeClass('loading').addClass('yes');
                    that.$footer_actions.removeClass('is-changed');
                    // Update company name in header
                    var company_name = $.trim(that.$form.find('#config-name').val());
                    $('#wa-account').find('.wa-dashboard-link h3').attr('title', company_name);
                    if (company_name.length > 18) {
                        company_name = company_name.substr(0, 15) +'...';
                    }
                    $('#wa-account').find('.wa-dashboard-link h3').text(company_name);
                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                } else if (res.errors) {
                    $.each(res.errors, function (i, error) {
                        if (error.field) {
                            fieldError(error.field, error.message);
                        }
                    });
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        function fieldError(field_name, message) {
            var $field = that.$form.find('input[name='+field_name+']'),
                $hint = $field.parent('.value').find('.js-error-place');

            $field.addClass('shake animated').focus();
            $hint.text(message);
            setTimeout(function(){
                $field.removeClass('shake animated').focus();
                $hint.text('');
            }, 1000);
        }

        that.$form.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.removeClass('green').addClass('yellow');
        });

        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
            return;
        });
    };

    return WASettingsGeneral;

})(jQuery);