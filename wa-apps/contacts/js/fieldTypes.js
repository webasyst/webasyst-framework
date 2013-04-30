/**
  * Base classs for all editor factory types, all editor factories and all editors.
  * Implements JS counterpart of contactsFieldEditor with no validation.
  *
  * An editor factory can be created out of factory type (see $.wa.contactEditor.initFactories())
  *
  * Editor factories create editors using factory.createEditor() method. Under the hood
  * a factory simply copies self, removes .createEditor() method from the copy and calls
  * its .initialize() method.
  */
$.wa.contactEditor.baseFieldType = {

    //
    // Public editor factory functions. Not available in editor instances.
    //

    /** For multifields, return a new (empty) editor for this field. */
    createEditor: function() {
        var result = $.extend({}, this);
        delete result.createEditor; // do not allow to use instance as a factory
        delete result.initializeFactory;
        result.parentEditorData = {};
        result.initialize();
        return result;
    },

    //
    // Editor properties set in subclasses.
    //

    /** Last value set by setValue() (or constructor).
      * Default implementation expects fieldValue to be string.
      * Subclasses may store anything here. */
    fieldValue: '',

    //
    // Editor functions that should be redefined in subclasses
    //

    /** Factory constructor. */
    initializeFactory: function(fieldData) {
        this.fieldData = fieldData;
    },

    /** Editor constructor. Should set all appropriate fields as if
      * this.setValue() got called with an empty data (with no record in db).
      * this.fieldData is available for standalone fields,
      * or empty {} for subfields of a multifield. */
    initialize: function() {
        this.setValue('');
    },

    reinit: function() {
        this.currentMode = 'null';
        this.initialize();
    },

    /** Load field contents from given data and update DOM. */
    setValue: function(data) {
        this.fieldValue = data;
        if (this.currentMode == 'null' || this.domElement === null) {
            return;
        }

        if (this.currentMode == 'edit') {
            this.domElement.find('.val').val(this.fieldValue);
        } else {
            this.domElement.find('.val').html(this.fieldValue);
        }
    },

    /** Get data from this field after (possible) user modifications.
      * @return mixed Data object as accepted by this.setValue() and server-side handler. */
    getValue: function() {
        var result = this.fieldValue;
        if (this.currentMode == 'edit' && this.domElement !== null) {
            var input = this.domElement.find('.val');
            if (input.length > 0) {
                result = '';
                if (!input.hasClass('empty')) { // default values use css class .empty to grey out value
                    if (input.attr('type') != 'checkbox' || input.attr('checked')) {
                        result = input.val();
                    }
                }
            }
        }
        return result;
    },

    /** true if this field was modified by user and now needs to save data */
    isModified: function() {
        return this.fieldValue != this.getValue();
    },

    /** Validate field value (and possibly change it if needed)
      * @param boolean skipRequiredCheck (default false) set to true to skip check required fields to be not empty
      * @return mixed Validation data accepted by showValidationErrors(), or null if no errors. Default implementation accepts simple string. */
    validate: function(skipRequiredCheck) {
        var val = this.getValue();
        if (!skipRequiredCheck && this.fieldData.required && !val) {
            return $_('This field is required.');
        }
        return null;
    },

    /** Return a new jQuery object that represents this field in given mode.
      * Use of $.wa.contactEditor.wrapper is recommended if apropriate.
      * In-place editors are initialized here.
      * Must contain exactly one element, even when field is currently not visible.
      * Default implementation uses this.newInlineFieldElement(), wraps it and initializes in-place editor.
      */
    newFieldElement: function(mode) {
        if(this.fieldData.read_only) {
            mode = 'view';
        }
        var inlineElement = this.newInlineFieldElement(mode);

        // Do not show anything if there's no inline element
        if(inlineElement === null && (!this.fieldData.show_empty || mode == 'edit')) {
            return $('<div style="display: none"></div>');
        }

        var nameAddition = '';
        if (mode == 'edit') {
            nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
        }

        return $.wa.contactEditor.wrapper(inlineElement, this.fieldData.name+nameAddition);
    },

    /** When used as a part of multi or composite field, corresponding wrapper
      * uses this function (if defined and not null) instead of newFieldElement().
      * Unwrapped value (but still $(...) wrapped) is expected. If null returned, field is not shown.
      */
    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }
        var result = null;
        if (mode == 'edit') {
            result = $('<span><input class="val" type="text"></span>');
            result.find('.val').val(this.fieldValue);
        } else {
            result = $('<span class="val"></span>');
            result.text(this.fieldValue);
        }
        return result;
    },

    /** Remove old validation errors if any and show given error info for this field.
      * Optional to redefine in subclasses.
      * Must be redefined for editors that do not use the default $.wa.contactEditor.wrapper().
      * Default implementation accepts simple string.
      * @param errors mixed Validation error data as generated by this.validate() (or server), or null to hide all errors. */
    showValidationErrors: function(errors) {
        if (this.domElement === null) {
            return;
        }

        var input = this.domElement.find('.val');
        input.parents('.value').children('em.errormsg').remove();

        if (errors !== null) {
            input.parents('.value').append($('<em class="errormsg">'+errors+'</em>'));
            input.addClass('error');
        } else {
            input.removeClass('error');
        }
    },

    //
    // Public properties that can be used in editors
    //

    /** Field data as returned from $typeClass->getValue() for this class in PHP.
      * When this field is a subfield for a multifield, this var contains
      * {id: null, multi: false, name: 'Subfield Name'} */
    fieldData: null,

    /** jQuery object that contains wrapping DOM element that currently
      * represents this field in #contact-info-block. When not null,
      * always contains exactly one element, even if field is currently not visible. */
    domElement: null,

    /** Is domElement in 'view', 'edit' or 'null' mode. */
    currentMode: 'null',

    /** Editor that uses this one as a subfield */
    parentEditor: null,

    /** Any data that parent would want to put here. */
    parentEditorData: null,

    //
    // Public editor functions
    //

    /** Set given editor mode and return DOM element that represents this field.
      * If this editor is already initialized (i.e. this.currentMode is not 'null'),
      * this function replaces old this.domElement in DOM with new value.
      * @param mode string 'edit' or 'view'
      * @param replaceEditor boolean (optional, default true) pass false to avoid creating dom element (e.g. to use as a subfield)
      */
    setMode: function(mode, replaceEditor) {
        if (typeof replaceEditor == 'undefined') {
            replaceEditor = true;
        }
        if (mode != 'view' && mode != 'edit') {
            throw new Error('Unknown mode: '+mode);
        }

        if (this.currentMode != mode) {
            this.currentMode = mode;
            if (replaceEditor) {
                var oldDom = this.domElement;
                this.domElement = this.newFieldElement(mode);
                if (oldDom !== null) {
                    oldDom.replaceWith(this.domElement);
                }
            }
        }

        return this.domElement;
    }
}; // end of baseFieldType

//
// Factory Types
//

$.wa.contactEditor.factoryTypes.String = $.extend({}, $.wa.contactEditor.baseFieldType, {
    setValue: function(data) {
        this.fieldValue = data;
        if (this.currentMode == 'null' || this.domElement === null) {
            return;
        }

        if (this.currentMode == 'edit') {
            this.domElement.find('.val').val(this.fieldValue);
        } else {
            this.domElement.find('.val').html(this.fieldValue);
        }
    },

    getValue: function() {
        var result = this.fieldValue;
        if (this.currentMode == 'edit' && this.domElement !== null) {
            var input = this.domElement.find('.val');
            result = '';
            if (!input.hasClass('empty')) { // default values use css class .empty to grey out value
                result = input.val();
            }
        }
        return result;
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }

        var result = null;
        var value = this.fieldValue;
        if (mode == 'edit') {
            if (this.fieldData.input_height <= 1) {
                result = $('<span><input class="val" type="text"></span>');
            } else {
                result = $('<span><textarea class="val" rows="'+this.fieldData.input_height+'"></textarea></span>');
            }
            result.find('.val').val(value);
        } else {
            result = $('<span class="val"></span>').text(value);
        }
        return result;
    }
});
$.wa.contactEditor.factoryTypes.Text = $.extend({}, $.wa.contactEditor.factoryTypes.String);
$.wa.contactEditor.factoryTypes.Phone = $.extend({}, $.wa.contactEditor.baseFieldType);
$.wa.contactEditor.factoryTypes.Select = $.extend({}, $.wa.contactEditor.baseFieldType, {
    notSet: function() {
        return '&lt;'+(this.fieldData.defaultOption || $_('not set'))+'&gt;';
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }

        if(mode == 'view') {
            return $('<span class="val"></span>').text(this.fieldData.options[this.fieldValue] || this.fieldValue);
        } else {
            var options = '';
            var selected = false, attrs;
            for(var i = 0; i<this.fieldData.oOrder.length; i++) {
                var id = this.fieldData.oOrder[i];
                if (!selected && id == this.fieldValue && this.fieldValue) {
                    selected = true;
                    attrs = ' selected';
                } else {
                    attrs = '';
                }
                if (id === '') {
                    attrs += ' disabled';
                }
                options += '<option value="'+id+'"'+attrs+'>'+this.fieldData.options[id]+'</option>';
            }
            return $('<div><select class="val"><option value=""'+(selected ? '' : ' selected')+'>'+this.notSet()+'</option>'+options+'</select></div>');
        }
    }
});
$.wa.contactEditor.factoryTypes.Conditional = $.extend({}, $.wa.contactEditor.factoryTypes.Select, {

    unbindEventHandlers: function() {},

    getValue: function() {
        var result = this.fieldValue;
        if (this.currentMode == 'edit' && this.domElement !== null) {
            var input = this.domElement.find('.val:visible');
            if (input.length > 0) {
                if (input.hasClass('empty')) {
                    result = '';
                } else {
                    result = input.val();
                }
            }
        }
        return result;
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }
        this.unbindEventHandlers();

        if(mode == 'view') {
            return $('<div></div>').append($('<span class="val"></span>').text((this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue));
        } else {
            var cond_field = this;

            // find the the field we depend on
            var parent_field_id_parts = (cond_field.fieldData.parent_field || '').split(':');
            var parent_field = $.wa.contactEditor.fieldEditors[parent_field_id_parts.shift()];
            while (parent_field && parent_field_id_parts.length) {
                subfields = parent_field.subfieldEditors;
                if (subfields instanceof Array) {
                    // This is a multi-field. Select the one that we're part of (if any)
                    parent_field = null;
                    for (var i = 0; i < subfields.length; i++) {
                        if (subfields[i] === cond_field.parentEditor) {
                            parent_field = subfields[i];
                            break;
                        }
                    }
                } else {
                    // This is a composite field. Select subfield by the next id part
                    parent_field = subfields[parent_field_id_parts.shift()];
                }
            }

            if (parent_field && parent_field.domElement) {
                var initial_value = (this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue;
                var input = $('<input type="text" class="hidden val">').val(initial_value);
                var select = $('<select class="hidden val"></select>').hide();
                var change_handler;

                var getVal = function() {
                    if (input.is(':visible')) {
                        return input.val();
                    } else if (select.is(':visible')) {
                        return select.val();
                    } else {
                        return initial_value;
                    }
                };

                // Listen to change events from field we depend on.
                // setTimeout() to ensure that field created its new domElement.
                setTimeout(function() {
                    var parent_val_element = parent_field.domElement.find('.val').change(change_handler = function() {
                        var old_val = getVal();
                        var parent_value = parent_val_element.val().toLowerCase();
                        var values = cond_field.fieldData.parent_options[parent_value];
                        if (values) {
                            input.hide();
                            select.show().children().remove();
                            for (i = 0; i < values.length; i++) {
                                select.append($('<option></option>').attr('value', values[i]).text(values[i]).attr('selected', cond_field.fieldValue == values[i]));
                            }
                            select.val(old_val);
                        } else {
                            input.val(old_val || '').show().blur();
                            select.hide();
                        }
                    });
                    change_handler.call(parent_val_element);
                }, 0);

                cond_field.unbindEventHandlers = function() {
                    if (change_handler) {
                        parent_field.domElement.find('.val').unbind('change', change_handler);
                    }
                    cond_field.unbindEventHandlers = function() {};
                };

                return $('<div></div>').append(input).append(select);
            } else {
                return $('<input type="text" class="val">').val(cond_field.fieldValue);
            }
        }
    }
});
$.wa.contactEditor.factoryTypes.Region = $.extend({}, $.wa.contactEditor.factoryTypes.Select, {
    notSet: function() {
        if (this.fieldData.options && this.fieldValue && !this.fieldData.options[this.fieldValue]) {
            return this.fieldValue;
        }
        return '&lt;'+$_('select region')+'&gt;';
    },

    unbindEventHandlers: function() {},

    setCurrentCountry: function() {
        var old_country = this.current_country;
        this.current_country = this.parentEditorData.parent.subfieldEditors.country.getValue();
        if (old_country !== this.current_country) {
            delete this.fieldData.options;
            return true;
        }
        return false;
    },

    getRegionsControllerUrl: function(country) {
        return ($.wa.contactEditor.regionsUrl || '?module=backend&action=regions&country=')+country;
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }

        this.unbindEventHandlers();

        if(mode == 'view') {
            return $('<div></div>').append($('<span class="val"></span>').text((this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue));
        } else {
            var region_field = this;

            // This field depends on currently selected country in address
            if (this.parentEditorData.parent && this.parentEditorData.parent.subfieldEditors.country) {
                this.setCurrentCountry();
                var handler;
                $('#contact-info-block').on('change', 'select', handler = function() {
                    if (region_field.setCurrentCountry()) {
                        region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());
                    }
                });
                region_field.unbindEventHandlers = function() {
                    $('#contact-info-block').off('change', 'select', handler);
                    region_field.unbindEventHandlers = function() {};
                };
            }

            if (this.fieldData.options === undefined && this.current_country && this.fieldData.region_countries[this.current_country]) {
                // Load list of regios via AJAX and then show select
                var country = this.current_country;
                $.get(this.getRegionsControllerUrl(country), function(r) {
                    if (mode !== region_field.currentMode || country !== region_field.current_country) {
                        return;
                    }
                    region_field.fieldData.options = r.data.options || false;
                    region_field.fieldData.oOrder = r.data.oOrder || [];
                    region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());
                }, 'json');
                return $('<div></div>').append($('<i class="icon16 loading"></i>'));
            } else if (this.fieldData.options) {
                // Show as select
                return $('<div></div>').append($.wa.contactEditor.factoryTypes.Select.newInlineFieldElement.call(this, mode));
            } else {
                // show as input
                var result = $('<div></div>').append($.wa.contactEditor.baseFieldType.newInlineFieldElement.call(this, mode));
                $.wa.defaultInputValue(result.find('.val'), this.fieldData.name+(this.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
                return result;
            }
        }
    }
});

$.wa.contactEditor.factoryTypes.Country = $.extend({}, $.wa.contactEditor.factoryTypes.Select);
$.wa.contactEditor.factoryTypes.Checklist = $.extend({}, $.wa.contactEditor.baseFieldType, {
    validate: function(skipRequiredCheck) {
        if (!skipRequiredCheck && this.fieldData.required && this.getValue().length <= 0) {
            return $_('This field is required.');
        }
        return null;
    },
    setValue: function(data) {
        this.fieldValue = data;

        if(this.currentMode == 'edit' && this.domElement) {
            this.domElement.find('input[type="checkbox"]').attr('checked', false);
            for (var id in this.fieldValue) {
                this.domElement.find('input[type="checkbox"][value="'+id+'"]').attr('checked', true);
            }
        } else if (this.currentMode == 'view' && this.domElement) {
            this.domElement.find('.val').html(this.getValueView());
        }
    },
    getValue: function() {
        if(this.currentMode != 'edit' || !this.domElement) {
            return this.fieldValue;
        }

        var result = [];
        this.domElement.find('input[type="checkbox"]:checked').each(function(k,input) {
            result.push($(input).val());
        });
        return result;
    },
    getValueView: function() {
        var options = '';
        // Show categories in alphabetical (this.fieldData.oOrder) order
        for(var i = 0; i<this.fieldData.oOrder.length; i++) {
            var id = this.fieldData.oOrder[i];
            if (this.fieldValue.indexOf(id) < 0) {
                continue;
            }
            options += (options ? ', ' : '')+'<a href="'+(this.fieldData.hrefPrefix || '#')+id+'">'+((this.fieldData.options[id] && $.wa.contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</a>';
        }
        return options || $_('&lt;none&gt;');
    },
    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !(this.fieldValue && this.fieldValue.length)) {
            return null;
        }

        if(mode == 'view') {
            return $('<span class="val"></span>').html(this.getValueView());
        }

        //
        // Edit mode
        //

        // Is there more than one option to select from?
        var optionsAvailable = 0; // 0, 1 or 2
        var id;
        for(id in this.fieldData.options) {
            optionsAvailable++;
            if (optionsAvailable > 1) {
                break;
            }
        }
        // Do not show the field at all if there's no options to select from
        if (!optionsAvailable) {
            return null;
        }

        var options = '';
        for(var i = 0; i<this.fieldData.oOrder.length; i++) {
            id = this.fieldData.oOrder[i];
            options += '<li><label><input type="checkbox" value="'+id+'"';

            // the item is checked if EITHER it is present in fieldValue
            // OR if we're showing a form to add new contact and there's only one
            // category available for non-admin
            if (this.fieldValue.indexOf(id-0) >= 0 || (!$.wa.contactEditor.contact_id && $.wa.contactEditor.limitedCategories && optionsAvailable < 2)) {
                options += ' checked="checked"';
            }

            // Checkboxes for system categories are disabled
            if (this.fieldData.disabled && this.fieldData.disabled[id]) {
                options += ' disabled="disabled"';
            }
            options += ' />'+((this.fieldData.options[id] && $.wa.contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</label></li>';
        }

        return $.wa.controller.initCheckboxList('<div class="c-checkbox-menu-container val"><div><ul class="menu-v compact with-icons c-checkbox-menu">'+options+'</ul></div></div>');
    }
});
$.wa.contactEditor.factoryTypes.Name = $.extend({}, $.wa.contactEditor.baseFieldType, {
    /** Cannot be used inline */
    newInlineFieldElement: null,

    newFieldElement: function(mode) {
        var title = '';
        if ($.wa.contactEditor.fieldEditors.title) {
            title = $.wa.contactEditor.fieldEditors.title.getValue()+' ';
        }
        // Update page header
        $('#contact-fullname').text(''+title+(this.fieldValue ? this.fieldValue : '<'+$_('no name')+'>'));

        // Update browser title
        $.wa.controller.setBrowserTitle($('#contact-fullname').text());

        // Update user name in top right hand corner
        if ($.wa.contactEditor.contact_id && $.wa.contactEditor.contact_id == $.wa.contactEditor.current_user_id) {
            $('#wa-my-username').text(''+(this.fieldValue ? this.fieldValue : '<'+$_('no name')+'>'));
        }

        return $('<div style="display: none"></div>');
    },
    setValue: function(data) {
        this.fieldValue = data;
    },
    getValue: function(forced) {
        if (this.fieldValue && !forced) {
            return this.fieldValue;
        }

        // Have to build it manually for new contacts
        var val = $.wa.contactEditor.fieldEditors.firstname.getValue();
        val += (val ? ' ' : '') + $.wa.contactEditor.fieldEditors.middlename.getValue();
        val += (val ? ' ' : '') + $.wa.contactEditor.fieldEditors.lastname.getValue();
        return val;
    },

    validate: function(skipRequiredCheck) {
        var val = this.getValue(true);
        if (!skipRequiredCheck && this.fieldData.required && !val) {
            // If all name parts are empy then set firstname to be value of the first visible non-empty input:text
            var newfname = $('#contact-info-block input:visible:text[value]:not(.empty)').val();
            if (!newfname) {
                return $_('At least one of these fields must be filled');
            }
            $.wa.contactEditor.fieldEditors.firstname.setValue(newfname);
        }
        return null;
    },

    showValidationErrors: function(errors) {
        var el = $('#contact-info-block');
        el.children('div.wa-errors-block').remove();
        if (errors !== null) {
            var err = $('<div class="field wa-errors-block"><div class="value"><em class="errormsg">'+errors+'</em></div></div>');
            if ($.wa.contactEditor.fieldEditors.lastname) {
                $.wa.contactEditor.fieldEditors.lastname.domElement.after(err);
            } else {
                el.prepend(err);
            }
        }
        var a = ['firstname', 'middlename', 'lastname'];
        for(var i=0; i<a.length; i++) {
            df = a[i];
            if ($.wa.contactEditor.fieldEditors[df]) {
                if (errors !== null) {
                    $.wa.contactEditor.fieldEditors[df].domElement.find('.val').addClass('external-error');
                } else {
                    $.wa.contactEditor.fieldEditors[df].domElement.find('.val').removeClass('external-error');
                }
            }
        }
    }
});

$.wa.contactEditor.factoryTypes.NameSubfield = $.extend({}, $.wa.contactEditor.baseFieldType, {});

$.wa.contactEditor.factoryTypes.Multifield = $.extend({}, $.wa.contactEditor.baseFieldType, {
    subfieldEditors: null,
    subfieldFactory: null,
    emptySubValue: null,

    initializeFactory: function(fieldData) {
        this.fieldData = fieldData;
        if (typeof this.fieldData.ext != 'undefined') {
            this.fieldData.extKeys = [];
            this.fieldData.extValues = [];
            for(var i in this.fieldData.ext) {
                this.fieldData.extKeys[this.fieldData.extKeys.length] = i;
                this.fieldData.extValues[this.fieldData.extValues.length] = this.fieldData.ext[i];
            }
        }
    },

    initialize: function() {
        this.subfieldFactory = $.extend({}, $.wa.contactEditor.factoryTypes[this.fieldData.type]);
        this.subfieldFactory.parentEditor = this;
        this.subfieldFactory.initializeFactory($.extend({}, this.fieldData));
        this.fieldData = $.extend({}, this.fieldData, {'required': this.subfieldFactory.fieldData.required});
        this.subfieldEditors = [this.subfieldFactory.createEditor()];
        if (typeof this.subfieldEditors[0].fieldValue == 'object') {
            this.emptySubValue = $.extend({}, this.subfieldEditors[0].fieldValue);
            if (this.fieldData.ext) {
                this.emptySubValue.ext = this.fieldData.extKeys[0];
            }
        } else {
            this.emptySubValue = {value: this.subfieldEditors[0].fieldValue};
            if (this.fieldData.ext) {
                this.emptySubValue.ext = this.fieldData.extKeys[0];
            }
        }
        this.fieldValue = [this.emptySubValue];
    },

    setValue: function(data) {
        // Check if there's at least one value
        if (!data || typeof data[0] == 'undefined') {
            data = [this.emptySubValue];
        }
        this.fieldValue = data;

        // Update data in existing editors
        // (If there's no data from PHP, still need to have at least one editor. Therefore, do-while.)
        var i = 0;
        do {
            // Add an editor if needed
            if (this.subfieldEditors.length <= i) {
                this.subfieldEditors[i] = this.subfieldFactory.createEditor();
                if (this.currentMode != 'null') {
                    this.subfieldEditors[i].setMode(this.currentMode).insertAfter(this.subfieldEditors[i-1].parentEditorData.domElement);
                }
            }
            if (typeof data[i] != 'undefined') {
                // if data[i] contain only ext and value, then pass value to child;
                // if there's something else, then pass the whole object.
                var passObject = false;
                for(var k in data[i]) {
                    if (k != 'value' && k != 'ext') {
                        passObject = true;
                        break;
                    }
                }

                this.subfieldEditors[i].setValue(passObject ? data[i] : (data[i].value ? data[i].value : ''));

                // save ext
                if (typeof this.fieldData.ext != 'undefined') {
                    var ext = data[i].ext;
                    if (this.currentMode != 'null' && this.subfieldEditors[i].parentEditorData.domElement) {
                        var el = this.subfieldEditors[i].parentEditorData.domElement.find('input.ext');
                        if (el.size() > 0) {
                            el[0].setExtValue(ext);
                        }
                    }
                }
            } else {
                throw new Error('At least one record must exist in data at this time.');
            }
            i++;

        } while(i < data.length);

        // Remove excess editors if needed
        if (data.length < this.subfieldEditors.length) {
            // remove dom elements
            for(i = data.length; i < this.subfieldEditors.length; i++) {
                if (i === 0) { // Never remove the first
                    continue;
                }
                if (this.currentMode != 'null') {
                    this.subfieldEditors[i].parentEditorData.domElement.remove();
                }
            }

            // remove editors
            var a = data.length > 0 ? data.length : 1; // Never remove the first
            this.subfieldEditors.splice(a, this.subfieldEditors.length - a);
        }

        this.origFieldValue = null;
    },

    getValue: function() {
        if (this.currentMode == 'null') {
            return $.extend({}, this.fieldValue);
        }

        var val = [];
        for(var i = 0; i < this.subfieldEditors.length; i++) {
            var sf = this.subfieldEditors[i];
            val[i] = {
                'value': sf.getValue()
            };

            // load ext
            if (typeof this.fieldData.ext != 'undefined') {
                var ext = this.fieldValue[i].ext;
                var el = sf.parentEditorData.domElement.find('input.ext')[0];
                if (sf.currentMode == 'edit' && el) {
                    ext = el.getExtValue();
                }
                val[i].ext = ext;
            }
        }

        return val;
    },

    isModified: function() {
        for(var i = 0; i < this.subfieldEditors.length; i++) {
            var sf = this.subfieldEditors[i];
            if (sf.isModified()) {
                return true;
            }
        }
        return false;
    },

    validate: function(skipRequiredCheck) {
        var result = [];

        // for each subfield add a record subfieldId => its validate() into result
        var allEmpty = true;
        for(var i = 0; i < this.subfieldEditors.length; i++) {
            var sf = this.subfieldEditors[i];
            var v = sf.validate(true);
            if (v) {
                result[i] = v;
            }

            var val = sf.getValue();
            if (val || typeof val != 'string') {
                allEmpty = false;
            }
        }

        if (!skipRequiredCheck && this.fieldData.required && allEmpty) {
            result[0] = 'This field is required.';
        }

        if (result.length <= 0) {
            return null;
        }
        return result;
    },

    showValidationErrors: function(errors) {
        for(var i = 0; i < this.subfieldEditors.length; i++) {
            var sf = this.subfieldEditors[i];
            if (errors !== null && typeof errors[i] != 'undefined') {
                sf.showValidationErrors(errors[i]);
            } else {
                sf.showValidationErrors(null);
            }
        }
    },

    /** Return button to delete subfield. */
    deleteSubfieldButton: function(sf) {
        var that = this;
        var r = $('<a class="delete-subfield hint" href="javascript:void(0)">'+$_('delete')+'</a>').click(function() {
            if (that.subfieldEditors.length <= 1) {
                return false;
            }

            var i = that.subfieldEditors.indexOf(sf);

            // remove dom element
            if (that.currentMode != 'null') {
                that.subfieldEditors[i].parentEditorData.domElement.remove();
            }

            // remove editor
            that.subfieldEditors.splice(i, 1);

            // Hide delete button if only one subfield left
            // have to do this because IE<9 lacks :only-child support
            if (that.subfieldEditors.length <= 1) {
                that.domElement.find('.delete-subfield').hide();
            }

            // (leaves a record in this.fieldValue to be able to restore it if needed)
            return false;
        });

        if (this.subfieldEditors.length <= 1) {
            r.hide();
        }

        return r;
    },

    newSubFieldElement: function(mode, i) {
        i = i-0;
        var sf = this.subfieldEditors[i];
        if(!sf.parentEditorData) {
            sf.parentEditorData = {};
        }
        sf.parentEditorData.parent = this;
        sf.parentEditorData.empty = false;
        var ext;

        // A (composite) field with no inline mode?
        if (typeof sf.newInlineFieldElement != 'function') {
            var nameAddition = '';
            if (mode == 'edit') {
                nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
            }
            var wrapper = $.wa.contactEditor.wrapper('<span class="replace-me-with-value"></span>', i === 0 ? (this.fieldData.name+nameAddition) : '', 'no-bot-margins');
            var rwv = wrapper.find('span.replace-me-with-value');

            // extension
            ext = this.fieldValue[i].ext;
            if (mode == 'edit') {
                ext = $.wa.contactEditor.createExtSelect(this.fieldData.ext, ext);
            } else {
                ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                ext = $('<strong>'+$.wa.contactEditor.htmlentities(ext)+'</strong>');
            }
            rwv.before(ext);

            // button to delete this subfield
            if (mode == 'edit') {
                rwv.before(this.deleteSubfieldButton(sf));
            }
            rwv.remove();

            sf.domElement = this.subfieldEditors[i].newFieldElement(mode);
            self = this;
            sf.domElement.find('div.name').each(function(i, el) {
                if (el.innerHTML.substr(0, self.fieldData.name.length) === self.fieldData.name) {
                    el.innerHTML = '';
                }
            });

            sf.parentEditorData.domElement = $('<div></div>').append(wrapper).append(sf.domElement);

            //this.initInplaceEditor(sf.parentEditorData.domElement, i);
            return sf.parentEditorData.domElement;
        }

        // Inline mode is available
        var value = sf.newInlineFieldElement(mode);
        if (value === null) {
            // Field is empty, return stub.
            sf.parentEditorData.domElement = sf.domElement = $('<div></div>');
            sf.parentEditorData.empty = true;
            return sf.parentEditorData.domElement;
        }

        sf.domElement = value;
        var result = $('<div class="value"></div>').append(value);
        var rwe = result.find('.replace-with-ext');
        if (rwe.size() <= 0) {
            result.append('<span><span class="replace-with-ext"></span></span>');
            rwe = result.find('.replace-with-ext');
        }

        // Extension
        if (typeof this.fieldData.ext != 'undefined') {
            ext = this.fieldValue[i].ext;
            if (mode == 'edit') {
                rwe.before($.wa.contactEditor.createExtSelect(this.fieldData.ext, ext));
            } else {
                ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                if (rwe.parents('.ext').size() > 0) {
                    rwe.before($.wa.contactEditor.htmlentities(ext));
                } else {
                    rwe.before($('<em class="hint"></em>').text(' '+ext));
                }
            }
        }

        // button to delete this subfield
        if (mode == 'edit') {
            rwe.before(this.deleteSubfieldButton(sf));
        }
        rwe.remove();

        sf.parentEditorData.domElement = result;
        //this.initInplaceEditor(sf.parentEditorData.domElement, i);
        return result;
    },

    newInlineFieldElement: null,

    newFieldElement: function(mode) {
        if(this.fieldData.read_only) {
            mode = 'view';
        }

        var childWrapper = $('<div class="multifield-subfields"></div>');
        var inlineMode = typeof this.subfieldFactory.newInlineFieldElement == 'function';

        var allEmpty = true;
        for(var i = 0; i < this.subfieldEditors.length; i++) {
            var result = this.newSubFieldElement(mode, i);
            childWrapper.append(result);
            allEmpty = allEmpty && this.subfieldEditors[i].parentEditorData.empty;
        }

        // do not show anything if there are no values
        if (allEmpty && !this.fieldData.show_empty) {
            return $('<div style="display: none"></div>');
        }

        // Wrap over for all subfields to be in separate div
        var wrapper = $('<div></div>').append(childWrapper);

        // A button to add more fields
        if (mode == 'edit') {
            var adder;
            if (inlineMode) {
                adder = $('<div class="value"><span class="replace-me-with-value"></span></div>');
            } else {
                adder = $.wa.contactEditor.wrapper('<span class="replace-me-with-value"></span>');
            }
            var that = this;
            adder.find('.replace-me-with-value').replaceWith(
                $('<a href="javascript:void(0)" class="small inline-link"><b><i>'+$_('Add another')+'</i></b></a>').click(function (e) {
                    var newLast = that.subfieldFactory.createEditor();
                    var index = that.subfieldEditors.length;

                    val = {
                        value: newLast.getValue(),
                        temp: true
                    };
                    if (typeof that.fieldData.ext != 'undefined') {
                        val.ext = '';
                    }
                    that.fieldValue[index] = val;

                    that.subfieldEditors[index] = newLast;
                    if (that.currentMode != 'null') {
                        newLast.setMode(that.currentMode);
                    }
                    childWrapper.append(that.newSubFieldElement(mode, index));
                    that.domElement.find('.delete-subfield').css('display', 'inline');
                })
            );
            wrapper.append(adder);
        }

        if (inlineMode) {
            var nameAddition = '';
            if (mode == 'edit') {
                nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
            }
            wrapper = $.wa.contactEditor.wrapper(wrapper, this.fieldData.name+nameAddition);
        }
        return wrapper;
    },

    setMode: function(mode, replaceEditor) {
        if (typeof replaceEditor == 'undefined') {
            replaceEditor = true;
        }
        if (mode != 'view' && mode != 'edit') {
            throw new Error('Unknown mode: '+mode);
        }

        if (this.currentMode != mode) {
            // When user switches from edit to view, we need to restore
            // deleted editors, if any. So we set initial value here to ensure that.
            if (mode == 'view' && this.currentMode == 'edit' && this.origFieldValue) {
                this.setValue(this.origFieldValue);
            } else if (this.currentMode == 'view' && !this.origFieldValue) {
                this.origFieldValue = [];
                for (var i = 0; i < this.fieldValue.length; i++) {
                    this.origFieldValue.push($.extend({}, this.fieldValue[i]));
                }
            }

            this.currentMode = mode;
            if (replaceEditor) {
                var oldDom = this.domElement;
                this.domElement = this.newFieldElement(mode);
                if (oldDom !== null) {
                    oldDom.replaceWith(this.domElement);
                }
            }
        }

        for(var i = 0; i < this.subfieldEditors.length; i++) {
            this.subfieldEditors[i].setMode(mode, false);
        }

        return this.domElement;
    }
}); // end of Multifield type

$.wa.contactEditor.factoryTypes.Composite = $.extend({}, $.wa.contactEditor.baseFieldType, {
    subfieldEditors: null,

    initializeFactory: function(fieldData) {
        this.fieldData = fieldData;
        if (this.fieldData.required) {
            for(var i in this.fieldData.required) {
                if (this.fieldData.required[i]) {
                    this.fieldData.required = true;
                    break;
                }
            }
            if (this.fieldData.required !== true) {
                this.fieldData.required = false;
            }
        }
    },

    initialize: function() {
        var val = {
            'data': {},
            'value': ''
        };

        this.subfieldEditors = {};
        this.fieldData.subfields = this.fieldData.fields;
        for(var sfid in this.fieldData.subfields) {
            var sf = this.fieldData.subfields[sfid];
            var editor = $.extend({}, $.wa.contactEditor.factoryTypes[sf.type]);
            var sfData = this.fieldData.fields[sfid];
            if (this.fieldData.required && this.fieldData.required[sfid]) {
                sfData.required = true;
            }
            var options = $.extend({}, sfData, {id: null, multi: false});
            editor.initializeFactory(options);
            editor.parentEditor = this;
            editor.parentEditorData = {};
            editor.initialize();
            editor.parentEditorData.sfid = sfid;
            editor.parentEditorData.parent = this;
            this.subfieldEditors[sfid] = editor;
            val.data[sfid] = editor.getValue();
        }

        this.fieldValue = val;
    },

    setValue: function(data) {
        if (!data) {
            return;
        }

        this.fieldValue = data;

        // Save subfields
        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            if (typeof data.data == 'undefined') {
                sf.initialize();
                sf.setValue(sf.getValue());
            } else if (typeof data.data[sfid] != 'undefined') {
                sf.setValue(data.data[sfid]);
            } else {
                sf.setValue(sf.getValue());
            }
        }
    },

    getValue: function() {
        if (this.currentMode == 'null') {
            return $.extend({}, this.fieldValue.data);
        }

        var val = {};

        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            val[sfid] = sf.getValue();
        }

        return val;
    },

    isModified: function() {
        for(var sfid in this.subfieldEditors) {
            if (this.subfieldEditors[sfid].isModified()) {
                return true;
            }
        }
        return false;
    },

    validate: function(skipRequiredCheck) {
        var result = {};

        // for each subfield add a record subfieldId => its validate() into result
        var errorsFound = false;
        for(var sfid in this.subfieldEditors) {
            var v = this.subfieldEditors[sfid].validate(skipRequiredCheck);
            if (v) {
                result[sfid] = v;
                errorsFound = true;
            }
        }

        if (!errorsFound) {
            return null;
        }
        return result;
    },

    showValidationErrors: function(errors) {
        if (this.domElement === null) {
            return;
        }

        // for each subfield call its showValidationErrors with errors[subfieldId]
        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            if (errors !== null && typeof errors[sfid] != 'undefined') {
                sf.showValidationErrors(errors[sfid]);
            } else {
                sf.showValidationErrors(null);
            }
        }
    },

    /** Cannot be used inline */
    newInlineFieldElement: null,

    newFieldElement: function(mode) {
        if(this.fieldData.read_only) {
            mode = 'view';
        }
        if (mode == 'view') {
            // Do not show anything in view mode if field is empty
            if(!this.fieldValue.value && !this.fieldData.show_empty) {
                return $('<div style="display: none"></div>');
            }
        }

        var wrapper = $('<div class="composite '+mode+'"></div>').append($.wa.contactEditor.wrapper('<span style="display:none" class="replace-with-ext"></span>', this.fieldData.name, 'hdr'));

        // For each field call its newFieldElement and add to wrapper
        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            var element = sf.newFieldElement(mode);
            sf.domElement = element;
            wrapper.append(element);
        }

        // In-place editor initialization (when not part of a multifield)
        /*if (mode == 'edit' && this.parent == null) {
            var that = this;
            result.find('span.info-field').click(function() {
                var buttons = $.wa.contactEditor.inplaceEditorButtons([that.fieldData.id], function(noValidationErrors) {
                    if (typeof noValidationErrors != 'undefined' && !noValidationErrors) {
                        return;
                    }
                    that.setMode('view');
                    buttons.remove();
                });
                result.after(buttons);
                that.setMode('edit');
            });
        }*/

        return wrapper;
    },

    setMode: function(mode, replaceEditor) {
        if (typeof replaceEditor == 'undefined') {
            replaceEditor = true;
        }
        if (mode != 'view' && mode != 'edit') {
            throw new Error('Unknown mode: '+mode);
        }

        if (this.currentMode != mode) {
            this.currentMode = mode;
            if (replaceEditor) {
                var oldDom = this.domElement;
                this.domElement = this.newFieldElement(mode);
                if (oldDom !== null) {
                    oldDom.replaceWith(this.domElement);
                }
            }
        }

        for(var sfid in this.subfieldEditors) {
            this.subfieldEditors[sfid].setMode(mode, false);
        }

        return this.domElement;
    }
}); // end of Composite field type

$.wa.contactEditor.factoryTypes.Address = $.extend({}, $.wa.contactEditor.factoryTypes.Composite, {
    showValidationErrors: function(errors) {
        if (this.domElement === null) {
            return;
        }

        // remove old errors
        this.domElement.find('em.errormsg').remove();
        this.domElement.find('.val').removeClass('error');

        if (!errors) {
            return;
        }

        // Show new errors
        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            if (typeof errors[sfid] == 'undefined') {
                continue;
            }
            var input = sf.domElement.find('.val').addClass('error');
            input.parents('.address-subfield').append($('<em class="errormsg">'+errors[sfid]+'</em>'));
        }
    },

    newInlineFieldElement: function(mode) {
        var result = '';

        if (mode == 'view') {
            // Do not show anything in view mode if field is empty
            if(!this.fieldValue.value) {
                return null;
            }
            return $('<div class="address-field"></div>')
                //.append('<div class="ext"><strong><span style="display:none" class="replace-with-ext"></span></strong></div>')
                .append(this.fieldValue.value)
                .append('<span style="display:none" class="replace-with-ext"></span>');
        }

        //
        // edit mode
        //
        var wrapper = $('<div class="address-field"></div>');
        wrapper.append('<span style="display:none" class="replace-with-ext"></span>');

        // Add fields
        // For each field call its newFieldElement and add to wrapper
        for(var sfid in this.subfieldEditors) {
            var sf = this.subfieldEditors[sfid];
            var element = sf.newInlineFieldElement('edit');
            sf.domElement = element;
            wrapper.append($('<div class="address-subfield"></div>').append(element));
            $.wa.defaultInputValue(element.find('input.val'), sf.fieldData.name+(sf.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
        }
        return wrapper;
    }
});

$.wa.contactEditor.factoryTypes.Date = $.extend({}, $.wa.contactEditor.baseFieldType, {

    datepickerOptions: function(element) {
        return {
            altField: element.find('input.val'),
            dateFormat: this.fieldData.format,
            altFormat: 'yy-mm-dd',
            yearRange: '-99:+1',
            changeYear: true,
            changeMonth: true
        };
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }

        var result = null;
        var value = this.fieldValue;
        var that = this;
        if (mode == 'edit') {
            result = $('<span><input class="datepicker" type="text"><input class="val" type="hidden"></span>');
            var el = result.find('input.datepicker').val(value);
            el.datepicker(this.datepickerOptions(result)).blur(function() {
                if (!$(this).val()) {
                    result.find('input.val').val('');
                }
            });

            // voodoo magic...
            // set the value of hidden field
            el.datepicker('setDate', el.datepicker('getDate'));
            // widget appears in bottom left corner for some reason, so we hide it
            el.datepicker('widget').hide();

            $(document).one('hashchange', function() {
                // if the <input> is still attached to DOM, then hide the datepicker
                // (that could possibly be still displayed by now)
                var element = el[0];
                while ( ( element = element.parentNode)) {
                    if (element === document) {
                        el.datepicker('hide').datepicker('destroy');
                        return;
                    }
                }
            });
        } else {
            result = $('<span class="val"></span>').text(value);
        }
        return result;
    }
});

$.wa.contactEditor.factoryTypes.IM = $.extend({}, $.wa.contactEditor.baseFieldType, {
    /** Accepts both a simple string and {value: previewHTML, data: stringToEdit} */
    setValue: function(data) {
        if (typeof data == 'undefined') {
            data = '';
        }
        if (typeof(data) == 'object') {
            this.fieldValue = data.data;
            this.viewValue = data.value;
        } else {
            this.fieldValue = this.viewValue = data;
        }
        if (this.currentMode == 'null' || !this.domElement) {
            return;
        }

        if (this.currentMode == 'edit') {
            this.domElement.find('input.val').val(this.fieldValue);
        } else {
            this.domElement.find('.val').html(this.viewValue);
        }
    },

    newInlineFieldElement: function(mode) {
        // Do not show anything in view mode if field is empty
        if(mode == 'view' && !this.fieldValue) {
            return null;
        }

        var result = null;
        if (mode == 'edit') {
            result = $('<span><input class="val" type="text"></span>');
            result.find('.val').val(this.fieldValue);
        } else {
            result = $('<span class="val"></span>').html(this.viewValue);
        }
        return result;
    }
});

$.wa.contactEditor.factoryTypes.Url = $.extend({}, $.wa.contactEditor.factoryTypes.IM, {
    validate: function(skipRequiredCheck) {
        var val = $.trim(this.getValue());

        if (!skipRequiredCheck && this.fieldData.required && !val) {
            return $_('This field is required.');
        }
        if (!val) {
            return null;
        }

        if (!(/^(https?|ftp|gopher|telnet|file|notes|ms-help)/.test(val))) {
            val = 'http://'+val;
            this.setValue(val);
        }

        var l = '[^`!()\\[\\]{};:\'".,<>?«»“”‘’\\s+]'; // letter allowed in url, including IDN
        var p = '[^`!\\[\\]{}\'"<>«»“”‘’\\s]'; // punctuation or letter allowed in url
        var regex = new RegExp('^(https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\\\\\))+'+p+'*$', 'i');
        if (!regex.test(val)) {
            return $_('Incorrect URL format.');
        }

        // More restrictions for common protocols
        if (/^(http|ftp)/.test(val.toLowerCase())) {
            regex = new RegExp('^(https?|ftp):((//)|(\\\\\\\\))+((?:'+l+'+\\.)+'+l+'{2,6})((/|\\\\|#)'+p+'*)?$', 'i');
            if (!regex.test(val)) {
                return $_('Incorrect URL format.');
            }
        }

        return null;
    }
});

$.wa.contactEditor.factoryTypes.Email = $.extend({}, $.wa.contactEditor.factoryTypes.Url, {
    validate: function(skipRequiredCheck) {
        var val = $.trim(this.getValue());

        if (!skipRequiredCheck && this.fieldData.required && !val) {
            return $_('This field is required.');
        }
        if (!val) {
            return null;
        }
        var regex = new RegExp('^([^@\\s]+)@[^\\s@]+\\.[^\\s@\\.]{2,}$', 'i');
        if (!regex.test(val)) {
            return $_('Incorrect Email address format.');
        }
        return null;
    }
});

$.wa.contactEditor.factoryTypes.Checkbox = $.extend({}, $.wa.contactEditor.baseFieldType, {
    /** Load field contents from given data and update DOM. */
    setValue: function(data) {
        this.fieldValue = parseInt(data);
        if (this.currentMode == 'null' || !this.domElement) {
            return;
        }

        if (this.currentMode == 'edit') {
            this.domElement.find('input.val').attr('checked', !!this.fieldValue);
        } else {
            this.domElement.find('.val').text(this.fieldValue ? 'Yes' : 'No');
        }
    },

    newInlineFieldElement: function(mode) {
        var result = null;
        if (mode == 'edit') {
            result = $('<span><input class="val" type="checkbox" value="1" checked="checked"></span>');
            if (!this.fieldValue) {
                result.find('.val').removeAttr('checked');
            }
        } else {
            result = $('<span class="val"></span>').text(this.fieldValue ? 'Yes' : 'No');
        }
        return result;
    }
});

$.wa.contactEditor.factoryTypes.Number = $.extend({}, $.wa.contactEditor.baseFieldType, {
    validate: function(skipRequiredCheck) {
        var val = $.trim(this.getValue());
        if (!skipRequiredCheck && this.fieldData.required && !val && val !== 0) {
            return $_('This field is required.');
        }
        if (val && !(/^-?[0-9]+([\.,][0-9]+$)?/.test(val))) {
            return $_('Must be a number.');
        }
        return null;
    }
});

// EOF
