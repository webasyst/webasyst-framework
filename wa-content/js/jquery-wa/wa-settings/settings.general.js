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

        that.initChangeBackground();
        //
        that.initUploadCustomBackground();
        //
        that.initRemoveCustomBackground();
        //
        that.initClearCache();
        //
        that.initSubmit();
    };

    WASettingsGeneral.prototype.initChangeBackground = function() {
        var that = this;

        that.$backgrounds_wrapper.on('click', 'li > a', function () {
            var $image = $(this),
                value = $image.data('value');

            that.$backgrounds_wrapper.find('.selected').removeClass('selected');
            $image.parents('li').addClass('selected');
            that.$background_input.val(value);
            that.$form.trigger('input');

            if (value.match(/^stock:/)) {
                that.$wrapper.find('.js-stretch-checkbox').prop('disabled', true);
            } else {
                that.$wrapper.find('.js-stretch-checkbox').prop('disabled', null);
            }

            return false;
        });
    };

    WASettingsGeneral.prototype.initUploadCustomBackground = function () {
        var that = this;

        that.$wrapper.on('change', '.js-background-upload', function (e) {
            e.preventDefault();

            if (!$(this).val()) {
                return;
            }

            var href = "?module=settingsUploadCustomBackground",
                image = new FormData();

            image.append("image", $(this)[0].files[0]);

            // Remove all custom image
            // Preview in list
            var old_value = that.$preview_wrapper.find('.js-custom-background-preview').data('value');
            that.$backgrounds_wrapper.find('a[data-value="'+ old_value +'"]').parents('li').remove();
            // Big preview
            that.$preview_wrapper.html('');

            that.$upload_preview_background_wrapper.find('.loading').show();

            $.ajax({
                url: href,
                type: 'POST',
                data: image,
                cache: false,
                contentType: false,
                processData: false
            }).done(function(res) {
                var $preview_template = $(that.$wrapper.find('.js-preview-template').html()),
                    $list_preview_template = that.$wrapper.find('.js-list-preview-template').clone();

                // Set value in hidden field
                that.$background_input.val(res.data.file_name);

                // Set big preview
                $preview_template.find('.js-custom-background-preview').attr('data-value', res.data.file_name);
                $preview_template.find('.js-image-img').attr('src', res.data.img_path);
                $preview_template.find('.js-image-width').text(res.data.width);
                $preview_template.find('.js-image-height').text(res.data.height);
                $preview_template.find('.js-image-size').text(res.data.file_size_formatted);
                $preview_template.find('.stretch').removeAttr('style').find('.js-stretch-checkbox').removeAttr('disabled');

                // Set preview in images list
                $list_preview_template.find('a').attr('data-value', res.data.file_name);
                $list_preview_template.find('img').attr('src', res.data.img_path).attr('alt', res.data.file_name);
                $list_preview_template.removeClass('js-list-preview-template').removeAttr('style');

                that.$backgrounds_wrapper.find('.selected').removeClass('selected');
                that.$backgrounds_wrapper.append($list_preview_template);

                that.$preview_wrapper.html($preview_template);

                that.$upload_preview_background_wrapper.find('.loading').hide();
            });
            $(this).val('');
        });
    };

    WASettingsGeneral.prototype.initRemoveCustomBackground = function () {
        var that = this;

        that.$wrapper.on('click', '.js-remove-custom-backgorund', function (e) {
            var $dialog_text = that.$wrapper.find('.js-remove-text').clone(),
                dialog_buttons = that.$wrapper.find('.js-remove-buttons').clone().html(),
                value = $(this).parents('.js-custom-background-preview').data('value');

            $dialog_text.show();
            e.preventDefault();
            // Show confirm dialog
            $($dialog_text).waDialog({
                'buttons': dialog_buttons,
                'width': '500px',
                'height': '65px',
                'min-height': '65px',
                onSubmit: function (d) {
                    var href = '?module=settingsRemoveCustomBackground';

                    $.get(href, function (res) {
                        that.$backgrounds_wrapper.find('a[data-value="'+ value +'"]').parents('li').remove();
                        that.$preview_wrapper.html('');
                        that.$backgrounds_wrapper.find('a[data-value="stock:bokeh_vivid.jpg"]').click();
                    });

                    d.trigger('close'); // close dialog
                    $('.dialog').remove(); // remove dialog
                    return false;
                }
            });
        });
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