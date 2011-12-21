
$.wa.contactEditor = {
	contact_id: null,
	contactType: 'person', // person|company
	baseFieldType: null, // defined in fieldTypes.js
	saveUrl: '?module=contacts&action=save', // URL to send data when saving contact

	/** Editor factory templates, filled below */
	factoryTypes: {
		// 'Type': ... // factory template
	},

	/** Editor factories by field id, filled by this.initFactories() */
	editorFactories: {/*
		...,
		field_id: editorFactory // Factory to get editor for given type from
		...,
	*/},

	/** Fields that we need to show. All fields available for editing or viewing present here
	  * (possibly with empty values). Filled by this.initFieldEditors() */
	fieldEditors: {/*
		...,
		// field_id as specified in field metadata file
		// An editor for this field instance. If field exists, but there's no data
		// in DB, a fully initialized editor with empty values is present anyway.
		field_id: fieldEditor,
		...
	*/},

	/** Empty and reinit this.editorFactories given data from php.
	  * this.factoryTypes must already be set.*/
	initFactories: function(fields) {
		this.editorFactories = {};
		this.fieldEditors = {};
		for(var fldId in fields) {
			if (typeof fields[fldId] != 'object' || !fields[fldId].type) {
				throw new Error('Field data error for '+fldId);
			}

			if (typeof this.factoryTypes[fields[fldId].type] == 'undefined') {
				throw new Error('Unknown factory type: '+fields[fldId].type);
			}

			if (fields[fldId].multi) {
				this.editorFactories[fldId] = $.extend({}, this.factoryTypes['Multifield']);
			} else {
				this.editorFactories[fldId] = $.extend({}, this.factoryTypes[fields[fldId].type]);
			}
			this.editorFactories[fldId].initializeFactory(fields[fldId]);
		}
	},

	/** Init (or reinit existing) editors with empty data. */
	initAllEditors: function() {
		for(var f in $.wa.contactEditor.editorFactories) {
			if (typeof this.fieldEditors[f] == 'undefined') {
				this.fieldEditors[f] = this.editorFactories[f].createEditor();
			} else {
				this.fieldEditors[f].reinit();
			}
		}
	},

	/** Reinit (maybe not all) of this.fieldEditors using data from php. */
	initFieldEditors: function(newData) {
		if (newData instanceof Array) {
			// must be an empty array that came from json
			return;
		}

		for(var f in newData) {
			if (typeof this.editorFactories[f] == 'undefined') {
				// This can happen when a new field type is added since user opened the page.
				// Need to reload. (This should not happen often though.)
				$.wa.controller.contactAction([this.contact_id]);
				//console.log(this.editorFactories);
				//console.log(newData);
				//throw new Error('Unknown field type: '+f);
				return;
			}

			if (typeof this.fieldEditors[f] == 'undefined') {
				this.fieldEditors[f] = this.editorFactories[f].createEditor();
			}
			this.fieldEditors[f].setValue(newData[f]);
		}
	},

	/** Empty #contact-info-block and add editors there in given mode.
	  * this.editorFactories and this.fieldEditors must already be initialized. */
	initContactInfoBlock: function (mode) {
		this.switchMode(mode, true);
	},

	/** Switch mode for all editors */
	switchMode: function (mode, init) {
		var el = $('#contact-info-block');
		if (init) {
			el.html('');
			el.removeClass('edit-mode');
			el.removeClass('view-mode');
		}
		if (mode == 'edit' && el.hasClass('edit-mode')) {
			return;
		}
		if (mode == 'view' && el.hasClass('view-mode')) {
			return;
		}

		// Remove all buttons
		el.find('div.field.buttons').remove();

		var fieldsToUpdate = [];
		for(var f in this.fieldEditors) {
			fieldsToUpdate.push(f);
			var fld = this.fieldEditors[f].setMode(mode);
			if (init) {
				el.append(fld);
			}
		}

		// Editor buttons
		if(mode == 'edit') {
			$('#c-editor-edit-link').hide();

			var buttons = this.inplaceEditorButtons(fieldsToUpdate, function(noValidationErrors) {
				if (typeof noValidationErrors != 'undefined' && !noValidationErrors) {
					return false;
				}

				if (typeof $.wa.contactEditor.justCreated != 'undefined' && $.wa.contactEditor.justCreated) {
					// new contact created
					var c = $('#sb-all-contacts-li .count');
					c.text(1+parseInt(c.text()));

					// Redirect to profile just created
					$.wa.setHash('/contact/'+$.wa.contactEditor.contact_id);
					return false;
				}

				$.wa.contactEditor.switchMode('view');
				$.scrollTo(0);
				return false;
			}, function() {
				if ($.wa.contactEditor.contact_id == null) {
					$.wa.back();
					return;
				}
				$.wa.contactEditor.switchMode('view');
				$.scrollTo(0);
			});
			el.append(buttons);
			el.removeClass('view-mode');
			el.addClass('edit-mode');
		} else {
			$('#c-editor-edit-link').show();
			el.removeClass('edit-mode');
			el.addClass('view-mode');
		}
	},

	/** Save all modified editors, reload data from php and switch back to view mode. */
	saveFields: function(ids, callback) {
		var data = {};
		var validationErrors = false;
		for(var i = 0; i < ids.length; i++) {
			var f = ids[i];
			var err = this.fieldEditors[f].validate();
			if (err) {
				if (!validationErrors) {
					validationErrors = this.fieldEditors[f].domElement;
					// find the first visible parent of the element
					while(!validationErrors.is(':visible')) {
						validationErrors = validationErrors.parent();
					}
				}
				this.fieldEditors[f].showValidationErrors(err);
			} else {
				this.fieldEditors[f].showValidationErrors(null);
			}
			data[f] = this.fieldEditors[f].getValue();
		}

		if (validationErrors) {
			$.scrollTo(validationErrors);
			$.scrollTo('-=100px');
			callback(false);
			return;
		}

		var that = this;
		$.post(this.saveUrl, {
			'data': $.JSON.encode(data),
			'type': this.contactType,
			'id': this.contact_id != null ? this.contact_id : 0
		}, function(newData) {
			if (newData.status != 'ok') {
				throw new Exception('AJAX error: '+$.JSON.encode(newData));
			}

			newData = newData.data;

			if (newData.history) {
				$.wa.history.updateHistory(newData.history);
			}

			if (newData.data && newData.data.top) {
				var html = '';
				for (var j = 0; j < newData.data.top.length; j++) {
					var f = newData.data.top[j];
					var icon = f.id == 'im' ? '' : '<i class="icon16 ' + f.id + '"></i>';
					html += '<li>' + icon + f.value + '</li>';
				}
				$("#contact-info-top").html(html);
				delete newData.data.top;
			}

			if ($.wa.contactEditor.contactType == 'company' && newData.data.name) {
				delete newData.data.name;
			}

			if($.wa.contactEditor.contact_id != null) {
				$.wa.contactEditor.initFieldEditors(newData.data);
			}

			// hide old validation errors and show new if exist
			var validationErrors = false;
			for(var f in that.fieldEditors) {
				if (typeof newData.errors[f] != 'undefined') {
					that.fieldEditors[f].showValidationErrors(newData.errors[f]);
					if (!validationErrors) {
						validationErrors = that.fieldEditors[f].domElement;
						// find the first visible parent of the element
						while(!validationErrors.is(':visible')) {
							validationErrors = validationErrors.parent();
						}
					}
				} else if (that.fieldEditors[f].currentMode == 'edit') {
					that.fieldEditors[f].showValidationErrors(null);
				}
			}

			if (validationErrors) {
				$.scrollTo(validationErrors);
				$.scrollTo('-=100px');
			} else if ($.wa.contactEditor.contact_id && newData.data.reload) {
				window.location.reload();
				return;
			}

			if ($.wa.contactEditor.contact_id == null && !validationErrors) {
				 $.wa.contactEditor.contact_id = newData.data.id;
				 $.wa.contactEditor.justCreated = true;
			}
			callback(!validationErrors);
		}, 'json');
	},

	/** Return jQuery object representing ext selector with given options and currently selected value. */
	createExtSelect: function(options, defValue) {
		var optString = '';
		var custom = true;
		for(var i in options) {
			var selected = '';
			if (options[i] === defValue || i === defValue) {
				selected = ' selected="selected"';
				custom = false;
			}
			var v = this.htmlentities(options[i]);
			optString += '<option value="'+(typeof options.length === 'undefined' ? i : v)+'"'+selected+'>'+v+'</option>';
		}

		var input;
		if (custom) {
			optString += '<option value="%custom" selected="selected">'+$_('other')+'...</option>';
			input = '<input type="text" class="small ext">';
		} else {
			optString += '<option value="%custom">'+$_('other')+'...</option>';
			input = '<input type="text" class="small empty ext">';
		}

		var result = $('<span><select class="ext">'+optString+'</select><span>'+input+'</span></span>');
		var select = result.children('select');
		input = result.find('input');
		input.val(defValue);
		if(select.val() !== '%custom') {
			input.hide();
		}

		defValue = $_('which?');

		var inputOnBlur = function() {
			if(!input.val() && !input.hasClass('empty')) {
				input.val(defValue);
				input.addClass('empty');
			}
		}
		input.blur(inputOnBlur);
		input.focus(function() {
			if (input.hasClass('empty')) {
				input.val('');
				input.removeClass('empty');
			}
		});

		select.change(function() {
			var v = select.val();
			if (v === '%custom') {
				if (input.hasClass('empty')) {
					input.val(defValue);
				}
				input.show();
			} else {
				input.hide();
				input.addClass('empty');
				input.val(v || '');
			}
			inputOnBlur();
		});

		input[0].getExtValue = function() {
			return select.val() === '%custom' && input.hasClass('empty') ? '' : input.val();
		}
		input[0].setExtValue = function(val) {
			if (options[val]) {
				select.val(val);
			} else {
				select.val('%custom');
			}
			input.val(val);
		}

		return result;
	},

	/** Create and return JQuery object with buttons to save given fields.
	  * @param fieldIds array of field ids
	  * @param saveCallback function save handler. One boolean parameter: true if success, false if validation errors occured
	  * @param cancelCallback function cancel button handler. If not specified, then saveCallback() is called with no parameter. */
	inplaceEditorButtons: function(fieldIds, saveCallback, cancelCallback) {
		var buttons = $('<div class="field buttons"><div class="value submit"><em class="errormsg" id="validation-notice"></em></div></div>');

		//
		// Save button and save on enter in input fields
		//
		var saveHandler = function() {
			buttons.find('.loading').show();
			$.wa.contactEditor.saveFields(fieldIds, function(p) {
				buttons.find('.loading').hide();
				saveCallback(p);
			});
			return false;
		};

		// refresh delegated event that submits form when user clicks enter
		var inputs_handler = $('#contact-info-block.edit-mode input[type="text"]', $('#c-core')[0]);
		inputs_handler.die('keyup');
		inputs_handler.live('keyup', function(event) {
			if(event.keyCode == 13){
				saveHandler();
			}
		});
		var saveBtn = $('<input type="submit" class="button green" value="'+$_('Save')+'" />').click(saveHandler);

		//
		// Cancel link
		//
		var that = this;
		var cancelBtn = $('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click(function(e) {
			buttons.find('.loading').hide();
			if (typeof cancelCallback != 'function') {
				saveCallback();
			} else {
				cancelCallback();
			}
			// remove topmost validation errors
			that.fieldEditors.name.showValidationErrors(null);
			$.scrollTo(0);
			return false;
		});
		buttons.children('div.value.submit')
			.append(saveBtn)
			.append(' '+$_('or')+' ')
			.append(cancelBtn)
			.append($('<i class="icon16 loading" style="margin-left: 16px; display: none;"></i>'));
		return buttons;
	},

	/** Utility function for common name => value wrapper.
	  * @param value	string|JQuery string to place in Value column, or a jquery collection of .value divs (possibly wrapped by .multifield-subfields)
	  * @param name	 string string to place in Name column (defaults to '')
	  * @param cssClass string optional CSS class to add to wrapper (defaults to none)
	  * @return resulting HTML
	  */
	wrapper: function(value, name, cssClass) {
		cssClass = (typeof cssClass != 'undefined') && cssClass ? ' '+cssClass : '';
		var result = $('<div class="field'+cssClass+'"></div>');

		if ((typeof name != 'undefined') && name) {
			result.append('<div class="name">'+name+'</div>');
		}

		if (typeof value != 'object' || !(value instanceof jQuery) || value.find('div.value').size() <= 0) {
			value = $('<div class="value"></div>').append(value);
		}
		result.append(value);
		return result;
	},

	/** Convert html special characters to entities. */
	htmlentities: function(s){
		var div = document.createElement('div');
		var text = document.createTextNode(s);
		div.appendChild(text);
		return div.innerHTML;
	}
}; // end of $.wa.contactEditor

// EOF
