var WaLoginAbstractForm = ( function($) {

    // Abstract class
    var WaLoginAbstractForm = function () {};
    var Self = WaLoginAbstractForm;
    Self.className = 'WaLoginAbstractForm';

    /**
     * [
     * @param var1
     * @param var2
     * @param var3
     * ...
     * ]
     */
    Self.def = function (var1, var2) {
        for (var i = 0, len = arguments.length; i < len; i++) {
            if (typeof arguments[i] !== 'undefined') {
                return arguments[i];
            }
        }
        return undefined;
    };

    var def = Self.def;

    /**
     * "inherit"
     * @param Child
     * @param Parent
     */
    Self.inherit = function (Child, Parent) {
        Child.prototype = Object.create(Parent.prototype);
        Child.prototype.constructor = Child;
    };

    Self.prototype.init = function(options) {
        var that = this;
        that.initVars(options);
        that.initSubmit();
        that.initErrorsAutoCleaner();
        that.showErrors(that.errors);
        that.setFocus();
        that.initCaptcha();
    };

    Self.prototype.initVars = function (options) {
        var that = this;

        options = options || {};

        that.$wrapper = def(that.$wrapper, options.$wrapper, $());

        // Form could be not just <FORM> but also <DIV>
        // It's need for that environment where we CAN'T use <FORM>
        // Eg. Old Shop Checkout, where login form injected inside of checkout form
        if (!that.$form) {
            that.$form = that.$wrapper.find('.js-wa-form-item');
            if (!that.$form.length) {
                that.$form = that.$wrapper.find('form');
            }
            if (!that.$form.length) {
                that.$form = $();
            }
        }

        that.namespace = def(that.namespace, options.namespace, '');

        that.$templates = def(that.$templates, options.$templates, {});
        that.$templates = $.extend({
                error_msg: $('<em class="wa-error-msg"></em>'),
                info_msg: $('<div class="wa-info-msg"></div>')
            },
            that.$templates
        );

        that.classes = def(that.classes, options.classes, {});
        that.classes = $.extend({

            error_input: 'wa-error',
            error_msg: 'wa-error-msg',
            uncaught_errors: 'wa-uncaught-errors',

            messages: 'wa-info-messages',
            messages_wrapper: 'wa-info-messages-wrapper',
            message_msg: 'wa-info-msg',

            field: 'field'

        }, that.classes);

        that.errors = def(that.errors, options.errors, {});
        that.is_errors = !$.isEmptyObject(that.errors);

        that.locale = def(that.locale, options.locale, {});
        that.js_validate = def(that.js_validate, options.js_validate, true);
        that.is_json_mode = def(that.is_json_mode, options.is_json_mode);
        that.className = def(that.className, Self.className);

        that.$captcha = def(that.$captcha, that.$wrapper.find('.wa-captcha-field'));

        // Default value is TRUE
        that.need_redirects = options.need_redirects !== undefined ? !!options.need_redirects : true;

        that.env = '';
        that.form_type = '';
    };

    Self.prototype.getFormItem = function () {
        return this.$form;
    };

    Self.prototype.getFormAction = function () {
        var that = this;
        if (that.$form.is('form')) {
            return that.$form.attr('action');
        } else {
            return that.$form.data('action');
        }
    };

    Self.prototype.getSerializedFormData = function () {
        var that = this,
            $form = that.getFormItem();
        if ($form.is('form')) {
            return $form.serializeArray();
        }

        // Because some outer js can un-disable inputs
        // We need to ENSURE turned off block not has :input disabled

        $form.find('[data-turn-off=1]').find(":input").attr('disabled', true);

        return $form.find(':input:not(:disabled)').serializeArray();
    };

    Self.prototype.beforeSubmit = function () {
        var that = this,
            $form = that.getFormItem();

        var getCsrfToken = function() {
            var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
            if (!matches || !matches[1]) {
                return '';
            }
            return decodeURIComponent(matches[1]);
        };

        // Update csrf token right before submit
        // in case it changed via another browser tab
        var $csrf_hidden_field = $form.find('[name="_csrf"]');
        $csrf_hidden_field.val(getCsrfToken);
    };

    Self.prototype.setFocus = function () {
        return;
    };

    Self.prototype.triggerEvent = function (event_name) {
        var that = this,
            context = {
                env: that.env,
                form_type: that.form_type,
                form_wrapper_id: that.$wrapper.attr('id')
            },
            args = Array.prototype.slice.call(arguments),
            args = args.slice(1);
        // Trigger a certain event with its arguments and with 'context' object at the and of arguments list
        that.$wrapper.trigger(event_name, args.concat( [ context ] ));
    };

    Self.prototype.initCaptcha = function () {
        var that = this,
            $wrapper = that.$wrapper;

        // If recaptcha presented and loaded
        if ($wrapper.find('.wa-captcha-field').length) {
            $(window).one('wa_recaptcha_loaded wa_captcha_loaded', function () {
                that.triggerEvent('wa_auth_form_loaded');
                that.triggerEvent('wa_auth_form_change_view');
            });
        } else {
            that.triggerEvent('wa_auth_form_loaded');
        }
    };


    /**
     * Hide block and temporary disabled all inputs inside of this block
     * BUT buttons are move to END of the FORM
     *
     * Temporary disabling of inputs need to prevent they influence to server submit-post processing
     *
     * Eg.
     *  If there is confirmation_code in form
     *  Then on submit post SERVER by arrangement protocol MUST verify that confirmation code
     *
     *  If there is NO confirmation_code in form
     *  Then on submit post SERVER by arrangement protocol MUST generate confirmation code and send by verification channel
     *
     * Why move buttons to END to the FORM?
     *
     *   When in form one button hidden and disabled (eg. button for send code) AND placed FIRST in DOM,
     *   but another button shown and enabled (eg. button for login) AND placed SECOND in DOM
     *   then ENTER key hitting can't work correctly
     *
     *
     * @see turnOnBlock
     * @param $block
     */
    Self.prototype.turnOffBlock = function ($block) {
        var that = this;

        $block.data('turnOff', 1).attr('data-turn-off', 1).hide();
        $block.find(":input").attr('disabled', true);

        // Buttons move to the end - place temporary dummy item
        $block.find("[type=button],:submit").each(function () {
            var $button = $(this),
                $old_place = $('<div class="wa-js-old-button-place"></div>'),
                $new_place = $('<div class="wa-js-new-button-place"></div>');

            $button.after($old_place);
            $old_place.data('button', $button);

            $new_place.hide();
            that.getFormItem().append($new_place);

            // move from old place to new place
            $new_place.html($button);

        });

        that.triggerEvent('wa_auth_form_change_view');
    };


    /**
     * Show block and restore from restore all back
     * @see turnOffBlock for meaning and example
     * @param $block
     */
    Self.prototype.turnOnBlock = function ($block) {
        var that = this;
        // Buttons un-detaching - re-place temporary dummy item but detached button
        $block.find('.wa-js-old-button-place').each(function () {
            var $old_place = $(this),
                $button = $old_place.data('button'),
                $new_place = $button.parent();
            $old_place.after($button);
            $old_place.remove();
            $new_place.remove();
        });

        $block.data('turnOff', '').attr('data-turn-off', '').show();
        $block.find(":input").attr('disabled', false);

        that.triggerEvent('wa_auth_form_change_view');

    };

    Self.prototype.isJsonMode = function () {
        var that = this;
        return that.is_json_mode;
    };

    /**
     * Format message DOM item ready to place it where needed
     * @param {String} message
     * @param {Boolean} escape Default (if skipped) value is TRUE
     * @param {String} name
     * @return $() - rendered in memory dom-item
     */
    Self.prototype.formatInfoMessage = function (message, escape, name) {

        // Default value for escape is TRUE
        escape = typeof escape === 'undefined' ? true : escape;

        var that = this,
            $info_msg = that.$templates.info_msg,
            $msg = $info_msg.clone();

        if (name !== undefined) {
            $msg.data('name', name).attr('data-name', name);
        }

        if (escape) {
            return $msg.text($.trim('' + message));
        } else {
            return $msg.html($.trim('' + message));
        }
    };

    Self.prototype.getInfoMessages = function (name) {
        var that = this,
            $wrapper = that.$wrapper,
            selector = '.' + that.classes.message_msg;
        if (name) {
            selector += '[data-name="' + name + '"]';
        }
        return $wrapper.find(selector);
    };

    Self.prototype.clearInfoMessages = function (name) {
        var that = this;
        that.getInfoMessages(name).remove();
        that.triggerEvent('wa_auth_form_change_view');
    };

    Self.prototype.showInfoMessages = function (all_messages) {
        var that = this,
            $wrapper = that.$wrapper,
            $messages = $wrapper.find('.' + that.classes.messages);

        $.each(all_messages || {}, function (name, messages) {
            var $input = that.getFormInput(name);
            if (typeof messages === 'string') {
                messages = [messages];
            }

            var messages_blocks = [];
            $.each(messages || [], function (code, msg) {
                var $msg = that.formatInfoMessage(msg, false, name);
                $msg.data('code', code).attr('data-code', code);
                messages_blocks.push($msg);
            });

            if ($input.length) {
                $input.after(messages_blocks);
            } else {
                $messages.show().append(messages_blocks);
            }
        });

        that.triggerEvent('wa_auth_form_change_view');
    };

    Self.prototype.clearErrors = function () {
        var that = this;
        if (!that.is_errors) {
            return;
        }

        var $wrapper = that.$wrapper;

        $wrapper.find('.' + that.classes.error_input).removeClass(that.classes.error_input);
        $wrapper.find('.' + that.classes.error_msg).not('[data-not-clear=1]').remove();

        if ($wrapper.find('.' + that.classes.uncaught_errors).find('.' + that.classes.error_msg).length <= 0) {
            $wrapper.find('.' + that.classes.uncaught_errors).hide();
        }

        that.is_errors = false;

        that.triggerEvent('wa_auth_form_change_view');
    };

    Self.prototype.getErrorTemplate = function (error_namespace, error, error_code) {
        return this.$templates.error_msg;
    };

    Self.prototype.escape = function (error) {
        var $div = $('<div>');
        $div.text($.trim('' + error));
        error = $div.text();
        $div.remove();
        return error;
    };

    Self.prototype.prepareErrorText = function (error_namespace, error, error_code) {
        return this.escape(error);
    };

    Self.prototype.prepareErrorItem = function (error_namespace, error, error_code) {
        var that = this,
            $error_msg = that.getErrorTemplate(error_namespace, error, error_code).clone(),
            $error = $error_msg.clone();
            $error
                .data('name', error_namespace)
                .data('code', error_code)
                .attr('data-name', error_namespace)
                .attr('data-code', error_code);
        $error.html(that.prepareErrorText(error_namespace, error, error_code));
        return $error;
    };

    Self.prototype.showInputError = function (name, error_items) {
        var that = this,
            $input = that.getFormInput(name);
        if (!$input.length) {
            return false;
        }
        $input.parent().append(error_items);
        $input.addClass(that.classes.error_input);
        return true;
    };

    Self.prototype.getFormInput = function(name) {
        var that = this,
            $form = that.getFormItem(),
            input_name = that.buildFormInputName(name);
        return $form.find('[name="' + input_name + '"]');
    };

    Self.prototype.buildFormInputName = function (name) {
        var that = this,
            namespace = that.namespace;
        return namespace ? namespace + '[' + name + ']' : name;
    };

    Self.prototype.getFormField = function (id) {
        var that = this,
            $wrapper = that.$wrapper;
        return $wrapper.find('.' + that.classes.field + '[data-field-id="' + id +'"]');
    };

    Self.prototype.getInfoMessageItem = function(name, code) {
        var that = this,
            $wrapper = that.$wrapper,
            $messages = $wrapper.find('.' + that.classes.message_msg + '[data-name="' + name + '"]');
        if (code !== undefined) {
            return $messages.filter('[data-code="'+ code +'"]');
        }
        return $messages;
    };

    Self.prototype.getErrorItem = function(error_namespace, error_code) {
        var that = this,
            $wrapper = that.$wrapper,
            $errors = $wrapper.find('.' + that.classes.error_msg + '[data-name="' + error_namespace + '"]');
        if (error_code !== undefined) {
            return $errors.filter('[data-code="'+ error_code +'"]');
        }
        return $errors;
    };

    Self.prototype.showUncaughtErrors = function (name, error_items, reset) {
        var that = this,
            $wrapper = that.$wrapper,
            $uncaught_errors = $wrapper.find('.' + that.classes.uncaught_errors);
        if (!$uncaught_errors.length) {
            return false;
        }
        $uncaught_errors.show();
        if (reset) {
            $uncaught_errors.html('');
        }
        $uncaught_errors.show().append(error_items);
        return true;
    };


    Self.prototype.afterShowErrors = function () {

    };

    Self.prototype.showErrors = function (all_errors) {
        var that = this;

        $.each(all_errors || {}, function (name, errors) {
            if (typeof errors === 'string') {
                errors = [errors];
            }

            var error_items = that.prepareErrorItems(name, errors);

            var res = that.showInputError(name, error_items);
            if (!res) {
                res = that.showUncaughtErrors(name, error_items, true);
            }
            if (!res && console && console.error) {
                $.each(error_items, function () {
                    var txt = "Uncaught validate error: " + $(this).text();
                    console.error(txt);
                });
            }
        });

        that.is_errors = true;
        that.afterShowErrors();
        that.triggerEvent('wa_auth_form_change_view');

    };

    Self.prototype.initSubmit = function () {
        var that = this,
            $form = that.getFormItem(),
            xhr = null;

        var handler = function (e) {
            var res_xhr = that.onSubmit(e);
            if (res_xhr) {
                xhr && xhr.abort();
                xhr = res_xhr;
            }
        };

        if ($form.is('form')) {
            $form.submit(function (e) {
                handler(e);
            });
        } else {
            // Click submit Emulate
            $form.on('click', ':submit,button', function (e) {
                var $button = $(this);
                if ($button.is(':disabled') || $button.data('ignore')) {
                    return;
                }
                e.preventDefault();
                handler(e);
            });
            // Press Enter submit emulate
            $form.on('keydown', 'input', function (e) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    // Emulate how browser works - find first button in FORM and click it
                    $form.find(':submit,button').not(':disabled').filter(':first').trigger('click');
                }
            })
        }

    };

    Self.prototype.validate = function () {
        return {};
    };

    Self.prototype.initErrorsAutoCleaner = function () {
        var that = this,
            $form = that.getFormItem();

        var contexts = {},
            captcha_input_name = that.buildFormInputName('captcha'),
            text_input_selector = ':text:not([name=' + captcha_input_name + ']),:password';

        $form.find(text_input_selector).each(function () {
            var $input = $(this),
                name = $input.attr('name'),
                val = $input.val();
            contexts[name] = {
                val: $.trim(val || ''),
                timer: null
            };
        });

        $form.on('keydown', text_input_selector, function (e) {
            if (e.keyCode == 13) {
                return;
            }
            var $input = $(this),
                name = $input.attr('name'),
                context = contexts[name] || {},
                prev_val = $.trim(context.val || ''),
                timer = context.timer || null;
            timer && clearTimeout(timer);
            context.timer = setTimeout(function () {
                var val = $.trim($input.val() || '');
                if (val !== prev_val) {
                    that.clearErrors();
                }
                context.val = val;
            }, 300);
        });

        $form.on('change', ':input', function () {
            var $input = $(this),
                name = $input.attr('name'),
                context = contexts[name] || {},
                prev_val = $.trim(context.val || ''),
                val = $.trim($input.val() || '');
            if (val !== prev_val) {
                that.clearErrors();
            }
            context.val = val;
        });
    };

    Self.prototype.onDoneSubmitHandlers = function () {
        var that = this;
        return {
            errors: function (errors, response) {
                that.showErrors(errors);
                return true;
            },
            redirect: function (url) {
                url = url || '';
                if (url.indexOf('#') !== -1 && url === window.location.href) {
                    window.location.reload();
                } else {
                    window.location.href = url;
                }
                return true;
            },
            messages: function (messages) {
                that.showInfoMessages(messages);
                return true;
            },
            rest: function (r) {
                return true;
            }
        };
    };
    
    Self.prototype.onDoneSubmit = function (r) {
        var that = this,
            handlers = that.onDoneSubmitHandlers(),
            ok = r && r.status === 'ok',
            data = (r && r.data) || {},
            messages = data.messages || {},
            errors = (r && r.errors) || {},
            stop = false;

        if (!ok) {
            stop = (handlers.errors && handlers.errors(errors, r));
            if (stop) {
                return;
            }
        }

        if (that.isRedirectResponse(r)) {
            var url = that.getRedirectUrl(r);
            stop = (handlers.redirect && handlers.redirect(url, r));
            if (stop) {
                return;
            }
        }

        if (!$.isEmptyObject(messages)) {
            stop = (handlers.messages && handlers.messages(messages, r));
            if (stop) {
                return;
            }
        }

        handlers.rest && handlers.rest(r);
    };

    Self.prototype.submit = function (options) {
        options = options || {};

        var that = this;

        that.beforeSubmit();

        that.clearErrors();

        if (that.js_validate) {
            var errors = that.validate();
            if (!$.isEmptyObject(errors)) {
                that.showErrors(errors);
                return;
            }
        }

        var $form = that.getFormItem(),
            $button = options.$button || $form.find(':submit'),
            $loading = options.$loading || $form.find('.wa-loading'),
            url = options.url || that.getFormAction(),
            data = that.getSerializedFormData();

        $loading.show();
        $button.attr('disabled', true);

        return that.jsonPost(url, data)
            .done(function (r) {

                if (!that.isRedirectResponse(r)) {
                    // On 'redirect' response
                    // DO NOT hide loading and enable button right away
                    // for UI/UX reason
                    $button.attr('disabled', false);
                    $loading.hide();
                }

                that.onDoneSubmit(r);

            })
            .fail(function () {
                $button.attr('disabled', false);
            });
    };

    Self.prototype.onSubmit = function (e) {
        var that = this;
        if (!that.isJsonMode()) {
            return;
        }
        e.preventDefault();
        return that.submit();
    };

    Self.prototype.mixinVarsInData = function (vars, data) {
        if ($.isPlainObject(data)) {
            data = $.extend(data, vars);
        } else if ($.isArray(data)) {
            $.each(vars, function (key, val) {
                data.push({
                    name: key,
                    value: val
                })
            });
        } else if (data) {
            $.each(vars, function (key, val) {
                data += '&' + key + '=' + val;
            });
        }
        return data;
    };

    Self.prototype.beforeJsonPost = function(url, data) {
        var that = this,
            vars = {
                wa_json_mode: 1,
                need_redirects: that.need_redirects ? 1 : 0
            };
        data = that.mixinVarsInData(vars, data);
        return data;
    };

    /**
     * Check type of response from json server
     * @param {Object} response
     * @returns {boolean}
     */
    Self.prototype.isRedirectResponse = function (response) {
        return this.getRedirectUrl(response) !== null;
    };

    /**
     * @param {Object} response
     * @returns {String|null} Null if not correct redirect url of it isn't presented
     */
    Self.prototype.getRedirectUrl = function (response) {
        var url = response && response.status === 'ok' && response.data && response.data.redirect_url;
        if (typeof url === 'string') {
            return url;
        } else {
            return null;
        }
    };

    Self.prototype.jsonPost = function (url, data) {
        var that = this;
        // prepare data
        data = that.beforeJsonPost(url, data);
        return $.post(url, data, 'json').always(function (r) {
            if (!that.isRedirectResponse(r)) {
                // Not need call redundant refresh request
                $('.wa-captcha-refresh').trigger('click');
            }
        });
    };

    Self.prototype.beforeErrorTimerStart = function (message, timeout, options) {
        // Override it
    };

    Self.prototype.afterErrorTimerFinish = function (message, timeout, options) {
        // Override it
    };

    /**
     * Prepare animated timeout error DOM item
     * @param {String} message message with substitute mask, see runTimeoutMessage
     * @param {Number} timeout in seconds
     * @param {Object} options
     * @param {String} [options.error_namespace]
     * @param {String|Number} [options.error_code]
     * @returns {*}
     */
    Self.prototype.prepareTimeoutErrorItem = function (message, timeout, options) {
        var that = this,
            $error = that.prepareErrorItem('timeout', message);

        options = options || {};

        that.beforeErrorTimerStart(message, timeout, options);

        // not need auto clearing on blur on input change
        $error.data('notClear', 1).attr('data-not-clear', 1);

        // Run timer
        that.runTimeoutMessage($error, {
            timeout: timeout,
            onFinish: function () {
                $error.remove();
                that.afterErrorTimerFinish(message, timeout, options);
            }
        });

        return $error;
    };

    Self.prototype.prepareErrorItems = function (error_namespace, errors) {
        var that = this,
            items = [];

        if (error_namespace === 'timeout') {
            var message = errors.message,
                timeout = errors.timeout;
            var $error = that.prepareTimeoutErrorItem(message, timeout, {
                error_namespace: error_namespace
            });
            items = [$error];
        } else {
            $.each(errors || [], function (error_code, error) {
                var $error;
                if (error_code === 'timeout') {
                    $error = that.prepareTimeoutErrorItem(error.message, error.timeout, {
                        error_namespace: error_namespace,
                        error_code: error_code
                    });
                } else {
                    $error = that.prepareErrorItem(error_namespace, error, error_code);
                }
                items.push($error);
            });
        }
        return items;
    };

    /**
     * Run timer-countdown for $message
     * Message must have <minutes>:<seconds> formatted substring inside, where <minutes> and <seconds> just some initial values
     * Example of message 'Try after 01:00'
     * @param $message
     * @param options
     *   'timeout' Timeout in seconds
     *   'onFinish' When timer is finish that callback will be call
     */
    Self.prototype.runTimeoutMessage = function ($message, options) {
        var that = this,
            ticks = options.timeout,
            msg = $.trim($message.html()),
            onFinish = options.onFinish;

        ticks = parseInt(ticks, 10);
        ticks = !isNaN(ticks) && ticks > 0 ? ticks : 60;

        onFinish = typeof onFinish === 'function' ? onFinish : null;

        if (!msg.match(/\d+:\d/)) {
            return;
        }

        that.triggerEvent('wa_auth_form_change_view');

        var timer = setInterval(function () {
            ticks -= 1;
            if (ticks <= 0) {
                clearInterval(timer);
                $message.remove();
                onFinish && onFinish();
                return;
            }

            var msg = $message.html(),
                minutes = parseInt(ticks / 60, 10),
                seconds = ticks % 60,
                minutes_str = (minutes <= 9 ? ('0' + minutes) : minutes),
                seconds_str = (seconds <= 9 ? ('0' + seconds) : seconds);
            msg = msg.replace(/\d+:\d+/, minutes_str + ':' + seconds_str);
            $message.html(msg);

        }, 1000);
    };

    return Self;

})(jQuery);
