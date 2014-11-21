/**
  * Base classs for all editor factory types, all editor factories and all editors.
  * Implements JS counterpart of contactsFieldEditor with no validation.
  *
  * An editor factory can be created out of factory type (see $.wa.contactEditorFactory.initFactories())
  *
  * Editor factories create editors using factory.createEditor() method. Under the hood
  * a factory simply copies self, removes .createEditor() method from the copy and calls
  * its .initialize() method.
  */
$.wa.fieldTypesFactory = function(contactEditor, fieldType) {
    
    contactEditor = contactEditor || $.wa.contactEditorFactory();
    
    contactEditor.baseFieldType = {

        //
        // Public editor factory functions. Not available in editor instances.
        //

        contactType: 'person',
                
        options: {},

        /** For multifields, return a new (empty) editor for this field. */
        createEditor: function(contactType) {
            this.contactType = contactType || 'person';
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
        initializeFactory: function(fieldData, options) {
            this.fieldData = fieldData;
            this.options = options || {};
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
          * Use of contactEditor.wrapper is recommended if apropriate.
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
                return $('<div style="display: none" class="field" data-field-id="' + this.fieldData.id + '"></div>');
            }

            var nameAddition = '';
//            if (mode == 'edit') {
//                nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
//            }
            var cssClass;
            if (this.contactType === 'person') {
                if (['firstname', 'middlename', 'lastname'].indexOf(this.fieldData.id) >= 0) {
                    cssClass = 'subname';
                    inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'title') {
                    cssClass = 'subname title';
                    inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'jobtitle') {
                    cssClass = 'jobtitle-company jobtitle';
                    //inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'company') {
                    cssClass = 'jobtitle-company company';
                    //inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                }
            } else if (this.fieldData.id === 'company') {
                cssClass = 'company';
            }
            return contactEditor.wrapper(inlineElement, this.fieldData.name+nameAddition, cssClass).attr('data-field-id', this.fieldData.id);
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
          * Must be redefined for editors that do not use the default contactEditor.wrapper().
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

    contactEditor.factoryTypes.Hidden = $.extend({}, contactEditor.baseFieldType, {
        newFieldElement: function(mode) {
            var inlineElement = this.newInlineFieldElement(mode);
            return contactEditor.wrapper(inlineElement, this.fieldData.name).attr('data-field-id', this.fieldData.id).hide();
        },
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input type="hidden" class="val" type="text"></span>');
                result.find('.val').val(this.fieldValue);
            }
            return result;
        }
    });

    contactEditor.factoryTypes.String = $.extend({}, contactEditor.baseFieldType, {
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
                    result = $('<span><input class="val" type="text"><i class="icon16 loading" style="display:none;"></i></span>');
                } else {
                    result = $('<span><textarea class="val" rows="'+this.fieldData.input_height+'"></textarea></span>');
                }
                result.find('.val').val(value);
            } else {
                result = $('<span class="val"></span><i class="icon16 loading" style="display:none;">').text(value);
            }
            return result;
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

            return this.domElement;
        }

        
    });
    contactEditor.factoryTypes.Text = $.extend({}, contactEditor.factoryTypes.String);
    contactEditor.factoryTypes.Phone = $.extend({}, contactEditor.baseFieldType);
    contactEditor.factoryTypes.Select = $.extend({}, contactEditor.baseFieldType, {
        notSet: function() {
            return '';
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
                    options += '<option value="'+id+'"'+attrs+'>' + $.wa.encodeHTML(this.fieldData.options[id]) + '</option>';
                }
                return $('<div><select class="val '  + (this.fieldData.type + '').toLowerCase() + '"><option value=""'+(selected ? '' : ' selected')+'>'+this.notSet()+'</option>'+options+'</select></div>');
            }
        }
    });
    contactEditor.factoryTypes.Conditional = $.extend({}, contactEditor.factoryTypes.Select, {

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
                var parent_field = contactEditor.fieldEditors[parent_field_id_parts.shift()];
                while (parent_field && parent_field_id_parts.length) {
                    var subfields = parent_field.subfieldEditors;
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

                if (parent_field) {
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
                        if (!parent_field.domElement) {
                            input.show();
                            return;
                        }
                        parent_field.domElement.on('change', '.val', change_handler = function() {
                            var parent_val_element = $(this);
                            var old_val = getVal();
                            var parent_value = (parent_val_element.val() || '').toLowerCase();
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
                        change_handler.call(parent_field.domElement.find('.val:visible')[0]);
                    }, 0);

                    cond_field.unbindEventHandlers = function() {
                        if (change_handler && parent_field.domElement) {
                            parent_field.domElement.find('.val').unbind('change', change_handler);
                        }
                        cond_field.unbindEventHandlers = function() {};
                    };

                    return $('<div></div>').append(input).append(select);
                } else {
                    return $('<div></div>').append($('<input type="text" class="val">').val(cond_field.fieldValue));
                }
            }
        }
    });
    contactEditor.factoryTypes.Region = $.extend({}, contactEditor.factoryTypes.Select, {
        notSet: function() {
//            if (this.fieldData.options && this.fieldValue && !this.fieldData.options[this.fieldValue]) {
//                return this.fieldValue;
//            }
            return '';
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
            return (contactEditor.regionsUrl || '?module=backend&action=regions&country=')+country;
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }

            this.unbindEventHandlers();

            var options = this.options || {};
            if (options.country !== undefined) {
                this.current_country = options.country;
            }

            if(mode == 'view') {
                return $('<div></div>').append($('<span class="val"></span>').text((this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue));
            } else {
                var region_field = this;

                // This field depends on currently selected country in address
                if (this.parentEditorData.parent && this.parentEditorData.parent.subfieldEditors.country) {
                    this.setCurrentCountry();
                    var handler;
                    $(document).on('change', 'select.country', handler = function() {
                        if (region_field.setCurrentCountry()) {
                            var prev_val = '';
                            var prev_val_el = region_field.domElement.find('.val');
                            if (prev_val_el.is('input')) {
                                prev_val = prev_val_el.val().trim();
                            } else {
                                prev_val = prev_val_el.find(':selected').text().trim();
                            }
                            
                            var lookup = function(select, val) {
                                var v = val.toLocaleLowerCase();
                                select.find('option').each(function() {
                                    if ($(this).text().trim().toLocaleLowerCase() === v) {
                                        $(this).attr('selected', true);
                                        return false;
                                    }
                                });
                            };
                            
                            region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());

                            var val_el = region_field.domElement.find('.val');
                            if (val_el.is('input') && !val_el.val()) {
                                val_el.val(prev_val);
                            } else if (val_el.is('select') && prev_val) {
                                lookup(val_el, prev_val);
                            } else {
                                region_field.domElement.unbind('load.fieldTypes').bind('load.fieldTypes', function() {
                                    var val_el = region_field.domElement.find('.val');
                                    if (val_el.is('select') && prev_val) {
                                        lookup(val_el, prev_val);
                                    }
                                });
                            }
                        }
                    });
                    region_field.unbindEventHandlers = function() {
                        $(document).off('change', 'select.country', handler);
                        region_field.unbindEventHandlers = function() {};
                    };
                }

                if (!options.no_ajax_select && this.fieldData.options === undefined && this.current_country && this.fieldData.region_countries[this.current_country]) {
                    // Load list of regios via AJAX and then show select
                    var country = this.current_country;
                    $.get(this.getRegionsControllerUrl(country), function(r) {
                        if (mode !== region_field.currentMode || country !== region_field.current_country) {
                            return;
                        }
                        region_field.fieldData.options = r.data.options || false;
                        region_field.fieldData.oOrder = r.data.oOrder || [];
                        if ($.isPlainObject(region_field.options) && region_field.options.country !== undefined) {
                            delete region_field.options.country;
                        }
                        var d = $('<div></div>');
                        d.append(region_field.newInlineFieldElement(mode).children());
                        region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());
                        region_field.domElement.trigger('load');
                    }, 'json');
                    return $('<div></div>').append($('<i class="icon16 loading"></i>'));
                } else if (this.fieldData.options) {
                    // Show as select
                    return $('<div></div>').append(contactEditor.factoryTypes.Select.newInlineFieldElement.call(this, mode));
                } else {
                    // show as input
                    var result = $('<div></div>').append(contactEditor.baseFieldType.newInlineFieldElement.call(this, mode));
                    
                    result.find('.val').val('');
                    
                    //$.wa.defaultInputValue(result.find('.val'), this.fieldData.name+(this.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
                    result.find('.val').attr('placeholder', this.fieldData.name+(this.fieldData.required ? ' ('+$_('required')+')' : ''));
                    return result;
                }
            }
        }
    });

    contactEditor.factoryTypes.Country = $.extend({}, contactEditor.factoryTypes.Select);
    contactEditor.factoryTypes.Checklist = $.extend({}, contactEditor.baseFieldType, {
        validate: function(skipRequiredCheck) {
//            if (!skipRequiredCheck && this.fieldData.required && this.getValue().length <= 0) {
//                return $_('This field is required.');
//            }
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
                options += (options ? ', ' : '')+'<a href="'+(this.fieldData.hrefPrefix || '#')+id+'">'+((this.fieldData.options[id] && contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</a>';
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

                // Checkboxes for system categories are disabled
                if (this.fieldData.disabled && this.fieldData.disabled[id]) {
                    options += ' disabled="disabled"';
                }
                
                if ((this.fieldValue || []).indexOf(id) !== -1) {
                    options += ' checked="checked"';
                }
                
                options += ' />'+((this.fieldData.options[id] && contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</label></li>';
            }
            return contactEditor.initCheckboxList('<div class="c-checkbox-menu-container val"><div><ul class="menu-v compact with-icons c-checkbox-menu">'+options+'</ul></div></div>');
        }
    });

    contactEditor.factoryTypes.Name = $.extend({}, contactEditor.baseFieldType, {
        /** Cannot be used inline */
        newInlineFieldElement: null,

        newFieldElement: function(mode) {
            return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
        },
        setValue: function(data) {
            this.fieldValue = data;
        },
        getValue: function(forced) {
            if (this.fieldValue && !forced) {
                return this.fieldValue;
            }

            // Have to build it manually for new contacts
            var val = [];
            if (this.contactType == 'person') {
                if (contactEditor.fieldEditors.firstname) {
                    val.push(contactEditor.fieldEditors.firstname.getValue());
                }
                if (contactEditor.fieldEditors.middlename) {
                    val.push(contactEditor.fieldEditors.middlename.getValue());
                }
                if (contactEditor.fieldEditors.lastname) {
                    val.push(contactEditor.fieldEditors.lastname.getValue());
                }
            } else {
                if (contactEditor.fieldEditors.company) {
                    val.push(contactEditor.fieldEditors.company.getValue());
                }
            }
            return val.join(' ').trim();
        },

        validate: function(skipRequiredCheck) {
            var val = this.getValue(true);
            if (!skipRequiredCheck && this.fieldData.required && !val) {
                // If all name parts are empy then set firstname to be value of the first visible non-empty input:text
                var newfname = $('#contact-info-block input:visible:text[value]:not(.empty)').val();
                if (!newfname) {
                    return $_('At least one of these fields must be filled');
                }
                contactEditor.fieldEditors.firstname.setValue(newfname);
            }
            return null;
        },

        showValidationErrors: function(errors) {
            var el = $('#contact-info-block');
            el.find('div.wa-errors-block').remove();
            if (errors !== null) {
                var err = $('<div class="field wa-errors-block"><div class="value"><em class="errormsg">'+errors+'</em></div></div>');
                if (contactEditor.fieldEditors.lastname) {
                    contactEditor.fieldEditors.lastname.domElement.after(err);
                } else {
                    el.prepend(err);
                }
            }
            var a = ['firstname', 'middlename', 'lastname'];
            for(var i=0; i<a.length; i++) {
                var df = a[i];
                if (contactEditor.fieldEditors[df]) {
                    if (errors !== null) {
                        contactEditor.fieldEditors[df].domElement.find('.val').addClass('external-error');
                    } else {
                        contactEditor.fieldEditors[df].domElement.find('.val').removeClass('external-error');
                    }
                }
            }
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

            var title = '';
            if (contactEditor.fieldEditors.title) {
                title = contactEditor.fieldEditors.title.getValue()+' ';
            }
            
            if (mode === 'view' && !contactEditor.not_update_name) {

                var contact_fullname = $('#contact-fullname');

                var firstname = '';
                var middlename = '';
                var lastname = '';
                if (contactEditor.fieldEditors.firstname) {
                    var prev_mode = contactEditor.fieldEditors.firstname.currentMode;
                    contactEditor.fieldEditors.firstname.currentMode = mode;
                    firstname = contactEditor.fieldEditors.firstname.getValue();
                    contactEditor.fieldEditors.firstname.currentMode = prev_mode;
                }
                if (contactEditor.fieldEditors.middlename) {
                    var prev_mode = contactEditor.fieldEditors.middlename.currentMode;
                    contactEditor.fieldEditors.middlename.currentMode = mode;
                    middlename = contactEditor.fieldEditors.middlename.getValue();
                    contactEditor.fieldEditors.middlename.currentMode = prev_mode;
                }
                if (contactEditor.fieldEditors.lastname) {
                    var prev_mode = contactEditor.fieldEditors.lastname.currentMode;
                    contactEditor.fieldEditors.lastname.currentMode = mode;
                    lastname = contactEditor.fieldEditors.lastname.getValue();
                    contactEditor.fieldEditors.lastname.currentMode = prev_mode;
                }
                var name = [
                    firstname,
                    middlename,
                    lastname
                ].join(' ').trim();
                if (!name) {
                    name = this.fieldValue || '<'+$_('no name')+'>';
                }

                // Update page header
                var h1 = contact_fullname.find('h1.name').html('<span class="title">' + $.wa.encodeHTML(title) + '</span>' + $.wa.encodeHTML(name));

                if (this.contactType === 'person') {
                    var jobtitle_company = '';
                    var jobtitle = '';
                    var company = '';

                    if (contactEditor.fieldEditors.jobtitle) {
                        var prev_mode = contactEditor.fieldEditors.jobtitle.currentMode;
                        contactEditor.fieldEditors.jobtitle.currentMode = mode;
                        jobtitle = contactEditor.fieldEditors.jobtitle.getValue();
                        contactEditor.fieldEditors.jobtitle.currentMode = prev_mode;
                    }
                    if (contactEditor.fieldEditors.company) {
                        var prev_mode = contactEditor.fieldEditors.company.currentMode;
                        contactEditor.fieldEditors.company.currentMode = mode;
                        company = contactEditor.fieldEditors.company.getValue();
                        contactEditor.fieldEditors.company.currentMode = prev_mode;
                    }
                    if (jobtitle) {
                        jobtitle_company += '<span class="title">' + $.wa.encodeHTML(jobtitle) + '</span> ';
                    }
                    if (jobtitle && company) {
                        jobtitle_company += '<span class="at">' + $_('@') + '</span> ';
                    }
                    if (company) {
                        jobtitle_company += '<span class="company">' + $.wa.encodeHTML(company) + '</span> ';
                    }
                    contact_fullname.find('h1.jobtitle-company').html(jobtitle_company);
                }
                // Update browser title
                if (contactEditor.update_title) {
                    $.wa.controller.setTitle(h1.text());
                }
            }

            if (contactEditor.contact_id && contactEditor.contact_id == contactEditor.current_user_id) {
                // Update user name in top right hand corner
                $('#wa-my-username').text(''+(this.fieldValue ? this.fieldValue : '<'+$_('no name')+'>'));
            }

            return this.domElement;
        }
                
    });

    contactEditor.factoryTypes.NameSubfield = $.extend({}, contactEditor.baseFieldType, {});

    contactEditor.factoryTypes.Multifield = $.extend({}, contactEditor.baseFieldType, {
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
            this.subfieldFactory = $.extend({}, contactEditor.factoryTypes[this.fieldData.type]);
            this.subfieldFactory.parentEditor = this;
            this.subfieldFactory.initializeFactory($.extend({}, this.fieldData));
            this.fieldData = $.extend({}, this.fieldData, {'required': this.subfieldFactory.fieldData.required});
            this.subfieldEditors = [this.subfieldFactory.createEditor(this.contactType)];
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
                    this.subfieldEditors[i] = this.subfieldFactory.createEditor(this.contactType);
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
//                if (mode == 'edit') {
//                    nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
//                }
                var wrapper = contactEditor.wrapper('<span class="replace-me-with-value"></span>', i === 0 ? (this.fieldData.name+nameAddition) : '', 'no-bot-margins');
                var rwv = wrapper.find('span.replace-me-with-value');

                // extension
                ext = this.fieldValue[i].ext;
                if (mode == 'edit') {
                    ext = contactEditor.createExtSelect(this.fieldData.ext, ext);
                } else {
                    ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                    ext = $('<strong>'+contactEditor.htmlentities(ext)+'</strong>');
                }
                rwv.before(ext);

                // button to delete this subfield
                if (mode == 'edit') {
                    rwv.before(this.deleteSubfieldButton(sf));
                }
                rwv.remove();

                sf.domElement = this.subfieldEditors[i].newFieldElement(mode);
                var self = this;
                sf.domElement.find('div.name').each(function(i, el) {
                    if (el.innerHTML.substr(0, self.fieldData.name.length) === self.fieldData.name) {
                        el.innerHTML = '';
                    }
                });

                sf.parentEditorData.domElement = $('<div></div>').append(wrapper).append(sf.domElement);

                if (mode == 'edit') {
                    sf.parentEditorData.empty = false;
                } else {
                    sf.parentEditorData.empty = !sf.fieldValue.value && !sf.fieldData.show_empty;
                }

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
            sf.domElement.data('subfield-index', i).attr('data-subfield-index', i);
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
                    rwe.before(contactEditor.createExtSelect(this.fieldData.ext, ext));
                } else {
                    ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                    if (rwe.parents('.ext').size() > 0) {
                        rwe.before(contactEditor.htmlentities(ext));
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
                return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
            }

            // Wrap over for all subfields to be in separate div
            var wrapper;
            if (inlineMode) {
                wrapper = $('<div></div>').append(childWrapper);
            } else {
                wrapper = $('<div class="field" data-field-id="'+this.fieldData.id+'"></div>').append(childWrapper);
            }

            // A button to add more fields
            if (mode == 'edit') {
                var adder;
                if (inlineMode) {
                    adder = $('<div class="value multifield-subfields-add-another"><span class="replace-me-with-value"></span></div>');
                } else {
                    adder = contactEditor.wrapper('<span class="replace-me-with-value"></span>');
                }
                var that = this;
                adder.find('.replace-me-with-value').replaceWith(
                    $('<a href="javascript:void(0)" class="small inline-link"><b><i>'+$_('Add another')+'</i></b></a>').click(function (e) {
                        var newLast = that.subfieldFactory.createEditor(this.contactType);
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
//                if (mode == 'edit') {
//                    nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
//                }
                wrapper = contactEditor.wrapper(wrapper, this.fieldData.name+nameAddition).attr('data-field-id', this.fieldData.id);
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

    contactEditor.factoryTypes.Composite = $.extend({}, contactEditor.baseFieldType, {
        subfieldEditors: null,

        initializeFactory: function(fieldData, options) {
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
            this.options = options || {};
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
                var editor = $.extend({}, contactEditor.factoryTypes[sf.type]);
                var sfData = this.fieldData.fields[sfid];
                if (this.fieldData.required && this.fieldData.required[sfid]) {
                    sfData.required = true;
                }
                var data = $.extend({}, sfData, {id: null, multi: false});
                editor.initializeFactory(data, this.options);
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
                    return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
                }
            }

            var wrapper = $('<div class="composite '+mode+' field" data-field-id="'+this.fieldData.id+'"></div>').append(contactEditor.wrapper('<span style="display:none" class="replace-with-ext"></span>', this.fieldData.name, 'hdr'));
            
            // For each field call its newFieldElement and add to wrapper
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                var element = sf.newFieldElement(mode);
                element.attr('data-field-id', sfid);
                element.data('fieldId', sfid);
                sf.domElement = element;
                wrapper.append(element);
            }

            // In-place editor initialization (when not part of a multifield)
            /*if (mode == 'edit' && this.parent == null) {
                var that = this;
                result.find('span.info-field').click(function() {
                    var buttons = contactEditor.inplaceEditorButtons([that.fieldData.id], function(noValidationErrors) {
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

    contactEditor.factoryTypes.Address = $.extend({}, contactEditor.factoryTypes.Composite, {
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
                var map_url = '';
                if (typeof this.fieldValue.for_map === 'string') {
                    map_url = this.fieldValue.for_map;
                } else {
                    if (this.fieldValue.for_map.coords) {
                        map_url = this.fieldValue.for_map.coords;
                    } else {
                        map_url = this.fieldValue.for_map.with_street;
                    }
                }
                result = $('<div class="address-field"></div>')
                    //.append('<div class="ext"><strong><span style="display:none" class="replace-with-ext"></span></strong></div>')
                    .append(this.fieldValue.value)
                    .append('<span style="display:none" class="replace-with-ext"></span> ')
                    .append('<a target="_blank" href="//maps.google.com/maps?q=' + encodeURIComponent(map_url) + '&z=15" class="small map-link">' + $_('map') + '</a>');
                return result;
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
                if (sf.fieldData.type !== 'Hidden') {
                    //$.wa.defaultInputValue(element.find('input.val'), sf.fieldData.name+(sf.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
                    element.find('input.val').attr(
                        'placeholder', 
                        sf.fieldData.name+(sf.fieldData.required ? ' ('+$_('required')+')' : '')
                    );
                }
            }
            return wrapper;
        }
    });

    contactEditor.factoryTypes.Birthday = contactEditor.factoryTypes.Date = $.extend({}, contactEditor.baseFieldType, {
        
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue.value) {
                return null;
            }
            var result = null;
            var data = this.fieldValue.data || {};
            var that = this;
            if (mode == 'edit') {
                var day_html = $('<select class="val" data-part="day"><option data=""></option></select>');
                for (var d = 1; d <= 31; d += 1) {
                    var o = $('<option data="' + d + '" value="' + d + '">' + d + '</option>');
                    if (d == data["day"]) {
                        o.attr('selected', true);
                    }
                    day_html.append(o);
                }
                
                var month_html = $('<select class="val" data-part="month"><option data=""></option></select>');
                var months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];
                for (var m = 0; m < 12; m += 1) {
                    var v = $_(months[m]);
                    var o = $('<option data="' + (m + 1) + '"  value="' + (m + 1) + '">' + v + '</option>');
                    if ((m + 1) == data["month"]) {
                        o.attr('selected', true);
                    }
                    month_html.append(o);
                }
                
                var year_html = $('<input type="text" data-part="year" class="val" style="min-width: 32px; width: 32px;" placeholder="' + $_('year') + '">');
                if (data['year']) {
                    year_html.val(data['year']);
                }
                result = $('<span></span>').
                        append(day_html).
                        append(' ').
                        append(month_html).
                        append(' ').
                        append(year_html);
            } else {
                result = $('<span class="val"></span>').text(this.fieldValue.value);
            }
            return result;
        },
                
        getValue: function() {
            var result = this.fieldValue;
            if (this.currentMode == 'edit' && this.domElement !== null) {
                var input = this.domElement.find('.val');
                if (input.length > 0) {
                    result = {
                        value: {
                            day: null,
                            month: null,
                            year: null
                        }
                    };
                    input.each(function() {
                        var el = $(this);
                        var p = el.data('part');
                        result.value[p] = parseInt(el.val(), 10) || null;
                    });
                }
            }
            return result;
        },
                
        setValue: function(data) {
            this.fieldValue = data;
            if (this.currentMode == 'null' || this.domElement === null) {
                return;
            }
            if (this.currentMode == 'edit') {
                this.domElement.find('.val').each(function() {
                    var el = $(this);
                    var part = el.data('part');
                    el.val(data.data[part] || '');
                });
            } else {
                this.domElement.find('.val').html(this.fieldValue);
            }
        }
                
    });

    contactEditor.factoryTypes.IM = $.extend({}, contactEditor.baseFieldType, {
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

    contactEditor.factoryTypes.SocialNetwork = $.extend({}, contactEditor.baseFieldType, {
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

    contactEditor.factoryTypes.Url = $.extend({}, contactEditor.factoryTypes.IM, {
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

    contactEditor.factoryTypes.Email = $.extend({}, contactEditor.factoryTypes.Url, {
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

    contactEditor.factoryTypes.Checkbox = $.extend({}, contactEditor.baseFieldType, {
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

    contactEditor.factoryTypes.Number = $.extend({}, contactEditor.baseFieldType, {
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
    
    if (fieldType) {
        return contactEditor.factoryTypes[fieldType];
    } else {
        return contactEditor.factoryTypes;
    }
};

$.wa.fieldTypeFactory = function(fieldType) {
    return $.extend({}, $.wa.fieldTypesFactory(null, fieldType));
};

// EOF
