$.wa.contactEditorFactory = function(options) {
    
    // OPTIONS
    
    options = $.extend({
        contact_id: null,
        current_user_id: null,
        contactType: 'person', // person|company
        baseFieldType: null, // defined in fieldTypes.js
        saveUrl: '?module=contacts&action=save', // URL to send data when saving contact
        el: '#contact-info-block',
        with_inplace_buttons: true,
        update_title: true
    }, options);
    
    
    // INSTANCE OF EDITOR
    
    var contactEditor = $.extend({

        fields: {},
        
        fieldsOrder: [],

        /** Editor factory templates, filled below */
        factoryTypes: {},


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
        initFactories: function(fields, fieldsOrder) {
            this.fields = fields;
            this.fieldsOrder = fieldsOrder,
            this.editorFactories = {};
            this.fieldEditors = {};
            for (var i = 0; i < fieldsOrder.length; i += 1) {
                var fldId = fieldsOrder[i];
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
            for (var i = 0; i < this.fieldsOrder.length; i += 1) {
                var f = this.fieldsOrder[i];
                if (typeof this.fieldEditors[f] == 'undefined') {
                    this.fieldEditors[f] = this.editorFactories[f].createEditor(this.contactType, f);
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
            for (var i = 0; i < this.fieldsOrder.length; i += 1) {
                var f = this.fieldsOrder[i];
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
                    this.fieldEditors[f] = this.editorFactories[f].createEditor(this.contactType);
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
            var el = $(this.el);
            var self = this;
            if (init) {
                el.html('');
                el.removeClass('edit-mode');
                el.removeClass('view-mode');
                $(el).add('#contact-info-top').off('click.map', '.map-link').on('click.map', '.map-link', function() {
                    var i = $(this).parent().data('subfield-index');
                    if (i !== undefined) {
                        var fieldValue = self.fieldEditors.address.fieldValue;
                        self.geocodeAddress(fieldValue, i);
                    }
                });
            }
            if (mode == 'edit' && el.hasClass('edit-mode')) {
                return;
            }
            if (mode == 'view' && el.hasClass('view-mode')) {
                return;
            }

            $('#tc-contact').trigger('before_switch_mode');

            // Remove all buttons
            el.find('div.field.buttons').remove();

            var fieldsToUpdate = [];
            for (var i = 0; i < this.fieldsOrder.length; i += 1) {
                var f = this.fieldsOrder[i];
                fieldsToUpdate.push(f);
                var fld = this.fieldEditors[f].setMode(mode);
                $(this).trigger('set_mode', [{
                    mode: mode,
                    el: el,
                    field_id: f,
                    field: this.fieldEditors[f]
                }]);
                if (init) {
                    el.append(fld);
                }
            }

            var that = this;
            // Editor buttons
            if(mode == 'edit') {
                
                el.find('.subname').wrapAll('<div class="subname-wrapper"></div>');
                el.find('.jobtitle-company').wrapAll('<div class="jobtitle-company-wrapper"></div>');
                
                if (this.with_inplace_buttons) {
                    var buttons = this.inplaceEditorButtons(fieldsToUpdate, function(noValidationErrors) {
                        if (typeof noValidationErrors != 'undefined' && !noValidationErrors) {
                            return false;
                        }

                        if (typeof that.justCreated != 'undefined' && that.justCreated) {
                            // new contact created
                            var c = $('#sb-all-contacts-li .count');
                            c.text(1+parseInt(c.text()));
                            
                            // Redirect to profile just created
                            $.wa.setHash('/contact/' + that.contact_id);
                            return false;
                        }

                        that.switchMode('view');
                        $.scrollTo(0);
                        return false;
                    }, function() {
                        if (that.contact_id == null) {
                            $.wa.back();
                            return;
                        }
                        that.switchMode('view');
                        $.scrollTo(0);
                    });
                    if (that.contact_id === null) {
                        buttons.find('.cancel, .or').remove();
                    }
                    el.append(buttons);
                    el.removeClass('view-mode');
                    el.addClass('edit-mode');
                    $('#edit-contact-double').hide();

                    el.find('.buttons').sticky({
                        fixed_css: { bottom: 0, background: '#fff', width: '100%', margin: '0' },
                        fixed_class: 'sticky-bottom',
                        isStaticVisible: function(e) {
                            var win = $(window);
                            var element_top = e.element.offset().top;
                            var window_bottom = win.scrollTop() + win.height();
                            return window_bottom > element_top;
                        }
                    });
                    el.find('.sticky-bottom .cancel').click(function() {
                        el.find('.buttons:not(.sticky-bottom) .cancel').click();
                        return false;
                    });
                    el.find('.sticky-bottom :submit').click(function() {
                        el.find('.buttons:not(.sticky-bottom) :submit').click();
                        return false;
                    });
                }
            } else {
                el.removeClass('edit-mode');
                el.addClass('view-mode');
                $('#edit-contact-double').show();
                if (el.find('.subname-wrapper').length) {
                    el.find('.subname').unwrap();
                }
                if (el.find('.jobtitle-company-wrapper').length) {
                    el.find('.jobtitle-company').unwrap();
                }
            }

            $('#tc-contact').trigger('after_switch_mode', [mode, this]);

        },

        /** Save all modified editors, reload data from php and switch back to view mode. */
        saveFields: function(ids, callback) {
            if (!ids) {
                ids = [];
                for (var k in this.fields) {
                    if (this.fields.hasOwnProperty(k)) {
                        ids.push(k);
                    }
                }
            }
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
            var save = function(with_gecoding) {
                with_gecoding = with_gecoding === undefined ? true : with_gecoding;
                $.post(that.saveUrl, {
                    'data': $.JSON.encode(data),
                    'type': that.contactType,
                    'id': that.contact_id != null ? that.contact_id : 0
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
                            var icon = f.id != 'im' ? (f.icon ? '<i class="icon16 ' + f.id + '"></i>' : '') : '';
                            html += '<li>' + icon + f.value + '</li>';
                        }
                        $("#contact-info-top").html(html);
                        delete newData.data.top;
                    }

                    if(that.contact_id != null) {
                        that.initFieldEditors(newData.data);
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
                    } else if (that.contact_id && newData.data.reload) {
                        window.location.reload();
                        return;
                    }

                    if (!validationErrors) {
                        
                        if (that.contact_id === null) {
                            that.justCreated = true;
                        }
                        
                        that.contact_id = newData.data.id;
                        if (with_gecoding) {
                            // geocoding
                            var last_geocoding = $.storage.get('contacts/last_geocoding') || 0;
                            if ((new Date()).getTime() - last_geocoding > 3600) {
                                $.storage.del('contacts/last_geocoding');
                                var address = newData.data.address;
                                if (!$.isEmptyObject(address)) {
                                    var requests = [];
                                    var indexes = [];
                                    for (var i = 0; i < address.length; i += 1) {
                                        requests.push(that.sendGeocodeRequest(address[i].for_map));
                                        indexes.push(i);
                                    }
                                    var fn = function(response, i) {
                                        if (response.status === "OK") {
                                            var lat = response.results[0].geometry.location.lat || '';
                                            var lng = response.results[0].geometry.location.lng || '';
                                            data['address'][i]['value'].lat = lat;
                                            data['address'][i]['value'].lng = lng;
                                        } else if (response.status === "OVER_QUERY_LIMIT") {
                                            $.storage.set('contacts/last_geocoding', (new Date()).getTime() / 1000);
                                        }
                                    };
                                    $.when.apply($, requests).then(function() {
                                        if (requests.length <= 1 && arguments[1] === 'success') {
                                            fn(arguments[0], indexes[0]);
                                        } else {
                                            for (var i = 0; i < arguments.length; i += 1) {
                                                if (arguments[i][1] === 'success') {
                                                    fn(arguments[i][0], indexes[i]);
                                                }
                                            }
                                        }
                                        save.call(that, false);
                                    });
                                } else {
                                    callback(!validationErrors);
                                }
                            } else {
                                callback(!validationErrors);
                            }
                        } else {
                            callback(!validationErrors);
                        }
                         
                    } else {
                        callback(!validationErrors);
                    }
                }, 'json');
            };
            
            save();
            
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
            };
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
            };
            input[0].setExtValue = function(val) {
                if (options[val]) {
                    select.val(val);
                } else {
                    select.val('%custom');
                }
                input.val(val);
            };

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
            var that = this;
            var saveHandler = function() {
                $('.buttons .loading').show();
                that.saveFields(fieldIds, function(p) {
                    $('.buttons .loading').hide();
                    saveCallback(p);
                });
                return false;
            };

            // refresh delegated event that submits form when user clicks enter
            var inputs_handler = $('#contact-info-block.edit-mode input[type="text"]', $('#c-core')[0]);
            inputs_handler.die('keyup');
            inputs_handler.live('keyup', function(event) {
                if(event.keyCode == 13 && (!$(event.currentTarget).data('autocomplete') || !$(event.currentTarget).data('contact_id'))){
                    saveHandler();
                }
            });
            var saveBtn = $('<input type="submit" class="button green" value="'+$_('Save')+'" />').click(saveHandler);

            //
            // Cancel link
            //
            var that = this;
            var cancelBtn = $('<a href="javascript:void(0)" class="cancel">'+$_('cancel')+'</a>').click(function(e) {
                $('.buttons .loading').hide();
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
                .append('<span class="or"> '+$_('or')+' </span>')
                .append(cancelBtn)
                .append($('<i class="icon16 loading" style="margin-left: 16px; display: none;"></i>'));

            return buttons;
        },
                
        destroy: function() {
            $(this.el).html('');
        },
                
        // UTILITIES

        /** Utility function for common name => value wrapper.
          * @param value    string|JQuery string to place in Value column, or a jquery collection of .value divs (possibly wrapped by .multifield-subfields)
          * @param name     string string to place in Name column (defaults to '')
          * @param cssClass string optional CSS class to add to wrapper (defaults to none)
          * @return resulting HTML
          */
                
        wrapper: function(value, name, cssClass) {
            cssClass = (typeof cssClass != 'undefined') && cssClass ? ' '+cssClass : '';
            var result = $('<div class="field'+cssClass+'"></div>');

            if ((typeof name != 'undefined') && name) {
                result.append('<div class="name">' + $.wa.encodeHTML(name) +'</div>');
            }

            if (typeof value != 'object' || !(value instanceof jQuery) || value.find('div.value').size() <= 0) {
                value = $('<div class="value"></div>').append(value);
            }
            result.append(value);
            return result;
        },
                
        setOptions: function(opts) {
            this.options = $.extend(options, this.options || {}, opts);
        },

            /** Convert html special characters to entities. */
        htmlentities: function(s){
            var div = document.createElement('div');
            var text = document.createTextNode(s);
            div.appendChild(text);
            return div.innerHTML;
        },
                
        addressToString: function(address) {
            var value = [];
            var order = [ 'street', 'city', 'country', 'region', 'zip' ];
            address = $.extend({}, address || {});
            for (var i = 0; i < order.length; i += 1) {
                value.push(address[order[i]] || '');
                delete address[order[i]];
            }
            for (var k in address) {
                if (address.hasOwnProperty(k) && k !== 'lat' && k != 'lng') {
                    value.push(address[k]);
                }
            }
            return value.join(',');
        },
                
        geocodeAddress: function(fieldValue, i) {
            var self = this;
            if (!fieldValue[i].data.lat || !fieldValue[i].data.lng) {
                this.sendGeocodeRequest(fieldValue[i].for_map || fieldValue[i].value, function(r) {
                    fieldValue[i].data.lat = r.lat;
                    fieldValue[i].data.lng = r.lng;
                    $.post('?module=contacts&action=saveGeocoords', {
                        id: self.contact_id,
                        lat: r.lat,
                        lng: r.lng,
                        sort: i
                    });
                });
            }
        },
                
        sendGeocodeRequest: function(value, fn, force) {
            var address = [];
            if (typeof value === 'string') {
                address.push(value);
            } else {
                if (value.with_street && value.without_street && value.with_street === value.without_street) {
                    address.push(value.with_street);
                } else {
                    if (value.with_street) {
                        address.push(value.with_street);
                    }
//                    if (value.without_street) {
//                        address.push(value.without_street);
//                    }
                }
            }
            if (address.length <= 1 && force === undefined) {
                force = true;
            }
            
            var df = $.Deferred();
            

      //Uncomment for test
//            console.log('//maps.googleapis.com/maps/api/geocode/json');
//            df.resolve([{
//                    status: 'OK',
//                    results: [
//                        {
//                            geometry: {
//                                location: {
//                                    lat: 60.2479758,
//                                    lng: 90.1104176
//                                }
//                            }
//                        }
//                    ]
//            }, 'success']);
//            return df;
            
            
            var self = this;
            $.ajax({
                url: '//maps.googleapis.com/maps/api/geocode/json',
                data: {
                    sensor: false,
                    address: address[0]
                },
                dataType: 'json',
                success: function(response) {
                    var lat, lng;
                    if (response) {
                        if (response.status === "OK") {
                            var n = response.results.length;
                            var r = false;
                            for (var i = 0; i < n; i += 1) {
                                if (!response.results[i].partial_match) {   // address correct, geocoding without errors
                                    lat = response.results[i].geometry.location.lat || '';
                                    lng = response.results[i].geometry.location.lng || '';
                                    if (fn instanceof Function) {
                                        fn({ lat: lat, lng: lng });
                                    }
                                    r = true;
                                    break;
                                }
                            }
                            if (!r) {
                                if (force) {
                                    lat = response.results[0].geometry.location.lat || '';
                                    lng = response.results[0].geometry.location.lng || '';
                                    if (fn instanceof Function) {
                                        fn({ lat: lat, lng: lng });
                                    }
                                    df.resolve([response, 'success']);
                                } else if (address[1]) {
                                    self.sendGeocodeRequest(address[1], fn, true).always(function(r) {
                                        df.resolve(r);
                                    });
                                } else {
                                    df.resolve([response, 'success']);
                                }
                            } else {
                                df.resolve([response, 'success']);
                            }
                        } else {
                            df.resolve([response, 'success']);
                        }
                    } else {
                        df.resolve([{}, 'error']);
                    }
                },
                error: function(response) {
                    df.resolve([response, 'error']);
                }
            });
            return df;
        },
        
        /** Helper to switch to particular tab in a tab set. */
        switchToTab: function(tab, onto, onfrom, tabContent) {
            if (typeof(tab) == 'string') {
                if (tab.substr(0, 2) == 't-') {
                    tab = '#'+tab;
                } else if (tab[0] != '#') {
                    tab = "#t-" + tab;
                }
                tab = $(tab);
            } else {
                tab = $(tab);
            }
            if (tab.size() <= 0 || tab.hasClass('selected')) {
                return;
            }

            if (!tabContent) {
                var id = tab.attr('id');
                if (!id || id.substr(0, 2) != 't-') {
                    return;
                }
                tabContent = $('#tc-'+id.substr(2));
            }

            if (onfrom) {
                var oldTab = tab.parent().children('.selected');
                if (oldTab.size() > 0) {
                    oldTab.each(function(k, v) {
                        onfrom.call(v);
                    });
                }
            }

            var doSwitch = function() {
                tab.parent().find('li.selected').removeClass('selected');
                tab.removeClass('hidden').css('display', '').addClass('selected');
                tabContent.siblings('.tab-content').addClass('hidden');
                tabContent.removeClass('hidden');
                if (onto) {
                    onto.call(tab[0]);
                }
                $('#c-info-tabs').trigger('after_switch_tab', [tab]);
            };

            $('#c-info-tabs').trigger('before_switch_tab', [tab]);

            // sliding animation (jquery.effects.core.min.js required)
            if ($.effects && $.effects.slideDIB && !tab.is(':visible')) {
                $.wa.controller.loadTabSlidingAnimation();
                tab.hide().removeClass('hidden').show('slideDIB', {direction: 'down'}, 300, function() {
                    doSwitch();
                });
            } else {
                doSwitch();
            }
        },
                
        /** 
        * Helper to append appropriate events to a checkbox list. 
        * */
        initCheckboxList: function(ul) {
            ul = $(ul);

            var updateStatus = function(i, cb) {
                var self = $(cb || this);
                if (self.is(':checked')) {
                    self.parent().addClass('highlighted');
                } else {
                    self.parent().removeClass('highlighted');
                }
            };

            ul.find('input[type="checkbox"]')
                .click(updateStatus)
                .each(updateStatus);
            return ul;
        },
                
        /** Load content from url and put it into elem. Params are passed to url as get parameters. */
        load: function (elem, url, params, beforeLoadCallback, afterLoadCallback) {
            var r = Math.random();
            var that = this;
            that.random = r;
            $.get(url, params, function (response) {
                if (that.random == r) {
                    if (beforeLoadCallback) {
                        beforeLoadCallback.call(that);
                    }
                    $(window).trigger('wa_before_load', [elem, url, params, response]);
                    $(elem).html(response);
                    $(window).trigger('wa_after_load', [elem, url, params, response]);
                    if (afterLoadCallback) {
                        afterLoadCallback.call(that);
                    }
                }
            });
        }
                
    }, options);
    $.wa.fieldTypesFactory(contactEditor);
    return contactEditor;
};

// one global instance of contact editor exists always
$.wa.contactEditor = $.wa.contactEditorFactory();