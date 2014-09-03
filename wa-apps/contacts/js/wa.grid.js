(function($) {
    $.wa.grid = {
        
        count: 0,
        
        // last loaded data by grid
        data: {},
        
        highlight_terms: [],
        
        init: function () {
            
            this.defaultSettings = {
                limit: 30, 
                offset: 0, 
                sort: 'name', 
                order: 1, 
                view: 'table'
            };
            this.settings = $.extend({}, this.defaultSettings);

            // Since 'change' does not propagate in IE, we cannot use it with live events.
            // In IE have to use 'click' instead.
            var that = this;
            $('#records-per-page').die($.browser.msie ? 'click' : 'change');
            $('#records-per-page').live($.browser.msie ? 'click' : 'change', function() {
                var newLimit = $(this).val();
                var newOffset = 0;
                if (that.settings && that.settings.offset) {
                    newOffset = that.settings.offset;
                }
                $.wa.setHash($.wa.grid.hash + $.wa.grid.getHash({limit: newLimit, offset: newOffset}));
            });
        },

        formatFieldValue: function(v, f) {
            if (f.id === '_access') {
                if (v == 'admin') {
                    return '<strong>'+$_('Administrator')+'</strong>';
                } else if (!v) {
                    return '<span style="color: red; white-space: nowrap">'+$_('No access')+'</span>';
                } else if (v == 'custom') {
                    return '<span style="white-space: nowrap">'+$_('Limited access')+'</span>';
                } else {
                    return v; // not used and should not be
                }
            }
            if (v) {
                return $.wa.encodeHTML(v);
            } else {
                return '';
            }
        },
                
        setHightlightTerms: function(terms) {
            var q = [];
            //var terms = data.q || ((data.info && data.info.q) ? data.info.q : []) || [];
            if ($.isArray(terms) && terms.length) {
                for (var i = 0, n = terms.length; i < n; i += 1) {
                    q.push(terms[i] || '');
                }
            } else if (typeof terms === 'string') {
                q.push(terms || '');
            }
            return this.highlight_terms = terms;
        },
                
        highlight: function(t) {
            var r = t;
            var q = this.highlight_terms;
            if (r && !$.isEmptyObject(q)) {
                var parser = $.parseXML ? $.parseXML : $.parseHTML;
                var replacer = function(r) {
                    for (var i = 0, n = q.length; i < n; i += 1) {
                        if (q[i]) {
                            r = ('' + r).replace(
                                new RegExp('(' + 
                                    q[i].replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&") + 
                                ')', 'ig'), 
                                '<span class="highlighted">$1</span>'
                            );
                        }
                    }
                    return r;
                };
                try {
                    var id = 'wrapper' + ('' + Math.random()).slice(2);
                    var elem = parser.call($, '<div id="' + id + '">' + r + '</div>');
                    var workup = function(elem) {
                        return elem.contents().map(function(i, el) {
                            if (el.nodeType == 3) {
                                return replacer($.wa.encodeHTML($(el).text()));
                            } else {
                                if ($(el).contents().length) {
                                    var html = $('<div>');
                                    workup($(el)).each(function(i, e) {
                                        html.append(e);
                                    });
                                    return html.find(':first').get(0);
                                } else {
                                    return el;
                                }
                            }
                        });
                    };
                    var html = $('<div>');
                    workup($(elem).find('#' + id)).each(function(i, el) {
                        html.append(el);
                    });
                    return html.html();
                } catch (error) {
                    if (console) {
                        console.log([r, error]);
                    }
                    return replacer(r);
                }
            }
            return r;
        },
                
        formNamesHtml: function(contact) {
            var name = this.highlight($.wa.encodeHTML(contact.name));
            var lastname = this.highlight($.wa.encodeHTML(contact.lastname));
            var middlename = this.highlight($.wa.encodeHTML(contact.middlename));
            var firstname = this.highlight($.wa.encodeHTML(contact.firstname));

            var strong = false;

            var contacted = [
                    firstname,
                    middlename,
                    lastname
            ].join(' ').trim();

            if (contact.lastname) {
                lastname = '<strong>' + lastname + '</strong>';
                strong = true;
            } else if (contact.firstname) {
                firstname = '<strong>' + firstname + '</strong>';
                strong = true;
            } else if (contact.middlename) {
                middlename = '<strong>' + middlename + '</strong>';
                strong = true;
            }

            var contacted_html = [
                firstname,
                middlename,
                lastname
            ].join(' ').trim();

            var name_html = [];

            // person
            if (!parseInt(contact.is_company, 10)) {

                if (contact.title) {
                    name_html.push("<span class='title'>" + this.highlight($.wa.encodeHTML(contact.title)) + "</span>");
                }
                if (contacted) {
                    name_html.push(contacted_html);
                } else if (name) {
                    name_html.push(name);
                } else {
                    name_html.push($('<span></span>').text($_("<no-name>")).html());
                }

                name_html = name_html.join(' ').trim();

                var company_html = '';
                if (contact.jobtitle || contact.company) {
                    if (contact.jobtitle) {
                        company_html += '<span class="title">' + this.highlight($.wa.encodeHTML(contact.jobtitle)) + '</span>';
                    }
                    if (contact.jobtitle && contact.company) {
                        company_html += ' <span class="at">'+$_('@')+'</span> ';
                    }
                    if (contact.company) {
                        if (strong) {
                            company_html += '<span class="company">' + this.highlight($.wa.encodeHTML(contact.company))  + '</span>';
                        } else {
                            company_html += '<span class="company"><strong>' + this.highlight($.wa.encodeHTML(contact.company))  + '</strong></span>';
                        }
                    }
                }

                return [name_html, company_html];

            } else {
                company_html = '';
                if (strong) {
                    company_html += (contact.company ? this.highlight($.wa.encodeHTML(contact.company)) : name);
                } else {
                    company_html += (contact.company ? "<strong>" + this.highlight($.wa.encodeHTML(contact.company)) : name) + "</strong>";
                }

                return [company_html];
            }

        },

        viewtable: function(data, no_pading) {
            var html = tmpl('template-contacts-list-table', data);
            this.count = data.count;
            if (!no_pading) {
                html += this.getPaging(data.count);
            }
            return html;
        },

        viewlist: function(data) {
            var html = tmpl('template-contacts-list-list', data);
            this.count = data.count;
            html += this.getPaging(data.count);
            return html;
        },

        viewthumbs: function(data) {
            var html = tmpl('template-contacts-list-thumbs', data);
            this.count = data.count;
            html += this.getPaging(data.count);
            return html;
        },

        load: function (url, ps, elem, hash, options) {
            this.url = url;
            this.options = options;
            this.hash = hash;
            this.settings = $.extend({}, this.defaultSettings);
            
            for (var n in ps) {
                if (ps[n] !== null) {
                    this.settings[n] = ps[n];
                }
            }
            
            var self = this;
            var r = Math.random();
            $.wa.controller.random = r; // prevents a lost request from updating a page

            $.post(url, this.settings, function (response) {
                if ($.wa.controller.random != r || response.status != 'ok') {
                    return false;
                }
                
                if (response.data.contacts) {
                    $.wa.grid.data = response.data;
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

                var beforeLoadReturn = null;
                if (self.options && self.options.beforeLoad) {
                    beforeLoadReturn = self.options.beforeLoad.call($.wa.controller, response.data);
                }
                
                if (response.data.title) {
                    $.wa.controller.setTitle(response.data.title, true);
                }
                if (response.data.desc) {
                    $.wa.controller.setDesc(response.data.desc);
                }

                // Update history
                if (response.data.history) {
                    $.wa.history.updateHistory(response.data.history);
                }

                elem = $(elem);
                if (beforeLoadReturn !== false) {
                    elem.html(self.view(self.settings.view, response.data/*, active_fields*/));
                }
                if (self.options && self.options.afterLoad) {
                    self.options.afterLoad(response.data);
                }
                
                $.wa.controller.clearLastView();
                
            }, "json");
        },

        getSelected: function (int) {
            var data = [];
            $("input.selector:checked").each(function () {
                if (int) {
                    var value = parseInt(this.value, 10);
                    if (!isNaN(value)) {
                        data.push(value);
                    }
                } else {
                    data.push(this.value);
                }
            });
            return data;
        },

        setView: function (view) {
            $.wa.setHash(this.hash + this.getHash({view: view}));
            return false;
        },

        selectItems: function (obj, container) {
            container = $('#' + (container || 'contacts-container'));
            if ($(obj).is(":checked")) {
                container.find('input.selector').attr('checked', 'checked').parents('.contact-row,.item-row').addClass('selected');
            } else {
                container.find('input.selector').removeAttr('checked').parents('.contact-row,.item-row').removeClass('selected');
            }
            $.wa.controller.updateSelectedCount();
        },

//        getFieldById: function (id) {
//            for (var i = 0; i < this.fields.length; i++) {
//                if (this.fields[i].id == id) {
//                    return this.fields[i];
//                }
//            }
//            return {};
//        },

        viewlistvalue: function (v, f) {
            if (typeof v !== 'object') {
                if (f.type === 'Select') {
                    var options = f.options || {};
                    if (options[v] !== undefined) {
                        v = options[v];
                    }
                }
                return $.wa.encodeHTML(v);
            }

            var html = '';
            if (f.icon) {
                html += '<i class="icon16 ' + f.icon + '"></i>';
            }
            
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
            $("#contacts-container .contacts-data").removeClass('padded').addClass('not-padded');
            var q = data.highlight_terms || ((data.info && data.info.highlight_terms) ? data.info.highlight_terms : []) || [];
            if (typeof q === 'string') {
                q = [q];
            }
            $.wa.grid.setHightlightTerms(q);
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

        /**
         * Make paginator
         * 
         * @param {Number} n
         * @param {Boolean} show_total need to show "Total contacts" area. Default: true
         * @param {Boolean} show_options need to show "Show X records on page" selector: Default: true
         * @returns {String} html block if paginator
         */
        getPaging: function (n, show_total, show_options) {
    
            show_total = typeof show_total === 'undefined' ? true : show_total;
            show_options = typeof show_options === 'undefined' ? true : show_options;
            var html = '<div class="block paging">';

            var type = $.wa.controller.options.paginator_type;

            // "Show X records on page" selector
            if (show_options) {
                var options = '';
                var o = [30, 50, 100, 200, 500];
                for(var i = 0; i < o.length; i++) {
                    options += '<option value="'+o[i]+'"'+(this.settings.limit == o[i] ? ' selected="selected"' : '')+'>'+o[i]+'</option>';
                }
                if (n > o[0]) {
                    html += '<span class="c-page-num">'+$_('Show %s records on a page').replace('%s', '<select id="records-per-page">'+
                            options+
                        '</select>')+'</span>';
                }
            }

            // Total number of contacts in view
            if (show_total && type === 'page') {
                html += '<span class="total">'+$_('Contacts')+': '+n+'</span>';
            }

            // Pagination
            var pages = Math.ceil(n / parseInt(this.settings.limit));
            var p = Math.ceil(parseInt(this.settings.offset) / parseInt(this.settings.limit)) + 1;
            if (pages > 1) {
                if (this.hash[this.hash.length-1] != '/') {
                    this.hash += '/';
                }
                if (type === 'page') {
                    html += '<span>'+$_('Pages')+':</span>';
                }
                if (pages == 1) {
                    return '';
                }
                
                if (type === 'page') {
                    var f = 0;
                    for (var i = 1; i <= pages; i++) {
                        if (Math.abs(p - i) < 2 || i < 2 || pages - i < 1) {
                            html += '<a ' + (i == p ? 'class="selected"' : '') + ' href="#' + this.hash + this.getHash({offset: (i - 1) * this.settings.limit}) + '">' + i + '</a>';
                            f = 0;
                        } else if (f++ < 3) {
                            html += '.';
                        }
                    }
                } else {
                    html += (parseInt(this.settings.offset, 10) + 1) + '&mdash;' + Math.min(n, (parseInt(this.settings.offset, 10) + parseInt(this.settings.limit, 10)));
                    html += ' ' + $_('of') + ' '  + n;
                }
            } else if (type !== 'page') {
                if (pages <= 0) {
                    html += $_('No contacts.');
                } else {
                    html += Math.min(parseInt(this.settings.offset, 10) + 1, n) + '&mdash;' + Math.min(n, (parseInt(this.settings.offset, 10) + parseInt(this.settings.limit, 10)));
                    html += ' ' + $_('of') + ' '  + n;
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