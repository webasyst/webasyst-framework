/**
 *
 */
/* (function($) { */
$.layout = {
    /*
     * #/list[:query][:offset?]/item/tab/?param1=value2
     */
    path: {
        'list': null, /* apps|plugins|themes */
        'query': null, /* all|app=shop|top related to */
        'item': null, /* item id */
        'tab': null,
        'raw': null,
        'filter': {}
        /* main */
    },
    options: {
        default_list: 'apps',
        default_query: {
            plugins: 'wa-plugins/payment'
        },
        title: 'Installer',
        debug: true,
        duration: 500,
        animation: false

    },
    queue: [],
    time: {
        start: new Date(),
        /**
         * @return int
         */
        interval: function (relative) {
            var d = new Date();
            return (parseFloat(d - this.start) / 1000.0 - (parseFloat(relative) || 0)).toFixed(3);
        }
    },

    handlers: {
        list: {
            related: ['query', 'filter'],
            parent: []
        },
        item: {
            parent: ['list']
        },
        tab: {}
    },

    window: {
        title: 'Installer',
        suffix: '',
        setTitle: function (title) {
            document.title = title || this.title + ' — ' + this.suffix;
        },
        getTitle: function () {
            return document.title;
        }
    },

    init: function (options) {
        $.extend(this.options, options);
        this.path.list = window.location.href.match(/\/\?/) ? '' : '';
        this.window.setTitle();
        this.initRouting();
    },
    initRouting: function () {
        var self = this;
        if (typeof($.History) != "undefined") {
            $.History.bind(function () {
                $.layout.dispatch();
            });
        } else {
            this.error('Ajax history failed');
        }
        $.wa.errorHandler = function (xhr) {
            return self.helper.ajaxErrorHandler(xhr);
        };
        var hash = window.location.hash;
        if (hash === '#/' || hash === '#' || !hash) {
            $.wa.setHash('#/' + this.options.default_list + '/');
        } else {
            $.wa.setHash(hash);
        }
    },

    /**
     *
     * @param String path
     * @return {
     *  'list':String,
     *  'query':String,
     *  'item':String,
     *  'tab':String,
     *  'tail':String,
     *  'raw':String,
     *  'filter':{}
     * }
     */
    parsePath: function (path) {
        /**
         * /apps&filter1=value1&filter2=value2/
         * /apps&filter1=value1&filter2=value2/%app_id%/
         * /apps&filter1=value1&filter2=value2/%app_id%/tab/
         * /plugins&filter1=value1&filter2=value2/%parent/slug%/
         * /plugins&filter1=value1&filter2=value2/%parent/slug%/%pugin_id%/
         * /plugins&filter1=value1&filter2=value2/%parent/slug%/%pugin_id%/%tab%/
         * /themes&filter1=value1&filter2=value2/
         * /themes&filter1=value1&filter2=value2&slug=%parent%/
         * /themes&filter1=value1&filter2=value2/slug%/%theme_id%/%tab%/
         * /themes&filter1=value1&filter2=value2&slug=%parent%/slug%/%theme_id%/%tab%/
         */
        path = path.replace(/(^[^#]*#\/*|\/$)/g, '');
        path = path.replace(/^.*#\//, '').replace(/(^\/|\/$)/, '');
        var matches;
        matches = path.split('/');
        var param_regexp = /&?\b\w+\b=.*$/;
        var param_matches;
        var filter = {};
        for (var i = 0; i < matches.length; i++) {
            if ((param_matches = param_regexp.exec(matches[i])) && param_matches[0]) {
                filter = $.layout.helper.parseParams(param_matches[0], filter);
                matches[i] = matches[i].replace(param_regexp, '');
                if (!matches[i]) {
                    matches.splice(i + 1, 1);
                    --i;
                }
            }
        }

        var list = matches.shift().replace(/:.*$/, '') || ( window.location.href.match(/\/\?/) ? '' : this.options.default_list);
        var query = '';
        var item = false;
        var query_size;

        switch (list) {
            case 'apps':
                item = matches.shift() || '';
                break;
            case 'plugins':
                if (matches.length) {
                    query_size = (matches[0] == 'wa-plugins') ? 2 : 1;
                    query = matches.slice(0, query_size).join('/') || '';
                    matches = matches.slice(query_size);
                    if (item = matches.shift() || '') {
                        item = query + '/' + item;
                        // query = this.path.query;
                    }
                } else {
                    query = this.options.default_query.plugins;
                }
                break;
            case 'themes':
                query = '';
                item = matches.shift();
                break;
        }

        return {
            list: list,
            query: query,
            item: item,
            tab: matches.length ? matches.shift() || false : false,
            tail: matches.join('/') || '',
            raw: path,
            filter: filter
        };
    },

    /**
     *
     * @param {} selector
     * @return {$}
     */
    container: function (selector) {
        return $('#wa-app > div' + (selector ? '#' + selector : '') + '.content');
    },

    /**
     *
     * @param String path
     */
    dispatch: function (path) {
        if (path === undefined) {
            path = window.location.hash;
        }
        if (typeof(path) == 'string') {
            path = this.parsePath(path);
        }
        $.layout.trace('$.layout.dispatch', [this.path, path.raw]);

        var queue = [];
        var Parent = null;

        for (subject in this.handlers) {
            var Subject = this.helper.ucfirst(subject);
            if (path[subject] != this.path[subject]) {
                $.layout.trace('$.layout.dispatch ' + subject + ': ', this.path[subject] + '→' + path[subject]);

                if (this.path[subject]) {
                    queue.unshift('blur' + Subject + this.helper.ucfirst(this.path[subject]));
                    queue.unshift('blur' + Subject);
                    if (Parent && (Parent != 'Tab')) {
                        queue.push('load' + Parent);
                    }
                }
                if (path[subject]) {
                    queue.push('load' + Subject);
                    queue.push('load' + Subject + this.helper.ucfirst(path[subject]));
                }
                break;
            } else {
                if (this.handlers[subject]['related']) {
                    var related = this.handlers[subject]['related'];
                    var changed = false;
                    for (var i = 0; i < related.length; i++) {
                        var rel = related[i];

                        switch (typeof(path[rel])) {
                            case 'object':
                                if ($.param(this.path[rel]) != $.param(path[rel])) {
                                    $.layout.trace('$.layout.dispatch ' + rel + ': ', $.param(this.path[rel]) + '→' + $.param(path[rel]));
                                    queue.push('load' + Subject);
                                    changed = true;
                                }
                                break;
                            case 'string':
                                if (this.path[rel] != path[rel]) {
                                    $.layout.trace('$.layout.dispatch ' + rel + ': ', this.path[rel] + '→' + path[rel]);
                                    queue.push('load' + Subject);
                                    queue.push('load' + Subject + this.helper.ucfirst(path[subject]));
                                    changed = true;
                                }
                                break;
                        }
                        if (changed) {
                            break;
                        }

                    }
                }
            }
            Parent = Subject;
        }
        var name;
        while (name = queue.shift()) {
            // standard convention: if method return false than stop bubble up
            if (this.call(name, [path]) === false) {
                queue = [];
                return false;
            }
        }
    },

    call: function (name, args, callback) {
        var result = null;
        var callable = this.isCallable(name);
        args = args || [];
        $.layout.trace('$.layout.call', [name, args, callable]);
        if (callable) {
            try {
                result = this[name].apply(this, args);
            } catch (e) {
                $.layout.error("Error at method $.layout." + name + ". Original message: " + e.message, e);
            }
        }
        return result;
    },

    blurList: function () {
        $('#wa-app > div.sidebar li.selected').removeClass('selected');
        this.path.query = false;
        this.path.item = false;
        this.path.tab = false;
        this.container().find('form').unbind();
        if (this.options.animation) {
            this.container().html('<div class="block"><i class="icon16 loading"></i></div>');
            $('#wa-app > div.content:not(:first)').remove();
        }
    },

    loadList: function (path) {
        var url = '?module=' + path.list;
        var self = this;
        if (path.query) {
            url += '&slug=' + path.query;
        }
        var filter;
        if (path.filter && (filter = decodeURIComponent($.param({'filter': path.filter})))) {
            url += '&' + filter;
        }
        var id = this.helper.getListId(path);
        var $container = this.container(id);
        if ($container.length && ($container.html() != 'null')) {
            self.focusList(path);
            self.dispatch(path);
        } else {
            $('#wa-app > div.content:last').after('<div class="content left200px" id="' + id + '" style="display:none;"><i class="icon16 loading"></i></div>');
            $.layout.trace('$.layout.loadList', url);
            $.layout.loadContent(url, function () {
                self.focusList(path);
                self.dispatch(path);
            }, self.container(id));
        }
    },


    focusList: function (path) {
        this.path.list = path.list;
        this.path.query = path.query;
        this.path.filter = path.filter;
        this.path.item = false;
        this.path.tab = false;

        path = path || this.path;
        if (['apps', 'themes', 'plugins'].indexOf(path.list) >= 0) {
            $('body').removeClass('i-apps i-themes i-plugins').addClass('i-' + path.list);
        }
        $('#wa-app > div.content:visible').hide();
        this.container(this.helper.getListId(path)).show();
        var filter = '';
        var query = '';
        var i, lists = [];
        if (path.query) {
            query = path.query;
            lists = path.query.split(',');
            for (i = 0; i < lists.length; i++) {
                lists[i] += '\\/';
            }
        } else {
            lists.push('')
        }

        if (path.filter) {
            for (var param in path.filter) {
                if (path.filter.hasOwnProperty(param)) {
                    if (param == 'slug') {
                        query += '&' + param + '=' + path.filter[param];
                        lists = path.filter[param].split(',');
                    } else {
                        filter += '&' + param + '=' + path.filter[param];
                    }
                }
            }
        }
        $('#wa-app > div.sidebar li.selected').removeClass('selected');
        $('#wa-app > div.sidebar a[href^="\\.\\/\\#\\/' + path.list + '\\/"]').parent('li').addClass('selected');

        var href = '\\#\\/' + path.list;
        var $list, $selected;


        $.layout.trace('$.layout.focusList', [filter, query]);

        var $container = this.container(this.helper.getListId(path));

        var self = this;
        /* update app filters */
        $container.find('.i-filters ul.js-query > li > ul > li.selected').removeClass('selected');
        $container.find('.i-filters ul.js-query > li > ul > li a').each(function () {
            self.helper.updateUrl($(this), [filter]);
        });

        var $query = $container.find('.i-filters ul.js-query:first');
        $selected = $container.find('.i-filters ul.js-query > li:first > a:first');
        $selected.find('> i.icon16, > img').remove();
        var $icons = [];
        var name = [];
        var hint = '';
        for (i = 0; i < lists.length; i++) {
            $list = $query.find('a[data-href^="' + href + '%s\\/' + lists[i] + '"]:first');
            if (!$list.length) {
                $list = $query.find('a[data-href^="' + href + '%s&slug=' + lists[i] + '\\/"]:first');
            }
            if ($list.length) {
                $list.parent('li').addClass('selected');
                hint = $list.find('span.hint').text().replace(/(^\s+|\s+$)/g, '') || hint;
                name.push($list.text().replace(hint, '').replace(/(^\s+|\s+$)/g, ''));
                $selected.prepend($list.find('i.icon16, img').clone());
            }

        }

        this.helper.updateUrl($selected, [filter, query]);
        $selected.find('strong').text(name.join(', '));
        $selected.find('span.hint').text(hint ? ' ' + hint : '');

        /* update list links */
        $container.find('a.js-item-link[data-href^="\\#"]').each(function () {
            self.helper.updateUrl($(this), (path.list == 'themes' ) ? [ query, filter] : [ filter, query]);
        });

        /* update commercial filters */
        var $commercial = $container.find('.i-filters ul.js-filter');
        var internal_filter;
        if (internal_filter = '' + $commercial.data('filter')) {
            var re = new RegExp('&' + internal_filter + '=[^&]*');
            internal_filter = filter.match(re) || '';
            filter = filter.replace(re, '');
        } else {
            internal_filter = filter;
        }
        $commercial.find('> li.selected').removeClass('selected');
        $commercial.find('> li a').each(function () {
            self.helper.updateUrl($(this), [filter, query]);
        });
        var $filter = $commercial.find('> li a[data-href^="' + href + internal_filter + '"]:first');
        $filter.parent('li').addClass('selected');
    },

    loadListPlugins__: function (path) {
        var $container = this.container();
    },

    loadItem: function (path) {

        var url = '?module=' + path.list;
        url += '&action=info';
        url += '&slug=' + path.item;
        url += '&query=' + path.query;
        var id = this.helper.getItemId(path);
        if (path.filter) {
            //todo edition&vendor&filter workaround
            //add chunks to url and id
        }
        $.layout.trace('$.layout.loadItem', id);

        var $container = $('#wa-app > div#' + id);

        if (!$container.length && ($container.html() !='null')) {
            var href = path.item.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g, '\\$1');
            var item_selector = '#wa-app > div.content:first a[href*="\\/' + href + '\\/"]:first';
            var $item = $(item_selector).parents('li');
            $('#wa-app > div.content:last').after('<div class="content left200px" id="' + id + '" style="display:none;">' + $item.html() + (this.options.animation ? '<i class="icon16 loading"></i>' : '')
                + '</div>');
            $container = $('#wa-app > div#' + id);
            $container.find('a').contents().unwrap();
            $.layout.trace('$.layout.loadList', url);

            var self = this;
            $.layout.loadContent(url, function () {
                self.path.item = path.item;
                // self.path.filter = path.filter;
                self.path.tab = false;
                self.focusItem(path, $container);
                self.dispatch(path);
            }, $container);
        } else {
            this.path.item = path.item;
            this.focusItem(path, $container);
            this.dispatch(path);
        }

    },

    loadTab: function (path) {
        this.path.tab = path.tab;
        var id = this.helper.getItemId(path);
        //todo edition&vendor&filter workaround
        //add chunks to url and id
        var $container = this.container(id);
        $container.find('section').hide();
        var $nav = $container.find('nav:first > ul:first');
        $nav.find('li.selected').removeClass('selected');
        if (path.tab) {
            $container.find('div.i-screenshots:first').hide();
            if ($nav.find('> li').length == 1) {
                $nav.find('> li').show();
            }
        } else {
            $container.find('div.i-screenshots:first').show();
            if ($nav.find('> li').length == 1) {
                $nav.find('> li').hide();
            }
        }
        var item = path.item;
        if (path.list == 'plugins') {
            item = item.replace(/\//, '/' + path.list + '/');
        }
        id = '#tab-' + (item + '-' + (path.tab || 'info')).replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g, '\\$1');
        //tab-pocketlists-info
        var href = path.item + '/';
        if (path.tab) {
            href += path.tab + '/';
        }
        href = href.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g, '\\$1');
        $nav.find('a[href$="' + href + '"]').parents('li').addClass('selected');
        $container.find(id).show();
    },
    blurTab: function (path) {
        this.path.tab = false;
        this.loadTab(path);
    },
    blurItem: function (path) {
        $('#wa-app > div.content').hide();
        var $sidebar = $('#wa-app > div.sidebar');
        var $content = $('#wa-app > div.content:first');

        $sidebar.show();
        if (this.options.animation) {
            $content.css('margin-left', '0px');
        }
        $content.show();
        if (this.options.animation) {
            $sidebar.animate({
                'width': '200px'
            }, this.options.duration).queue(function () {
                $(this).dequeue();
            });

            $content.animate({
                'margin-left': '200px'
            }, this.options.duration);
        } else {
            $sidebar.css({
                'width': '200px'
            });
            $content.css({
                'margin-left': '200px'
            });
        }
        var $body = $('body');
        $body.removeClass($body.attr('class')).addClass('i-' + path.list);
        $body = $('html');
        $body.removeClass($body.attr('class'));

        this.blurTab(this.path);
        this.path.tab = null;
        this.path.item = null;
    },
    focusItem: function (path, $container) {
        $.layout.trace('$.layout.focusItem', path);
        $('#wa-app > div.content:visible').hide();

        if (this.options.animation) {
            $('#wa-app > div.sidebar').show().animate({
                'width': '0px'
            }, this.options.duration).queue(function () {
                $(this).hide();
                $(this).dequeue();
            });
        } else {
            $('#wa-app > div.sidebar').hide();
        }
        if (this.options.animation) {
            $container.show().animate({
                'margin-left': '0px'
            }, this.options.duration).queue(function () {
                $(this).dequeue();
            });
        } else {
            $container.show().css({
                'margin-left': '0px'
            });
        }

        var $body = $('html');
        $body.addClass(this.premium.getClass());

        var self = this;

        if (this.path.filter) {
            var filter = '';
            for (var param in this.path.filter) {
                if (this.path.filter.hasOwnProperty(param)) {
                    if (param != 'slug') {
                        filter += '&' + param + '=' + this.path.filter[param];
                    }
                }
            }
            $container.find('a.js-item-link[data-href^="\\#"]').each(function () {
                self.helper.updateUrl($(this), (self.path.list == 'themes' ) ? [ self.path.query, filter] : [ filter, self.path.query]);
            });
        }

        $container.on('click', 'a.js-action', function () {
            return self.click($(this));
        });


    },

    showScreenshot: function ($el) {
        var $img = $el.find('img:first');
        $el.parents('ul').find('> li.selected').removeClass('selected');
        var $big_img = $el.parents('.i-screenshots').find('#current img:first');
        $big_img.attr('src', $img.data('src'));
        $el.parent('li').addClass('selected');
        return false;
    },

    showLicense: function ($el) {
        $el.parents('div.value:first').find('div.dialog:first').show();
    },

    hideLicense: function ($el) {
        $el.parents('div.dialog:first').hide();
    },

    /**
     * Handle js section interactions
     *
     * @param {JQuery} $el
     * @return {Boolean}
     */
    click: function ($el) {
        var args = $el.attr('href').replace(/.*#\//, '').replace(/\/$/, '').split('/');
        var params = [];
        var method = $.layout.getMethod(args, this);

        if (method.name) {
            $.layout.trace('$.layout.click', method);
            if (!$el.hasClass('js-confirm') || confirm($el.data('confirm-text') || $el.attr('title') || 'Are you sure?')) {
                method.params.push($el);
                this[method.name].apply(this, method.params);
            }
        } else {
            $.layout.error('Not found js handler for link', [method, $el])
        }
        return false;
    },

    load: function (path) {
        var $container = this.container();
        if (true || !$container.length || ($container.data('product-id') != path.id)) {
            var self = this;
            var url = '?module=' + path.list;
            $.layout.trace('$.layout.load', url);
            $('#wa-app > div.sidebar li.selected').removeClass('selected');
            $.layout.loadContent(url, function () {
                self.path.list = path.list;
                var href = path.list.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g, '\\$1');
                $('#wa-app > div.sidebar a[href="\\#\\/' + href + '\\/"]').parent('li').addClass('selected');
                self.dispatch(path);
            }, this.container(this.helper.getListId(path)));
        }
    },

    loadContent: function (url, callback, container) {
        var r = Math.random();
        this.random = r;
        var self = this;
        $.layout.trace('$.layout.loadContent', [url, container]);
        $.get(url, function (result) {
            if (!container && (self.random != r)) {
                // too late: user clicked something else.
                return;
            }
            container = container || self.container();
            if (result) {
                container.html(result);
                $('html, body').animate({
                    scrollTop: 0
                }, 200);
                if ((self.random == r) && (typeof(callback) == 'function')) {
                    try {
                        callback();
                    } catch (e) {
                        $.layout.error('$.layout.loadContent callback error: ' + e.message, e);
                    }
                }
            }
        });
    },

    onLoad: function () {
        $('#s-settings-content').on('click', 'a.js-action', function () {
            return self.click($(this));
        });
    },

    /**
     * @param {Array} args
     * @param {object} scope
     * @param {String} name
     * @return {'name':{String},'params':[]}
     */
    getMethod: function (args, scope, name) {
        var chunk, callable;
        var method = {
            'name': false,
            'params': []
        };
        if (args.length) {
            $.layout.trace('$.getMethod', args);
            name = name || args.shift();
            while (chunk = args.shift()) {
                name += chunk.substr(0, 1).toUpperCase() + chunk.substr(1);
                callable = (typeof(scope[name]) == 'function');
                $.layout.trace('$.getMethod try', [name, callable, args]);
                if (callable === true) {
                    method.name = name;
                    method.params = args.slice(0);
                }
            }
        }
        return method;
    },

    /**
     * Debug trace helper
     *
     * @param String message
     * @param {} data
     */
    trace: function (message, data) {
        var timestamp = null;
        if (this.options.debug && console) {
            timestamp = this.time.interval();
            console.log(timestamp + ' ' + message, data);
        }
        return timestamp;
    },

    /**
     * Handler error messages
     *
     * @param String message
     * @param {} data
     */
    error: function (message, data) {
        if (console) {
            console.error(message, data);
        }
    },
    isCallable: function (name) {
        return (typeof(this[name]) == 'function');
    },
    premium: {
        classes: {},
        setClass: function (path, css_class) {
            this.classes[path] = css_class;
        },
        getClass: function () {
            return this.classes[this.path()] || '';
        },
        path: function () {
            return $.layout.path.list + ':' + $.layout.path.item;
        }

    },
    helper: {
        updateUrl: function ($el, filter) {
            var href = '' + $el.data('href');
            if (href) {
                var internal_filter;
                var re;
                if (internal_filter = '' + $el.data('filter')) {
                    re = new RegExp('&' + internal_filter + '=[^&]*');
                } else {
                    re = new RegExp('');
                }
                for (var i = 0; i < filter.length; i++) {
                    href = href.replace(/%s/, filter[i].replace(re, ''));
                }
                $el.attr('href', href.replace(/%s/g, '').replace(/[&\/]+\//g, '/').replace(/([&\/])\1+/g, '$1'));
            }
        },
        getItemId: function (path) {
            return 'ajax-item-' + this.replace(path.list) + '-' + this.replace(path.item);
        },
        getListId: function (path) {
            return 'ajax-list-' + this.replace(path.list)
                + '-' + this.replace(path.query || '')
                + this.replace('' + $.param(path.filter));
        },
        replace: function (string) {
            return ('' + (string || '')).replace(/\//g, '-').replace(/,/g, '_').replace(/[^\w_\-]+/g, '');
        },

        /**
         * @param {String} params_string
         * @param {object} params
         * @return {object}
         */
        parseParams: function (params_string, params) {
            params_string = ('' + params_string).replace(/^&/, '');
            params = params || {};
            if (!params_string) {
                return params;
            }
            var p = params_string.split('&');
            for (var i = 0; i < p.length; i++) {
                var t = p[i].split('=');
                params[t[0]] = t.length > 1 ? t[1] : '';
            }
            return params;
        },
        /**
         * @param String string
         * @return String
         */
        ucfirst: function (string) {
            return string ? (string.substr(0, 1).toUpperCase() + string.substr(1)) : '';
        },
        ajaxErrorHandler: function (xhr) {
            if ((xhr.status === 403) || (xhr.status === 404)) {
                var text = $(xhr.responseText);
                console.log(text);
                if (text.find('.dialog-content').length) {
                    text = $('<div class="block double-padded"></div>').append(text.find('.dialog-content *'));

                } else {
                    text = $('<div class="block double-padded"></div>').append(text.find(':not(style)'));
                }
                $("#s-content").empty().append(text);
                return false;
            }
            return true;
        },
        submit: function (form, event) {
            var $form = $(form);
            $form.find(':submit').attr('disabled', true).after('<i class="icon16 loading"></i>');
            try {
                var d = new Date();
                var url = $form.attr('action') + '&_=' + d.getMilliseconds();
                $.layout.trace('$.layout.submit', url);
                $.ajax(url, {
                    'data': $form.serialize(),
                    'type': 'POST',
                    'success': function (data, textStatus, jqXHR) {
                        $.layout.blurList();
                        $.layout.container().html(data);
                        $('html, body').animate({
                            scrollTop: 0
                        }, 200);
                        $.layout.focusList();
                    },
                    'error': function (jqXHR, textStatus, errorThrown) {
                        $.layout.error('Error at $.settings.featuresFeatureValueTypeEdit', errorThrown);
                    }
                });
            } catch (e) {

            }
            return false;
        }
    }
};
/* }); */

