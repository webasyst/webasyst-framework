var WASettingsEmail = ( function($) {

    WASettingsEmail = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options['$wrapper'];
        that.$form = that.$wrapper.find('form');
        that.$items_wrapper = that.$form.find('.js-settings-items');
        that.$item_add = that.$wrapper.find('.js-add-item');
        that.$item_template = that.$wrapper.find('.js-template');
        that.$footer_actions = that.$form.find('.js-footer-actions');
        that.$button = that.$footer_actions.find('.js-submit-button');
        that.$cancel = that.$footer_actions.find('.js-cancel');
        that.$loading = that.$form.find('.s-loading');
        that.is_locked = false;
        that.item_class = ".js-item";
        that.item_remove_class = ".js-remove";
        that.key_class = ".js-key";
        that.transport_class = ".js-transport";
        that.dkim_checkbox_class = ".js-dkim-checkbox";

        // VARS

        // DYNAMIC VARS

        // INIT
        that.initClass();
    };

    WASettingsEmail.prototype.initClass = function() {
        var that = this;

        //
        $('#s-sidebar-wrapper').find('ul li').removeClass('selected');
        $('#s-sidebar-wrapper').find('[data-id="email"]').addClass('selected');
        //
        that.initChangeTransport();
        //
        that.initDkim();
        //
        that.initAddRemoveItem();
        //
        that.initSubmit();
    };

    WASettingsEmail.prototype.initChangeTransport = function() {
        var that = this;

        that.$wrapper.on('change', that.transport_class, function () {
            var $item = $(this).parents(that.item_class),
                transport = $item.find(that.transport_class).val();

            $item.find('.js-params').hide(); // Hide all params
            $item.find('.js-transport-description').css('display', 'none'); // Hide all descriptions
            $item.find('.js-'+ transport +'-description').css('display', 'inline-block'); // Show needed description
            $item.find('.js-'+ transport +'-params').show(); // Show needed params
        });
    };

    WASettingsEmail.prototype.initDkim = function () {
        var that = this;


        that.$wrapper.on('change', that.dkim_checkbox_class, function () {
            var $dkim_checkbox = $(this),
                $item = $dkim_checkbox.parents(that.item_class),
                is_on = $dkim_checkbox.is(':checked');

            if (is_on) {
                dkim($item, 'generateDkim');
            } else {
                dkim($item, 'removeDkim');
            }
        });

        // Remove dkim settings if email or domain is changed
        that.$wrapper.on('input', that.key_class, function () {
            var $item = $(this).parents(that.item_class);
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
                    href = '?module=settingsGenerateDkim',
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
    };

    WASettingsEmail.prototype.initAddRemoveItem = function () {
        var that = this;

        // Add item
        that.$item_add.on('click', function (e) {
            e.preventDefault();
            var $item = that.$item_template.clone().removeClass('js-template').addClass('js-item');
            $item.find('.js-key').val('');
            that.$items_wrapper.append($item);
            that.$form.trigger('input');
        });

        // Remove item
        that.$wrapper.on('click', that.item_remove_class, function (e) {
            e.preventDefault();
            var $item = $(this).parents(that.item_class);
            $item.remove();
            that.$form.trigger('input');
        });
    };

    WASettingsEmail.prototype.initSubmit = function () {
        var that = this;

        that.$form.on('submit', function (e) {
            e.preventDefault();

            // Set attribute name for all item fields
            // by data-name attribute
            var $all_items = that.$items_wrapper.find('.js-item');
            $.each($all_items, function (i, item) {
                setNames($(item));
            });

            // Send post
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
                    setTimeout(function(){
                        that.$loading.hide();
                    }, 2000);
                } else if (res.errors) {
                    $.each(res.errors, function (i, error) {
                        if (error.field) {
                            fieldError(error);
                        }
                    });
                    that.$loading.hide();
                } else {
                    that.$loading.hide();
                }
                that.is_locked = false;
                that.$button.prop('disabled', false);
            });

            function setNames($item) {
                var item_key = $item.find(that.key_class).val(),
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

        function fieldError(error) {
            var $field = that.$form.find('input[name='+error.field+']'),
                $hint = $field.parent('.value').find('.js-error-place');

            $field.addClass('shake animated').focus();
            $hint.text(error.message);
            setTimeout(function(){
                $field.removeClass('shake animated').focus();
                $hint.text('');
            }, 1000);
        }

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

    return WASettingsEmail;

})(jQuery);