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
        that.is_locked = false;

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    WASettingsMaps.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="maps"]').addClass('selected');
        //
        that.initChangeAdapter();
        //
        that.initSubmit();
    };

    WASettingsMaps.prototype.initChangeAdapter = function() {
        var that = this;

        that.$wrapper.on('change', ':input[name="map_adapter"]', function(e){
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

        that.$wrapper.find(':input[name="map_adapter"]:checked').change();
    };

    WASettingsMaps.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();
            if (that.is_locked) {
                return;
            }

            var href = that.$form.attr('action'),
                data = that.$form.serialize();

            $.post(href, data, function (res) {
                if (res.status === 'ok') {
                    that.$button.removeClass('yellow').addClass('green');
                    that.$loading.removeClass('loading').addClass('yes');
                    that.$footer_actions.removeClass('is-changed');
                    setTimeout(function(){
                        that.$loading.hide();
                    },2000);
                } else {
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });
        });

        that.$form.on('input', function () {
            that.$footer_actions.addClass('is-changed');
            that.$button.removeClass('green').addClass('yellow');
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