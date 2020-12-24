var WASettingsPush = ( function($) {

    WASettingsPush = function(options) {
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

    WASettingsPush.prototype.initClass = function() {
        var that = this;

        //
        var $sidebar = $('#s-sidebar-wrapper');
        if (that.wa2) {
            $sidebar = $('#js-sidebar-wrapper');
        }
        $sidebar.find('ul li').removeClass('selected');
        $sidebar.find('[data-id="push"]').addClass('selected');
        //
        that.initChangeAdapter();
        //
        that.initSubmit();
    };

    WASettingsPush.prototype.initChangeAdapter = function() {
        var that = this;

        that.$wrapper.on('change', ':input[name="push_adapter"]', function(e){
            var $scope = $(this).parents('div.field'),
                fast = e.originalEvent ? false : true;

            if (fast) {
                $scope.find('div.js-push-adapter-settings').hide();
                if (this.checked) {
                    $scope.find('div.js-push-adapter-settings[data-adapter-id="' + this.value + '"]').show();
                }
            } else {
                $scope.find('div.js-push-adapter-settings').slideUp();
                if (this.checked) {
                    $scope.find('div.js-push-adapter-settings[data-adapter-id="' + this.value + '"]').slideDown();
                }
            }
        });

        that.$wrapper.find(':input[name="push_adapter"]:checked').change();
    };

    WASettingsPush.prototype.initSubmit = function () {
        var that = this,
            $errors = that.$wrapper.find('.js-errors');

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }

            $errors.text('').hide();
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
                    if (res.data.reload) {
                        setTimeout(function(){
                            $.wa.content.reload();
                        },2000);
                    }

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
                if (res.status === 'fail') {
                    $errors.text(res.errors).show();
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

    return WASettingsPush;

})(jQuery);