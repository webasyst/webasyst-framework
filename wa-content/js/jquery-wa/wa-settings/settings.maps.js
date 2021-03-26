var WASettingsMaps = ( function($) {

    WASettingsMaps = function(options) {
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

    WASettingsMaps.prototype.initClass = function() {
        var that = this;

        //
        var $sidebar = $('#js-sidebar-wrapper');

        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="maps"]').addClass('selected');
        //
        that.initChangeAdapter();
        //
        that.initSubmit();
    };

    WASettingsMaps.prototype.initChangeAdapter = function() {
        var that = this;

        that.$wrapper.on('change', ':input.js-map-adapter-field', function(e){
            var $scope = $(this).parents('div.field'),
                fast = e.originalEvent ? false : true;

            if (fast) {
                $scope.find('div.js-map-adapter-settings').hide();
                if (this.checked) {
                    $scope.find('div.js-map-adapter-settings[data-adapter-id="' + this.value + '"]').show();
                }
            } else {
                $scope.find('div.js-map-adapter-settings').slideUp();
                if (this.checked) {
                    $scope.find('div.js-map-adapter-settings[data-adapter-id="' + this.value + '"]').slideDown();
                }
            }
        });

        that.$wrapper.find(':input.js-map-adapter-field:checked').change();
    };

    WASettingsMaps.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }

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

    return WASettingsMaps;

})(jQuery);