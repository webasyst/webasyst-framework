(function($) {
    $.wa.grid = {
        init: function (config) {
            if (config) {
                this.config = config;
            }
            this.defaultSettings = {limit: 30, offset: 0, sort: 'name', order: 1, view: 'table'};
            this.settings = $.extend({}, this.defaultSettings);
            this.fields = [
                {id: 'photo', title: $_('Photo'), // attrs: '',
                    filter: function (data) {
                        var src;
                        if (!data.photo || data.photo == '0') {
                            src = $.wa.controller.options.url+'wa-content/img/userpic96.jpg';
                        } else if (''+parseInt(data.photo) == data.photo) {
                            src = $.wa.controller.options.url+'wa-data/public/contacts/photo/'+data.id+'/'+data.photo+'.96x96.jpg';
                        } else {
                            src = data.photo;
                        }
                        return '<div class="image"><a href="#/contact/'+data.id+'"><img src="' + src + '" /></a></div>';
                    }
                },
                {id: 'f', title: 'Field', filter: function (data, options) {
                    var h = options.hash.replace(/duplicates/, 'search');
                    return '<a href="#' + h.substr(0, h.length - 1) + '=' + encodeURIComponent(data.f) +'/0/~data/0/list/">'+ data.f + '</a>';
                }, sorted: true},
                {id: 'n', title: 'Number', sorted: true}
            ];

            for (var field_id in this.config.fields) {
                f = {
                    id: field_id,
                    title: this.config.fields[field_id].name
                };
                if (field_id == 'email') {
                    f.filter = function (d, p) {
                        if (p && p.value) {
                            return '<a href="mailto:' + encodeURIComponent(p.value.value || p.value) + '">' + $.wa.encodeHTML(p.value.value || p.value) + '</a>';
                        } else {
                            return '';
                        }
                    };
                    f.attrs = 'class="alist"';
                } else if (field_id == 'company') {
                    f['filter'] = function (data) {
                        if (data.company && !$.wa.controller.free) {
                            return '<a href="#/contacts/search/company=' + encodeURIComponent(data.company) + '/">' + $.wa.encodeHTML(data.company) + '</a>';
                        } else {
                            return $.wa.encodeHTML(data.company);
                        }
                    };
                    f['sorted'] = true;
                } else if (field_id == 'name') {
                    f['attrs'] = 'class="wa-c-name"';
                    f['filter'] = function (data) {
                        return '<a href="#/contact/'+ data.id +'">'+ $.wa.encodeHTML(data.name || '') + '</a>';
                    };
                    f['sorted'] = true;
                }
                this.fields.push(f);
            }

            this.fields.push({
                id: '_access',
                title: $_('Access'),
                filter: function(data) {
                    if (data._access == 'admin') {
                        return '<strong>'+$_('Administrator')+'</strong>';
                    } else if (!data._access) {
                        return '<span style="color: red; white-space: nowrap">'+$_('No access')+'</span>';
                    } else if (data._access == 'custom') {
                        return '<span style="white-space: nowrap">'+$_('Limited access')+'</span>';
                    } else {
                        return data._access; // not used and should not be
                    }
                }
            });
            this.fields.push({
                id: '_online_status', title: '',
                filter: function (data) {
                    switch (data._online_status) {
                        case 'online':
                            return '<i class="icon10 online"></i>';
                        default: // 'offline', 'not-complete'
                            return '';
                    }
                }
            });

            // Since 'change' does not propagate in IE, we cannot use it with live events.
            // In IE have to use 'click' instead.
            var that = this;
            $('#records-per-page').die($.browser.msie ? 'click' : 'change');
            $('#records-per-page').live($.browser.msie ? 'click' : 'change', function() {
                var newLimit = $(this).val();
                var newOffset = 0;

                // Change offset correctly
                if(that.settings && that.settings.offset) {
                    newOffset = Math.floor(that.settings.offset / newLimit)*newLimit;
                }

                $.wa.setHash($.wa.grid.hash + $.wa.grid.getHash({limit: newLimit, offset: newOffset}));
            });
        },

        load: function (url, ps, elem, hash, options) {
            this.url = url;
            this.options = options;
            this.hash = hash;
            this.settings = $.extend({}, this.defaultSettings);
            var active_fields = ['id', 'name', 'email', 'company'];
            for (var n in ps) {
                if (n != 'fields') {
                    if (ps[n] !== null) {
                        this.settings[n] = ps[n];
                    }
                } else {
                    active_fields = ps[n];
                }
            }
            if (typeof active_fields != 'string' && active_fields.join) {
                this.settings.fields = active_fields.join(',');
            } else {
                this.settings.fields = active_fields;
            }
            var self = this;
            var r = Math.random();
            $.wa.controller.random = r; // prevents a lost request from updating a page

            $.post(url, this.settings, function (response) {
                if ($.wa.controller.random != r || response.status != 'ok') {
                    return false;
                }

                // if there's no contacts on current page, but there are contacts in this view
                // then we need to change current page
                if (response.data.count && response.data.contacts && !response.data.contacts.length) {
                    var newOffset = Math.floor((response.data.count-1)/self.settings.limit)*self.settings.limit;
                    if (newOffset != self.settings.offset) {
                        $.wa.setHash($.wa.grid.hash + $.wa.grid.getHash({offset: newOffset}));
                    }
                    return false;
                }

                if (self.options && self.options.beforeLoad) {
                    self.options.beforeLoad.call($.wa.controller, response.data);
                }
                $("#contacts-container .tools-view li.selected").removeClass('selected');
                $("#contacts-container .tools-view li[rel=" + self.settings.view + "]").addClass('selected');

                if (response.data.title) {
                    $.wa.controller.setTitle(response.data.title);
                }
                if (response.data.desc) {
                    $.wa.controller.setDesc(response.data.desc);
                }
                if (response.data.fields) {
                    active_fields = response.data.fields;
                }

                // Update history
                if (response.data.history) {
                    $.wa.history.updateHistory(response.data.history);
                }

                elem = $(elem);
                elem.html(self.view(self.settings.view, response.data, active_fields));
                if (!options.hide_head) {
                    var pre = self.topLineHtml(self.settings.view);
                    if (pre) {
                        elem.before($(pre));
                    }
                }
                if (self.options && self.options.afterLoad) {
                    self.options.afterLoad(response.data);
                }
            }, "json");
        },

        getSelected: function () {
            var data = new Array();
            $("input.selector:checked").each(function () {
                data.push(this.value);
            });
            return data;
        },

        setView: function (view) {
            $.wa.setHash(this.hash + this.getHash({view: view}));
            return false;
        },

        selectItems: function (obj) {
            if ($(obj).is(":checked")) {
                $('#contacts-container').find('input.selector').attr('checked', 'checked').parents('.contact-row').addClass('selected');
            } else {
                $('#contacts-container').find('input.selector').removeAttr('checked').parents('.contact-row').removeClass('selected');
            }
            $.wa.controller.updateSelectedCount();
        },

        viewtable: function (data, active_fields) {
            var html = '<table class="zebra full-width bottom-bordered">' +
            '<thead><tr>' +
            '<th class="wa-check-td min-width"><input onclick="$.wa.grid.selectItems(this)" type="checkbox" /></th>' +
            '<th class="min-width"></th>';
            for (var i = 0; i < this.fields.length; i++) {
                if (active_fields.indexOf(this.fields[i].id) == -1) continue;
                if (this.fields[i].sorted) {
                    var p = {sort: this.fields[i].id, order: 1};
                    if (this.settings.sort == p.sort) {
                        p.order = 1 - this.settings.order;
                    }

                    html += '<th><a style="white-space:nowrap" href="#' + this.hash + this.getHash(p) + '">' +
                    this.fields[i].title +
                        (this.settings.sort == p.sort ?
                            (p.order ?
                                ' <i class="icon10 darr"></i>' :
                                ' <i class="icon10 uarr"></i>') :
                            '') +
                        '</a></th>';
                } else {
                    html += '<th>' + this.fields[i].title + '</th>';
                }
            }
            html += '</tr></thead>';
            for (var i = 0; i < data.contacts.length; i++) {

                var contact = data.contacts[i];
                html += '<tr class="contact-row">' +
                '<td><input class="selector" type="checkbox" value="' + contact.id + '" /></td>' +
                '<td></td>';
                for (var j = 0; j < this.fields.length; j++) {
                    if (active_fields.indexOf(this.fields[j].id) == -1) continue;
                    var v = contact[this.fields[j].id];
                    if (v == undefined) {
                        v = '';
                    } else if (typeof(v) == 'object') {
                        var temp_v = [];
                        for (var l = 0; l < v.length; l++) {
                            if (typeof(v[l]) == 'object') {
                                temp_v.push('<span title="' + v[l].ext + '">' + (this.fields[j].filter ? this.fields[j].filter(contact, {hash: this.hash, value: v[l].value}) : v[l].value) + '</span>');
                            } else {
                                temp_v.push($.trim(this.fields[j].filter ? this.fields[j].filter(contact, {hash: this.hash, value: v[l]}) : v[l]));
                            }
                        }
                        v = temp_v.join(', ');
                    } else if (this.fields[j].filter) {
                        v = this.fields[j].filter(contact, {hash: this.hash, value: v});
                    } else {
                        v = $.wa.encodeHTML(v);
                    }
                    html += '<td '+ (this.fields[j].attrs ? this.fields[j].attrs : '') +'>' + v + '</td>';
                }
                html += '</tr>';
            }
            html += '</table>';
            html += this.getPaging(data.count);
            return html;
        },

        getFieldById: function (id) {
            for (var i = 0; i < this.fields.length; i++) {
                if (this.fields[i].id == id) {
                    return this.fields[i];
                }
            }
            return {};
        },

        topLineHtml: function(view) {
            if (view != 'list' && view != 'thumbs') {
                return '';
            }

            var html = '<div class="c-list-top-line"><input onclick="$.wa.grid.selectItems(this)" type="checkbox"><span>'+$_('Sort by')+':</span>';

            // Sort options
            var names = {
                'name': $_('Name'),
                'company': $_('Company')
            };
            for(var k in names) {
                var p = {sort: k, order: 1};
                if (this.settings.sort == p.sort) {
                    p.order = 1 - this.settings.order;
                }
                html += '<a href="#' + this.hash + this.getHash(p) + '">'+
                            names[k]+
                            (this.settings.sort == p.sort ?
                                (p.order ?
                                    '<i class="icon10 darr"></i>' :
                                    '<i class="icon10 uarr"></i>')
                                : '')+
                        '</a>';
            }
            return html;
        },

        viewthumbs: function (data) {
            $("#contacts-container .contacts-data").removeClass('not-padded').addClass('padded');
            var html = '<ul class="thumbs li100px">';
            for (var i = 0; i < data.contacts.length; i++) {
                var contact = data.contacts[i];

                var f = this.getFieldById('photo');
                var photo = contact.photo;
                if (f.filter) {
                    photo = f.filter(contact, {hash: this.hash, value: photo});
                }
                var url = '#/contact/' + contact.id;
                name = contact.name;
                f = this.getFieldById('name');
                if (f.filter) {
                    name = f.filter(contact, {hash: this.hash, value: name});
                }

                // The item must be inside .contact-row container. When selected, .contact-row
                // becomes .contact-row.selected (code in $.wa.grid.selectItems() and $.wa.controller.init())
                html += '<li class="contact-row">' +
                photo +
                '<div class="c-name-check"><input class="selector" value="' + contact.id + '" type="checkbox"><a href="'+url+'">' + name + '</a></div>' +
                '<div class="status"></div>' +
                '</li>';
            }
            html += '</ul>';
            html += this.getPaging(data.count);
            return html;
        },

        viewlist: function (data) {
            $("#contacts-container .contacts-data").removeClass('padded').addClass('not-padded');
            var html = '<ul class="zebra">';
            for (var i = 0; i < data.contacts.length; i++) {
                var f = this.getFieldById('photo');
                contact = data.contacts[i];
                var photo = contact.photo;
                if (f.filter) {
                    photo = f.filter(contact, {hash: this.hash, value: photo});
                }
                name = contact.name;
                f = this.getFieldById('name');
                if (f.filter) {
                    name = f.filter(contact, {hash: this.hash, value: name});
                }
                var url = '#/contact/' + contact.id;
                html += '<li class="contact-row"><div class="profile image96px">' + photo +
                '<div class="details"><input class="selector" name="c_list_selector" value="' + contact.id + '" type="'+(this.options.selector == 'radio' ? 'radio' : 'checkbox')+'">' +
                '<p class="contact-name"><a href="'+url+'">' + name + '</a></p>';
                var skip = {
                    title: true,
                    name: true,
                    photo: true,
                    firstname: true,
                    middlename: true,
                    lastname: true,
                    locale: true,
                    timezone: true
                };
                for (var field_id in this.config.fields) {
                    if (skip[field_id]) {
                        continue;
                    }
                    if (!contact[field_id] || contact[field_id] == '0000-00-00' || (typeof contact[field_id].length != 'undefined' && !contact[field_id].length)) {
                        continue;
                    }
                    f = this.config.fields[field_id];

                    if (f.fields) {
                        if (typeof(contact[field_id]) == 'object') {
                            for (var j = 0; j < contact[field_id].length; j++) {
                                html += '<p><span class="c-details-label">' + f['name'];
                                if($.trim(contact[field_id][j].ext)) {
                                    // is it a predefined extension?
                                    if (f.ext && f.ext[contact[field_id][j].ext]) {
                                        html += ' <span>(' + f.ext[contact[field_id][j].ext] + ')</span>';
                                    } else {
                                        html += ' <span>(' + $.wa.encodeHTML(contact[field_id][j].ext) + ')</span>';
                                    }
                                }
                                html += ':</span> ' + this.viewlistvalue(contact[field_id][j], f) + '</p>';
                            }
                        } else {
                            html += '<p><span class="c-details-label">' + f['name'] + ':</span> ' + this.viewlistvalue(contact[field_id], true) + '</p>';
                        }
                    } else {
                        html += '<p><span class="c-details-label">' + f['name'] + ':</span> ';
                        if (typeof(contact[field_id]) == 'object') {
                            v = [];
                            for (var j = 0; j < contact[field_id].length; j++) {
                                v.push(this.viewlistvalue(contact[field_id][j], f));
                            }
                            html += v.join(', ');
                        } else {
                            html += this.viewlistvalue(contact[field_id], f);
                        }
                        html += '</p>';
                    }
                }
                html += '</div></div></li>';

            }
            html += '</ul>';

            if (!this.options.hide_foot) {
                html += this.getPaging(data.count);
            }
            return html;
        },

        viewlistvalue: function (v, f) {
            if (typeof(v) != 'object') {
                return $.wa.encodeHTML(v);
            }

            var html = '';

            // value should be encoded only if there's only value and ext
            var enc = true;
            for(var i in v) {
                if (i != 'ext' && i != 'value') {
                    enc = false;
                    break;
                }
            }

            if (v.value) {
                html += enc ? $.wa.encodeHTML(v.value) : v.value;
            }
            if ($.trim(v.ext) && !f.fields) {
                // is it a predefined extension?
                if (f.ext && f.ext[v.ext]) {
                    html += ' <em class="hint">' + f.ext[v.ext] + '</em>';
                } else {
                    html += ' <em class="hint">' + $.wa.encodeHTML(v.ext) + '</em>';
                }
            }
            return html;
        },

        view: function (view, data, params) {
            if (!this['view' + view]) {
                view = 'table';
            }
            $("#list-views li.selected").removeClass('selected');
            $("#list-views li[rel=" + view + "]").addClass('selected');
            return this['view' + view](data, params);
        },

        getHash: function (ps) {
            var p = {};
            for (var n in this.settings) {
                p[n] = this.settings[n];
            }
            for (var n in ps) {
                p[n] = ps[n];
            }
            var hash = p.offset + '/' + p.sort + '/' + p.order + '/' + p.view + '/' + p.limit + '/';
            return hash;
        },

        getPaging: function (n) {
            var html = '<div class="block paging">';

            // "Show X records on page" selector
            var options = '';
            var o = [30, 50, 100, 200, 500];
            for(var i = 0; i < o.length; i++) {
                options += '<option value="'+o[i]+'"'+(this.settings.limit == o[i] ? ' selected="selected"' : '')+'>'+o[i]+'</option>';
            }
            html += '<span class="c-page-num">'+$_('Show %s records on a page').replace('%s', '<select id="records-per-page">'+
                    options+
                '</select>')+'</span>';

            // Total number of contacts in view
            html += '<span class="total">'+$_('Contacts')+': '+n+'</span>';

            // Pagination
            var pages = Math.ceil(n / parseInt(this.settings.limit));
            var p = Math.ceil(parseInt(this.settings.offset) / parseInt(this.settings.limit)) + 1;
            if (pages > 1) {
                if (this.hash[this.hash.length-1] != '/') {
                    this.hash += '/';
                }

                html += '<span>'+$_('Pages')+':</span>';
                if (pages == 1) {
                    return '';
                }
                var f = 0;
                for (var i = 1; i <= pages; i++) {
                    if (Math.abs(p - i) < 3 || i < 5 || pages - i < 3) {
                        html += '<a ' + (i == p ? 'class="selected"' : '') + ' href="#' + this.hash + this.getHash({offset: (i - 1) * this.settings.limit}) + '">' + i + '</a>';
                        f = 0;
                    } else if (f++ < 3) {
                        html += '.';
                    }
                }
            }

            // Prev and next links
            if (p > 1) {
                html += '<a href="#' + this.hash + this.getHash({offset: (p - 2) * this.settings.limit}) +	'" class="prevnext"><i class="icon10 larr"></i> '+$_('prev')+'</a>';
            }
            if (p < pages) {
                html += '<a href="#' + this.hash + this.getHash({offset: p * this.settings.limit}) +	'" class="prevnext">'+$_('next')+' <i class="icon10 rarr"></i></a>';
            }

            return html + '</div>';
        }

    };
    //$.wa.grid.init();

})(jQuery);