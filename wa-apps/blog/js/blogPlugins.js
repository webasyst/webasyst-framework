/**
 *
 */
$.wa_blog.plugins = {
    options: {
        plugins: {}
    },
    list: null,
    hash: null,
    js: {},
    container: null,
    init: function () {
        var self = this;
        self.container = $("#blog-ajax-settings-container");
        self.list = $('#plugin-list').sortable({
            'containment': 'parent',
            'distance': 5,
            'tolerance': 'pointer',
            'update': self.sortHandler
        });

        if (typeof ($.History) != "undefined") {
            $.History.bind(function () {
                self.dispatch();
            });
        }
        var hash = window.location.hash;
        if (hash === '#/' || !hash) {
            hash = $.storage.get('blog/plugins/hash');
            if (hash && hash != null) {
                $.wa.setHash('#/' + hash);
            } else {
                this.dispatch();
            }
        } else {
            $.wa.setHash(hash);
        }

    },
    sortHandler: function (event, ui) {
        $.ajax({
            type: 'POST',
            url: '?module=plugins&action=sort',
            data: {
                slug: $(ui.item).attr('id').replace(/^plugin-/, ''),
                pos: $(ui.item).index()
            },
            success: function (data, textStatus, jqXHR) {
                if (!data || !data.status || (data.status != "ok") || !data.data
                    || (data.data != "ok")) {
                    self.list.sortable('cancel');
                }
            },
            error: function () {
                self.list.sortable('cancel');
            }
        });
    },
    dispatch: function (hash) {
        $.wa_blog.plugins.hash = '';
        if (hash == undefined) {
            hash = window.location.hash;
        }
        hash = hash.replace(/^[^#]*#\/*/, '');
        if (hash) {
            hash = hash.split('/');
            if (hash[0]) {
                var actionName = "";
                var attrMarker = hash.length;
                for (var i = 0; i < hash.length; i++) {
                    var h = hash[i];
                    if (i < 2) {
                        if (i === 0) {
                            actionName = h;
                        } else if (false) {
                            attrMarker = i;
                            break;
                        } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                            actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
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
                if (hash.length >= 4) {
                    $.wa_blog.plugins.hash = hash.slice(0, 2).join('/');
                }
                this.preAnyAction(actionName, attr);
                if (this[actionName + 'Action']) {
                    this[actionName + 'Action'].apply(this, attr);
                    // save last page to return to by default later
                    $.storage.set('blog/plugins/hash', hash.join('/'));
                } else {
                    if (console) {
                        console.log('Invalid action name:', actionName + 'Action');
                    }
                }
            } else {
                this.preAnyAction();
                this.defaultAction();
            }
        } else {
            this.preAnyAction();
            this.defaultAction();
        }
    },

    preAnyAction: function (actionName, attr) {
        // $.photos.highlightSidebarItem(actionName, attr);
        // $.photos.unsetArrowsPhotostreamNavigation();
    },
    settingsGenericAction: function (plugin_slug) {
        if (this.options.plugins[plugin_slug]) {
            this.load('?module=plugins&action=settings&slug=' + plugin_slug, plugin_slug);
        } else {
            this.defaultAction();
        }
    },
    settingsCustomAction: function (plugin_slug) {
        if (this.options.plugins[plugin_slug]) {
            this.load('?plugin=' + plugin_slug + '&action=settings', plugin_slug);
        } else {
            this.defaultAction();
        }
    },
    select: function (plugin_slug) {

        $('#plugin-list li.selected').removeClass('selected');
        $('#plugin-list li#plugin-' + plugin_slug).addClass('selected');
    },

    defaultAction: function () {
        $.storage.del('blog/plugins/hash');
        $.wa.setHash('#/');
    },

    directLoad: function (html) {
        this.container.empty();
        this.container.html(html);
    },

    load: function (url, plugin_slug) {
        var self = this;
        for (id in self.js) {
            $.wa_blog.common.ajaxPurge(id);
        }
        self.container.load(url, function () {
            self.select(plugin_slug);
            self.js = $.wa_blog.common.ajaxInit();
        });
    }
};
