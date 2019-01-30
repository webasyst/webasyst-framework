var WASettingsSubfieldConditionalValues = (function ($) {

    var WASettingsSubfieldConditionalValues = function (options) {
        var that = this;

        // DOM
        that.$wrapper = $(".subfields-list");
        that.$hidden = options.hidden;
        that.$dialog_link = options.dialog_link;

        // VARS
        that.dialog_url = options.dialog_url;
        that.locales = options.locales;

        // DYNAMIC VARS
        // INIT
        that.initClass();
    };

    WASettingsSubfieldConditionalValues.prototype.initClass = function () {
        var that = this;
        //
        that.initValuesLink();
    };

    WASettingsSubfieldConditionalValues.prototype.initValuesLink = function() {
        var that = this;

        that.$wrapper.find(that.$dialog_link).on("click", function() {
            $.get(that.dialog_url, function(html) {
                // Init the values dialog
                new WASettingsDialog({
                    html: html,
                    onOpen: function ($dialog_wrapper, dialog){
                        that.editValues($dialog_wrapper, dialog);
                        //
                        that.initSubmit($dialog_wrapper, dialog);
                    }
                });
            });
        });

    };

    WASettingsSubfieldConditionalValues.prototype.editValues = function($dialog_wrapper, dialog) {
        var that = this,
            $form = $dialog_wrapper.find('form');

        // Link to add new rule
        $dialog_wrapper.on('click', '.s-add-rule', function() {
            var item_tmpl = $dialog_wrapper.find('.s-new-rule');

            if (item_tmpl.length) {
                var new_item = item_tmpl.clone();
                new_item.removeClass('s-new-rule').removeAttr('style').insertBefore(item_tmpl);
                new_item.find('input[name^="parent"]').attr('disabled', false);
                new_item.find('.s-add-value').click();
                that.sortable($dialog_wrapper);

                var index = parseInt(item_tmpl.find('input[name="parent[]"]').val(), 10) + 1 || 1;
                item_tmpl.find('input[name="parent[]"]').val(index);
                item_tmpl.find('input[name^="parent_value"]').attr('name', 'parent_value['+index+']');
                item_tmpl.find('input[name^="value"]').attr('name', 'value['+index+'][0]');
                dialog.resize();
            }
            return false;
        });

        // Link to add new value
        $dialog_wrapper.on('click', '.s-add-value', function() {
            var self = $(this),
                parent = self.parents('table:first'),
                item_tmpl = parent.find('.s-new-value');

            if (item_tmpl.length) {
                var new_item = item_tmpl.clone();
                new_item.addClass('sortable').removeClass('s-new-value').removeAttr('style').insertBefore(item_tmpl);
                new_item.find('input').attr('disabled', false);

                // increment index of new_item (that indexes <= 0)
                var name = item_tmpl.find('input:first').attr('name'),
                    pos = name.lastIndexOf('['),
                    index = (parseInt(name.substr(pos + 1), 10) || 0) - 1;

                name = name.substr(0, pos) + '['+index+']';
                item_tmpl.find('input:first').attr('name', name);
            }
            dialog.resize();
            return false;
        });

        // Link to delete value
        $dialog_wrapper.on('click', '.s-delete-value', function() {
            var self = $(this),
                id = self.attr('data-id'),
                tr = self.parents('tr:first'),
                table = tr.parents('table:first');

            if (id) {
                $form.append('<input type="hidden" name="delete[]" value="'+id+'">');
            }
            tr.remove();
            if (!table.find('tr.sortable:first').length) {
                table.parents('div.field:first').remove();
            }
            dialog.resize();
            return false;
        });
        that.sortable($dialog_wrapper);

        if (that.$hidden.val()) {
            $dialog_wrapper.find('select.otherwise-options').val('hide');
        } else {
            $dialog_wrapper.find('select.otherwise-options').val('input');
        }
    };

    WASettingsSubfieldConditionalValues.prototype.initSubmit = function ($dialog_wrapper, dialog) {
        var that = this,
            $form = $dialog_wrapper.find('form'),
            $button = $dialog_wrapper.find('.js-save-values'),
            $loading = $('<i class="icon16 loading" style="vertical-align: middle;margin-left: 10px;"></i>'),
            $done = $('<i class="icon16 yes" style="vertical-align: middle;margin-left: 10px;"></i>');

        $form.submit(function(e) {
            e.preventDefault();

            $button.prop('disabled', true);
            $('.loading').remove(); // remove old .loading (paranoia)
            $loading.appendTo($button.parent());

            // Validation
            var validation_passed = true;
            $dialog_wrapper.find('.errormsg').remove();
            $dialog_wrapper.find('.error').removeClass('error');
            $dialog_wrapper.find('[name^="parent_value["]:not(:disabled)').each(function() {
                if (!this.value) {
                    validation_passed = false;
                    $(this).addClass('error').after($('<em class="errormsg"></em>').text(that.locales["field_is_required"]));
                }
            });
            $dialog_wrapper.find('[name^="value["]:not(:disabled)').each(function() {
                if (!this.value) {
                    validation_passed = false;
                    $(this).addClass('error').after($('<em class="errormsg"></em>').text(that.locales["field_is_required"]));
                }
            });
            if (!validation_passed) {
                $('#s-field-values').closest('.dialog').find('.dialog-buttons :submit').attr('disabled', false);
                return false;
            }

            // Copy to main form the data that is to be saved to ContactField config
            if ($dialog_wrapper.find('select.otherwise-options').val() == 'input') {
                that.$hidden.val('');
            } else {
                that.$hidden.val('1').closest('td').find('input:checkbox[name$="[required]"]').attr('checked', false);
            }

            $.post($form.attr('action'), $form.serialize())
                .done(function (r) {
                    $('.loading').remove();
                    $done.appendTo($button.parent());
                    if (r.status == 'ok') {
                        that.$dialog_link.parents('.field-advanced-settings').find('.show-when-modified').show();
                        that.$dialog_link.parents('.field-advanced-settings').find('.hide-when-modified').hide();
                        setTimeout(function() {
                            dialog.close();
                        }, 1000);
                        return;
                    }
                });
        })
    };

    // Helper to init/reinit sortable list of values
    WASettingsSubfieldConditionalValues.prototype.sortable = function(d, refresh) {
        d.find('.value table>tbody').sortable({
            distance: 5,
            helper: 'clone',
            items: 'tr.sortable',
            opacity: 0.75,
            axis: 'y',
            tolerance: 'pointer'
        });
    };

    return WASettingsSubfieldConditionalValues;

})(jQuery);

