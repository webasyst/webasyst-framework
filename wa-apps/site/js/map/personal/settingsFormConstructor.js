var SitePersonalSettingsFormConstructor = ( function($) {

    SitePersonalSettingsFormConstructor = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$available_fields = that.$wrapper.find('.js-available-fields');
        that.$preview = that.$wrapper.find('.js-form-constructor-preview');

        // VARS
        that.domain_id = options.domain_id || '';

        // DYNAMIC VARS

        // INIT
        that.init();

    };

    SitePersonalSettingsFormConstructor.prototype.init = function () {
        var that = this;
        that.initEditableItems();
        that.initSelector();
        that.initSortable();
        that.initServiceAgreementControl();
    };

    SitePersonalSettingsFormConstructor.prototype.initServiceAgreementControl = function () {
        var $preview_wrapper = $('#form-constructor-service-agreement'),
            $preview_text = $preview_wrapper.find('.preview-text'),
            $radio_wrapper = $('#service-agreement-wrapper'),
            $textarea = $radio_wrapper.find('textarea'),
            previous_default_text = null;

        // Update text in preview when textarea changes
        $textarea.on('keyup keypress change blur', function() {
            $preview_text.html($textarea.val());
        });

        // Update textarea and preview visibility when radio is selected
        $radio_wrapper.on('change', ':radio', function() {
            if (!$textarea.val() || previous_default_text == $textarea.val()) {
                setDefaultText();
            }

            $textarea.change();
            switch(this.value) {
                case 'notice':
                    $preview_wrapper.show();
                    $textarea.closest('.text-editor').show();
                    $preview_wrapper.find(':checkbox').hide();
                    break;
                case 'checkbox':
                    $preview_wrapper.show();
                    $textarea.closest('.text-editor').show();
                    $preview_wrapper.find(':checkbox').show();
                    break;
                default:
                    $preview_wrapper.hide();
                    $textarea.closest('.text-editor').hide();
                    break;
            }
        }).find(':radio:checked').change();

        // Replace textarea value when user clicks on 'Restore original text' link
        $radio_wrapper.on('mousedown', '.generalte-example-link', function(e) {
            setDefaultText();
            $textarea.focus();
            return false;
        });

        function setDefaultText() {
            previous_default_text = $('#service-agreement-wrapper :radio:checked').closest('label').data('default-text') || '';
            $textarea.val(previous_default_text).change();
        }
    };

    SitePersonalSettingsFormConstructor.prototype.getAvailableField = function (id) {
        var that = this,
            $available_fields = that.$available_fields;
        return $available_fields.find('.js-available-field[data-id="' + id + '"]');
    };

    SitePersonalSettingsFormConstructor.prototype.getAvailableFieldCheckbox = function (id) {
        var that = this,
            $field = that.getAvailableField(id);
        return $field.find(':checkbox');
    };

    SitePersonalSettingsFormConstructor.prototype.toggleAvailableField = function (id, to_show) {
        var that = this,
            $field = that.getAvailableField(id),
            $checkbox = that.getAvailableFieldCheckbox(id);

        if (typeof to_show === 'undefined') {
            to_show = $checkbox.is(':checked');
        } else {
            to_show = !!to_show;
        }

        if (to_show) {
            $checkbox.attr('disabled', false);
            $field.show();
            that.togglePreviewField(id, true);
        } else {
            $checkbox.attr('disabled', true).attr('checked', false);
            $field.hide();
            that.togglePreviewField(id, false);
        }
    };

    SitePersonalSettingsFormConstructor.prototype.getPreviewField = function(id) {
        var that = this,
            $preview = that.$preview;
        return $preview.find('[data-fld-wrapper="' + id + '"]');
    };

    SitePersonalSettingsFormConstructor.prototype.togglePreviewField = function (id, to_show) {
        var that = this,
            $field = that.getPreviewField(id),
            $inputs = $field.find(':input'),
            clz = 'show-when-enabled';

        if (typeof to_show === 'undefined') {
            to_show = !$field.hasClass(clz);
        } else {
            to_show = !!to_show;
        }

        if (to_show) {
            $field.addClass(clz);
            $inputs.attr('disabled', false);
        } else {
            $field.removeClass(clz);
            $inputs.attr('disabled', true);
        }
    };

    SitePersonalSettingsFormConstructor.prototype.updatePreviewField = function (id, params) {
        var that = this,
            $field = that.getPreviewField(id),
            $checkbox = $field.find(':checkbox');
        if (params.required) {
            $checkbox.attr('checked', true);
        }
        $checkbox.attr('disabled', !!params.disabled)
    };

    SitePersonalSettingsFormConstructor.prototype.isAvailableFieldSelected = function(id) {
        return this.getAvailableFieldCheckbox(id).is(':checked');
    };

    SitePersonalSettingsFormConstructor.prototype.forceSelectAvailableField = function (id) {
        var that = this,
            $checkbox = that.getAvailableFieldCheckbox(id);
        if (!$checkbox.is(':checked')) {
            $checkbox.trigger('click');
        }
        $checkbox.attr('disabled', true);
        that.updatePreviewField(id, {
            required: true,
            disabled: true
        });
    };

    SitePersonalSettingsFormConstructor.prototype.unDisableAvailableField = function (id) {
        var that = this,
            $checkbox = that.getAvailableFieldCheckbox(id),
            is_checked = $checkbox.is(':checked');
        that.togglePreviewField(id, is_checked);
        $checkbox.attr('disabled', false);
        that.updatePreviewField(id, {
            disabled: false
        });
    };

    SitePersonalSettingsFormConstructor.prototype.initSelector = function () {
        var that = this,
            $available_fields = that.$available_fields;
        $available_fields.on('change', ':checkbox', function () {
            var $checkbox = $(this),
                field_id = $checkbox.data('fld-id'),
                is_checked = $checkbox.is(':checked');
            that.togglePreviewField(field_id, is_checked);
        });
    };

    SitePersonalSettingsFormConstructor.prototype.initSortable = function () {
        var that = this,
            $wrapper = that.$wrapper,
            context = $wrapper.find('[data-form-constructor="enabled-fields"]');
        context.sortable({
            distance: 5,
            helper: 'clone',
            items: '.field.sortable',
            opacity: 0.75,
            handle: '.sort',
            tolerance: 'pointer',
            containment: context
        });
    };

    SitePersonalSettingsFormConstructor.prototype.initEditableItems = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $editable = $wrapper.find('[data-editable-element="true"]');
        $editable.each(function () {
            that.initEditableItem($(this));
        });
    };

    SitePersonalSettingsFormConstructor.prototype.initEditableItem = function ($el) {
        var $input = $el.next();
        if (!$input.is(':input')) {
            return;
        }

        $el.closest('.editable-wrapper').addClass('editor-off');

        var switchEls = function(){
            $el.addClass('hidden');
            $input.removeClass('hidden').focus();
            if ($input.is('.show-when-editable')) {
                $input.siblings('.show-when-editable.hidden').removeClass('hidden');
            }
            $el.parents('.caption.left').width('48%')
                .siblings('.placeholder').css('margin-left', '50%');
            $el.closest('.editable-wrapper').removeClass('editor-off').addClass('editor-on');
        };

        $el.on('click', function(){
            switchEls();
            return false;
        });

        $input.on('blur', function(){
            $input.addClass('hidden');
            $el.removeClass('hidden');
            $el.closest('.editable-wrapper').removeClass('editor-on').addClass('editor-off');
            if ($input.is('.show-when-editable')) {
                $input.siblings('.show-when-editable').addClass('hidden');
            }
            if ($el.hasClass('editable_button')) {
                $el.val($input.val());
            } else if ($el.hasClass('contains_html')) {
                $el.html($input.val());
            } else {
                $el.text($input.val());
            }
        });

        $input.on('keydown', function(e){
            var code = e.keyCode || e.which;

            switch (code) {
                case 13: //on enter, esc
                case 27:
                    $(this).trigger('blur');
                    return;
                default:
                    break;
            }
        });
    };

    return SitePersonalSettingsFormConstructor;

})(jQuery);
