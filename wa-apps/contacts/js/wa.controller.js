(function ($) {
    $.wa.controller = {
        /** Remains true for free (not premium) version of Contacts app. */
        free: true,

        /** Kinda constructor. All the initialization stuff. */
        init: function (options) {
            this.frontend_url = (options && options.url) || '/';
            this.backend_url = (options && options.backend_url) || '/webasyst/';

            // Initialize "persistent" storage
            $.storage = new $.store();

            // Set up AJAX to never use cache
            $.ajaxSetup({
                cache: false
            });

            this.lastView = {
                title: $.storage.get('contacts/lastview/title'),
                hash: $.storage.get('contacts/lastview/hash')
            };
            this.options = options;
            this.random = null;

            $.wa.dropdownsCloseEnable();

            // call dispatch when hash changes
            if (typeof($.History) != "undefined") {
                $.History.bind(function (hash) {
                    $.wa.controller.dispatch(hash);
                });
            }

            $.wa.errorHandler = function (xhr) {
                if (xhr.status == 404) {
                    $.wa.setHash('/contacts/all/');
                    return false;
                }
                return true;
            }

            // .selected class for selected items in list
            $("#contacts-container .contacts-data input.selector").live('click', function () {
                if ($(this).is(':radio')) {
                    $(this).parents('.contact-row').siblings().removeClass('selected');
                }

                if ($(this).is(":checked")) {
                    $(this).parents('.contact-row').addClass('selected');
                } else {
                    $(this).parents('.contact-row').removeClass('selected');
                }

                $.wa.controller.updateSelectedCount();
            });

            // Collapsible sidebar sections
            var toggleCollapse = function () {
                $.wa.controller.collapseSidebarSection(this, 'toggle');
            };
            $(".collapse-handler", $('#wa-app')).die('click').live('click', toggleCollapse);

            // Restore collapsible sections status
            this.restoreCollapsibleStatusInSidebar();

            // Collapsible subsections
            $("ul.collapsible i.darr").click(function () {
                if ($(this).hasClass('darr')) {
                    $(this).parent('li').children('ul').hide();
                    $(this).removeClass('darr').addClass('rarr');
                } else {
                    $(this).parent('li').children('ul').show();
                    $(this).removeClass('rarr').addClass('darr');
                }
            });

            // Smart menu.
            // Implement a delay before mouseover and showing menu contents.
            var recentlyOpened = null;
            var animate = function(menu) {
                if (menu.hasClass('animated')) {
                    return false;
                }
                menu.addClass('animated');
                menu.hoverIntent({
                    over: function() {
                        recentlyOpened = setTimeout(function() {
                            recentlyOpened = null;
                        }, 500);
                        menu.removeClass('disabled');
                    },
                    timeout: 0.3, // out() is called after 0.3 sec after actual mouseout
                    out: function() {
                        menu.addClass('disabled');
                        if (recentlyOpened) {
                            clearTimeout(recentlyOpened);
                            recentlyOpened = null;
                        }
                    }
                });
                return true;
            };
            $('#c-list-toolbar-menu', $('#c-core')[0]).live('mouseover', function() {
                var menu = $(this);
                if (animate(menu)) {
                    menu.mouseover();
                }
            });
            // Open/close menu by mouse click
            $('#c-list-toolbar-menu', $('#c-core')[0]).live('click', function(e) {
                var menu = $(this);

                // do not close menu if it was just opened via mouseover
                if (recentlyOpened && !menu.hasClass('disabled')) {
                    return;
                }

                // do not count clicks in nested menus
                if ($(e.target).parents('ul#c-list-toolbar-menu ul').size() > 0) {
                    return;
                }

                menu.toggleClass('disabled');
                if (!animate(menu) && recentlyOpened) {
                    clearTimeout(recentlyOpened);
                    recentlyOpened = null;
                }
            });

            // Do not save 404 pages as last hashes
            $(document).ajaxError(function() {
                $.storage.del('contacts/last-hash');
            });

            if (this.initFull) {
                this.initFull(options);
            }
        }, // end of init()

        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Dispatch-related
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        /** Cancel the next n automatic dispatches when window.location.hash changes */
        stopDispatch: function (n) {
            this.stopDispatchIndex = n;
        },


        // last hash processed by this.dispatch()
        previousHash: null,

        /** Force reload current hash-based 'page'. */
        redispatch: function() {
            this.previousHash = null;
            this.dispatch();
        },

        /**
          * Called automatically when window.location.hash changes.
          * Call a corresponding handler by concatenating leading non-int parts of hash,
          * e.g. for #/aaa/bbb/ccc/111/dd/12/ee/ff
          * a method $.wa.controller.AaaBbbCccAction(['111', 'dd', '12', 'ee', 'ff']) will be called.
          */
        dispatch: function (hash) {
            if (this.stopDispatchIndex > 0) {
                this.stopDispatchIndex--;
                return false;
            }

            if (hash === undefined) {
                hash = this.getHash();
            } else {
                hash = this.cleanHash(hash);
            }

            if (this.previousHash == hash) {
                return;
            }
            this.previousHash = hash;

            hash = hash.replace(/^[^#]*#\/*/, '');

            if (hash) {
                var save_hash = true;
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h) {
                                actionName += h.substr(0,1).toUpperCase() + h.substr(1);
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

                    if (this[actionName + 'Action']) {
                        this.currentAction = actionName;
                        this.currentActionAttr = attr;
                        this[actionName + 'Action'](attr);
                    } else {
                        save_hash = false;
                        if (console) {
                            console.log('Invalid action name:', actionName+'Action');
                        }
                    }
                } else {
                    //if (console) console.log('DefaultAction');
                    this.defaultAction();
                }

                if (hash.join) {
                    hash = hash.join('/');
                }

                // save last page to return to by default later
                if(save_hash) {
                    $.storage.set('contacts/last-hash', hash);
                }
            } else {
                //if (console) console.log('DefaultAction');
                this.defaultAction();
                $.storage.del('contacts/last-hash');
            }

            // Highlight current item in history, if exists
            this.highlightSidebar();

            $(document).trigger('hashchange', [hash]);
        },

        /** Load last page  */
        lastPage: function() {
            var hash = $.storage.get('contacts/last-hash');
            if (hash) {
                $.wa.setHash('#/'+hash);
            } else {
                this.defaultAction();
            }
        },

        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Actions (called by dispatch() when hash changes)
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        /** Called when action is not found */
        defaultAction: function () {
            this.contactsAllAction();
        },

        /** Access control form for a group. */
        groupsRightsAction: function(p) {
            if (!p || !p[0]) {
                if (console) {
                    console.log('Group id not specified for groupsRightsAction.');
                }
                return;
            }

            this.loadHTML("?module=groups&action=rights&id="+p[0], null, function() {
                this.setBlock();
            });
        },

        /** Empty form to create a user group */
        groupsCreateAction: function(params) {
            this.groupsEditAction();
        },

        /** Form to edit or create a user group */
        groupsEditAction: function(params) {
            this.loadHTML("?module=groups&action=editor"+(params && params[0] ? '&id='+params[0] : ''), null, function() {
                this.setBlock();
            });
        },

        /** Empty form to create a contacts category */
        categoriesCreateAction: function(params) {
            this.categoriesEditAction();
        },

        /** Form to edit or create a contacts category */
        categoriesEditAction: function(params) {
            this.loadHTML("?module=categories&action=editor"+(params && params[0] ? '&id='+params[0] : ''), null, function() {
                this.setBlock();
            });
        },

        /** Dialog to confirm deletion of a category. */
        categoriesDeleteAction: function(params) {
            var backOnCancel = true;
            if (!params || !params[0]) {
                params = [this.current_category_id];
                backOnCancel = false;
            }

            $.wa.dialogCreate('delete-dialog', {
                content: $('<h2>'+$_('Delete this category?')+'</h2><p>'+$_('No contacts will be deleted.')+'</p>'),
                buttons: $('<div></div>')
                    .append(
                        $('<input type="submit" class="button red" value="'+$_('Delete category')+'">').click(function() {
                            if ($(this).find('.loading').size() <= 0) {
                                $('<i style="margin: 8px 0 0 10px" class="icon16 loading"></i>').insertAfter(this);
                            }
                            $.post('?module=categories&action=delete', {'id': params[0]}, function() {
                                // Remove deleted category from sidebar
                                $.wa.controller.reloadSidebar();
                                $.wa.dialogHide();
                                $.wa.setHash('#/users/all/');
                            });
                        })
                    )
                    .append(' '+$_('or')+' ')
                    .append($('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click(function() {
                        $.wa.dialogHide();
                        if (backOnCancel) {
                            $.wa.controller.stopDispatch(1);
                            $.wa.back();
                        }
                    })),
                small: true
            });
            return false;
        },

        /** List of contacts in user group.  */
        contactsGroupAction: function (params) {
            if (!params || !params[0]) {
                return;
            }

            this.showLoading();
            var p = this.parseParams(params.slice(1), 'contacts/group/'+params[0]);
            p.fields = ['name', 'email', 'company', '_access'];
            p.query = 'group/' + params[0];
            this.loadGrid(p, '/contacts/group/' + params[0] + '/', false, {
                afterLoad: function (data) {
                    $('#list-group li[rel="group'+params[0]+'"]').children('span.count').html(data.count);
                },
                beforeLoad: function() {
                    this.current_group_id = params[0];
                    this.setBlock('contacts-list', null, ['group-actions']);
                }
            });
        },

        /** Dialog to confirm deletion of a user group. */
        groupsDeleteAction: function(params) {
            var backOnCancel = true;
            if (!params || !params[0]) {
                params = [this.current_group_id];
                backOnCancel = false;
            }

            $.wa.dialogCreate('delete-dialog', {
                content: $('<h2>'+$_('Delete this group?')+'</h2><p>'+$_('No contacts will be deleted.')+'</p>'),
                buttons: $('<div></div>')
                    .append(
                        $('<input type="submit" class="button red" value="'+$_('Delete group')+'">').click(function() {
                            $('<i style="margin: 8px 0 0 10px" class="icon16 loading"></i>').insertAfter(this);
                            $.post('?module=groups&action=delete', {'id': params[0]}, function() {
                                // Remove deleted group from sidebar
                                $('#wa-app .sidebar a[href="#/contacts/group/'+params[0]+'/"]').parent().remove();
                                if ($('#list-group').children().size() <= 0) {
                                    $.wa.controller.reloadSidebar();
                                }
                                $.wa.dialogHide();
                                $.wa.setHash('#/users/all/');
                            });
                        })
                    )
                    .append(' '+$_('or')+' ')
                    .append($('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click(function() {
                        $.wa.dialogHide();
                        if (backOnCancel) {
                            $.wa.controller.stopDispatch(1);
                            $.wa.back();
                        }
                    })),
                small: true
            });
            return false;
        },

        /** Empty form to create a new contact. */
        contactsAddAction: function (params) {
            this.loadHTML("?module=contacts&action=add"+(params && params[0] ? '&company=1' : ''), null, function() {
                this.setBlock('contacts-info');
            });
        },

        /** Contact profile */
        contactAction: function (params) {
            var p = {};
            if (params[1]) {
                p = {'tab': params[1]};
            }
            this.showLoading();
            this.load("#c-core", "?module=contacts&action=info&id=" + params[0], p, function() {
                this.setBlock('contacts-info');
            });
        },

        /** Contact photo editor */
        contactPhotoAction: function (params) {
            this.showLoading();
            this.loadHTML("?module=photo&action=editor&id=" + params[0] + (params[1] ? '&uploaded=1' : ''), {}, function() {
                this.setBlock('contacts-info');
            });
        },

        /** List of all contacts */
        contactsAllAction: function (params) {
            this.showLoading();
            this.loadGrid(this.parseParams(params, 'contacts/all'), '/contacts/all/', false, {
                beforeLoad: function() {
                    this.setBlock('contacts-list');
                },
                afterLoad: function (data) {
                    $('#sb-all-contacts-li span.count').html(data.count);
                }
            });
        },

        /** Anvanced search form (in premium contacts) or search results list, including simple search. */
        contactsSearchAction: function (params, options) {
            if (!params || !params[0] || params[0] == 'form') {
                if (!this.contactsSearchForm) {
                    if (console) {
                        console.log('No advanced search in free contacts.');
                        setTimeout(function() { $.wa.setHash('#/'); }, 1);
                    }
                    return;
                }
                return this.contactsSearchForm(params && params[0] === 'form' ? params.slice(1) : []);
            }
            this.showLoading();

            if (params[0] == 'results') {
                if (!options) {
                    options = {search: true};
                }
                params = params.slice(1);
            }
            var filters = params[0];
            if (filters.substr(0,1) == '?') {
                filters = filters.substr(1);
            }
            var p = this.parseParams(params.slice(1), 'contacts/search/'+filters);
            p.query = filters;
            if (options && options.search) {
                p.search = 1;
            }

            var hash = this.cleanHash('#/contacts/search/'+filters);
            var el = null;
            this.loadGrid(p, hash.substr(1), null, {
                beforeLoad: function() {
                    this.setBlock('contacts-list', false, ['search-actions']);
                    el = this.setTitle();
                    if (options && options.search) {
                        $("#list-main .item-search").show();
                        $("#list-main .item-search a").attr('href', '#/contacts/search/results/' + params[0] + '/');
                        //if (!this.free) {
                        //    $('<a style="display:block" href="#/contacts/search/form/' + params[0] + '/">'+$_('edit search')+'</a>').insertAfter(el);
                        //}
                        p.search = 1;
                    }
                }
            });
        },

        /** List of contacts in a category */
        contactsCategoryAction: function (params) {
            if(!params || !params[0]) {
                return;
            }

            this.showLoading();
            var p = this.parseParams(params.slice(1), 'contacts/category/'+params[0]);
            p.query = '/category/' + params[0];
            this.loadGrid(p, '/contacts/category/' + params[0] + '/', false, {
                beforeLoad: function(data) {
                    this.current_category_id = params[0];
                    this.setBlock('contacts-list', null, data && data.system_category ? [] : ['category-actions']);
                },
                afterLoad: function (data) {
                    $('#list-category li[rel="category'+params[0]+'"]').children('span.count').html(data.count);
                }
            });
        },

        /** List of all users */
        usersAllAction: function (params) {
            this.showLoading();
            var p = this.parseParams(params, 'users/all');
            p.query = '/users/all/';
            p.fields = ['name', 'email', 'company', '_access'];
            this.loadGrid(p, '/users/all/', false, {
                beforeLoad: function() {
                    this.setBlock('contacts-list');
                },
                afterLoad: function (data) {
                    $('#sb-all-users-li span.count').html(data.count);
                }
            });
        },

        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Other UI-related stuff: dialogs, form submissions etc.
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        /** Simple search submit. */
        simpleSearch: function () {
            var s = $.trim($("#search-text").val());
            if (!s) {
                return;
            }

            var q = '';
            /*if (s.indexOf('=') == -1) {*/
                s = s.replace(/\\/g, '\\\\').replace(/%/g, '\\%').replace(/_/g, '\\_').replace(/&/g, '\\&').replace(/\+/g, '%2B').replace(/\//g, '%2F');
                if (s.indexOf('@') != -1) {
                    q = "email*=" + s; //encodeURIComponent(s);
                } else {
                    q = "name*=" + s; //encodeURIComponent(s);
                }
            /*} else {
                q = s;
            }*/
            $.wa.controller.stopDispatch(1);
            $.wa.setHash("#/contacts/search/" + q + '/');
            $.wa.controller.contactsSearchAction([q], {search: true});
        },

        /**
         * Add contacts to categories and show success message above contacts list.
         * @param {Array|Number} category_ids
         * @param {Array|Number} contact_ids defaults to selected contacts
         **/
        addToCategory: function(category_ids, contact_ids) {
            contact_ids = contact_ids || $.wa.grid.getSelected();
            $.wa.controller.showMessage('', true);
            if (!contact_ids.length || !category_ids) {
                return;
            }

            $.post('?module=categories&type=add', {
                contacts: contact_ids,
                categories: category_ids
            }, function(response) {
                if (response.data && response.data.count) {
                    for (var category_id in response.data.count) {
                        $('#list-category li[rel="category' + category_id+ '"] span.count').html(response.data.count[category_id]);
                    }
                }
                $.wa.controller.showMessage(response.data.message, true);
            }, 'json');
        },

        /** Dialog to choose categories to add selected contacts to. */
        dialogAddSelectedToCategory: function() {
            if ($.wa.grid.getSelected().length <= 0 || $('#list-category li:not(.empty):not(.selected):not(.hint)').size() <= 0) {
                $.wa.controller.showMessage('<span class="errormsg">'+$_('No categories available')+'</span>', true);
                return;
            }
            var self = this;
            $('<div id="add-to-category-dialog"></div>').waDialog({
                url: '?module=categories&action=addSelected'+(self.current_category_id ? '&disabled='+self.current_category_id : '')
            });
        },

        /** Confirm to remove selected contacts from current category. */
        dialogRemoveSelectedFromCategory: function(ids) {
            ids = ids || $.wa.grid.getSelected();
            if (ids.length <= 0 || !$.wa.controller.current_category_id) {
                return;
            }
            $('<div id="confirm-remove-from-category-dialog" class="small"></div>').waDialog({
                content: $('<h2></h2>').text($_('Exclude contacts from category "%s"?').replace('%s', $('h1.wa-page-heading').text())),
                buttons: $('<div></div>')
                .append(
                    $('<input type="submit" class="button red" value="'+$_('Exclude')+'">').click(function() {
                        $('<i style="margin: 8px 0 0 10px" class="icon16 loading"></i>').insertAfter(this);
                        $.post('?module=categories&type=del', {'categories': [$.wa.controller.current_category_id], 'contacts': ids}, function(response) {
                            $.wa.dialogHide();
                            $.wa.controller.afterInitHTML = function () {
                                $.wa.controller.showMessage(response.data.message);
                            };
                            $.wa.controller.redispatch();
                        }, 'json');
                    })
                )
                .append(' '+$_('or')+' ')
                .append($('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click($.wa.dialogHide))
            });
        },

        /** Remove selected contacts from current group and show success message above contacts list.
          * Not used and probably needs to be rewritten. */
        excludeFromGroup: function () {
            var group_id = $.wa.grid.settings.group;
            $.post("?module=contacts&action=groups&type=del",
                {'group_id': group_id, 'id[]': $.wa.grid.getSelected()},
                function (response) {
                if (response.status == 'ok') {
                    $.wa.controller.afterInitHTML = function () {
                        $.wa.controller.showMessage(response.data.message);
                    };
                    $.wa.controller.redispatch();
                }
            }, "json");
        },

        /** For a set of contact ids (defaults to currently selected) show a delete confirm dialog
          * with list of links to other applications. */
        contactsDelete: function (ids) {
            ids = ids || $.wa.grid.getSelected();
            if (ids.length <= 0) {
                return;
            }
            $.wa.dialogCreate('delete-dialog', {
                content: '<h2>'+$_('Checking links to other applications...')+' <i class="icon16 loading"></i></h2>',
                url: '?module=contacts&action=links',
                small: true,
                post: {
                    'id[]': ids
                }
            });
        },

        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Helper functions
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        listTabs: [],
        addListTab: function(showCallback) {
            this.listTabs.push(showCallback);
        },

        /** Prepare application layout to load new content. */
        setBlock: function (name, title, menus) {
            if (!name) {
                name = 'default';
            }

            if (title === undefined) {
                title = $_('Loading...');
            }

            var prevBlock = this.block;
            this.block = name;
            $("#c-core .c-core-header").remove();

            var el = $('#c-core .c-core-content');
            if (el.size() <= 0) {
                el = $('#c-core').empty()
                                .append($('<div class="contacts-background"><div class="block not-padded c-core-content"></div></div>'))
                                .find('.c-core-content');
            }
            el.html('<div class="block"><h1 class="wa-page-heading">' + title + ' <i class="icon16 loading"></i></h1></div>');

            // Scroll to window top
            $.scrollTo(0);

            //
            // Some menus need to be shown near the header
            //
            // Actions with group
            if (menus && menus.indexOf('group-actions') >= 0 && this.current_group_id) {
                el.find('div.block').prepend(
                    '<ul class="menu-h c-actions">'+
                        '<li>'+
                            '<a href="#/groups/edit/'+this.current_group_id+'/"><i class="icon16 edit"></i>'+$_('Edit group')+'</a>'+
                        '</li>'+
                        '<li>'+
                            '<a href="#/groups/rights/'+this.current_group_id+'/"><i class="icon16 lock-unlocked"></i>'+$_('Customize access')+'</a>'+
                        '</li>'+
                        '<li>'+
                            '<a href="#/groups/delete/'+this.current_group_id+'/" onclick="return $.wa.controller.groupsDeleteAction();"><i class="icon16 delete"></i>'+$_('Delete')+'</a>'+
                        '</li>'+
                    '</ul>');
            } else
            // Actions with category
            if (menus && menus.indexOf('category-actions') >= 0 && this.current_category_id && this.global_admin) {
                el.find('div.block').prepend(
                    '<ul class="menu-h c-actions">'+
                        '<li>'+
                            '<a href="#/categories/edit/'+this.current_category_id+'/"><i class="icon16 edit"></i>'+$_('Edit category')+'</a>'+
                        '</li>'+
                        '<li>'+
                            '<a href="#/categories/delete/'+this.current_category_id+'/" onclick="return $.wa.controller.categoriesDeleteAction();"><i class="icon16 delete"></i>'+$_('Delete')+'</a>'+
                        '</li>'+
                    '</ul>');
            } else
            // actions with search
            if (menus && menus.indexOf('search-actions') >= 0 && !this.free) {
                el.find('div.block').prepend(
                    '<ul class="menu-h c-actions">'+
                        '<li>'+
                            '<a href="#" onclick="return $.wa.controller.saveSearchAsFilter()"><i class="icon16 save-as-filter"></i>'+$_('Save as a filter')+'</a>'+
                        '</li>'+
                    '</ul>');
            } else
            // actions with list
            if (menus && menus.indexOf('list-actions') >= 0 && this.current_view_id) {
                el.find('div.block').prepend(
                    '<ul class="menu-h c-actions">'+
                        '<li>'+
                            '<a href="#/lists/edit/'+this.current_view_id+'/"><i class="icon16 edit"></i>'+$_('Edit list')+'</a>'+
                        '</li>'+
                        '<li>'+
                            '<a href="#" onclick="return $.wa.controller.deleteList('+this.current_view_id+')"><i class="icon16 delete"></i>'+$_('Delete')+'</a>'+
                        '</li>'+
                    '</ul>');
            } else
            // actions with filter
            if (menus && menus.indexOf('view-actions') >= 0 && this.current_view_id) {
                el.find('div.block').prepend(
                    '<ul class="menu-h c-actions">'+
                        '<li>'+
                            '<a href="#/filters/edit/'+this.current_view_id+'/"><i class="icon16 edit"></i>'+$_('Edit filter')+'</a>'+
                        '</li>'+
                        '<li>'+
                            '<a href="#" onclick="return $.wa.controller.deleteList('+this.current_view_id+', true)"><i class="icon16 delete"></i>'+$_('Delete')+'</a>'+
                        '</li>'+
                    '</ul>');
            }

            switch (name) {
                case 'contacts-duplicates':
                case 'contacts-list':
                    // Tabs above contacts list
                    if (this.listTabs.length > 0) {
                        var tabs = $('<ul class="tabs" id="c-list-tabs"></ul>');

                        // currently selected view in sidebar
                        var currentView = ($('#wa-app .sidebar .selected a').attr('href') || '').replace(/^[^#]*#/g, '');
                        if (currentView[0] !== '/') {
                            currentView = '/'+currentView;
                        }

                        // Plugin tabs
                        for(var i = 0; i < this.listTabs.length; i++) {
                            if (typeof this.listTabs[i] == 'function') {
                                this.listTabs[i].call($.wa.controller, tabs, currentView);
                            }
                        }

                        if (tabs.children().size() > 0) {
                            // Contacts tab
                            tabs.prepend($('<li class="selected"></li>').append(
                                    $('<a href="#'+currentView+'">'+$_('Contacts')+'</a>').click(function() {
                                        $('#c-list-tabs .selected').removeClass('selected');
                                        $(this).parent().addClass('selected');
                                        $.wa.controller.showLoading();
                                        return true;
                                    })
                                )
                            );
                            el.append(tabs);
                        }
                    }

                    el.append('<div id="contacts-container" class="tab-content"></div>');
                    if (!el.next().hasClass('clear-left')) {
                        $('<div class="clear-left"></div>').insertAfter(el);
                    }
                    this.showMenus(menus ? menus : []);
                    el.find('#contacts-container').append('<div class="block not-padded contacts-data"></div>');
                    break;
                case 'contacts-info':
                case 'default':
                    break;
                default:
                    this.block = prevBlock;
                    throw new Error('Unknown block: '+name);
            }

            if (this.afterInitHTML) {
                this.afterInitHTML();
                this.afterInitHTML = '';
            }
        },

        /** Add or update a toolbar above contacts list. */
        showMenus: function (show) {
            // Actions with selected
            var toolbar =
                '<li>' +
                    '<a href="javascript:void(0)" class="inline-link"><b><i><strong>'+$_('Actions with selected')+'</strong></i></b><i class="icon10 darr"></i></a>' +
                        '<ul class="menu-v" id="actions-with-selected">' +
                            '<li class="line-after">'+
                                '<span id="selected-count">0</span> <span id="selected-count-word-form">'+$_(0, 'contacts selected')+'</span>'+
                            '</li>'+
                            '<li id="add-to-category-link">' +
                                '<a href="#" onclick="$.wa.controller.dialogAddSelectedToCategory(); return false"><i class="icon16 contact"></i>'+$_('Add to category')+'</a>' +
                            '</li>' +
                            ((show.indexOf('category-actions') >= 0 && this.current_category_id) ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.dialogRemoveSelectedFromCategory(); return false"><i class="icon16 contact"></i>'+$_('Exclude from this category')+'</a>'+
                                '</li>' : '') +
                            ((show.indexOf('list-actions') >= 0 && this.current_view_id) ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.dialogRemoveSelectedFromList(); return false"><i class="icon16 from-list"></i>'+$_('Exclude from this list')+'</a>'+
                                '</li>' : '') +
                            ($.wa.controller.addToListDialog ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.addToListDialog(); return false"><i class="icon16 add-to-list"></i>'+$_('Add to list')+'</a>' +
                                '</li>' : '') +
                            (($.wa.controller.contactsMerge && $.wa.controller.admin) ?
                                '<li>' +
                                    '<a href="#" onClick="$.wa.controller.contactsMerge(); return false"><i class="icon16 merge"></i>'+$_('Merge')+'</a>' +
                                '</li>' : '') +
                            ($.wa.controller.exportDialog ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.exportDialog(); return false"><i class="icon16 export"></i>'+$_('Export')+'</a>' +
                                '</li>' : '') +
                            '<li>' +
                                '<a href="#" onclick="$.wa.controller.contactsDelete(); return false" class="red" id="show-dialog-delete"><i class="icon16 delete"></i>'+$_('Delete')+'</a>' +
                            '</li>' +
                        '</ul>' +
                    '</li>';

            // View selection
            toolbar =
                '<ul id="list-views" class="menu-h float-right">' +
                    '<li rel="table"><a href="#" onclick="return $.wa.grid.setView(\'table\');"><i class="icon16 only view-table"></i></a></li>' +
                    '<li rel="list"><a href="#" onclick="return $.wa.grid.setView(\'list\');"><i class="icon16 only view-thumb-list"></i></a></li>' +
                    '<li rel="thumbs"><a href="#" onclick="return $.wa.grid.setView(\'thumbs\');"><i class="icon16 only view-thumbs"></i></a></li>' +
                '</ul>' +
                '<ul id="c-list-toolbar-menu" class="menu-h dropdown disabled">' + toolbar + '</ul>';

            var el = $('#contacts-container').find('.c-list-toolbar');
            if (el.size() <= 0) {
                el = $('<div class="block c-list-toolbar"></div>').prependTo($('#contacts-container'));
            }
            el.html(toolbar);
            $.wa.controller.updateSelectedCount();
        },

        /** Show the loading indicator in the header */
        showLoading: function() {
            var h1 = $('h1');
            if(h1.size() <= 0) {
                return; // could show it somewhere else in theory...
            }
            h1 = $(h1[0]);
            if (h1.find('.loading').size() > 0) {
                return;
            }
            h1.append('<i class="icon16 loading"></i>');
        },

        /** Hide indicator shown by this.showLoading() */
        hideLoading: function() {
            $('h1 .loading').remove();
        },

        /** Update number of selected contacts shown in Actions with selected menu. */
        updateSelectedCount: function() {
            var cnt = $("input.selector:checked").size();
            $('#selected-count').text(cnt);
            $('#selected-count-word-form').text($_(cnt, 'contacts selected'));
            if (cnt <= 0) {
                $('#actions-with-selected li').addClass('disabled');
            } else {
                $('#actions-with-selected li').removeClass('disabled');

                // if there are no categories then leave add to category link disabled
                if ($('#list-category li:not(.empty):not(.selected)').size() <= 0) {
                    $('#add-to-category-link').addClass('disabled');
                }
            }
        },

        getUrl: function () {
            return this.options.url;
        },

        /** Load html from url into main content block. */
        loadHTML: function (url, params, beforeLoadCallback, el) {
            this.showLoading();
            this.load(el || "#c-core .c-core-content", url, params, beforeLoadCallback);
        },

        /** Load content from url and put it into elem. Params are passed to url as get parameters. */
        load: function (elem, url, params, beforeLoadCallback) {
            var r = Math.random();
            this.random = r;
            $.get(url, params, function (response) {
                if ($.wa.controller.random == r) {
                    if (beforeLoadCallback) {
                        beforeLoadCallback.call($.wa.controller);
                    }
                    $(elem).html(response);
                }
            });
        },

        /** Helper function to parse contacts list params from a hash */
        parseParams: function (params, hashkey) {
            var p = {};
            if (params && params[0]) {
                p.offset = params[0];
            }
            if (params && params[1]) {
                p.sort = params[1];
            }
            if (params && params[2]) {
                p.order = params[2];
            }

            if (params && params[3]) {
                p.view = params[3];
                if (hashkey) {
                    $.storage.set('contacts/view/'+hashkey, p.view);
                }
            } else {
                // If view is not explicitly set then use the last opened view, if present
                p.view = $.storage.get('contacts/view/'+hashkey) || 'table';
            }
            if (params && params[4]) {
                p.limit = params[4];
                if (hashkey) {
                    $.storage.set('contacts/limit/'+hashkey, p.limit);
                }
            } else {
                p.limit = $.storage.get('contacts/limit/'+hashkey);
            }
            return p;
        },

        /** Helper to set browser window title. */
        setBrowserTitle: function(title) {
            document.title = title + ' — ' + this.accountName;
        },

        /** Helper to set contacts list header. */
        setTitle: function (title, options) {
            var el = $("#c-core h1.wa-page-heading");
            if (typeof title != 'undefined') {
                this.lastView = {
                    title: title,
                    hash: window.location.hash.toString()
                };
                $.storage.set('contacts/lastview/title', this.lastView.title);
                $.storage.set('contacts/lastview/hash', this.lastView.hash);

                el.text(title);
                this.setBrowserTitle(title);
                if (options && options.click) {
                    el.click(options.click);
                }
            }
            return el;
        },

        /** Helper to load contacts list */
        loadGrid: function (params, hash, url, options) {
            this.current_category_id = this.current_group_id = this.current_view_id = null;
            if (!url) {
                url = '?module=contacts&action=list';
            }
            if (!options) {
                options = {};
            }
            $.wa.grid.load(url, params, "#contacts-container .contacts-data", hash, options);
        },

        /** Append a message above contacts list. */
        showMessage: function (message, deleteContent) {
            var oldMessages = $('#c-core .wa-message');
            if (deleteContent) {
                oldMessages.remove();
                oldMessages = $();
            }

            if (!message) {
                return;
            }

            var html = $('<div class="wa-message wa-success"><a onclick="$(this).parent().empty().hide();"><i class="icon16 close"></i></a></div>')
                .prepend($('<span class="wa-message-text"></span>').append(message));

            if (oldMessages.size()) {
                $(oldMessages[0]).empty().append(html);
            } else {
                if ($("#c-core h1:first").size()) {
                    html.insertAfter($("#c-core h1:first"));
                } else {
                    $("#c-core").prepend(html);
                }
            }
        },

        /** Change current hash */
        setHash: function (hash) {
            return $.wa.setHash(this.cleanHash(hash));
        },

        /** Current hash */
        getHash: function () {
            return this.cleanHash();
        },

        /** Make sure hash has a # in the begining and exactly one / at the end.
          * For empty hashes (including #, #/, #// etc.) return an empty string.
          * Otherwise, return the cleaned hash.
          * When hash is not specified, current hash is used. */
        cleanHash: function (hash) {
            if(typeof hash == 'undefined') {
                hash = window.location.hash.toString();
            }

            if (!hash.length) {
                hash = ''+hash;
            }
            while (hash.length > 0 && hash[hash.length-1] === '/') {
                hash = hash.substr(0, hash.length-1);
            }
            hash += '/';

            if (hash[0] != '#') {
                if (hash[0] != '/') {
                    hash = '/' + hash;
                }
                hash = '#' + hash;
            } else if (hash[1] && hash[1] != '/') {
                hash = '#/' + hash.substr(1);
            }

            if(hash == '#/') {
                return '';
            }

            return hash;
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
            };

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

        /** Helper to append appropriate events to a checkbox list. */
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

        collapseSidebarSection: function(el, action) {
            if (!action) {
                action = 'coollapse';
            }
            el = $(el);
            if(el.size() <= 0) {
                return;
            }

            var arr = el.find('.darr, .rarr');
            if (arr.size() <= 0) {
                arr = $('<i class="icon16 darr">');
                el.prepend(arr);
            }
            var newStatus;
            var id = el.attr('id');
            var oldStatus = arr.hasClass('darr') ? 'shown' : 'hidden';

            var hide = function() {
                el.parent().find('ul.collapsible').hide();
                arr.removeClass('darr').addClass('rarr');
                newStatus = 'hidden';
            };
            var show = function() {
                el.parent().find('ul.collapsible').show();
                arr.removeClass('rarr').addClass('darr');
                newStatus = 'shown';
            };

            switch(action) {
                case 'toggle':
                    if (oldStatus == 'shown') {
                        hide();
                    } else {
                        show();
                    }
                    break;
                case 'restore':
                    if (id) {
                        var status = $.storage.get('contacts/collapsible/'+id);
                        if (status == 'hidden') {
                            hide();
                        } else {
                            show();
                        }
                    }
                    break;
                case 'uncollapse':
                    show();
                    break;
                //case 'collapse':
                default:
                    hide();
                    break;
            }

            // save status in persistent storage
            if (id && newStatus) {
                $.storage.set('contacts/collapsible/'+id, newStatus);
            }
        },

        /** Collapse sections in sidebar according to status previously set in $.storage */
        restoreCollapsibleStatusInSidebar: function() {
            $("#wa-app .collapse-handler").each(function(i,el) {
                $.wa.controller.collapseSidebarSection(el, 'restore');
            });
        },

        /** Gracefully reload sidebar. */
        reloadSidebar: function() {
            $.post("?module=backend&action=sidebar", null, function (response) {
                var sb = $("#wa-app .sidebar");
                sb.css('height', sb.height()+'px') // prevents blinking in some browsers
                  .html(response)
                  .css('height', '');
                $.wa.controller.highlightSidebar();
                $.wa.controller.restoreCollapsibleStatusInSidebar();
                if ($.wa.controller.initSidebarDragAndDrop) {
                    $.wa.controller.initSidebarDragAndDrop();
                }
            });
        },

        /** Add .selected css class to li with <a> whose href attribute matches current hash.
          * If no such <a> found, then the first partial match is highlighted.
          * Hashes are compared after this.cleanHash() applied to them. */
        highlightSidebar: function() {
            var currentHash = this.cleanHash(location.hash);
            var partialMatch = false;
            var match = false;
            $('#wa-app .sidebar li a').each(function(k, v) {
                v = $(v);
                var h = $.wa.controller.cleanHash(v.attr('href'));

                // Perfect match?
                if (h == currentHash) {
                    match = v;
                    return false;
                }

                // Partial match? (e.g. for urls that differ in paging only)
                if (!partialMatch && h.length > 2 && currentHash.substr(0, h.length) === h) {
                    partialMatch = v;
                }
            });

            if (!match && partialMatch) {
                match = partialMatch;
            }

            if (match) {
                $('#wa-app .sidebar .selected').removeClass('selected');

                // Only highlight items that are outside of dropdown menus
                if (match.parents('ul.dropdown').size() <= 0) {
                    var p = match.parent();
                    while(p.size() > 0 && p[0].tagName.toLowerCase() != 'li') {
                        p = p.parent();
                    }
                    if (p.size() > 0) {
                        p.addClass('selected');
                    }
                }
            }
        },

        // Custom sliding animation to hide and show tabs
        loadTabSlidingAnimation: function() {
            if ($.effects && !$.effects.slideDIB) {
                $.effects.slideDIB = function(o) {
                    return this.queue(function() {
                        // Create element
                        var el = $(this), props = ['position','top','left','width','height','margin'];

                        // Set options
                        var mode = $.effects.setMode(el, o.options.mode || 'show'); // Set Mode
                        var direction = o.options.direction || 'left'; // Default Direction

                        // Adjust
                        $.effects.save(el, props); el.show(); // Save & Show
                        $.effects.createWrapper(el).css({overflow:'hidden',display: 'inline-block',width:'auto'}); // Create Wrapper
                        var ref = (direction == 'up' || direction == 'down') ? 'top' : 'left';
                        var motion = (direction == 'up' || direction == 'left') ? 'pos' : 'neg';
                        var distance = o.options.distance || (ref == 'top' ? el.outerHeight({margin:true}) : el.outerWidth({margin:true}));
                        if (mode == 'show') {
                            el.css(ref, motion == 'pos' ? -distance : distance); // Shift
                        }

                        // Animation
                        var animation = {};
                        animation[ref] = (mode == 'show' ? (motion == 'pos' ? '+=' : '-=') : (motion == 'pos' ? '-=' : '+=')) + distance;

                        // Animate
                        el.animate(animation, { queue: false, duration: o.duration, easing: o.options.easing, complete: function() {
                            if(mode == 'hide') {
                                el.hide(); // Hide
                            }
                            $.effects.restore(el, props); $.effects.removeWrapper(el); // Restore
                            if(o.callback) {
                                o.callback.apply(this, arguments); // Callback
                            }
                            el.dequeue();
                        }});
                    });
                }; // end of $.effects.slideDIB
            }
        }
    }; // end of $.wa.controller
})(jQuery);
