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

        // VARS
        that.wa2 = options['wa2'] || false;

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsMaps.prototype.initClass = function() {
        var that = this;

        //
        var $sidebar = $('#s-sidebar-wrapper');
        if (that.wa2) {
            $sidebar = $('#js-sidebar-wrapper');
        }
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
            if (that.wa2) {
                var $button_text = that.$button.text(),
                    $loader_icon = ' <i class="fas fa-spinner fa-spin"></i>',
                    $success_icon = ' <i class="fas fa-check-circle"></i>';
                that.$button.empty().html($button_text + $loader_icon);
            } else {
                that.$loading.removeClass('yes').addClass('loading').show();
            }

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    if (that.wa2) {
                        that.$button.empty().html($button_text + $success_icon).removeClass('yellow');
                        that.$footer_actions.removeClass('is-changed');
                    }else{
                        that.$button.removeClass('yellow').addClass('green');
                        that.$loading.removeClass('loading').addClass('yes');
                        that.$footer_actions.removeClass('is-changed');
                    }
                    setTimeout(function(){
                        if (that.wa2) {
                            that.$button.empty().html($button_text);
                        }else{
                            that.$loading.hide();
                        }
                    },2000);
                } else {
                    if (that.wa2) {
                        that.$button.empty().html($button_text);
                    }else{
                        that.$loading.hide();
                    }
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$form.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            if (that.wa2) {
                that.$button.addClass('yellow').next().show();
            }else{
                that.$button.removeClass('green').addClass('yellow');
            }
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