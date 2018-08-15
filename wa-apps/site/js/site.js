(function ($) {
$.storage = new $.store();

$.wa.errorHandler = function (xhr) {
    $.storage.del('site/' + $.wa.site.domain + '/hash');
    if (xhr.status == 404) {
        $.wa.setHash('#/');
        return false;
    }
    return true;
};

$.wa.site = {
    options: [],
    domain: 0,
    helper: '',
    init: function (options) {
        this.domain = options.domain;
        this.redirectToProblemDomain();
        if (typeof($.History) != "undefined") {
            $.History.bind(function () {
                $.wa.site.dispatch();
            });
        }
        this.options = options;
        var hash = window.location.hash;
        if (hash === '#/' || !hash) {
            hash = $.storage.get('site/' + this.domain + '/hash');
            if (hash && hash != null) {
                $.wa.setHash('#/' + hash);
            } else {
                this.dispatch();
            }
        } else {
            $.wa.setHash(hash);
        }
    },

    setHelper: function (helper) {
        if (helper === true) {
            return false;
        }
        if (helper) {
            this.helper = helper;
            $("#s-save-panel div.wa-dropdown").show();
        } else {
            this.helper = '';
            $("#s-save-panel div.wa-dropdown").hide();
        }
    },

    dispatch: function (hash) {
        if (hash == undefined) {
            hash = window.location.hash;
        }
        hash = hash.replace(/^[^#]*#\/*/, ''); /* fix sintax highlight*/
        if (hash) {
            hash = hash.split('/');
            if (hash[0]) {
                var actionName = "";
                var attrMarker = hash.length;
                for (var i = 0; i < hash.length; i++) {
                    var h = hash[i];
                    if (i < 2) {
                        if (i === 0) {
                            actionName = h;
                        } else if (actionName == 'files') {
                            this.filesAction(hash.slice(i).join('/'));
                            return;
                        } else if (parseInt(h, 10) != h && h.indexOf('=') == -1 && actionName != 'plugins') {
                            actionName += h.substr(0,1).toUpperCase() + h.substr(1);
                        } else {
                            attrMarker = i;
                            break;
                        }
                    } else {
                        attrMarker = i;
                        break;
                    }
                }
                var attr = hash.slice(attrMarker);

                // replace actionName with a hyphen in the camel case
                actionName = actionName.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });

                if (actionName === 'systemSettings') {
                    actionName += 'General';
                }

                if (this[actionName + 'Action']) {
                    this[actionName + 'Action'].apply(this, attr);
                    // save last page to return to by default later
                    $.storage.set('site/' + this.domain + '/hash', hash.join('/'));
                } else {
                    if (console) {
                        console.log('Invalid action name:', actionName+'Action');
                    }
                }
            } else {
                this.defaultAction();
            }
        } else {
            this.defaultAction();
        }
    },

    // DEFAULT ACTION

    defaultAction: function () {
        var hash = $("div.s-sidebar ul.s-links a:first").attr('href');
        $.wa.setHash(hash);
    },

    // SITE ACTIONS

    pagesAction: function (id) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        if ($('#wa-page-container').length) {
            waLoadPage(id);
        } else {
            $('#s-save-panel input').replaceWith('<input id="wa-page-button" type="button" class="button green" value="' + $_('Save') + '">');
            $("#s-content").load('?module=pages', 'domain_id=' + this.domain + (id ? '&id=' + id : ''), function () {
                $.wa.site.savePanel(true, 's-page-editor');
                $.wa.site.active($("#s-link-pages"));
                $('#wa-page-button').click(function () {
                    $("#wa-page-form").submit();
                });
            });
        }
    },

    designAction: function (params) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        if ($('#wa-design-container').length) {
            waDesignLoad(params === undefined ? '' : params);
            $.wa.site.savePanel(params && (params.indexOf('action=edit') != -1 || params.indexOf('file=') != -1));
            $('#wa-design-button').removeClass('yellow').addClass('green');
        } else {
            var p = this.parseParams(params);
            $('#s-save-panel input').replaceWith('<input id="wa-design-button" type="button" class="button green" value="' + $_('Save') + '">');
            $("#s-content").load('?module=design', 'domain_id=' + this.domain, function () {
                $.wa.site.savePanel(params && (params.indexOf('action=edit') != -1 || params.indexOf('file=') != -1));
                $.wa.site.active($("#s-link-design"));
                $('#wa-design-button').click(function () {
                    $("#wa-design-form").submit();
                });
                waDesignLoad(params === undefined ? '' : params);
            });
        }
    },

    themesAction: function (params) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        this.savePanel(false);
        if ($('#wa-design-container').length) {
            waDesignLoad();
        } else {
            $("#s-content").load('?module=design', 'domain_id=' + this.domain, function () {
                $.wa.site.active($("#s-link-design"));
                waDesignLoad();
            });
        }
    },

    designAddAction: function (params) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        this.designAction(params + '&file=');
    },

    routingAction: function (id) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        this.savePanel(false);
        $("#s-content").load('?module=routing', 'domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-routing"));
            $("tr#route-" + id + ' .s-route-settings').click();
        });
    },

    personalSettingsAction: function (hash) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=settings&hash=' + (hash || ''), 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
            });
        }

        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalAppAction: function (app_id) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=app&app_id=' + app_id, 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
                $('#s-personal-app-' + app_id).addClass('selected');
                $('#s-app-frontend-link').html($('#s-personal-app-' + app_id).data('link')).attr('href', $('#s-personal-app-' + app_id).data('link'));
            });
        }
        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalProfileAction: function () {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=profile', 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
                $('#s-personal-profile-link').addClass('selected');
            });
        }
        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalAction: function (callback) {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        this.savePanel(false);
        $("#s-content").load('?module=personal', 'domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-personal"));
            if (callback) {
                callback();
            } else {
                if ($('#s-personal-settings-link').data('enabled')) {
                    $.wa.setHash($('ul.s-personal-structure a:first').attr('href'));
                } else {
                    $.wa.setHash('#/personal/settings/');
                }
            }
        });
    },

    settingsAction: function (tab) {
        this.savePanel(false);
        $("#s-content").load('?module=settings&domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-settings"));
        });
    },

    settingsRoutingAction: function () {
        if (this.options.mirror) {
            $.wa.setHash('#/');
            return null;
        }

        this.settingsAction('routing');
    },

    // SYSTEM BLOCK ACTIONS

    newsiteAction: function (params) {
        var params = this.parseParams(params)
        $("#domain-name").val(params.domain || '');
        $('#addsite-dialog span.error').empty().hide();
        $("#addsite-dialog").waDialog({
            onSubmit: function () {
                var f = $(this);
                $.post(f.attr('action'), f.serialize(), function (response) {
                    if (response.status == 'ok') {
                        location.href = '?domain_id=' + response.data.id + '#/settings/';
                    } else if (response.status == 'fail') {
                        $("#addsite-dialog span.errormsg").html(response.errors).show();
                    }
                }, "json");
                return false;
            }
        });
        if ($("#s-content .triple-padded .loading").length) {
            this.defaultAction();
        }
    },

    pluginsAction: function (params) {
        this.savePanel(false);
        if ($('#wa-plugins-container').length) {
            $.plugins.dispatch(params);
        } else {
            $("#s-content").load('?module=plugins', {}, function () {
                $.wa.site.active($("#s-link-plugins"));
            });
        }
    },

    systemSettingsGeneralAction: function () {
        this.savePanel(false);
        $("#s-content").load('?module=systemSettingsGeneral', {}, function () {
            $.wa.site.active($("#s-link-system-settings"));
            $('#s-system-settings-sidebar').find('li').removeClass('selected');
            $('#s-system-settings-sidebar').find('.js-general').addClass('selected');

            /* Generate settings page */
            var $wrapper = $('#s-general-settings-page'),
                $form = $wrapper.find('form'),
                $footer_actions = $form.find('.js-footer-actions'),
                $button = $footer_actions.find('.js-submit-button'),
                $cancel = $footer_actions.find('.js-cancel'),
                $loading = $footer_actions.find('.s-loading'),
                is_locked = false;

            /* Background image in authorization page */
            var $backgrounds_wrapper = $wrapper.find('.js-background-images'),
                $preview_wrapper = $wrapper.find('.js-custom-preview-wrapper'),
                $background_input = $wrapper.find('input[name="auth_form_background"]'),
                $upload_preview_background_wrapper = $wrapper.find('.js-upload-preview');

            /* Click on background image */
            $backgrounds_wrapper.on('click', 'li > a', function () {
                var $image = $(this),
                    value = $image.data('value');

                $backgrounds_wrapper.find('.selected').removeClass('selected');
                $image.parents('li').addClass('selected');
                $background_input.val(value);
                $form.trigger('input');

                if (value.match(/^stock:/)) {
                    $wrapper.find('.js-stretch-checkbox').prop('disabled', true);
                } else {
                    $wrapper.find('.js-stretch-checkbox').prop('disabled', null);
                }

                return false;
            });

            // Upload custom background image
            $('.js-background-upload').on('change', function (e) {
                e.preventDefault();

                if (!$(this).val()) {
                    return;
                }

                var href = "?module=systemSettingsUploadCustomBackground",
                    image = new FormData();
                image.append("image", $(this)[0].files[0]);

                // Remove all custom image
                // Preview in list
                var old_value = $preview_wrapper.find('.js-custom-background-preview').data('value');
                $backgrounds_wrapper.find('a[data-value="'+ old_value +'"]').parents('li').remove();
                // Big preview
                $preview_wrapper.html('');

                $upload_preview_background_wrapper.find('.loading').show();

                $.ajax({
                    url: href,
                    type: 'POST',
                    data: image,
                    cache: false,
                    contentType: false,
                    processData: false
                }).done(function(res) {
                    var $preview_template = $($wrapper.find('.js-preview-template').html()),
                        $list_preview_template = $wrapper.find('.js-list-preview-template').clone();

                    // Set value in hidden field
                    $background_input.val(res.data.file_name);

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

                    $backgrounds_wrapper.find('.selected').removeClass('selected');
                    $backgrounds_wrapper.append($list_preview_template);

                    $preview_wrapper.html($preview_template);

                    $upload_preview_background_wrapper.find('.loading').hide();
                });
                $('.js-background-upload').val('');
            });

            // Remove custom background image
            $wrapper.on('click', '.js-remove-custom-backgorund', function (e) {
                var $dialog_text = $wrapper.find('.js-remove-text').clone(),
                    dialog_buttons = $wrapper.find('.js-remove-buttons').clone().html(),
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
                        var href = '?module=systemSettingsRemoveCustomBackground';

                        $.get(href, function (res) {
                            $backgrounds_wrapper.find('a[data-value="'+ value +'"]').parents('li').remove();
                            $preview_wrapper.html('');
                            $backgrounds_wrapper.find('a[data-value="stock:bokeh_vivid.jpg"]').click();
                        });

                        d.trigger('close'); // close dialog
                        $('.dialog').remove(); // remove dialog
                        return false;
                    }
                });
            });

            // Clear cache
            $wrapper.on('click', '.js-clear-cache', function () {
                var href = '?module=systemSettingsClearCache',
                    $cache_loading = $wrapper.find('.js-cache-loading');

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

            // Submit
            $form.on('submit', function (e) {
                e.preventDefault();
                if (is_locked) {
                    return;
                }
                is_locked = true;
                $button.prop('disabled', true);
                $loading.removeClass('yes').addClass('loading').show();

                var href = $form.attr('action'),
                    data = $form.serialize();

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $button.removeClass('yellow').addClass('green');
                        $loading.removeClass('loading').addClass('yes');
                        $footer_actions.removeClass('is-changed');
                        setTimeout(function(){
                            $loading.hide();
                        },2000);
                    } else if (res.errors) {
                        $.each(res.errors, function (i, error) {
                            if (error.field) {
                                fieldError(error.field, error.message);
                            }
                        });
                        $loading.hide();
                    }
                    is_locked = false;
                    $button.prop('disabled', false);
                });
            });

            $form.on('input', function () {
                $footer_actions.addClass('is-changed');
                $button.removeClass('green').addClass('yellow');
            });

            // Reload on cancel
            $cancel.on('click', function (e) {
                e.preventDefault();
                location.reload();
            });

            function fieldError(field_name, message) {
                var $field = $form.find('input[name='+field_name+']'),
                    $hint = $field.parent('.value').find('.js-error-place');

                $field.addClass('shake animated').focus();
                $hint.text(message);
                setTimeout(function(){
                    $field.removeClass('shake animated').focus();
                    $hint.text('');
                }, 1000);
            }
        });
    },

    systemSettingsEmailAction: function () {
        this.savePanel(false);
        $("#s-content").load('?module=systemSettingsEmail', {}, function () {
            $.wa.site.active($("#s-link-system-settings"));
            $('#s-system-settings-sidebar').find('li').removeClass('selected');
            $('#s-system-settings-sidebar').find('.js-email').addClass('selected');

            /* Email settings page */
            var $wrapper = $('#s-email-settings-page'),
                $form = $wrapper.find('form'),
                $items_wrapper = $form.find('.js-settings-items'),
                $item_add = $wrapper.find('.js-add-item'),
                $item_template = $wrapper.find('.js-template'),
                $footer_actions = $form.find('.js-footer-actions'),
                $button = $footer_actions.find('.js-submit-button'),
                $cancel = $footer_actions.find('.js-cancel'),
                $loading = $form.find('.s-loading'),
                is_locked = false,
                item_class = ".js-item",
                item_remove_class = ".js-remove",
                key_class = ".js-key",
                transport_class = ".js-transport",
                dkim_checkbox_class = ".js-dkim-checkbox";

            // Change transport
            $wrapper.on('change', transport_class, function () {
                var $item = $(this).parents(item_class),
                    transport = $item.find(transport_class).val();

                $item.find('.js-params').hide(); // Hide all params
                $item.find('.js-transport-description').css('display', 'none'); // Hide all descriptions
                $item.find('.js-'+ transport +'-description').css('display', 'inline-block'); // Show needed description
                $item.find('.js-'+ transport +'-params').show(); // Show needed params
            });

            // DKIM
            $wrapper.on('change', dkim_checkbox_class, function () {
                var $dkim_checkbox = $(this),
                    $item = $dkim_checkbox.parents(item_class),
                    is_on = $dkim_checkbox.is(':checked');

                if (is_on) {
                    dkim($item, 'generateDkim');
                } else {
                    dkim($item, 'removeDkim');
                }
            });

            // Remove dkim settings if email or domain is changed
            $wrapper.on('input', key_class, function () {
                var $item = $(this).parents(item_class);
                if (!$.trim($(this).val())) {
                    dkim($item, 'showNeedEmail');
                } else {
                    dkim($item, 'hideNeedEmail');
                }
            });

            function dkim($item, action) {
                var $dkim_checkbox = $item.find('.js-dkim-checkbox'),
                    $dkim_sender_input = $item.find('.js-key'),
                    $dkim_wrapper = $item.find('.js-dkim-field'),
                    $dkim_private_key = $dkim_wrapper.find('.js-dkim-pvt-key'),
                    $dkim_public_key = $dkim_wrapper.find('.js-dkim-pub-key'),
                    $dkim_selector = $dkim_wrapper.find('.js-dkim-selector'),
                    $dkim_info = $item.find('.js-dkim-info'),
                    $dkim_one_string_key = $dkim_wrapper.find('.js-one-string-key'),
                    $dkim_host_selector = $dkim_wrapper.find('.js-dkim-host-selector'),
                    $dkim_domain_0 = $dkim_wrapper.find('.js-sender-domain-0'),
                    $dkim_domain = $dkim_wrapper.find('.js-domain'),
                    $dkim_needs_email = $dkim_wrapper.find('.js-dkim-needs-email'),
                    $dkim_error = $dkim_wrapper.find('.js-dkim-error');

                if (action === "generateDkim") {
                    var email = $.trim($dkim_sender_input.val()),
                        href = '?module=systemSettingsGenerateDkim',
                        data = { email: email };

                    $dkim_error.slideUp().text('');
                    $.post(href, data, function(r) {
                        if (r.status == 'ok') {
                            $dkim_private_key.val(r.data.dkim_pvt_key);
                            $dkim_public_key.val(r.data.dkim_pub_key);
                            $dkim_selector.val(r.data.selector);
                            $dkim_one_string_key.text(r.data.one_string_key);
                            $dkim_host_selector.text(r.data.selector);
                            $dkim_domain_0.text(r.data.domain);
                            $dkim_domain.text(r.data.domain);
                            $dkim_info.slideDown();
                        } else if (r.status == 'fail' && r.errors) {
                            $dkim_error.text(r.errors[0]).slideDown();
                        }
                    }, 'json')
                    .error(function() {
                        $dkim_error.text('Failed to create DKIM signature').slideDown();
                    });
                } else if (action === "removeDkim") {
                    $dkim_info.slideUp();
                    setTimeout(function () {
                        removeDkimData();
                    }, 150);
                } else if (action === "hideNeedEmail") {
                    $dkim_needs_email.hide();
                    $dkim_checkbox.prop('checked', false);
                    $dkim_info.slideUp();
                    setTimeout(function () {
                        removeDkimData();
                    }, 150);
                } else if (action === "showNeedEmail") {
                    $dkim_needs_email.show();
                    $dkim_checkbox.prop('checked', false);
                    $dkim_info.slideUp();
                    setTimeout(function () {
                        removeDkimData();
                    }, 150);
                }

                function removeDkimData() {
                    $dkim_error.slideUp().text('');
                    $dkim_private_key.val('');
                    $dkim_public_key.val('');
                    $dkim_selector.val('');
                    $dkim_one_string_key.text('');
                    $dkim_host_selector.text('');
                    $dkim_domain_0.text('');
                    $dkim_domain.text('');
                }
            }

            // Add item
            $item_add.on('click', function (e) {
                e.preventDefault();
                var $item = $item_template.clone().removeClass('js-template').addClass('js-item');
                $item.find('.js-key').val('');
                $items_wrapper.append($item);
                $form.trigger('input');
            });

            // Remove item
            $wrapper.on('click', item_remove_class, function (e) {
                e.preventDefault();
                var $item = $(this).parents(item_class);
                $item.remove();
                $form.trigger('input');
            });

            // Submit
            $form.on('submit', function (e) {
                e.preventDefault();

                // Set attribute name for all item fields
                // by data-name attribute
                var $all_items = $items_wrapper.find('.js-item');
                $.each($all_items, function (i, item) {
                    setNames($(item));
                });

                // Send post
                if (is_locked) {
                    return;
                }
                is_locked = true;
                $button.prop('disabled', true);
                $loading.removeClass('yes').addClass('loading').show();

                var href = $form.attr('action'),
                    data = $form.serialize();

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $button.removeClass('yellow').addClass('green');
                        $loading.removeClass('loading').addClass('yes');
                        $footer_actions.removeClass('is-changed');
                        setTimeout(function(){
                            $loading.hide();
                        }, 2000);
                    } else if (res.errors) {
                        $.each(res.errors, function (i, error) {
                            if (error.field) {
                                fieldError(error);
                            }
                        });
                        $loading.hide();
                    } else {
                        $loading.hide();
                    }
                    is_locked = false;
                    $button.prop('disabled', false);
                });

                function setNames($item) {
                    var item_key = $item.find(key_class).val(),
                        item_fields = $item.find('[data-name]');

                    if (typeof item_key !== 'string' || !item_key) {
                        return;
                    }

                    $.each(item_fields, function (i, field) {
                        var $field = $(field);
                        $field.attr('name', 'data['+ item_key +']['+ $field.data('name') +']');
                    });
                }

            });

            $form.on('input', function () {
                $footer_actions.addClass('is-changed');
                $button.removeClass('green').addClass('yellow');
            });

            // Reload on cancel
            $cancel.on('click', function (e) {
                e.preventDefault();
                location.reload();
            });

            function fieldError(error) {
                var $field = $form.find('input[name='+error.field+']'),
                    $hint = $field.parent('.value').find('.js-error-place');

                $field.addClass('shake animated').focus();
                $hint.text(error.message);
                setTimeout(function(){
                    $field.removeClass('shake animated').focus();
                    $hint.text('');
                }, 1000);
            }
        });
    },

    systemSettingsMapsAction: function () {this.savePanel(false);
        $("#s-content").load('?module=systemSettingsMaps', {}, function () {
            $.wa.site.active($("#s-link-system-settings"));
            $('#s-system-settings-sidebar').find('li').removeClass('selected');
            $('#s-system-settings-sidebar').find('.js-maps').addClass('selected');

            /* Maps settings page */
            var $wrapper = $('#s-maps-settings-page'),
                $form = $wrapper.find('form'),
                $footer_actions = $form.find('.js-footer-actions'),
                $button = $footer_actions.find('.js-submit-button'),
                $cancel = $footer_actions.find('.js-cancel'),
                $loading = $footer_actions.find('.s-loading'),
                is_locked = false;

            // Change adapter
            $wrapper.on('change', ':input[name="map_adapter"]', function(e){
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

            $wrapper.find(':input[name="map_adapter"]:checked').change();

            // Submit
            $form.on('submit', function (e) {
                e.preventDefault();
                if (is_locked) {
                    return;
                }
                is_locked = true;
                $button.prop('disabled', true);
                $loading.removeClass('yes').addClass('loading').show();

                var href = $form.attr('action'),
                    data = $form.serialize();

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $button.removeClass('yellow').addClass('green');
                        $loading.removeClass('loading').addClass('yes');
                        $footer_actions.removeClass('is-changed');
                        setTimeout(function(){
                            $loading.hide();
                        },2000);
                    } else {
                        $loading.hide();
                    }
                    is_locked = false;
                    $button.prop('disabled', false);
                });
            });

            $form.on('input', function () {
                $footer_actions.addClass('is-changed');
                $button.removeClass('green').addClass('yellow');
            });

            // Reload on cancel
            $cancel.on('click', function (e) {
                e.preventDefault();
                location.reload();
            });
        });
    },

    systemSettingsCaptchaAction: function () {this.savePanel(false);
        $("#s-content").load('?module=systemSettingsCaptcha', {}, function () {
            $.wa.site.active($("#s-link-system-settings"));
            $('#s-system-settings-sidebar').find('li').removeClass('selected');
            $('#s-system-settings-sidebar').find('.js-captcha').addClass('selected');

            /* Maps settings page */
            var $wrapper = $('#s-captcha-settings-page'),
                $form = $wrapper.find('form'),
                $footer_actions = $form.find('.js-footer-actions'),
                $button = $footer_actions.find('.js-submit-button'),
                $cancel = $footer_actions.find('.js-cancel'),
                $loading = $footer_actions.find('.s-loading'),
                is_locked = false;

            // Change adapter
            $form.find(':input[name="captcha"]').on('change', function(){
                if (this.value == 'waReCaptcha') {
                    $form.find('div.js-captcha-adapter-settings').slideDown();
                } else {
                    $form.find('div.js-captcha-adapter-settings').slideUp();
                }
            });

            // Submit
            $form.on('submit', function (e) {
                e.preventDefault();
                if (is_locked) {
                    return;
                }
                is_locked = true;
                $button.prop('disabled', true);
                $loading.removeClass('yes').addClass('loading').show();

                var href = $form.attr('action'),
                    data = $form.serialize();

                $.post(href, data, function (res) {
                    if (res.status === 'ok') {
                        $button.removeClass('yellow').addClass('green');
                        $loading.removeClass('loading').addClass('yes');
                        $footer_actions.removeClass('is-changed');
                        setTimeout(function(){
                            $loading.hide();
                        },2000);
                    } else {
                        $loading.hide();
                    }
                    is_locked = false;
                    $button.prop('disabled', false);
                });
            });

            $form.on('input', function () {
                $footer_actions.addClass('is-changed');
                $button.removeClass('green').addClass('yellow');
            });

            // Reload on cancel
            $cancel.on('click', function (e) {
                e.preventDefault();
                location.reload();
            });
        });
    },

    filesAction: function (load, path) {
        this.savePanel(false);
        var page = 1;
        if (load === true) {
            var params = path || this.filesPath();
        } else {
            var params = Array.prototype.join.call(arguments, '/');
            load = false;
        }

        params = decodeURI(params);
        if (params && (params.indexOf('?') != -1) && params.substr(-1) != '/') {
            var tmp = params.substr(params.indexOf('?') + 1);
            params = params.substr(0, params.indexOf('?'));
            tmp = tmp.split('=');
            if (tmp[0] == 'page') {
                page = tmp[1];
            }
        }
        //s-files-tree
        var loadFiles =  function () {
            $.wa.site.active($("#s-link-files"));
            $("#s-files-tree li.selected").removeClass('selected');
            if (!params) {
                $("#s-folder-actions-li").hide();
                $("a.s-baseurl").addClass('selected');
            } else {
                $("a.s-baseurl").removeClass('selected');
                $("#s-folder-actions-li").show();

                var a = $("#s-files-tree a[href]").filter(function() {
                    return $(this).attr('href') == '#/files/' + params + '';
                });
                a.parent().addClass('selected');
                var p = a.parent();
                while (p.length) {
                    var i = p.find('> i.overhanging');
                    if (i.hasClass('rarr')) {
                        i.click();
                    }
                    p = p.parent('ul').parent('li');
                }
            }

            if ($.wa.site.filesPath()) {
                $("#s-folder-actions-li").show();
            } else {
                $("#s-folder-actions-li").hide();
            }

            $.wa.site.filesList(params, page);
            $("#s-upload-path").val(params || '');
            $("#s-current-path").html('/' + (params || ''));
            $("#s-files-count").html('0');
            $("#s-files-grid input.all").removeAttr('checked');
        };
        if ($("#s-files-tree").length && !load) {
            loadFiles();
        } else {
            $("#s-content").load('?module=files', 'domain_id=' + this.domain, function () {
// deprecated                $(".s-scrollable-part").scrollTop(0);
                $("#s-files-tree i.overhanging").click(function () {
                    var i = $(this);
                    if (i.hasClass('rarr')) {
                        i.removeClass('rarr').addClass('darr').parent().children('ul').show();
                    } else {
                        i.removeClass('darr').addClass('rarr').parent().children('ul').hide();
                    }
                });
                if (load === true && path) {
                    $.wa.setHash('#/files/' + path);
                } else {
                    loadFiles();
                }
            });
        }
    },

    blocksAction: function (params) {
        $('#s-save-panel input').replaceWith('<input id="s-editor-save-button" type="button" class="button green" value="' + $_('Save') + '">');
        $("#s-content").load('?module=blocks', params, function () {
// deprecated            $(".s-scrollable-part").scrollTop(0);
            waEditorAceInit({
                id: 'content',
                save_button: 's-editor-save-button'
            });
            $('#s-editor-save-button').click(function () {
                $("#site-form").submit();
            })
            $.wa.site.savePanel(true);
            $.wa.site.setHelper('app=');
            $.wa.site.active($("#s-link-blocks"));
        });
    },

    blocksAddAction: function () {
        this.blocksAction('id=');
    },

    // HELP FUNCTIONS BLOCK

    parseParams: function (params) {
        if (!params) return {};
        var p = params.split('&');
        var result = {};
        for (i = 0; i < p.length; i++) {
            var t = p[i].split('=');
            result[t[0]] = t.length > 1 ? t[1] : '';
        }
        return result;
    },

    active: function (el) {
        $(".sidebar a.selected").removeClass('selected');
        $("ul.s-links li.selected").removeClass('selected');
        if (el && el.length) {
            el.addClass('selected');
        }
    },

    savePanel: function (show, add_class) {
        if (show) {
            $("#s-save-panel").show();
            $("#s-save-panel input").removeClass('yellow').addClass('green');
            $("#wa-editor-status").empty();
            if (add_class) {
                $("#s-save-panel .s-bottom-fixed-bar-content-offset").addClass(add_class);
            } else {
                $("#s-save-panel .s-bottom-fixed-bar-content-offset").attr('class', 's-bottom-fixed-bar-content-offset');
            }
        } else {
            $("#s-save-panel").hide();
        }
    },

    getTreeHTML: function (data, cl, hash, dirs_decoded) {
        var hash = hash || '';
        var dirs_decoded = dirs_decoded || {};
        var html = '<ul' + (cl ? '' : ' style="display:none"') + ' class="menu-v with-icons' + (cl ? ' ' + cl : '') + '">';
        var id = '';
        var title = '';
        var $div = $('<div>');

        for (var i = 0; i < data.length; i++) {
            id = typeof(data[i]) == 'string' ? data[i] : data[i]['id'];
            html += '<li>';
            if (typeof(data[i]) != 'string') {
                html += '<i class="icon16 rarr overhanging"></i>';
            }
            if (dirs_decoded.hasOwnProperty(id)) {
                title = dirs_decoded[id];
            } else {
                title = '';
            }

            html += '<a href="#/files/' + hash + decodeURI(id) + '/" title = \"'+ $div.text(title).html() +'\"><i class="icon16 folder"></i><b>' + decodeURI(id) + '</b></a>';

            if (typeof(data[i]) != 'string') {
                html +=  this.getTreeHTML(data[i]['childs'], false, hash + decodeURI(id) + '/', dirs_decoded);
            }
            html += '</li>';
        }
        html += '</ul>';
        return html;
    },

    filesPath: function (full) {
        var prefix = full ? 'wa-data/public/site/' : '';
        if ($("#s-files-tree li.selected").length) {
            return prefix + $("#s-files-tree li.selected a").attr('href').substr(8);
        }
        return prefix;
    },

    filesPathOptions: function (el, prefix, is_folder) {
        var prefix = prefix || '';
        var result = '';
        if (prefix == '') {
            result = '<option value="">wa-data/public/site</a>';
            prefix = '&nbsp;&nbsp;&nbsp;'
        }
        var is_folder = is_folder || false;
        el.children('li').each(function () {
            if ((is_folder && $(this).find('> ul > li.selected').length) ||
                (!is_folder && $(this).hasClass('selected'))) {
                var selected = true;
            } else {
                var selected = false;
            }

            var a = $(this).children('a');
            result += '<option ' + (selected ? 'selected="selected"' : '') + ' value="' + a.attr('href').substr(8)  + '">' + prefix + a.children('b').html() + '</option>';
            if ($(this).children('ul').length && (!is_folder || !$(this).hasClass('selected'))) {
                result += $.wa.site.filesPathOptions($(this).children('ul'), prefix + '&nbsp;&nbsp;&nbsp;', is_folder);
            }
        });
        return result;
    },

    filesList: function (path, page) {
        if (!path) {
            path = this.filesPath();
        }
        if (!page) {
            page = this.filesPage();
        }
        var url = 'http://' + this.options.domain_url + '/wa-data/public/site/' + $.wa.site.filesPath();
        $.post("?module=files&action=list&page=" + page, {path: path}, "json").then(function (response) {
            $("#s-files-grid tr.s-file").remove();
            $("div.s-pagination").empty();
            for (var i = 0; i < response.data.files.length; i++) {
                var r = response.data.files[i];
                var html = '<tr class="s-file"><td class="min-width"><input type="checkbox" value="' + r.file + '" /></td>' +
                    '<td><ul class="menu-h dropdown clickable"><li>' +
                    '<a href="'+url+r.file+'"><i class="icon16 ' + r.type + '"></i> ' +
                    r.file + ' <i class="icon10 darr no-overhanging s-file-actions"></i></a>' +
                    '</li></ul></td>' +
                    '<td>' + r.datetime + '</td>' +
                    '<td><span class="float-right">' + $.wa.site.getFileSize(r.size) + '</span></td></tr>';
                $("#s-files-grid").append(html);
            }
            if (response.data.pages > 1) {
                var html = '<ul class="menu-h">';
                for (var i = 1; i <= response.data.pages; i++) {
                    html += '<li' + (i == page ? ' class="selected"' : '') + '><a href="#/files/' + path + '?page=' + i + '">' + i + '</a></li>';
                }
                html += '</ul>';
                $("div.s-pagination").html(html).show();
            }
        }, function() {
        });
    },

    getFileSize: function (size) {
        if (size < 1024) {
            return size + ' B';
        } else if (size < 1024 * 1024) {
            return Math.round(size/1024) + ' KB';
        } else if (size < 1024 * 1024 * 1024) {
            return Math.round(size/(1024 * 1024)) + ' MB';
        } else {
            return Math.round(size/(1024 * 1024 * 1024)) + ' GB';
        }
    },

    checkFileType: function (type) {
        return type == 'image' || type == 'text' || type == 'script-css' || type == 'script-js';
    },

    getFileMenu: function (file) {
        var url = this.options.domain_url + '/wa-data/public/site/' + $.wa.site.filesPath() + file;
        var menu = $('<ul class="menu-v width-icons" style="display:block"></ul>');
        if (file.substr(-4) != '.php' && file.substr(-6) != '.phtml' && file.substr(0,1) != '.') {
            menu.append('<li>' +
                '<i class="icon16 globe"></i>' + $_('File URL') + ': ' +
                '<a href="'+ this.options.domain_protocol + url + '" target="_blank" class="bold">' + url + '<i class="icon10 new-window"></i></a>' +
                '</li>');
        }
        if (file.substr(-4) != '.php' && file.substr(-6) != '.phtml') {
            menu.append('<li>' +
                '<a href="?module=files&action=download&path=' +
                $.wa.site.filesPath() + '&file=' + file + '"><i class="icon16 download"></i>' + $_('Download') +
                '</a></li>');
        }
        menu.append($('<li></li>').append('<a href="#"><i class="icon16 edit"></i>' + $_('Rename') + '</a>').click(function () {
            $("#s-rename-dialog").waDialog({
                disableButtonsOnSubmit: true,
                onLoad: function () {
                    $("#s-name").val(file).focus().select();
                    $(this).find('span').html($.wa.site.filesPath(true));
                },
                onSubmit: function () {
                    var name = $("#s-name").val();
                    $.post('?module=files&action=rename', { path: $.wa.site.filesPath(), name: name, file: file}, function (response) {
                        if (response.status == 'ok') {
                            $.wa.site.filesList();
                            $("#s-rename-dialog").hide();
                        } else if (response.status == 'fail') {
                            alert(response.errors);
                            $("#s-rename-dialog input[type=submit]").removeAttr('disabled');
                        }
                    }, "json");
                    return false;
                }
            });
            return false;
        }));
        menu.append($('<li></li>').append('<a href="#"><i class="icon16 move"></i>' + $_('Move to folder') + '</a>').click(function () {
            $("#s-move-dialog select").html($.wa.site.filesPathOptions($("#s-files-tree > ul.s-folderlist"), ''));
            $("#s-move-dialog-files").html('<input type="hidden" name="file" value="' + file + '" />');
            $("#s-move-dialog input[name=path]").val($.wa.site.filesPath());
            $("#s-move-dialog h1 span").empty();
            $("#s-move-dialog").waDialog({
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    $.post('?module=files&action=move', $("#s-move-dialog form").serialize() , function (response) {
                        if (response.status == 'ok') {
                            $("#s-move-dialog").hide();
                            $.wa.site.filesList();
                        } else if (response.status == 'fail') {
                            alert(response.errors);
                            $("#s-move-dialog input[type=submit]").removeAttr('disabled');
                        }
                    }, "json");
                    return false;
                }
            });
            return false;
        }));
        menu.append($('<li></li>').append('<a href="#"><i class="icon16 delete"></i>' + $_('Delete') + '</a>').click(function () {
            $("#s-delete-dialog").waDialog({
                content: '<h1>' + $_('Delete file') + '</h1><p>' + $_('File') + ' <b>' + file + '</b> ' + $_('will be deleted without the ability to recover.') + '</p>',
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    $.post('?module=files&action=delete', {path: $.wa.site.filesPath(), file: file}, function (response) {
                        if (response.status == 'ok') {
                            $.wa.site.filesList();
                            $("#s-delete-dialog").hide();
                        } else if (response.status == 'fail') {
                            alert(response.errors);
                            $("#s-delete-dialog input[type=submit]").removeAttr('disabled');
                        }
                    }, "json");
                }
            });
            return false;
        }));
        return menu;
    },

    systemSettings: function () {
        var $sidebar = $('#s-system-settings-sidebar'),
            $general_link = $sidebar.find('.js-general'),
            $email_link = $sidebar.find('.js-email');

        $general_link.on('click', function () {
            this.systemSettingsGeneralAction;
        });

        $email_link.on('click', function () {
            this.systemSettingsEmailAction;
        });
    },

    filesPage: function (hash) {
        if (!hash) {
            hash = location.hash;
        }
        hash = hash.split('/');
        hash = hash[hash.length - 1];
        if (hash && hash.substr(0, 1) == '?') {
            hash = hash.substr(1).split('=');
            if (hash[0] == 'page') {
                return hash[1];
            }
        }
        return 1;
    },

    /**
     * If in url not found hash and this domain have problem need redirect user to settlements settings.
     * return bool and redirect user.
     */
    redirectToProblemDomain: function () {
        var hash = window.location.hash,
            problem_domain = $('#error-domain-' + this.domain + '.visible');
        if (problem_domain && (hash === '#/' || !hash)) {
            var first_problem_url = problem_domain.parent().prop('href');
            if (first_problem_url) {
                window.location = first_problem_url + '#/routing/';
                return false;
            }
        }
        return true
    },

    checkHashAvailability: function (hash) {
        if (hash) {

        }

        return true
    },

    // ROUTING ERRORS BLOCK

    updateRoutingErrors: function (routing_errors) {
        if (routing_errors) {
            if (routing_errors.incorrect) {
                //Show red notification icon
                $('.s-domain-list').find('li.active').find('i.indicator.red').removeClass('hide').addClass('visible');
            } else {
                //Hide red notification icon
                $('.s-domain-list').find('li.active').find('i.indicator.red').removeClass('visible').addClass('hide');
            }

            if (routing_errors.not_install) {
                $('#s-link-routing').find('i.indicator.red').show();
            } else {
                $('#s-link-routing').find('i.indicator.red').hide();
            }

            //Update text
            $('#not-install-error').text(routing_errors.not_install);
            $('#incorrect-install-error').text(routing_errors.incorrect);

            this.updateIncorrectRouting(routing_errors.incorrect_ids)
        }
    },

    updateIncorrectRouting: function(incorrect_ids) {
        var $rules = $('#s-rules');

        $rules.find('.s-incorrect-route').hide();
        if (incorrect_ids && typeof $rules === 'object') {
            var active_class = "is-exclamation";

            $(".s-routing ." + active_class).removeClass(active_class);

            $.each(incorrect_ids, function(index, value) {
                var $node = $('#route-'+index).addClass(active_class);
            });
        }
    }
};
})(jQuery);

$(function () {
    $(".s-add-new-site").live('click', function () {
        $.wa.site.newsiteAction();
        return false;
    });

    $("#wa-app > div.s-sidebar a, #wa-header a").live('click', function () {
        if ($("#s-save-panel").is(":visible") && $('#s-save-panel input:button').hasClass('yellow')) {
            return confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"));
        }
    });

});

