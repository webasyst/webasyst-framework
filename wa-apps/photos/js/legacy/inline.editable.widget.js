(function($) {
    $.fn.inlineEditable = function(options, ext)
    {
        var binded = false;

        if (typeof options == 'string') {
            if (options == 'setOption') {
                var settings = this.data('inlineEditableSettings') || {};
                $.extend(true, settings, ext);
                if (typeof ext.hold !== 'undefined' && typeof ext.hold !== 'function') {
                    settings.hold = _scalarToFunc(settings.hold);
                }
                this.data('inlineEditableSettings', settings);
            }
            return this;        // means that widget is installed already
        }
        var prev_settings = this.data('inlineEditableSettings');   // prev-old settings
        this.data('inlineEditableSettings', $.extend({
            inputType: 'text',
            size: {
                height: null,
                width: null
            },
            minSize: {
                height: null,
                width: null
            },
            maxSize: {
                height: null,
                width: null
            },
            editLink: null,
            editOnItself: true,
            placeholder: null,
            makeReadableBy: ['blur', 'enter', 'esc'],        // available 'blur', 'enter', 'esc', function
            updateBy: ['ctrl+enter','alt+enter'],
            beforeBackReadable: function() {},
            afterBackReadable: function() {},
            beforeMakeEditable: function() {},
            placeholderClass: 'hint',
            truncate: false,
            hold: false,
            html: false,
            allowEmpty: false
        }, prev_settings, options || {}));

        var settings = this.data('inlineEditableSettings'),
            self = this,
            mode = 'read',                            // read|edit
            text = '';                                // previous text

        if (typeof settings.hold !== 'function') {
            settings.hold = _scalarToFunc(settings.hold);
        }
        init.call(this);

        function init() {
            if (this.data('inited')) {    // has initialized already. Don't initialize again
                return;
            }
            if (settings.truncate && typeof settings.truncate == 'boolean') {
                settings.truncate = 255; // default value of truncate
            }
            if (settings.truncate) {
                var text = !settings.placeholder || settings.placeholder !== this.text() ? this.text() : '';
                this.data('real_text', text);       // here real text. Non truncated text
                this.text(text.length < settings.truncate - 3 ? text : text.substr(0, settings.truncate - 3) + '...');
            }
            if (settings.placeholder) {
                setPlaceholder.call(this);
            }
            if (settings.editLink) {
                $(settings.editLink).click(function() {
                    if (settings.hold.call(self)) {
                        return;
                    }
                    if (mode != 'edit') {
                        makeEditable.call(self.get(0));
                    }
                });
            }
            if (settings.editOnItself) {
                this.click(function() {
                    if (settings.hold.call(self)) {
                        return;
                    }
                    if (mode != 'edit') {
                        makeEditable.call(this);
                    }
                });
            }
            this.bind('editable', function() {
                if (settings.hold.call(self)) {
                    return;
                }
                if (mode != 'edit') {
                    makeEditable.call(this);
                }
            });
            this.bind('placeholder', function(e, s) {
                if (s && settings.placeholder) {
                    setPlaceholder.call(this);
                }
                if (!s) {
                    $(this).removeClass(settings.placeholderClass);
                }
            });
            this.data('inited', true);
        }

        function setPlaceholder()
        {
            var text = getText($(this));
            if (!text) {
                $(this).addClass(settings.placeholderClass).text(settings.placeholder);
            }
        }

        function unsetPlaceholder()
        {
            var text = getText($(this));
            if (text == settings.placeholder) {
                $(this).text('').removeClass(settings.placeholderClass);
            }
        }

        function backPlaceholder(input)
        {
            if (!$(input).val()) {
                $(this).text(settings.placeholder).addClass(settings.placeholderClass);
                return true;
            }
            return false;
        }

        function makeEditable()
        {
            mode = 'edit';
            this.id = this.id || ('' + Math.random()).slice(2);

            var input_id = this.id + '-input',
                input = $('#' + input_id);

            if (!input.length) {
                self.after(inputHtml(input_id));
                input = $('#' + input_id);
            }
            setSize(input, self);
            if (settings.placeholder) {
                if (getText($(this)) == settings.placeholder) {
                    unsetPlaceholder.call(self);
                }
            }

            // fire before-callback
            settings.beforeMakeEditable.call(this, input);

            text = settings.truncate ? self.data('real_text') : getText(self);      // save text - for restoring if need
            input.val(text).show().focus();
            self.hide();
            $(settings.editLink).hide();

            if (!binded) {        // bind event handler only once
                var key_codes = [];
                var save_key_codes = [];
                for (var i = 0, n = settings.makeReadableBy.length; i < n; ++i) {
                    var item = settings.makeReadableBy[i];
                    if (item == 'blur') {
                        input.blur(function() {
                            if (mode != 'read') {
                                makeReadable.call(this);
                            }
                        });
                    }
                    if (item == 'enter') {
                        key_codes.push(13);
                    }
                    if (item == 'esc') {
                        key_codes.push(27);
                    }
                }
                for (var i = 0, n = settings.updateBy.length; i < n; ++i) {
                    var item = settings.updateBy[i];
                    if (item == 'alt+enter') {
                        save_key_codes.push({'ctrl':false,'alt':true,'shift':false,'key':13});
                    }
                    if (item == 'ctrl+enter') {
                        save_key_codes.push({'ctrl':true,'alt':false,'shift':false,'key':13});
                    }
                    if (item == 'ctrl+s') {
                        save_key_codes.push({'ctrl':true,'alt':false,'shift':false,'key':17});
                    }
                }
                if (key_codes.length) {
                    (function(key_codes) {
                        input.keydown(function(e) {
                            if (~key_codes.indexOf(e.keyCode) && !e.ctrlKey && !e.altKey && !e.shiftKey) {
                                if (mode != 'read') {
                                    makeReadable.call(this, e.keyCode == 27 ? text : null);
                                }
                            }
                            if(save_key_codes.length) {
                                for(var i in save_key_codes) {
                                    var k = save_key_codes[i];
                                    if ((e.keyCode == k.key) && (e.ctrlKey == k.ctrl) && (e.altKey == k.alt) && (e.shiftKey == k.shift) ){
                                        self.trigger('readable');
                                        break;
                                    }
                                }
                            }
                        });
                    })(key_codes);
                }
                self.bind('readable', function(e, disable_handlers) {
                    if (mode != 'read') {
                        var input_id = this.id + '-input';
                        var input = $('#' + input_id);
                        makeReadable.call(input, input.val(), disable_handlers);
                    }
                });
                binded = true;
            }
        }

        function makeReadable(new_text, disable_handlers)
        {
            disable_handlers = typeof disable_handlers === 'undefined' ? false : true;
            if (new_text != undefined && new_text != null) {
                $(this).val(new_text);
            } else {
                new_text = $(this).val();
            }
            if (disable_handlers === false) {
                if (settings.beforeBackReadable.call(self.get(0), this, {
                    changed: new_text != text,
                    old_text: text,
                    new_text: new_text
                }) === false) 
                {
                    return;
                }
            }
            mode = 'read';
            // this - input
            if (!settings.placeholder || !backPlaceholder.call(self, this)) {
                if (settings.allowEmpty || new_text) {
                    if (settings.truncate) {
                        self.data('real_text', new_text);       // here real text. Non truncated text
                        self.text(new_text.length < settings.truncate - 3 ? new_text : new_text.substr(0, settings.truncate - 3) + '...' );
                    } else {
                        setText(self, new_text);
                    }
                    if (!new_text) {
                        setPlaceholder.call(this);
                    }
                }
            }
            self.show();
            $(this).hide();
            $(settings.editLink).show();
            // fire after-callback
            if (disable_handlers === false) {
                settings.afterBackReadable.call(self.get(0), this, {
                   changed: new_text != text,
                   old_text: text
                });
            }
        }

        function inputHtml(id)
        {
            switch (settings.inputType) {
                case 'textarea':
                    return '<textarea id="' + id + '" style="display:none;"></textarea>';
                case 'input':
                default:
                    return '<input type="text" id="' + id + '" style="display:none;">';
            }
        }

        function setSize(dst, src)
        {
            var height = settings.size.height || src.height(),
                width = settings.size.width || src.width() * 1.5;

            height = settings.minSize.height && height < settings.minSize.height ? settings.minSize.height : height;
            width = settings.minSize.width && width < settings.minSize.width ? settings.minSize.width : width;

            height = settings.maxSize.height && height > settings.maxSize.height ? settings.maxSize.height : height;
            width = settings.maxSize.width && width > settings.maxSize.width ? settings.maxSize.width : width;

            dst.height(height);
            dst.width(width);
        }

        function _scalarToFunc(scalar) {
            return function() {
                return scalar;
            };
        }

        function getText(item)
        {
            return !settings.html ? item.text() : item.html();
        }

        function setText(item, text)
        {
            return !settings.html ? item.text(text) : item.html(text);
        }

        return this;

    };
})(jQuery);