var WASettingsCaptcha = ( function($) {

    WASettingsCaptcha = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$form = that.$wrapper.find('form');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$footer_actions.find('.s-loading');

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsCaptcha.prototype.initClass = function() {
        var that = this;

        that.initBindEvent();
    };

    WASettingsCaptcha.prototype.initBindEvent = function() {
        var that = this;

        //
        var $sidebar = $('#js-sidebar-wrapper');

        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="captcha"]').addClass('selected');
        //
        that.initChangeAdapter();
        //
        that.initSubmit();
    };

    WASettingsCaptcha.prototype.initChangeAdapter = function () {
        var that = this;

        that.$form.find(':input[name="captcha"]').on('change', function(){
            if (this.value == 'waReCaptcha') {
                that.$form.find('div.js-captcha-adapter-settings').slideDown();
            } else {
                that.$form.find('div.js-captcha-adapter-settings').slideUp();
            }
        });
    };

    WASettingsCaptcha.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }
            that.is_locked = true;
            that.$button.prop('disabled', true);
            var $button_text = that.$button.text(),
                $loader_icon = ' <i class="fas fa-spinner fa-spin"></i>',
                $success_icon = ' <i class="fas fa-check-circle"></i>';
            that.$button.empty().html($button_text + $loader_icon);

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.empty().html($button_text + $success_icon).removeClass('yellow');
                    that.$footer_actions.removeClass('is-changed');
                    setTimeout(function(){
                        that.$button.empty().html($button_text);
                    },2000);
                } else {
                    that.$button.empty().html($button_text);
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$form.on('input change', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.addClass('yellow').next().show();
        });

        // Reload on cancel
        that.$cancel.on('click', function (e) {
            e.preventDefault();
            $.wa.content.reload();
            return;
        });
    };

    return WASettingsCaptcha;

})(jQuery);