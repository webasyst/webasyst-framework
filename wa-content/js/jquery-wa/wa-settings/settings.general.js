const WASettingsGeneral = ( function($) {
    return class WASettingsGeneral {
        constructor(options) {
            let that = this;

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

            that.$text_logo_block = that.$form.find('.js-config-text-logo');
            that.$image_logo_block = that.$form.find('.js-config-image-logo');
            that.$logo_text_image = that.$image_logo_block.find('.js-logo-area');
            that.$logo_text_area = that.$text_logo_block.find('.js-logo-area');
            that.$logo_text_input = that.$form.find('.js-logo-text');
            that.$change_bgcolor_btn = that.$text_logo_block.find('.js-switch-color');
            that.$switch_two_line = that.$form.find('.js-switch-two-line');
            that.$two_line_field = that.$form.find('#two-line-field');
            that.$custom_colors = that.$form.find('.js-custom-colors');

            that.$logo_type_toggle = that.$form.find(".js-logo-type-toggle");
            that.$color_type_toggle = that.$form.find(".js-color-type-toggle");

            that.$picker_btn = that.$wrapper.find('.js-color-picker-button');

            // VARS
            that.local = options.local
            that.$pickr_options = {
                el: '.pickr-color-picker',
                container: '.custom-colors',
                theme: 'classic',
                lockOpacity: true,
                position: 'right-start',
                components: {
                    palette: true,
                    hue: true,
                    interaction: {
                        cancel: true,
                        save: true
                    }
                },
                i18n: {
                    'btn:save': that.local.save,
                    'btn:cancel': that.local.cancel,
                }
            }

            // DYNAMIC VARS
            that.is_locked = false;
            that.logo_text = that.$logo_text_area.text().trim();
            that.gradient_from = null
            that.gradient_to = null

            // INIT
            that.initClass();
        }

        initClass() {
            let that = this;

            //
            let $sidebar = $('#js-sidebar-wrapper');

            $sidebar.find('ul li').removeClass('selected');
            $sidebar.find('[data-id="general"]').addClass('selected');

            //
            that.initClearCache();
            //
            that.initSubmit();
            //
            that.initColorPicker();
            //
            that.initSwitchTwoLines();
            //
            that.initLogoTyping();
            //
            that.initSwitchColorType();
            //
            that.initSwitchLogoType();
            //
            that.initChangeLogoBg();
            //
            that.initImageDelete();
            //
            that.initCustomColorToggle();
            //
            that.initTextSettings();
        }

        initColorPicker() {
            let that = this
            this.$custom_colors.find(this.$pickr_options.el).each(function (i, el) {
                let $color_input = ($(el).prev('input'));
                that.$pickr_options.el = $(el)[0];
                that.$pickr_options.default = $color_input.val() || '#42445a';
                const pickr = Pickr.create(that.$pickr_options)
                    .on('save', color => {
                        $color_input.val(color.toHEXA().toString(0));
                        if ($color_input.hasClass('js-text-color')) {
                            that.$logo_text_area.css('color', color.toHEXA().toString(0));
                        }
                        pickr.hide();
                        that.changeSaveBtnColor()
                    })
                    .on('cancel', () => pickr.hide());
            });
        }

        initSwitchTwoLines() {
            let that = this

            that.$switch_two_line.waSwitch({
                change: function(active) {
                    that.$two_line_field.val(active ? '1' : '0');
                    if (!active) {
                        that.logo_text = that.logo_text.replace(/\n/gm,"")
                    } else if (active && that.logo_text.length > 3) {
                        that.logo_text = that.insertChar(that.logo_text, '\n', 2)
                    }
                    that.$logo_text_area.text(that.logo_text).toggleClass('two-lines', active)
                }
            })
        }

        initLogoTyping() {
            let that = this;
            that.$logo_text_input.on('keyup', function (){
                let text = $(this).val()

                if(text.length > 3 && that.$switch_two_line.hasClass('is-active')) {
                    text = that.insertChar(text, '\n', 2)
                }

                that.logo_text = text;
                that.$logo_text_area.text(text);
                $(this).val(text);
            })
        }

        initSwitchLogoType() {
            let that = this
            that.$logo_type_toggle.waToggle({
                ready: function (toggle) {
                    toggle.$wrapper.find('input').val(toggle.$active.data('logo-type'));
                },
                change: function (event, target, toggle) {
                    let $el = $(target),
                        type = $el.data('logo-type');
                    that.$text_logo_block.toggle();
                    that.$image_logo_block.toggle();
                    toggle.$wrapper.find('input').val(type);
                }
            })
        }

        initImageDelete() {
            let that = this,
                $logo_delete = that.$wrapper.find('.js-image-logo-delete'),
                $logo_delete_input = that.$wrapper.find('.js-image-logo-delete-input')

            $logo_delete.on('click', function (e) {
                e.preventDefault()
                $logo_delete_input.val('1')
                that.$logo_text_image.find('img').css('opacity', '0.3')
                $(this).hide()
                that.$footer_actions.addClass('is-changed');
                that.$button.addClass('yellow').next().show();
            });
        }

        initSwitchColorType() {
            let that = this
            that.$color_type_toggle.waToggle({
                change: function (event, target) {
                    //$(target).addClass('selected').siblings().removeClass('selected')
                }
            })
        }

        initChangeLogoBg() {
            let that = this,
                $color_text = that.$wrapper.find('.js-text-color'),
                $color_text_pickr = $color_text.next('.pickr').find('.pcr-button'),
                $first_color = that.$wrapper.find('.js-first-color'),
                $first_color_pickr = $first_color.next('.pickr').find('.pcr-button'),
                $second_color = that.$wrapper.find('.js-second-color'),
                $second_color_pickr = $second_color.next('.pickr').find('.pcr-button')

            that.$change_bgcolor_btn.on('click', function(){
                let $btn = $(this),
                    gradient = $btn.data('gradient')

                    that.gradient_from = $btn.data('gradient-from')
                    that.gradient_to = $btn.data('gradient-to')

                $color_text.val('#FFFFFF')
                $color_text_pickr.css('color', '#FFFFFF')
                $first_color.val(that.gradient_from)
                $first_color_pickr.css('color', that.gradient_from)
                $second_color.val(that.gradient_to)
                $second_color_pickr.css('color', that.gradient_to)
                that.$logo_text_area.attr('data-background', `gradient${ gradient }`).removeAttr('style').css('color', '#ffffff')
                $btn.empty().append('<i class="fas fa-check"></i>').siblings().empty().append('<i>&nbsp;</i>')
                that.$picker_btn.attr('data-background', `gradient${ gradient }`).removeAttr('style')
                that.$footer_actions.addClass('is-changed');
                that.$button.addClass('yellow').next().show();
            })
        }

        initCustomColorToggle() {
            let that = this,
                $toggle = that.$wrapper.find('.js-custom-color-toggle')

            $toggle.on('click', function (e) {
                e.preventDefault();
                that.$custom_colors.slideToggle();
            });
        }

        initTextSettings() {
            let that = this,
                $settings_logo = that.$wrapper.find('.js-settings-logo')

            that.$picker_btn.on('click', function (e) {
                e.preventDefault();
                $settings_logo.slideToggle();
            });
        }

        initClearCache () {
            let that = this;

            that.$wrapper.on('click', '.js-clear-cache', function () {
                let href = '?module=settingsClearCache',
                    $cache_loading = that.$wrapper.find('.js-cache-loading'),
                    $loader_icon = ' <i class="fas fa-spinner fa-spin"></i>',
                    $success_icon = ' <i class="fas fa-check-circle"></i>',
                    $error_icon = ' <i class="fas fa-times-circle"></i>',
                    $clear_cache_btn = $(this),
                    $clear_cache_btn_inner = $clear_cache_btn.find('span'),
                    $clear_cache_btn_text = $clear_cache_btn_inner.text();

                $cache_loading.removeClass('yes').addClass('loading').show();
                //wa2
                $clear_cache_btn.prop('disabled', true).find('span').empty().html($clear_cache_btn_text + $loader_icon);

                $.get(href, function(r) {
                    if (r.status == 'ok') {
                        $cache_loading.removeClass('loading').addClass('yes');
                        //wa2
                        $clear_cache_btn_inner.empty().html($clear_cache_btn_text + $success_icon);
                    } else if (r.status == 'fail') {
                        $cache_loading.removeClass('loading').addClass('no');
                        //wa2
                        $clear_cache_btn_inner.empty().html($clear_cache_btn_text + $error_icon);
                    }
                    setTimeout(function(){
                        $cache_loading.hide();
                        //wa2
                        $clear_cache_btn.prop('disabled', false).find('span').empty().html($clear_cache_btn_text);
                    },2000);
                }, 'json')
                    .error(function() {
                        $cache_loading.removeClass('loading').addClass('yes');
                        //wa2
                        $clear_cache_btn_inner.empty().html($clear_cache_btn_text + $success_icon);
                        setTimeout(function(){
                            $cache_loading.hide();
                            //wa2
                            $clear_cache_btn.prop('disabled', false).find('span').empty().html($clear_cache_btn_text);
                        },2000);
                    });
            });
        }

        async sendAjaxRequest() {
            let that = this,
                href = that.$form.attr('action'),
                formData = new FormData(),
                formParams = that.$form.serializeArray(),
                matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)")),
                csrf = matches ? decodeURIComponent(matches[1]) : '';

            $.each(that.$form.find('input[type="file"]'), function(i, el) {
                $.each($(el)[0].files, function(i, file) {
                    formData.append(el.name, file);
                });
            });

            $.each(formParams, function(i, val) {
                formData.append(val.name, val.value);
            });

            if (csrf) {
                formData.append("_csrf", csrf);
            }

            try {
                let response = await fetch(href, {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (e) {
                return e;
            }
        }

        initSubmit () {
            let that = this

            that.$form.on('submit', function (e) {
                e.preventDefault();
                if (that.is_locked) {
                    return;
                }
                that.is_locked = true;
                that.$button.prop('disabled', true);

                let $button_text = that.$button.text(),
                    $loader_icon = ' <i class="fas fa-spinner fa-spin"></i>',
                    $success_icon = ' <i class="fas fa-check-circle"></i>';

                    that.$button.empty().html($button_text + $loader_icon);

                that.sendAjaxRequest().then(res => {
                    if (res.status === 'ok') {
                        that.$button.empty().html($button_text + $success_icon).removeClass('yellow');
                        that.$footer_actions.removeClass('is-changed');

                        let logo_type = $('[name="logo[mode]"]').val()

                        if ( logo_type === 'image') {
                            let uploaded_image = $('.js-logo-area:visible').attr('style')
                            let $header_logo = $('#wa-account > a');
                            if (uploaded_image) {
                                $header_logo.empty().attr('style', uploaded_image)
                            }
                        }else{
                            // Update company name in header
                            let $logo = $('#wa-account'),
                                $h3 = $logo.find('h3'),
                                company_name = $.trim(that.$form.find('#config-logo-text').val());

                            if($('#two-line-text').is(':checked')) {
                                $h3.addClass('two-lines').text(company_name);
                            }else{
                                $logo.find('h3').removeClass('two-lines').text(company_name);
                            }

                            $logo.css({
                                'background': `linear-gradient(-90deg, ${that.gradient_from}, ${that.gradient_to})`
                            })
                        }

                        setTimeout(function(){
                            that.$button.empty().html($button_text);
                        },2000);
                    } else if (res.errors) {
                        $.each(res.errors, function (i, error) {
                            if (error.field) {
                                fieldError(error.field, error.message);
                            }
                        });
                        that.$button.empty().html($button_text);
                    }

                }).catch(error => {
                    console.error(error)
                }).finally(() => {
                    that.is_locked = false;
                    that.$button.prop('disabled', false);
                })

            });

            function fieldError(field_name, message) {
                let $field = that.$form.find('input[name='+field_name+']'),
                    $hint = $field.parent('.value').find('.js-error-place');

                $field.addClass('shake animated').focus();
                $hint.text(message);
                setTimeout(function(){
                    $field.removeClass('shake animated').focus();
                    $hint.text('');
                }, 1000);
            }

            that.$form.on('input change', function () {
                that.changeSaveBtnColor()
            });

            that.$change_bgcolor_btn.on('click', function () {
                that.changeSaveBtnColor()
            });

            that.$cancel.on('click', function (e) {
                e.preventDefault();
                $.wa.content.reload();
            });
        }

        insertChar(str, substr, pos) {
            const array = str.split('');
            array.splice(pos, 0, substr);
            return array.join('');
        }

        changeSaveBtnColor() {
            this.$footer_actions.addClass('is-changed');
            this.$button.addClass('yellow').next().show();
        }
    }
})(jQuery);
