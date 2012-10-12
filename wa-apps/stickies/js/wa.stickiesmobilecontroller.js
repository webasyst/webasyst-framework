(function($) {

    $(function() {
        $.mobile.ajaxEnabled = false;
        $.mobile.hashListeningEnabled = false;
        $.mobile.ajaxLinksEnabled = false;
        $.mobile.pushStateEnabled = false;
        $.mobile.linkBindingEnabled = false;
    });

    var urlHistory = {
        back : false,
        stack : [],
        addUrl : function(url) {
            if (url == urlHistory.stack[1]) {
                urlHistory.stack.shift();
                urlHistory.back = true;
            } else {
                urlHistory.stack.unshift(url);
                urlHistory.back = false;
            }

            if (urlHistory.stack.length > 30) {
                urlHistory.stack.pop();
            }
        },
        isBack : function() {
            return urlHistory.back;
        },
        is_first : true,
        isFirst : function() {
            if (this.is_first) {
                this.is_first = false;
                return true;
            } else {
                return false;
            }
        }
    };

    $.wa.stickiesmobilecontroller = {
        options : {
            'separator' : '/',
            'debug' : false,
            'default_background': ''
        },
        init : function(options) {
            var self = this;
            this.trace('init');
            this.options.escaped_separator = this.options.separator.replace(
                    /([ #;&,.+*~\':"!^$[\]()=>|\/])/g, '\\$1');

            $(window).unload(function() {
                self.checkChanges();
            });

            // prepare templates
            $(document).ready(function() {
                $(function() {
                    self.onDomReady();
                });
            });

        },


        dispatch : function(hash) {
            this.trace('dispatch hash', hash);
            if (hash) {
                hash = hash.replace(/^.*#\/?/, '').replace(/\-+/,
                        this.options.separator).split(this.options.separator);
                this.trace('splited hash', hash);
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for ( var i in hash) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i == 0) {
                                actionName = h;
                            } else if (h.match(/[a-z]+/i)) {
                                actionName += h.substr(0, 1).toUpperCase()
                                        + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    this.execute(actionName, attr);

                } else {
                    this.execute();
                }
            } else {
                this.execute();
            }
            return false;
        },

        execute : function(actionName, attr) {
            actionName = actionName || 'default';
            this.trace('execute action ' + actionName, attr);
            if (this[actionName + 'Action']) {
                this.currentAction = actionName;
                this.currentActionAttr = attr;
                try {
                    return this[actionName + 'Action'](attr);
                } catch (ex) {
                    this.log('Exception', ex.message);
                }
            } else {
                this.log('Invalid action name', actionName + 'Action');
            }
        },

        checkChanges : function() {
            var self = this;
            $('.stick-status.nosaved').each(function(i) {
                var id = parseInt($(this).attr('id').match(/\d+$/));
                self.log('force save', id);
                $('#sticky_content_' + id).change();
            });
        },
        defaultAction : function() {
            this.execute('sheets');
        },

        goPage : function(page) {
            this.trace('goPage', page);
            page = $(page);

            $.mobile.hidePageLoadingMsg();
            if (urlHistory.isFirst()) {
                $.mobile.changePage(page, {
                    'transition' : 'none'
                });
            } else {
                $.mobile.changePage(page, {
                    'transition' : 'slide',
                    'changeHash' : false,
                    'reverse' : urlHistory.isBack()
                });
            }
        },

        loadPage : function(url, data, page_id, template_id, callback, data_callback) {
            this.trace('loadPage', [url, page_id, template_id]);
            var self = this;
            $.mobile.showPageLoadingMsg();
            this.sendRequest(url, data, function(data) {// success
                $(page_id).remove();
                $.tmpl(template_id, data).insertAfter('#loading');
                setTimeout(function() {
                    self.goPage(page_id);
                    if (typeof(callback) == 'function') {
                        callback(data, page_id);
                    }
                }, 50);
            }, function() {// fail
                $.mobile.hidePageLoadingMsg();
            });
        },

        sheetsAction : function() {
            var url = '?module=sheet&action=list';
            var page_id = '#sheets';
            if ($(page_id).length) {
                $('body').attr('class', this.options.default_background);
                this.goPage(page_id);
            } else {
                $('body').attr('class', this.options.default_background);
                this.loadPage(url, {}, page_id, 'sheet-list');
            }
        },

        sheetAction : function(params) {
            this.trace('sheetAction', params);
            var sheet_id = parseInt(params[0]);
            var page_id = '#sheet' + this.options.escaped_separator + sheet_id;
            if (params[1] && (params[1] == 'refresh')) {
                $(page_id).remove();
            }
            if ($(page_id).length) {
                this.goPage(page_id);
                $('body').attr('class', $(page_id).data('bg')||this.options.default_background);
            } else {
                var url = '?module=sheet&action=view';
                this.loadPage(url,{'sheet_id':sheet_id}, page_id, 'sheet', this.sheetDisplay);
            }

        },

        sheetDisplay : function(data, page_id) {
            $('body').attr('class',data.current_sheet.background_id||this.options.default_background);
            $(page_id).data('bg', data.current_sheet.background_id);

        },

        stickyAction : function(params) {

            var id = parseInt(params[0]);
            var page_id = '#sticky' + this.options.escaped_separator + id;

            if ($(page_id).length) {
                this.goPage(page_id);
            } else {
                var url = '?module=sticky&action=view';
                this.loadPage(url, {'id':id}, page_id, 'sticky', this.stickyDisplay);
            }

        },

        stickyDisplay : function(data, page_id) {
            $('body').attr('class',data.sheet.background_id||this.options.default_background);
            $(page_id).data('bg', data.sheet.background_id);
            var container = $(page_id.replace(/\\\//,'-content-'));
            container.html(container.html().replace(/\n/g,'<br>'));
        },

        sendRequest : function(url, request_data, success_handler,
                error_handler) {
            var self = this;
            $
                    .ajax({
                        'url' : url,
                        'data' : request_data || {},
                        'type' : 'POST',
                        'success' : function(data, textStatus, XMLHttpRequest) {
                            try {
                                data = $.parseJSON(data);
                            } catch (e) {
                                self.log('Invalid server JSON responce',
                                        e.description);
                                if (typeof (error_handler) == 'function') {
                                    error_handler();
                                }
                                self.displayNotice('Invalid server responce'
                                        .translate()
                                        + '<br>' + e, 'error');
                            }
                            if (data) {
                                switch (data.status) {
                                    case 'fail' : {
                                        self.displayNotice(data.errors.error
                                                || data.errors, 'error');
                                        if (typeof (error_handler) == 'function') {
                                            error_handler(data);
                                        }
                                        break;
                                    }
                                    case 'ok' : {
                                        if (typeof (success_handler) == 'function') {
                                            success_handler(data.data);
                                        }
                                        break;
                                    }
                                    default : {
                                        self.log('unknown status responce',
                                                data.status);
                                        if (typeof (error_handler) == 'function') {
                                            error_handler(data);
                                        }
                                        break;
                                    }
                                }
                            } else {
                                self.log('empty responce', textStatus);
                                if (typeof (error_handler) == 'function') {
                                    error_handler();
                                }
                                self.displayNotice('Empty server responce'
                                        .translate(), 'warning');
                            }

                        },
                        'error' : function(XMLHttpRequest, textStatus,
                                errorThrown) {
                            self.log('AJAX request error', textStatus
                                    + errorThrown);
                            if (typeof (error_handler) == 'function') {
                                error_handler();
                            }
                            self
                                    .displayNotice('AJAX request error'
                                            .translate(), 'warning');
                        }
                    });
        },

        onDomReady : function() {
            this.compileTemplates();

            if (default_background) {
                this.options.default_background = default_background;
                $('#wa').addClass('class', this.options.default_background);
            }

            $(window).bind(
                    "hashchange",
                    function(e, triggered) {
                        var h = (parent
                                ? parent.window.location.hash
                                : location.hash)
                                || '#/sheets/';
                        $.wa.stickiesmobilecontroller.trace('hashchange', h);
                        urlHistory.addUrl(h);
                        $.wa.stickiesmobilecontroller.dispatch(h);
                        e.preventDefault();
                        e.stopPropagation();
                    })
                    .bind('pagebeforeload',function(url, absUrl ,dataUrl ){
                        $.wa.stickiesmobilecontroller.trace('pagebeforeload', [url, absUrl ,dataUrl]);
                    })
                    .bind('pageload',function(url, absUrl ,dataUrl ){
                        $.wa.stickiesmobilecontroller.trace('pageload', [url, absUrl ,dataUrl]);
                    });

            var h = parent ? parent.window.location.hash : location.hash;
            if (h.length < 2) {
                $.wa.setHash('#/sheets/');
            } else {
                $.wa.stickiesmobilecontroller.dispatch(h);
                urlHistory.addUrl('#/sheets/');
            }
        },


        compileTemplates : function() {
            var pattern = /<[\\]+\/(\w+)/g;
            var replace = '</$1';

            $("script[type$='x-jquery-tmpl']").each(function() {
                var id = $(this).attr('id').replace(/-template-js$/, '');
                try {
                    var template = $(this).html().replace(pattern, replace);
                    $.template(id, template);
                } catch (e) {
                    if (typeof (console) == 'object') {
                        console.log('Error while compile template '+id, e.message);
                    }
                }
            });
        },

        displayNotice : function(message, type) {
            var container = $('#wa-system-notice');
            if (container) {
                // TODO remove js from message?
                var delay = 1500;
                switch (type) {
                    case 'error' : {
                        delay = 6000;
                        break;
                    }
                }
                container.html(message
                        .replace(/<script[\s\S]*?\/script>/gm, ''));
                container.slideDown().delay(delay).slideUp();

            } else {
                alert(message);
            }

        },

        log : function(message, params) {
            if (console) {
                console.log(message, params);
            }
        },
        trace : function(message, params) {
            if (console && this.options.debug) {
                console.log(message, params);
            }
        }

    };

})(jQuery, this);
(function($, window, undefined) {
    $.wa.stickiesmobilecontroller.init();
})(jQuery, this);
