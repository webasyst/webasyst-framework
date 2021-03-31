class WASettingsFieldEdit {
    constructor(options) {
        let that = this;

        // DOM
        that.$wrapper = options.$wrapper;
        that.$form = that.$wrapper.find('form');
        that.$button = that.$form.find('.js-save');

        // VARS
        that.dialog = that.$wrapper.data('dialog');
        that.field = options.field || null;
        that.remove_subitem_confirm = options.remove_subitem_confirm;
        that.locales = options.locales;

        // DYNAMIC VARS
        // INIT
        that.initClass();
    }

    initClass() {
        let that = this;
        //
        that.bindEvents();
        //
        that.editSubFields();

        if (!that.field) {
            that.initIDAutoFiller();
        }
    }

    bindEvents() {
        let that = this,
            $form = that.$form;

        $form.on('change', '.s-field-type-select', function () {
            let $el = $(this),
                val = $el.val(),
                $txt_wrapper = $form.find('.s-values-textarea-wrapper').hide();
            if (val === 'Select' || val === 'Radio') {
                $txt_wrapper.show();
            }
        });

        $form.on('change', '.js-name-another-language-wrapper', function () {
            let $el = $(this).find('option:selected'),
                id = $el.data('id'),
                region = $el.data('name-region'),
                $main_wrapper = $form.find('.s-local-input-wrapper'),
                $clone = $main_wrapper.clone();

            $clone
                .find('input')
                .attr('name', 'name[' + id + ']')
                .val('')
                .attr('disabled', false)
                .attr('data-main-locale', '')
                .attr('data-error-id', id)
                .removeClass('state-error')
                .end()
                .find('.s-name-region')
                .text(region)
                .end()
                .find('.state-error-hint')
                .text('')
                .end()
                .insertAfter($main_wrapper);

            $el.closest('li').hide();

            if ($form.find('.js-name-another-language:not(:hidden)').length <= 0) {
                $form.find('.js-name-another-language-wrapper').hide();
            }
        });

        that.initSubmit();

        if (that.field) {
            if (that.field.editable) {
                that.initDeleteLink();
            } else {
                that.initDisableLink();
            }
        }
    }

    initSubmit() {
        let that = this,
            $form = that.$form,
            xhr = null;

        $form.on('input change', function () {
            that.toggleButton(true);
        });

        $form.submit(function (e) {
            e.preventDefault();
            if (xhr) {
                xhr.abort();
                xhr = null;
            }
            xhr = that.save();
        });
    }

    initDeleteLink() {
        let that = this,
            $wrapper = that.$wrapper,
            xhr = null,
            href = '?module=settingsFieldDeleteConfirm';


        $wrapper.on('click', '.s-field-delete', function (e) {
            e.preventDefault();
            xhr = $.post(href, { id: that.field.id }, function(html) {
                $.waDialog({
                    html: html,
                    options: {
                        edit_dialog: that.dialog
                    },
                    onOpen: function() {
                        that.dialog.$wrapper.hide();
                    }
                });
            });
        });
    }

    initDisableLink() {
        let that = this,
            $form = that.$form,
            xhr = null;

        $form.on('click', '.s-field-enable,.s-field-disable', function (e) {
            e.preventDefault();

            if (xhr) {
                xhr.abort();
                xhr = null;
            }

            let $el = $(this),
                data = {
                    enable: $el.hasClass('s-field-enable')
                };

            xhr = $.post($form.attr('action'), data, function (r) {
                if (r.status == 'ok') {
                    that.dialog.close();
                    $.wa.content.reload();
                }
            });
        });
    }

    save() {
        let that = this,
            $form = that.$form,
            $button = that.$button,
            $loading = $('<i class="fas fa-spinner fa-spin loading" style="vertical-align: middle;margin-left: 10px;"></i>'),
            href = $form.attr('action'),
            data = $form.serialize();

        $('.loading').remove(); // remove old .loading

        $button.prop('disabled', true);

        // Validation
        let validation_passed = true;
        $form.find('.state-error-hint').text('');
        $form.find('.state-error').removeClass('state-error');
        $('[name$="[localized_names]"]').each(function() {
            let self = $(this);
            if (!self.val() && self.parents('.template').length <= 0) {
                if (self.closest('tr').find('[name$="[_disabled]"]:checked').length) {
                    validation_passed = false;
                    self.addClass('state-error').parent().append($('<em class="state-error"></em>').text(that.locales["field_is_required"]));
                }
            }
        });

        if (!validation_passed) {
            $button.attr('disabled', false);
            return false;
        }

        $loading.appendTo($button.parent());

        $.post(href, data, function (r) {
            if (r.status == 'ok') {
                $('.loading').remove();
                let $done = $('<i class="fas fa-check-circle" style="vertical-align: middle;margin-left: 10px;"></i>');
                $done.appendTo($button.parent());
                setTimeout(function() {
                    $.wa.content.reload();
                    that.dialog.close();
                }, 1000);
            }

            if (r.status !== 'ok' && r.errors) {
                $button.removeProp('disabled');
                $('.loading').remove();
                for (let i = 0, l = r.errors.length; i < l; i += 1) {
                    let e = r.errors[i];
                    if (typeof e === 'string') {
                        $form.find('.state-error').append(e);
                    } else if (typeof e === 'object') {
                        for (let k in e) {
                            if (e.hasOwnProperty(k)) {
                                let input = $form.find('[data-error-id="' + k + '"]');
                                input.addClass('state-error');
                                input.nextAll('.state-error-hint:first').text(e[k]);

                                $form.one('input, keydown', '.state-error', function () {
                                    $(this).removeClass('state-error')
                                        .nextAll('.state-error-hint:first').empty();
                                });
                            }
                        }
                    }
                }
                $form.find('[type=submit]').attr('disabled', false);
            }
        });
    }

    initIDAutoFiller() {
        let that = this,
            transliterateTimer,
            $form = that.$form,
            $main_loc_input = $form.find('input[name^="name["][data-main-locale]'),
            $id_val_input = $form.find('input[name="id_val"]'),
            xhr = null,
            ns = '.s-id-auto-filler';

        $id_val_input.on(
            'keydown.check_edited',
            function() {
                let $el = $(this);
                $el.data('val', $el.val());
            })
            .on(
                'keyup.check_edited',
                function() {
                    let $el = $(this);
                    if ($el.val() && $el.val() != $el.data('value')) {
                        $el.off('.check_edited');
                        $el.data('edited', 1);
                    }
                });

        if ($id_val_input.prop('disabled') || $id_val_input.data('edited')) {
            return;
        }

        $form.on('keydown' + ns, 'input[name^="name["]',
            function() {
                let $input = $(this),
                    $submit = $form.find('[type="submit"]'),
                    $loading = $id_val_input.next('.loading');

                if (!$input.data('main-locale') && $main_loc_input.val()) {
                    return;
                }

                if ($id_val_input.prop('disabled') || $id_val_input.data('edited')) {
                    $form.off(ns);
                    return;
                }

                $submit.prop('disabled', true);

                $loading = $loading.length ? $loading : $('<i class="fas fa-spinner fa-spin loading" style="vertical-align: middle;margin-left: 10px;"></i>');
                $loading.insertAfter($id_val_input);

                transliterateTimer && clearTimeout(transliterateTimer);
                transliterateTimer = setTimeout(function () {

                    let clear = function () {
                        if (xhr) {
                            xhr.abort();
                            xhr = null;
                        }
                        transliterateTimer && clearTimeout(transliterateTimer);
                        $submit.prop('disabled', false);
                        $loading.remove();
                    };

                    if ($id_val_input.data('edited')) {
                        clear();
                        return;
                    }

                    xhr = $.post('?module=settingsFieldTransliterate',
                        $form.find('input[name^="name["]').serialize(),
                        function (r) {
                            clear();
                            if (r.status === 'ok' && !$id_val_input.data('edited')) {
                                $id_val_input.val(r.data);
                            }
                        },
                        'json');
                }, 300);

            }
        );
    }

    editSubFields() {
        let that = this,
            $wrapper = that.$wrapper,
            $sub_table = $wrapper.find('.subfields-list > .ui-sortable'),
            max_field = 1;

        $sub_table.sortable({
            items : ".field-row",
            handle : ".js-subfield-sort",
            axis: 'y',
            update: function() {
                that.toggleButton(true);
            }
        });

        // Link to add new subfield
        $sub_table.on('click', 'a.js-add-subfield', function(e) {
            e.preventDefault();
            // Clone row template
            let tmpl = $sub_table.find('.field-row.template'),
                tr = tmpl.clone().insertBefore(tmpl).removeClass('template').removeClass('hidden');

            that.dialog.resize();

            // Replace field id placeholder with generated field id
            let fid = '__'+max_field;
            max_field++;
            tr.find('[name]').each(function() {
                let self = $(this);
                self.attr('name', self.attr('name').replace(/%FID%/g, fid));
            });
            tr.data('fieldId', fid);
            tr.find('select.type-selector').change();
            that.toggleButton(true);
        });

        // Edit subfield
        $wrapper.on('click', '.edit', function(e) {
            e.preventDefault();
            $(this).parents('tr').addClass('editor-on').removeClass('editor-off');
            that.toggleButton(true);
        });

        // Delete subfield
        $wrapper.on('click', '.js-delete-subfield', function() {
            let $tr = $(this).closest('tr');

            if ($tr.hasClass('just-added')) {
                $tr.remove();
                return false;
            }

            that.dialog.hide();

            new WASettingsDialog({
                html: $(that.remove_subitem_confirm),
                onConfirm: function () {
                    $tr.addClass('editor-off').removeClass('editor-on');
                    let name = $tr.find('input:hidden[name$="[_disabled]"]').attr('name').replace("[_disabled]", "[_deleted]");
                    $('.js-field-form-edit').append($('<input type="hidden" name="" value="1">').attr('name', name));
                    $tr.children().children(':not(label)').remove();
                    $tr.find('label').addClass('gray').addClass('strike');
                    that.toggleButton(true);
                    that.dialog.show();
                },
                onCancel: function () {
                    that.dialog.show();
                }
            });
        });

        // Just resize on click to 'add item'
        $sub_table.on('click', 'a.add-item', function() {
            that.dialog.resize();
        });

        // Load appropriate settings block when user changes field type
        $wrapper.on('change', 'select.type-selector', function() {
            let $select = $(this),
                $tr = $select.closest('tr'),
                $table = $tr.closest('table'),
                $adv_settings_block = $tr.find('.field-advanced-settings').html('<i class="fas fa-spinner fa-spin loading" style="vertical-align: middle;margin-left: 10px;"></i>');

            $.post('?module=settingsFieldEditor', {
                ftype: $select.val(),
                fid: $tr.data('fieldId'),
                parent: $table.data('fieldParent') || '',
                prefix: $table.data('fieldPrefix')
            }, function(res) {
                $adv_settings_block.html(res);
                that.toggleButton(true);
            });
        });

        $wrapper.on('change', ":checkbox, .name-input", function() {
            that.toggleButton(true);
        });
    }

    toggleButton(is_changed) {
        let that = this,
            $button = that.$button;

        if (is_changed) {
            $button.addClass("yellow").removeAttr("disabled");
        } else {
            $button.removeClass("yellow");
        }
    }
}