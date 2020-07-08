/**
 * @example
 * translate['Hello world'] = 'Bonjour tout le monde';
 * alert('Hello world'.translate());
 */
var translate = {};
String.prototype.translate = function () {
    return translate[this] ? translate[this] : this;
};

/**
 * @link http://habrahabr.ru/blogs/javascript/116852/
 * @link https://github.com/theshock/console-cap
 *
 */

(function () {
    var global = this;
    var original = global.console;
    var console = global.console = {};
    console.production = false;

    if (original && !original.time) {
        original.time = function (name, reset) {
            if (!name) {
                return;
            }
            var time = new Date().getTime();
            if (!console.timeCounters)
                console.timeCounters = {};

            var key = "KEY" + name.toString();
            if (!reset && console.timeCounters[key]) {
                return;
            }
            console.timeCounters[key] = time;
        };

        original.timeEnd = function (name) {
            var time = new Date().getTime();

            if (!console.timeCounters) {
                return null;
            }

            var key = "KEY" + name.toString();
            var timeCounter = console.timeCounters[key];

            if (timeCounter) {
                var diff = time - timeCounter;
                var label = name + ": " + diff + "ms";
                console.info(label);
                delete console.timeCounters[key];
            }
            return diff;
        };
    }

    var methods = ['assert', 'count', 'debug', 'dir', 'dirxml', 'error', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log', 'markTimeline', 'profile',
        'profileEnd', 'table', 'time', 'timeEnd', 'trace', 'warn'];

    for (var i = methods.length; i--;) {
        (function (methodName) {
            console[methodName] = function () {
                if (original && (methodName in original) && !console.production) {
                    try {
                        original[methodName].apply(original, arguments);
                    } catch (e) {
                        alert(arguments);
                    }
                }
            };
        })(methods[i]);
    }
})();


/**
 * @typedef {object} installerState
 * @property {string} stage_status,
 * @property {number} timestamp
 */
/**
 * @typedef {object} installerStateData
 * @property {installerState[]} state,
 * @property {installerState} current_state
 */


(function ($) {
    $.installer = {
        options: {
            cache_url: '?module=settings&action=clearCache',
            redirect_url: null,
            redirect_timeout: 3000, /*ms*/
            updateStateInterval: 2000, /* ms */
            updateStateErrorInterval: 6000, /* ms */
            queue: [],
            install: false,
            trial: false,
            trial_dir: null,
            logMode: 'raw', /* raw|apps */
            timestamp: null,
            end: null
        },
        timeout: {
            /** @var int|null **/
            state: null
        },
        counter: 0,
        offset: 0,
        complete: null,
        thread_id: null,

        /**
         *
         * @param {object} options
         * @param {string=} thread_id
         */
        init: function (options, thread_id) {
            this.trace('init');
            this.thread_id = thread_id || null;
            this.options = $.extend({}, this.options, options || {});
            if (this.options.timestamp) {
                var date = new Date();
                this.offset = date.getTime() / 1000 - parseInt(this.options.timestamp);
                if (Math.abs(this.offset) > 10) {
                    console.error('Invalid timestamp at response, check server time', this.offset);
                }
            }
            var self = this;

            this.helper.compileTemplates();

            if (this.options.queue.length) {
                this.execute('update', this.options.queue);
            } else {
                this.execute('state', null);
            }
            $('body').addClass('i-fixed-body');
            this.onResize();
            $(window).resize(function () {
                self.onResize();
            });
        },

        helper: {
            plural: function (n) {
                return ((n % 10 == 1 && n % 100 != 11) ? 0 : ((n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) ? 1 : 2));
            },
            /**
             * prepare templates
             */
            compileTemplates: function () {
                var pattern = /<\\\/(\w+)/g;
                var replace_pattern = '<\/$1';

                $("script[type$='x-jquery-tmpl']").each(function () {
                    try {
                        var template_id = $(this).attr('id').replace(/-template-js$/, '');
                        $.installer.trace('Compile template', template_id);
                        $.template(template_id, $(this).html().replace(pattern, replace_pattern));
                    } catch (e) {
                        console.error(e);
                    }
                });
            },
            /**
             *
             * @param {string} target
             * @returns {string}
             */
            subject: function (target) {
                var matches,
                    subject = 'generic',
                    trial_dir = ($.installer.options.trial_dir) ? $.installer.options.trial_dir.replace(/\//g,"\\\/") : null,
                    trial_regexp = (trial_dir) ? new RegExp('^' + trial_dir +'\\\w+\/(\\\w+)') : null;

                if (target.match(/^wa-apps/) || (trial_dir && target.match(trial_regexp))) {
                    if (matches = target.match(/^wa-apps\/\w+\/(\w+)/)) {
                        subject = 'app_' + matches[1];
                        /* it's extras */
                    } else if (matches = target.match(trial_regexp)) {
                        subject = 'app_' + matches[1];
                        /* it's extras */
                    } else {
                        subject = 'apps';
                        /* it's apps */
                    }
                } else if (target.match(/^wa-plugins/)) {
                    if (matches = target.match(/^wa-plugins\/\w+\/(\w+)/)) {
                        subject = 'systemplugins_' + matches[1];
                        /* it's extras */
                    } else {
                        subject = 'systemplugins';
                        /* it's apps */
                    }
                } else if (target.match(/^wa-widgets/)) {
                    subject = 'system_widgets';
                    /* it's widget */
                }
                return subject;
            },
            /**
             *
             * @param {number=500} interval
             */
            hideCounter: function (interval) {
                interval = interval || 500;
                setTimeout(function () {
                    $("#wa-app-installer span.indicator").remove();
                    $("#wa-app li span.indicator").remove();
                }, interval);
            }
        },

        onResize: function () {
            setInterval(function () {
                $('.content .i-app-update-screen').css('max-height', (parseInt($('#wa').css('height')) - 110) + 'px');
            }, 500);
        },

        execute: function (actionName, attr) {
            actionName = actionName || 'default';
            this.trace('execute action ' + actionName, attr);
            if (this[actionName + 'Action']) {
                this.currentAction = actionName;
                this.currentActionAttr = attr;
                try {
                    return this[actionName + 'Action'](attr);
                } catch (e) {
                    console.error('Exception while execute ' + actionName + 'Action', e);
                }
            } else {
                console.error('Invalid action name', actionName + 'Action');
            }
            return null;
        },

        defaultAction: function () {

        },

        stateAction: function () {
            var url = '?module=update&action=state';
            var self = this;
            try {
                this.sendRequest(url, {
                    mode: this.options.logMode
                }, function (data) {
                    self.updateStateHandler(data);
                }, function (data) {
                    self.updateStateErrorHandler(data);
                });
            } catch (e) {
                console.error('Exception while execute stateAction', e);
                this.execute('state', null);
            }
        },

        updateAction: function (apps) {
            var url = '?module=update&action=execute';
            var params = {
                thread_id: this.thread_id,
                app_id: apps,
                mode: this.options.logMode,
                install: this.options.install ? '1' : '0',
                trial: this.options.trial ? '1' : '0'
            };
            var self = this;
            this.sendRequest(url, params, function (data) {
                try {
                    self.updateExecuteHandler(data);
                } catch (e) {
                    console.error('Exception while execute updateExecuteHandler', e);
                }
            }, function (data) {
                try {
                    self.updateExecuteErrorHandler(data);
                } catch (e) {
                    console.error('Exception while execute updateExecuteErrorHandler', e);
                }
            }, function () {
                self.timeout.state = setTimeout(function () {
                    self.execute('state', null);
                }, Math.max(2000, self.options.updateStateInterval * 4));
            });
        },

        updateExecuteHandler: function (data) {
            this.trace('updateExecuteHandler', data);
            if (this.timeout.state) {
                clearTimeout(this.timeout.state);
            }
            var result = {
                success: 0,
                success_plural: 0,
                fail: 0,
                fail_plural: 0
            };
            var complete_result = {};
            var state = false;
            var subject = 'generic';
            if (!data) {
                return;
            }

            this.complete = true;
            if (data.sources) {
                for (var id in data.sources) {
                    if (data.sources.hasOwnProperty(id)) {
                        subject = this.helper.subject(data.sources[id].target);
                        if (subject !== 'generic') {
                            if (!complete_result[subject]) {
                                complete_result[subject] = {
                                    success: 0,
                                    fail: 0,
                                    plural: null
                                };
                            }

                            if (data.sources[id].skipped) {
                                ++result.fail;
                                state = state || 'no';
                                ++complete_result[subject].fail;
                            } else {
                                ++result.success;
                                state = state || 'yes';
                                ++complete_result[subject].success;
                            }
                        }
                    }
                }
            }
            result.success_plural = this.helper.plural(result.success);
            result.fail_plural = this.helper.plural(result.fail);
            state = state || 'no';
            this.helper.hideCounter(100);
            var self = this;
            this.drawStateInfo(data.state, state);
            setTimeout(function () {
                $.tmpl('application-update-result', {
                    current_state: data.current_state,
                    result: result,
                    sources: data.sources
                }).appendTo('#update-raw');

                setTimeout(function () {
                    var targetOffset = $('div.i-app-update-screen :last').offset().top;
                    $('div.i-app-update-screen').scrollTop(targetOffset);
                    self.redirectOnComplete(data);
                    self.animateOnInstall();
                }, 500);
            }, 500);
        },

        updateExecuteErrorHandler: function (data) {
            this.trace('updateExecuteErrorHandler', data);
            /*
             * TODO handle errors and try to restart action if it possible
             */
        },

        /**
         *
         * @param {installerStateData} data
         */
        updateStateHandler: function (data) {
            this.trace('stateHandler', data);
            if (this.timeout.state || this.complete) {
                clearTimeout(this.timeout.state);
            }
            var self = this;
            try {
                if (this.complete) {
                    this.redirectOnComplete(data);
                } else {
                    /* update/add stage info */
                    var draw = false;
                    var date = new Date();
                    var interval = data.current_state ? Math.abs(this.offset - (date.getTime() / 1000 - parseInt(data.current_state.timestamp))) : null;
                    var state_is_actual = data.current_state && (interval < 15);
                    if (state_is_actual) {
                        if (data.current_state.stage_status === 'error') {
                            draw = true;
                        } else if ((data.current_state.stage_status === 'complete') && (data.current_state.stage_name === 'update')) {
                            draw = true;
                        }
                    }
                    if (draw) {
                        this.drawStateInfo(data.state, (data.current_state.stage_status === 'error') ? 'no' : 'yes');
                        $.tmpl('application-update-result', {
                            current_state: data.current_state,
                            result: null
                        }).appendTo('#update-raw');
                    } else if (state_is_actual && data.state && (data.current_state.stage_status !== 'none')) {
                        this.drawStateInfo(data.state);

                        this.timeout.state = setTimeout(function () {
                            if (!self.complete) {
                                self.execute('state', null);
                            }
                        }, this.options.updateStateInterval);
                    } else {
                        this.timeout.state = setTimeout(function () {
                            if (!self.complete) {
                                self.execute('state', null);
                            }
                        }, this.options.updateStateErrorInterval);
                    }
                }
            } catch (e) {
                this.timeout.state = setTimeout(function () {
                    if (!self.complete) {
                        self.execute('state', null);
                    }
                }, this.options.updateStateErrorInterval);
                console.error('updateStateHandler error: ' + e.message, e);
            }
        },

        updateStateErrorHandler: function (data) {
            this.trace('StateErrorHandler', data);
            if (this.timeout.state) {
                clearTimeout(this.timeout.state);
            }
            var self = this;
            this.timeout.state = setTimeout(function () {
                if (!self.complete) {
                    self.execute('state', null);
                }
            }, this.options.updateStateErrorInterval);
        },

        /**
         *
         * @param {installerState[]} state
         * @param state_class
         */
        drawStateInfo: function (state, state_class) {
            /**
             * @todo check timestamp
             */
            var target = '#template-placeholder';
            var id, html;
            state_class = state_class || 'loading';
            switch (this.options.logMode) {
                case 'raw' :
                    if (state && state.length) {
                        for (id in state) {
                            if (state.hasOwnProperty(id) && !state[id]['datetime']) {
                                state[id]['datetime'] = new Date(parseInt(state[id]['stage_start_time']) * 1000);
                            }
                        }
                        html = $(target).html();
                        try {
                            $(target).empty();
                            $.tmpl('application-update-raw', {
                                stages: state,
                                apps: this.options.queue,
                                state_class: state_class
                            }).appendTo(target);
                        } catch (e) {
                            console.error('Error while parse template ', e);
                            $(target).html(html);
                        }
                    }
                    break;
                /*case 'apps' :*/
                default :
                    html = $(target).html();
                    try {
                        $(target).empty();
                        for (var app_id in state) {
                            if (state.hasOwnProperty(app_id)) {
                                for (id in state[app_id]) {
                                    if (state[app_id].hasOwnProperty(id) && !state[app_id][id]['datetime']) {
                                        state[app_id][id]['datetime'] = new Date(parseInt(state[app_id][id]['stage_start_time']) * 1000);
                                    }
                                }
                                var d = new Date(parseInt(state[app_id][1]['stage_start_time']) * 1000);
                                $.tmpl('application-update-apps', {
                                    slug: app_id,
                                    timestamp: d,
                                    stages: state[app_id],
                                    state_class: state_class
                                }).appendTo(target);
                            }
                        }
                    } catch (e) {
                        console.error('Error while parse template ', e);
                        $(target).html(html);
                    }
                    break;
            }

            setTimeout(function () {
                var targetOffset = $('div.i-app-update-screen :last').offset().top;
                $('div.i-app-update-screen').scrollTop(targetOffset);
            }, 100);
        },

        redirectOnComplete: function (data) {
            /* @todo verify that there no fails */
            this.clearCache();

            var redirect_url = (data['design_redirect'] || null);
            if (this.options.redirect_url) {
                redirect_url = this.options.redirect_url;
            }

            if (redirect_url) {
                setTimeout(function () {
                    window.location = redirect_url;
                }, this.options.redirect_timeout);
            }
        },

        animateOnInstall: function () {
            var $app_menu = $('#wa-applist ul');
            $('#update-result-apps li').each(function () {
                var $this = $(this);
                $this.parent().show();
                var position = $this.offset();

                var target = null;
                var insert_last = true;
                var $item_edition = $app_menu.find('> li[id^=' + $this.attr('id') + ']');
                if ($item_edition.length) {
                    target = $item_edition.offset();
                } else {
                    if (insert_last) {
                        target = $app_menu.find('#wa-moreapps').offset();
                        if (!target.left) {
                            target = $app_menu.find('> li[id^=wa-app-]:last').offset();
                            target.left = target.left + 75;
                        }
                    } else {
                        target = $app_menu.find('> li[id^=wa-app-]:first').offset();
                    }
                }
                var animate_params = {
                    left: target.left,
                    top: target.top
                };
                var css_params = {
                    top: position.top,
                    left: position.left,
                    position: 'absolute',
                    display: 'inline-block'
                };
                var css_params_complete = {
                    top: 0,
                    left: 0,
                    position: 'relative',
                    display: 'inline-block'
                };

                $this.css(css_params);
                var $element = $this;
                $this.animate(animate_params, 700, function () {

                    $element.css(css_params_complete);
                    if ($item_edition.length) {
                        $item_edition.replaceWith($element);
                    } else {
                        if (insert_last) {
                            $element.appendTo($app_menu);
                        } else {
                            $element.prependTo($app_menu);
                        }
                    }
                    $(window).resize();
                });
            });
        },

        /**
         *
         * @param {string} stage
         * @param {*=} data
         */
        trace: function (stage, data) {
        },

        sendRequest: function (url, request_data, success_handler, error_handler, before_send_handler) {
            var self = this;
            var timestamp = new Date();
            $.ajax({
                url: url + '&timestamp=' + timestamp.getTime(),
                data: request_data,
                type: 'GET',
                dataType: 'json',
                success: function (data, textStatus) {
                    try {
                        try {
                            if (typeof(data) !== 'object') {
                                data = $.parseJSON(data);
                            }
                        } catch (e) {
                            console.error('Invalid server JSON response', e);
                            if (typeof(error_handler) === 'function') {
                                error_handler();
                            }
                            throw e;
                        }
                        if (data) {
                            switch (data.status) {
                                case 'fail' :
                                    self.displayMessage(data.errors.error || data.errors, 'error');
                                    if (typeof(error_handler) === 'function') {
                                        error_handler(data);
                                    }
                                    break;
                                case 'ok' :
                                    if (typeof(success_handler) === 'function') {
                                        success_handler(data.data);
                                    }
                                    break;
                                default :
                                    console.error('unknown status response', data.status);
                                    if (typeof(error_handler) === 'function') {
                                        error_handler(data);
                                    }
                                    break;
                            }
                        } else {
                            console.error('empty response', textStatus);
                            if (typeof(error_handler) === 'function') {
                                error_handler();
                            }
                            self.displayMessage('Empty server response', 'warning');
                        }
                    } catch (e) {
                        console.error('Error handling server response ', e);
                        if (typeof(error_handler) === 'function') {
                            error_handler(data);
                        }
                        self.displayMessage('Invalid server response' + '<br>' + e.description, 'error');
                    }

                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    console.error('AJAX request error', [textStatus, errorThrown]);
                    if (typeof(error_handler) === 'function') {
                        error_handler();
                    }
                    self.displayMessage('AJAX request error', 'warning');
                },
                beforeSend: before_send_handler
            });
        },
        displayMessage: function (message, type) {

        },

        clearCache: function () {
            if (this.options.cache_url) {
                $.ajax({
                    url: this.options.cache_url,
                    type: 'GET',
                    dataType: 'json',
                    success: this.responseHandler
                });
            }
        },
        responseHandler: function (data) {
            var self = $.installer;
            try {
                if (data.status !== 'ok') {
                    setTimeout(function () {
                        self.clearCache()
                    }, 3000);
                }
            } catch (e) {
            }

        }
    }
})(jQuery);
