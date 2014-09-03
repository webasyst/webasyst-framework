(function ($) {
    $.wa.controller = {
        /** Remains true for free (not premium) version of Contacts app. */
        free: true,
        
        // last 10 hashes
        hashes: [],

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
                title: null,
                hash: null,
                sort: null,
                order: null,
                offset: null
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
            };

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

            $('#c-core').off('click.contact-choose', '.contact-row a.contact').on('click.contact-choose', '.contact-row a.contact', function() {
                $.wa.controller.setLastView({
                    offset: $(this).data('offset') || 0,
                    sort: $.wa.grid.settings.sort,
                    order: $.wa.grid.settings.order,
                    title: $.wa.controller.getTitle(),
                    hash: location.hash || ''
                });
            });
            $('#c-core').off('click.contact-choose', 'a.contact.next, a.contact.prev').on('click.contact-choose', 'a.contact.next, a.contact.prev', function() {
                $.wa.controller.setLastView({
                    offset: $(this).data('offset') || 0,
                });
            });
            
        }, // end of init()

        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        // *   Dispatch-related
        // * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

        /** 
         * Cancel the next n automatic dispatches when window.location.hash changes 
         * {Number} n
         * */
        stopDispatch: function (n, push_hash) {
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
        dispatch: function (hash, args) {
            if (hash === undefined) {
                hash = this.getHash();
            } else {
                hash = this.cleanHash(hash);
            }
            var h = hash.replace(/^[^#]*#\/*/, '');
            var prev_h = (this.previousHash || '').replace(/^[^#]*#\/*/, '');
            if (h !== prev_h) {
                this.hashes.unshift(hash.replace(/^[^#]*#\/*/, ''));
            }
            if (this.hashes.length > 10) {
                this.hashes.pop();
            }
            
            if (this.stopDispatchIndex > 0) {
                this.previousHash = hash;
                this.stopDispatchIndex--;
                return false;
            }

            if (this.previousHash == hash) {
                return;
            }
            var old_hash = this.previousHash;
            this.previousHash = hash;

            var e = new $.Event('wa_before_dispatched');
            $(window).trigger(e, [hash]);
            if (e.isDefaultPrevented()) {
                this.previousHash = old_hash;
                window.location.hash = old_hash;
                return false;
            }

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
                            } else if (parseInt(h, 10) != h && h.indexOf('.') == -1) {
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
                        this[actionName + 'Action'].apply(this, [].concat([attr], args || []));
                        //this[actionName + 'Action'](attr);
                    } else {
                        save_hash = false;
                        if (console) {
                            console.log('Invalid action name:', actionName+'Action');
                        }
                        $.wa.setHash('#/contacts/all/');
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

            $(document).trigger('hashchange', [hash]); // Kinda legacy
            $(window).trigger('wa-dispatched');
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

        /** Empty form to create a user group */
        groupsCreateAction: function(params) {
            this.current_group_id = 0;
            this.groupsEditAction(params, $_('New user group'));
        },

        /** Form to edit or create a user group */
        groupsEditAction: function(params, title) {
            var title = $_('New user group');
            if (params[0]) {
                //title = $_('Edit user group');
                this.current_group_id = params[0];
                title = this.groups[this.current_group_id].name;
            }
            if ($('#c-users-sidebar-menu').length) {
                this.showLoading();
            } else {
                this.setBlock('contacts-users', title);
            }
            this.setTitle(title);
            this.load(
                '#c-core .c-core-content', "?module=groups&action=editor"+(params && params[0] ? '&id='+params[0] : ''), 
                null, 
                null,
                function() {
                    this.setTitle(title);
                    $('#c-group-edit-name').focus();
                    $('.wa-page-heading .loading').hide();
                    $('#c-users-sidebar-menu li.selected').removeClass('selected');
                    if (!params || !params[0]) {
                        $('#c-create-group-toggle').addClass('selected');
                    } else {
                        $('#list-group li[rel=group' + params[0] + ']').addClass('selected');
                    }
                }
            );
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

        deleteCustomField: function(field_id) {
            $('.custom-field-th[data-field-id="'+field_id+'"]').remove();
            $('.custom-field-td[data-field-id="'+field_id+'"]').remove();
            var len = $('.custom-field-th').length;
            if (!len) {
                $('.with-custom-fields').removeClass('with-custom-fields');
                $('.list-with-custom-fields').removeClass('list-with-custom-fields');
            } else {
                var bounds = ['first', 'last'];
                for (var i = 0; i < bounds.length; i+= 1) {
                    var bound = bounds[i];
                    var th = $('.custom-field-th.' + bound);
                    if (!th.length) {
                        th.removeClass(bound);
                        $('.custom-field-td.' + bound).removeClass(bound);
                        $('.custom-field-th:' + bound).addClass(bound);
                        $('.contact-row').each(function() {
                            $(this).find('.custom-field-td:' + bound).addClass(bound);
                        });
                    }
                }
            }
        },
        
        contactsGroupAction: function (params) {
            if (!params || !params[0]) {
                return;
            }
            var p = this.parseParams(params.slice(1), 'contacts/group/'+params[0]);
            p.fields = ['name', 'email', 'company', '_access'];
            p.query = 'group/' + params[0];
            $('.wa-page-heading').css({
                width: ''
            });
            this.loadGrid(p, '/contacts/group/' + params[0] + '/', false, {
                beforeLoad: function(data) {
                    this.current_group_id = params[0];
                    if (data.count > 0) {
                        this.setBlock('contacts-users', null, ['group-settings', 'group-actions']);
                    } else {
                        this.setBlock('contacts-users', null, 'group-settings');
                        
                        $('#contacts-container').html(
                            '<div class="block double-padded" style="margin-top: 35px;">' + 
                                '<p>' + $_('No users in this group.') + '</p> <p>' + 
                                    $_('To add users to group, go to <a href="#/users/all/">All users</a>, select them, and click <strong>Actions with selected / Add to group</strong>.') +
                                '</p>' + 
                            '</div>'
                        );
                        return false;
                    }
                },
                afterLoad: function (data) {
                    $('#list-group li[rel="group'+params[0]+'"]').children('span.count').html(data.count);
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
                                if ($('#list-group').find('.c-group').length <= 0) {
                                    $('#list-group').find('.c-shown-on-no-groups').show();
                                }
                                delete $.wa.controller.groups[params[0]];
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

        clearLastView: function() {
            this.lastView = {
                title: null,
                hash: null,
                sort: null,
                order: null,
                offset: null
            };
        },

        /** Empty form to create a new contact. */
        contactsAddAction: function (params) {
            this.setBlock('contacts-info');
            this.load($("#c-core .c-core-content"), "?module=contacts&action=add"+(params && params[0] ? '&company=1' : ''), {}, null, function() {
                $.wa.controller.setTitle($_('New contact'), true);
                $.wa.controller.clearLastView();
            });
        },

        /** Contact profile */
        contactAction: function (params) {
            var p = {};
            if (params[1]) {
                p = {'tab': params[1]};
            }
            if (this.lastView && this.lastView.hash !== null) {
                p['last_hash'] = this.lastView.hash;
                p['sort'] = this.lastView.sort;
                p['offset'] = this.lastView.offset;
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
            if (!params[5]) {
                p.view = 'list';
            }
            var hash = this.cleanHash('#/contacts/search/'+filters);
            $.wa.controller.setBlock('contacts-list', null, ['search-actions']);
            this.loadGrid(p, hash.substr(1), null, {
                afterLoad: function(data) {
                    $.wa.controller.hideLoading();
                    if (options && options.search) {
                        $("#list-main .item-search").show();
                        $("#list-main .item-search a").attr('href', '#/contacts/search/results/' + params[0] + '/');
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
                    this.setBlock('contacts-users', $_('All users'), ['group-actions']);
                },
                afterLoad: function (data) {
                    $('#sb-all-users-li span.count').html(data.count);
                    $('#c-core .sidebar ul.stack li:first').addClass('selected');
                }
            });
        },
        
        usersAddAction: function(params) {
            this.setBlock('contacts-users', $_('New user'), false);
            this.setTitle($_('New user'));
            $('.wa-page-heading').find('.loading').hide();
            $('.contacts-data').html(
                '<div class="block double-padded">' + 
                    '<p>' + 
                        $_('You can grant access to your account backend to any existing contact.') + '<br><br>' +
                        $_('Find a contact, or <a href="#/contacts/add/">create a new contact</a>, and then customize their access rights on Access tab.') + 
                    '</p>' + 
                '</div>');
        },

        addToGroupDialog: function () {
            if ($.wa.grid.getSelected().length <= 0) {
                return false;
            }
            $.wa.controller.last_selected = $.wa.grid.getSelected();
            $.wa.dialogCreate('c-d-add-to-group', {
                url: "?module=groups&action=add"
            });
        },
                
        addToGroup: function(ids) {
            $.post('?module=groups&action=contactSave', {
                    'id[]': $.wa.grid.getSelected(),
                    'groups[]': ids || []
                }, function (response) {
                    $.wa.controller.last_selected = [];
                    if (response.status === "ok") {
                        $.wa.controller.updateGroupCounters(response.data.counters || {});
                        $.wa.controller.afterInitHTML = function () {
                            $.wa.controller.showMessage(response.data.message, true, 'float-left max-width');
                        };
                        $.wa.controller.redispatch();
                        $.wa.dialogHide();
                        $.wa.grid.selectItems($('#c-select-all-items').attr('checked', false));
                    } else {
                        $.wa.controller.showMessage(response.data.message);
                    }
            }, "json");
        },
        
        updateGroupCounters: function(counters) {
            if (!$.isEmptyObject(counters)) {
                for (var id in counters) {
                    if (counters.hasOwnProperty(id)) {
                        var cnt = counters[id] || 0;
                        $('#list-group').find('li[rel=group'+id+'] .count').text(cnt);
                        if (this.groups[id]) {
                            this.groups[id].cnt = cnt;
                        }
                    }
                }
            }
        },

        merge: function() {
            var selected = $.wa.grid.getSelected();
            if (selected.length < 2) {
                return false;
            }
            var hash = selected.join(',');
            $.wa.setHash('/contacts/merge/' + hash);
        },

        contactsMergeAction: function (params) {
            if (params[0]) {
                var ids = [];
                var items = params[0].split(',');
                for (var i = 0; i < items.length; i += 1) {
                    if (parseInt(items[i], 10)) {
                        ids.push(items[i]);
                    }
                }
                $('#c-abc-index').remove();
                this.showLoading();
                this.load( "#c-core .c-core-content", '?module=contacts&action=mergeSelectMaster', { ids: ids });
            } else {
                $.wa.setHash('/contacts/all/');
            }
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
                //$.wa.controller.showMessage('<span class="errormsg">'+$_('No categories available')+'</span>', true);
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
                        $.post('?module=categories&action=deleteFrom', {
                                categories: [$.wa.controller.current_category_id], 
                                contacts: ids
                            },
                            function(response) {
                                $.wa.dialogHide();
                                if (response.status === 'ok') {
                                    $.wa.controller.afterInitHTML = function () {
                                        $.wa.controller.showMessage(response.data.message);
                                    };
                                    $.wa.controller.redispatch();
                                }
                            }, 'json');
                    })
                )
                .append(' '+$_('or')+' ')
                .append($('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click($.wa.dialogHide))
            });
        },
        
        /** Confirm to remove selected contacts from current category. */
        dialogRemoveSelectedFromGroup: function(ids) {
            ids = ids || $.wa.grid.getSelected();
            if (ids.length <= 0 || !$.wa.controller.current_group_id) {
                return;
            }
            $('<div id="confirm-remove-from-category-dialog" class="small"></div>').waDialog({
                content: $('<h2></h2>').text($_('Exclude users from group "%s"?').replace('%s', $('h1.wa-page-heading').text())),
                buttons: $('<div></div>')
                .append(
                    $('<input type="submit" class="button red" value="'+$_('Exclude')+'">').click(function() {
                        $('<i style="margin: 8px 0 0 10px" class="icon16 loading"></i>').insertAfter(this);
                        $.post('?module=groups&action=deleteFrom', {
                                groups: [$.wa.controller.current_group_id], 
                                contacts: ids
                            }, 
                            function(response) {
                                $.wa.dialogHide();
                                if (response.status === 'ok') {
                                    $.wa.controller.updateGroupCounters(response.data.counters || {});
                                    $.wa.controller.afterInitHTML = function () {
                                        $.wa.controller.showMessage(response.data.message, null);
                                    };
                                    $.wa.controller.redispatch();
                                }
                            }, 
                        'json');
                    })
                )
                .append(' '+$_('or')+' ')
                .append($('<a href="javascript:void(0)">'+$_('cancel')+'</a>').click($.wa.dialogHide))
            });
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
                
        addGroup: function(group) {
            if (!$.isEmptyObject(group)) {
                $.wa.controller.groups[group.id] = group;
                $('#list-group').replaceWith(this.renderGroups($.wa.controller.groups));
            }
        },
                
        renderGroups: function(groups) {
            var groups_html = '<ul class="menu-v with-icons collapsible" id="list-group">';
            groups_html 
                += '<li class="hint c-shown-on-no-groups" style="padding:0 20px; ' + (!$.isEmptyObject(groups) ? 'display: none;' : '') + '">'
                    + $_('User groups are for organizing Webasyst users and setting common access rights for groups.')
                + '</li>';
            
            if (!$.isEmptyObject(groups)) {
                var grps = [];
                for (var i in groups) {
                    if (groups.hasOwnProperty(i)) {
                        grps.push(groups[i]);
                    }
                }
                grps = grps.sort(function (s1, s2) {
                    return s1.name.toString().localeCompare(s2.name.toString());
                });
                for (var i = 0, n = grps.length; i < n; i += 1) {
                    var group = grps[i];
                    groups_html += '<li rel="group' + group.id + '" class="c-group">';
                    groups_html += '<span class="count">' + group.cnt + '</span>';
                    groups_html += '<a href="#/contacts/group/' + group.id + '/"><img src="../../wa-content/img/users/' + group.icon + '.png"> <b class="name">' + group.name + '</b></a>';
                    groups_html += '</li>';
                }
            }
            groups_html += '</ul>';
            return groups_html;
        },
                
        updateGroup: function(id, group) {
            this.groups[id] = group;
            var item = $('#list-group').find('li[rel=group' + id + ']');
            item.html(
                '<span class="count">' + group.cnt + '</span>' + 
                '<a href="#/contacts/group/' + group.id + '"><img src="../../wa-content/img/users/' + group.icon + '.png"> <b>' + group.name + '</b></a>'
            );
        },

        /** Prepare application layout to load new content. */
        setBlock: function (name, title, menus, options) {
            if (!name) {
                name = 'default';
            }

            if (title === undefined || title === null) {
                title = $_('Loading...');
            }

            var prevBlock = this.block;
            this.block = name;
            $("#c-core .c-core-header").remove();

            options = options || {};
            menus = typeof menus === 'undefined' ? [] : menus;
            
            var el = '';
            
            if (name === 'contacts-users') {
                el = $('#c-core .c-core-content');
                var groups_html = '';
                if (options.groups !== false) {
                    groups_html += this.renderGroups(options.groups || this.groups);
                }
                $('#c-core').html(
                    '<div class="shadowed" id="c-users-page">' + 
                        '<div class="sidebar left200px" style="min-height: 300px;">' + 
                            '<ul class="menu-v with-icons stack" id="c-users-sidebar-menu">' + 
                                '<li class="selected" style="margin-left: 17px;"><a class="" href="#/users/all/"><i class="icon16 user"></i>' + $_('All users') + '</a></li>' + 
                                '<li style="margin-left: 15px;"><a class="small" href="#/users/add/"><i class="icon10 add"></i>' + $_('New user') + '</a></li>' + 
                                '<li class="" style="text-transform: uppercase;"><h5 class="heading">' + $_('Groups') + '</h5></li>' + 
                                    groups_html +  
                                '<li class="small" id="c-create-group-toggle" style="margin-left: 14px;">' + 
                                    '<a href="#/groups/create/"><i class="icon10 add"></i>' + $_('New group') + '</a>' + 
                                '</li>' + 
                            '</ul>' + 
                        '</div>' + 
                        '<div class="content left200px bordered-left blank">' +
                            '<div class="block not-padded c-core-content">' + 
                                '<div class="block" style="overflow:hidden;">' + 
                                    '<div class="c-actions-wrapper float-right" style="margin: 8px;"></div>' + 
                                    '<h1 class="wa-page-heading">' + (title || $_('All users')) + ' <i class="icon16 loading"></i></h1>' +  
                                '</div>' + 
                                '<div class="block not-padded tab-content float-left" style="width: 100%;" id="contacts-container">'  + 
                                    '<div class="block not-padded contacts-data"></div>' + 
                                '</div>' + 
                            '</div>' + 
                            '<div class="clear"></div>' + 
                        '</div>' + 
                        '<div class="clear"></div>' + 
                    '</div>');
                el = $('#c-core .c-core-content');
                $('#c-create-group-toggle').click(function() {
                    $.wa.controller.last_selected = [];
                });
                if ($.isArray(menus)) {
                    this.showMenus(menus);
                } else {
                    el.find('.c-list-toolbar').remove();
                }
            } else {
                el = $('#c-core').empty().
                    append(
                        $(
                            '<div class="contacts-background">' + 
                                '<div class="block not-padded c-core-content"></div>' + 
                            '</div>'
                        )
                    ).find(
                        '.c-core-content'
                    );
                el.html('<div class="block"><div class="c-actions-wrapper"></div><h1 class="wa-page-heading">' + title + ' <i class="icon16 loading"></i></h1></div>');
            }

            // Scroll to window top
            $.scrollTo(0);

            //
            // Some menus need to be shown near the header
            //
            // Actions with group
            if (menus && menus.indexOf('group-settings') >= 0 && this.current_group_id) {
                el.find('.c-actions-wrapper').html(
                    '<a href="#/groups/edit/' + this.current_group_id + '/"><i class="icon16 settings"></i></a>'
                );
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
                    this.showMenus((menus || []).concat(['merge', 'delete']));
                    el.find('#contacts-container').append('<div class="block not-padded contacts-data"></div>');
                    break;
                case 'contacts-users':
                case 'contacts-info':
                case 'default':
                    break;
                default:
                    this.block = prevBlock;
                    throw new Error('Unknown block: '+name);
            }

            // Kinda legacy
            if (this.afterInitHTML) {
                this.afterInitHTML();
                this.afterInitHTML = '';
            }
            $(window).trigger('wa_after_init_html', [name, title, menus]);
        },

        /** Add or update a toolbar above contacts list. */
        showMenus: function (show) {
            // Actions with selected
            var toolbar =
                '<li>' +
                    '<a href="javascript:void(0)" class="inline-link"><b><i>'+$_('Actions with selected')+' (<span id="selected-count" class="selected-count">0</span>)</i></b><i class="icon10 darr"></i></a>' +
                        '<ul class="menu-v" id="actions-with-selected">' +
                            ((show.indexOf('group-actions') >= 0) ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.addToGroupDialog(); return false"><i class="icon16 contact"></i>'+$_('Add to group')+'</a>'+
                                '</li>' : '') +
                            '<li id="add-to-category-link">' +
                                '<a href="#" onclick="$.wa.controller.dialogAddSelectedToCategory(); return false"><i class="icon16 contact"></i>'+$_('Add to category')+'</a>' +
                            '</li>' +
                            ((show.indexOf('group-actions') >= 0 && this.current_group_id) ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.dialogRemoveSelectedFromGroup(); return false"><i class="icon16 contact"></i>'+$_('Exclude from this group')+'</a>'+
                                '</li>' : '') +
                            ((show.indexOf('category-actions') >= 0 && this.current_category_id) ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.dialogRemoveSelectedFromCategory(); return false"><i class="icon16 contact"></i>'+$_('Exclude from this category')+'</a>'+
                                '</li>' : '') +
                            ((show.indexOf('merge') >= 0 && $.wa.controller.merge && $.wa.controller.admin) ?
                                '<li class="two-or-more disabled">' +
                                    '<a href="#" onClick="$.wa.controller.merge(); return false"><i class="icon16 merge"></i>'+$_('Merge contacts')+'</a>' +
                                '</li>' : '') + 
                            (show.indexOf('delete') >= 0 ?
                                '<li>' +
                                    '<a href="#" onclick="$.wa.controller.contactsDelete(); return false" class="red" id="show-dialog-delete"><i class="icon16 delete"></i>'+$_('Delete')+'</a>' +
                                '</li>' : '' )+
                        '</ul>' +
                    '</li>';

            // View selection
            toolbar =
                '<ul id="list-views" class="menu-h float-right">' +
                    '<li rel="table"><a href="#"><i class="icon16 only view-table" title="' + $_('List') + '"></i></a></li>' +
                    '<li rel="list"><a href="#" title="' + $_('Details') + '"><i class="icon16 only view-thumb-list"></i></a></li>' +
                    '<li rel="thumbs"><a href="#" title="' + $_('Userpics') + '"><i class="icon16 only view-thumbs"></i></a></li>' +
                '</ul>' +
                '<div id="c-list-toolbar-menu-wrapper">' + 
                    '<input id="c-select-all-items" onclick="$.wa.grid.selectItems(this)" type="checkbox">' + 
                    '<ul id="c-list-toolbar-menu" class="menu-h dropdown disabled" style="display:inline-block;">' + toolbar + '</ul>' + 
                '</div>';
            var el = $('#contacts-container').find('.c-list-toolbar');
            if (el.size() <= 0) {
                el = $('<div class="block c-list-toolbar"></div>').prependTo($('#contacts-container'));
            }
            el.html(toolbar);
            
            $('#contacts-container').off('click.contacts_view', '#list-views > li').on('click.contacts_view', '#list-views > li', function() {
                $.wa.grid.setView($(this).closest('li').attr('rel'));
                return false;
            });
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
                // Merge link is only active when there are 2 or more contacts selected
                if (cnt < 2) {
                    $('#actions-with-selected li.two-or-more').addClass('disabled');
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
        load: function (elem, url, params, beforeLoadCallback, afterLoadCallback) {
            $.wa.contactEditor.load.apply(this, Array.prototype.slice.apply(arguments));
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
                p.limit = $.storage.get('contacts/limit/'+hashkey) || 30;
            }
            return p;
        },

        /** Helper to set browser window title. */
        setTitle: function(title, page_header) {
            this.title = title;
            if (this.accountName) {
                document.title = title + ' — ' + this.accountName;
            } else {
                document.title = title;
            }
            if (page_header) {
                $("#c-core h1.wa-page-heading").text(typeof page_header === 'string' ? page_header : title);
            }
        },
                
        getTitle: function() {
            return this.title || '';
        },
                
        setLastView: function(options) {
            this.lastView = $.extend(this.lastView, options);
        },
                
        clearLastView: function() {
            this.lastView = {
                title: null,
                hash: null,
                sort: null,
                order: null,
                offset: null
            };
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
        showMessage: function (message, deleteContent, style) {
            var oldMessages = $('#c-core .wa-message');
            if (deleteContent) {
                oldMessages.remove();
                oldMessages = $();
            }

            if (!message) {
                return;
            }

            style = style || '';
            var html = $('<div class="wa-message wa-success ' + style + '"><a onclick="$(this).parent().empty().hide();"><i class="icon16 close"></i></a></div>')
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
                el.trigger('collapsible', ['hide']);
            };
            var show = function() {
                el.parent().find('ul.collapsible').show();
                arr.removeClass('rarr').addClass('darr');
                newStatus = 'shown';
                el.trigger('collapsible', ['show']);
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
;
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

})(jQuery);;
$.wa.history = {
    data: null,
    updateHistory: function(historyData) {
        this.data = historyData;
        var searchUl = $('#wa-search-history').empty();
        var creationUl = $('#wa-creation-history').empty();
        var currentHash = $.wa.controller.cleanHash(location.hash);
        for(var i = 0; i < historyData.length; i++) {
            var h = historyData[i];
            h.hash = $.wa.controller.cleanHash(h.hash);
            var li = $('<li rel="'+h.id+'">'+
                            (h.cnt >= 0 ? '<span class="count">'+h.cnt+'</span>' : '')+
                            '<a href="'+h.hash+'"><i class="icon16 '+h.type+'"></i></a>'+
                        '</li>');

            if (h.type == 'search' || h.type == 'import') {
                li.addClass('wa-h-type-search');
            }

            li.children('a').append($('<b>').text(h.name));

            if (h.type == 'import') {
                creationUl.append(li);
            } else if (h.type == 'add') {
                li.find('.icon16').removeClass(h.type).addClass('userpic20').css('background-image', 'url('+h.icon+')');
                creationUl.append(li);
            } else if (h.type == 'search') {
                searchUl.append(li);
            }
        }

        var lists = [searchUl, creationUl];
        for(var l = 0; l < lists.length; l++) {
            var ul = lists[l];
            if (ul.children().size() > 0) {
                ul.parents('.block.wrapper').show();
            } else {
                ul.parents('.block.wrapper').hide();
            }
        }
        $.wa.controller.highlightSidebar();
    },
    clear: function(type) {
        if (!type || type == 'search') {
            $('#wa-search-history').parents('.block.wrapper').hide();
            $('#wa-search-history').empty();
            type = '&ctype='+type
        } else if (type && type == 'creation') {
            $('#wa-creation-history').parents('.block.wrapper').hide();
            $('#wa-creation-history').empty();
            type = '&ctype[]=import&ctype[]=add';
        } else {
            type = '';
        }
        $.get('?module=contacts&action=history&clear=1'+type);
        return false;
    }
};

// EOF;
/**
  * Base classs for all editor factory types, all editor factories and all editors.
  * Implements JS counterpart of contactsFieldEditor with no validation.
  *
  * An editor factory can be created out of factory type (see $.wa.contactEditorFactory.initFactories())
  *
  * Editor factories create editors using factory.createEditor() method. Under the hood
  * a factory simply copies self, removes .createEditor() method from the copy and calls
  * its .initialize() method.
  */
$.wa.fieldTypesFactory = function(contactEditor, fieldType) {
    
    contactEditor = contactEditor || $.wa.contactEditorFactory();
    
    contactEditor.baseFieldType = {

        //
        // Public editor factory functions. Not available in editor instances.
        //

        contactType: 'person',
                
        options: {},

        /** For multifields, return a new (empty) editor for this field. */
        createEditor: function(contactType) {
            this.contactType = contactType || 'person';
            var result = $.extend({}, this);
            delete result.createEditor; // do not allow to use instance as a factory
            delete result.initializeFactory;
            result.parentEditorData = {};
            result.initialize();
            return result;
        },

        //
        // Editor properties set in subclasses.
        //

        /** Last value set by setValue() (or constructor).
          * Default implementation expects fieldValue to be string.
          * Subclasses may store anything here. */
        fieldValue: '',

        //
        // Editor functions that should be redefined in subclasses
        //

        /** Factory constructor. */
        initializeFactory: function(fieldData, options) {
            this.fieldData = fieldData;
            this.options = options || {};
        },

        /** Editor constructor. Should set all appropriate fields as if
          * this.setValue() got called with an empty data (with no record in db).
          * this.fieldData is available for standalone fields,
          * or empty {} for subfields of a multifield. */
        initialize: function() {
            this.setValue('');
        },

        reinit: function() {
            this.currentMode = 'null';
            this.initialize();
        },

        /** Load field contents from given data and update DOM. */
        setValue: function(data) {
            this.fieldValue = data;
            if (this.currentMode == 'null' || this.domElement === null) {
                return;
            }

            if (this.currentMode == 'edit') {
                this.domElement.find('.val').val(this.fieldValue);
            } else {
                this.domElement.find('.val').html(this.fieldValue);
            }
        },

        /** Get data from this field after (possible) user modifications.
          * @return mixed Data object as accepted by this.setValue() and server-side handler. */
        getValue: function() {
            var result = this.fieldValue;
            if (this.currentMode == 'edit' && this.domElement !== null) {
                var input = this.domElement.find('.val');
                if (input.length > 0) {
                    result = '';
                    if (!input.hasClass('empty')) { // default values use css class .empty to grey out value
                        if (input.attr('type') != 'checkbox' || input.attr('checked')) {
                            result = input.val();
                        }
                    }
                }
            }
            return result;
        },

        /** true if this field was modified by user and now needs to save data */
        isModified: function() {
            return this.fieldValue != this.getValue();
        },

        /** Validate field value (and possibly change it if needed)
          * @param boolean skipRequiredCheck (default false) set to true to skip check required fields to be not empty
          * @return mixed Validation data accepted by showValidationErrors(), or null if no errors. Default implementation accepts simple string. */
        validate: function(skipRequiredCheck) {
            var val = this.getValue();
            if (!skipRequiredCheck && this.fieldData.required && !val) {
                return $_('This field is required.');
            }
            return null;
        },

        /** Return a new jQuery object that represents this field in given mode.
          * Use of contactEditor.wrapper is recommended if apropriate.
          * In-place editors are initialized here.
          * Must contain exactly one element, even when field is currently not visible.
          * Default implementation uses this.newInlineFieldElement(), wraps it and initializes in-place editor.
          */
        newFieldElement: function(mode) {
            if(this.fieldData.read_only) {
                mode = 'view';
            }
            var inlineElement = this.newInlineFieldElement(mode);

            // Do not show anything if there's no inline element
            if(inlineElement === null && (!this.fieldData.show_empty || mode == 'edit')) {
                return $('<div style="display: none" class="field" data-field-id="' + this.fieldData.id + '"></div>');
            }

            var nameAddition = '';
            if (mode == 'edit') {
                //nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
            }
            var cssClass;
            if (this.contactType === 'person') {
                if (['firstname', 'middlename', 'lastname'].indexOf(this.fieldData.id) >= 0) {
                    cssClass = 'subname';
                    inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'title') {
                    cssClass = 'subname title';
                    inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'jobtitle') {
                    cssClass = 'jobtitle-company jobtitle';
                    //inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                } else if (this.fieldData.id === 'company') {
                    cssClass = 'jobtitle-company company';
                    //inlineElement.find('.val').attr('placeholder', this.fieldData.name);
                }
            } else if (this.fieldData.id === 'company') {
                cssClass = 'company';
            }
            return contactEditor.wrapper(inlineElement, this.fieldData.name+nameAddition, cssClass).attr('data-field-id', this.fieldData.id);
        },

        /** When used as a part of multi or composite field, corresponding wrapper
          * uses this function (if defined and not null) instead of newFieldElement().
          * Unwrapped value (but still $(...) wrapped) is expected. If null returned, field is not shown.
          */
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input class="val" type="text"></span>');
                result.find('.val').val(this.fieldValue);
            } else {
                result = $('<span class="val"></span>');
                result.text(this.fieldValue);
            }
            return result;
        },

        /** Remove old validation errors if any and show given error info for this field.
          * Optional to redefine in subclasses.
          * Must be redefined for editors that do not use the default contactEditor.wrapper().
          * Default implementation accepts simple string.
          * @param errors mixed Validation error data as generated by this.validate() (or server), or null to hide all errors. */
        showValidationErrors: function(errors) {
            if (this.domElement === null) {
                return;
            }

            var input = this.domElement.find('.val');
            input.parents('.value').children('em.errormsg').remove();

            if (errors !== null) {
                input.parents('.value').append($('<em class="errormsg">'+errors+'</em>'));
                input.addClass('error');
            } else {
                input.removeClass('error');
            }
        },

        //
        // Public properties that can be used in editors
        //

        /** Field data as returned from $typeClass->getValue() for this class in PHP.
          * When this field is a subfield for a multifield, this var contains
          * {id: null, multi: false, name: 'Subfield Name'} */
        fieldData: null,

        /** jQuery object that contains wrapping DOM element that currently
          * represents this field in #contact-info-block. When not null,
          * always contains exactly one element, even if field is currently not visible. */
        domElement: null,

        /** Is domElement in 'view', 'edit' or 'null' mode. */
        currentMode: 'null',

        /** Editor that uses this one as a subfield */
        parentEditor: null,

        /** Any data that parent would want to put here. */
        parentEditorData: null,

        //
        // Public editor functions
        //

        /** Set given editor mode and return DOM element that represents this field.
          * If this editor is already initialized (i.e. this.currentMode is not 'null'),
          * this function replaces old this.domElement in DOM with new value.
          * @param mode string 'edit' or 'view'
          * @param replaceEditor boolean (optional, default true) pass false to avoid creating dom element (e.g. to use as a subfield)
          */
        setMode: function(mode, replaceEditor) {
            if (typeof replaceEditor == 'undefined') {
                replaceEditor = true;
            }
            if (mode != 'view' && mode != 'edit') {
                throw new Error('Unknown mode: '+mode);
            }

            if (this.currentMode != mode) {
                this.currentMode = mode;
                if (replaceEditor) {
                    var oldDom = this.domElement;
                    this.domElement = this.newFieldElement(mode);
                    if (oldDom !== null) {
                        oldDom.replaceWith(this.domElement);
                    }
                }
            }

            return this.domElement;
        }
    }; // end of baseFieldType

    //
    // Factory Types
    //

    contactEditor.factoryTypes.Hidden = $.extend({}, contactEditor.baseFieldType, {
        newFieldElement: function(mode) {
            var inlineElement = this.newInlineFieldElement(mode);
            return contactEditor.wrapper(inlineElement, this.fieldData.name).attr('data-field-id', this.fieldData.id).hide();
        },
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input type="hidden" class="val" type="text"></span>');
                result.find('.val').val(this.fieldValue);
            }
            return result;
        }
    });

    contactEditor.factoryTypes.String = $.extend({}, contactEditor.baseFieldType, {
        setValue: function(data) {
            this.fieldValue = data;
            if (this.currentMode == 'null' || this.domElement === null) {
                return;
            }

            if (this.currentMode == 'edit') {
                this.domElement.find('.val').val(this.fieldValue);
            } else {
                this.domElement.find('.val').html(this.fieldValue);
            }
        },

        getValue: function() {
            var result = this.fieldValue;
            if (this.currentMode == 'edit' && this.domElement !== null) {
                var input = this.domElement.find('.val');
                result = '';
                if (!input.hasClass('empty')) { // default values use css class .empty to grey out value
                    result = input.val();
                }
            }
            return result;
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }

            var result = null;
            var value = this.fieldValue;
            if (mode == 'edit') {
                if (this.fieldData.input_height <= 1) {
                    result = $('<span><input class="val" type="text"><i class="icon16 loading" style="display:none;"></i></span>');
                } else {
                    result = $('<span><textarea class="val" rows="'+this.fieldData.input_height+'"></textarea></span>');
                }
                result.find('.val').val(value);
            } else {
                result = $('<span class="val"></span><i class="icon16 loading" style="display:none;">').text(value);
            }
            return result;
        },
        
        setMode: function(mode, replaceEditor) {
            if (typeof replaceEditor == 'undefined') {
                replaceEditor = true;
            }
            if (mode != 'view' && mode != 'edit') {
                throw new Error('Unknown mode: '+mode);
            }

            if (this.currentMode != mode) {
                this.currentMode = mode;
                if (replaceEditor) {
                    var oldDom = this.domElement;
                    this.domElement = this.newFieldElement(mode);
                    if (oldDom !== null) {
                        oldDom.replaceWith(this.domElement);
                    }
                }
            }

            return this.domElement;
        },
        
        
        
    });
    contactEditor.factoryTypes.Text = $.extend({}, contactEditor.factoryTypes.String);
    contactEditor.factoryTypes.Phone = $.extend({}, contactEditor.baseFieldType);
    contactEditor.factoryTypes.Select = $.extend({}, contactEditor.baseFieldType, {
        notSet: function() {
            return '';
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }

            if(mode == 'view') {
                return $('<span class="val"></span>').text(this.fieldData.options[this.fieldValue] || this.fieldValue);
            } else {
                var options = '';
                var selected = false, attrs;
                for(var i = 0; i<this.fieldData.oOrder.length; i++) {
                    var id = this.fieldData.oOrder[i];
                    if (!selected && id == this.fieldValue && this.fieldValue) {
                        selected = true;
                        attrs = ' selected';
                    } else {
                        attrs = '';
                    }
                    if (id === '') {
                        attrs += ' disabled';
                    }
                    options += '<option value="'+id+'"'+attrs+'>' + $.wa.encodeHTML(this.fieldData.options[id]) + '</option>';
                }
                return $('<div><select class="val '  + (this.fieldData.type + '').toLowerCase() + '"><option value=""'+(selected ? '' : ' selected')+'>'+this.notSet()+'</option>'+options+'</select></div>');
            }
        }
    });
    contactEditor.factoryTypes.Conditional = $.extend({}, contactEditor.factoryTypes.Select, {

        unbindEventHandlers: function() {},

        getValue: function() {
            var result = this.fieldValue;
            if (this.currentMode == 'edit' && this.domElement !== null) {
                var input = this.domElement.find('.val:visible');
                if (input.length > 0) {
                    if (input.hasClass('empty')) {
                        result = '';
                    } else {
                        result = input.val();
                    }
                }
            }
            return result;
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            this.unbindEventHandlers();

            if(mode == 'view') {
                return $('<div></div>').append($('<span class="val"></span>').text((this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue));
            } else {
                var cond_field = this;

                // find the the field we depend on
                var parent_field_id_parts = (cond_field.fieldData.parent_field || '').split(':');
                var parent_field = contactEditor.fieldEditors[parent_field_id_parts.shift()];
                while (parent_field && parent_field_id_parts.length) {
                    var subfields = parent_field.subfieldEditors;
                    if (subfields instanceof Array) {
                        // This is a multi-field. Select the one that we're part of (if any)
                        parent_field = null;
                        for (var i = 0; i < subfields.length; i++) {
                            if (subfields[i] === cond_field.parentEditor) {
                                parent_field = subfields[i];
                                break;
                            }
                        }
                    } else {
                        // This is a composite field. Select subfield by the next id part
                        parent_field = subfields[parent_field_id_parts.shift()];
                    }
                }

                if (parent_field) {
                    var initial_value = (this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue;
                    var input = $('<input type="text" class="hidden val">').val(initial_value);
                    var select = $('<select class="hidden val"></select>').hide();
                    var change_handler;

                    var getVal = function() {
                        if (input.is(':visible')) {
                            return input.val();
                        } else if (select.is(':visible')) {
                            return select.val();
                        } else {
                            return initial_value;
                        }
                    };

                    // Listen to change events from field we depend on.
                    // setTimeout() to ensure that field created its new domElement.
                    setTimeout(function() {
                        if (!parent_field.domElement) {
                            input.show();
                            return;
                        }
                        parent_field.domElement.on('change', '.val', change_handler = function() {
                            var parent_val_element = $(this);
                            var old_val = getVal();
                            var parent_value = (parent_val_element.val() || '').toLowerCase();
                            var values = cond_field.fieldData.parent_options[parent_value];
                            if (values) {
                                input.hide();
                                select.show().children().remove();
                                for (i = 0; i < values.length; i++) {
                                    select.append($('<option></option>').attr('value', values[i]).text(values[i]).attr('selected', cond_field.fieldValue == values[i]));
                                }
                                select.val(old_val);
                            } else {
                                input.val(old_val || '').show().blur();
                                select.hide();
                            }
                        });
                        change_handler.call(parent_field.domElement.find('.val:visible')[0]);
                    }, 0);

                    cond_field.unbindEventHandlers = function() {
                        if (change_handler && parent_field.domElement) {
                            parent_field.domElement.find('.val').unbind('change', change_handler);
                        }
                        cond_field.unbindEventHandlers = function() {};
                    };

                    return $('<div></div>').append(input).append(select);
                } else {
                    return $('<div></div>').append($('<input type="text" class="val">').val(cond_field.fieldValue));
                }
            }
        }
    });
    contactEditor.factoryTypes.Region = $.extend({}, contactEditor.factoryTypes.Select, {
        notSet: function() {
//            if (this.fieldData.options && this.fieldValue && !this.fieldData.options[this.fieldValue]) {
//                return this.fieldValue;
//            }
            return '';
        },

        unbindEventHandlers: function() {},

        setCurrentCountry: function() {
            var old_country = this.current_country;
            this.current_country = this.parentEditorData.parent.subfieldEditors.country.getValue();
            if (old_country !== this.current_country) {
                delete this.fieldData.options;
                return true;
            }
            return false;
        },

        getRegionsControllerUrl: function(country) {
            return (contactEditor.regionsUrl || '?module=backend&action=regions&country=')+country;
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }

            this.unbindEventHandlers();

            var options = this.options || {};
            if (options.country !== undefined) {
                this.current_country = options.country;
            }

            if(mode == 'view') {
                return $('<div></div>').append($('<span class="val"></span>').text((this.fieldData.options && this.fieldData.options[this.fieldValue]) || this.fieldValue));
            } else {
                var region_field = this;

                // This field depends on currently selected country in address
                if (this.parentEditorData.parent && this.parentEditorData.parent.subfieldEditors.country) {
                    this.setCurrentCountry();
                    var handler;
                    $(document).on('change', 'select.country', handler = function() {
                        if (region_field.setCurrentCountry()) {
                            var prev_val = '';
                            var prev_val_el = region_field.domElement.find('.val');
                            if (prev_val_el.is('input')) {
                                prev_val = prev_val_el.val().trim();
                            } else {
                                prev_val = prev_val_el.find(':selected').text().trim();
                            }
                            
                            var lookup = function(select, val) {
                                var v = val.toLocaleLowerCase();
                                select.find('option').each(function() {
                                    if ($(this).text().trim().toLocaleLowerCase() === v) {
                                        $(this).attr('selected', true);
                                        return false;
                                    }
                                });
                            };
                            
                            region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());

                            var val_el = region_field.domElement.find('.val');
                            if (val_el.is('input') && !val_el.val()) {
                                val_el.val(prev_val);
                            } else if (val_el.is('select') && prev_val) {
                                lookup(val_el, prev_val);
                            } else {
                                region_field.domElement.unbind('load.fieldTypes').bind('load.fieldTypes', function() {
                                    var val_el = region_field.domElement.find('.val');
                                    if (val_el.is('select') && prev_val) {
                                        lookup(val_el, prev_val);
                                    }
                                });
                            }
                        }
                    });
                    region_field.unbindEventHandlers = function() {
                        $(document).off('change', 'select.country', handler);
                        region_field.unbindEventHandlers = function() {};
                    };
                }

                if (!options.no_ajax_select && this.fieldData.options === undefined && this.current_country && this.fieldData.region_countries[this.current_country]) {
                    // Load list of regios via AJAX and then show select
                    var country = this.current_country;
                    $.get(this.getRegionsControllerUrl(country), function(r) {
                        if (mode !== region_field.currentMode || country !== region_field.current_country) {
                            return;
                        }
                        region_field.fieldData.options = r.data.options || false;
                        region_field.fieldData.oOrder = r.data.oOrder || [];
                        if ($.isPlainObject(region_field.options) && region_field.options.country !== undefined) {
                            delete region_field.options.country;
                        }
                        var d = $('<div></div>');
                        d.append(region_field.newInlineFieldElement(mode).children());
                        region_field.domElement.empty().append(region_field.newInlineFieldElement(mode).children());
                        region_field.domElement.trigger('load');
                    }, 'json');
                    return $('<div></div>').append($('<i class="icon16 loading"></i>'));
                } else if (this.fieldData.options) {
                    // Show as select
                    return $('<div></div>').append(contactEditor.factoryTypes.Select.newInlineFieldElement.call(this, mode));
                } else {
                    // show as input
                    var result = $('<div></div>').append(contactEditor.baseFieldType.newInlineFieldElement.call(this, mode));
                    
                    result.find('.val').val('');
                    
                    //$.wa.defaultInputValue(result.find('.val'), this.fieldData.name+(this.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
                    result.find('.val').attr('placeholder', this.fieldData.name+(this.fieldData.required ? ' ('+$_('required')+')' : ''));
                    return result;
                }
            }
        }
    });

    contactEditor.factoryTypes.Country = $.extend({}, contactEditor.factoryTypes.Select);
    contactEditor.factoryTypes.Checklist = $.extend({}, contactEditor.baseFieldType, {
        validate: function(skipRequiredCheck) {
//            if (!skipRequiredCheck && this.fieldData.required && this.getValue().length <= 0) {
//                return $_('This field is required.');
//            }
            return null;
        },
        setValue: function(data) {
            this.fieldValue = data;

            if(this.currentMode == 'edit' && this.domElement) {
                this.domElement.find('input[type="checkbox"]').attr('checked', false);
                for (var id in this.fieldValue) {
                    this.domElement.find('input[type="checkbox"][value="'+id+'"]').attr('checked', true);
                }
            } else if (this.currentMode == 'view' && this.domElement) {
                this.domElement.find('.val').html(this.getValueView());
            }
        },
        getValue: function() {
            if(this.currentMode != 'edit' || !this.domElement) {
                return this.fieldValue;
            }

            var result = [];
            this.domElement.find('input[type="checkbox"]:checked').each(function(k,input) {
                result.push($(input).val());
            });
            return result;
        },
        getValueView: function() {
            var options = '';
            // Show categories in alphabetical (this.fieldData.oOrder) order
            for(var i = 0; i<this.fieldData.oOrder.length; i++) {
                var id = this.fieldData.oOrder[i];
                if (this.fieldValue.indexOf(id) < 0) {
                    continue;
                }
                options += (options ? ', ' : '')+'<a href="'+(this.fieldData.hrefPrefix || '#')+id+'">'+((this.fieldData.options[id] && contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</a>';
            }
            return options || $_('&lt;none&gt;');
        },
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !(this.fieldValue && this.fieldValue.length)) {
                return null;
            }

            if(mode == 'view') {
                return $('<span class="val"></span>').html(this.getValueView());
            }

            //
            // Edit mode
            //

            // Is there more than one option to select from?
            var optionsAvailable = 0; // 0, 1 or 2
            var id;
            for(id in this.fieldData.options) {
                optionsAvailable++;
                if (optionsAvailable > 1) {
                    break;
                }
            }
            // Do not show the field at all if there's no options to select from
            if (!optionsAvailable) {
                return null;
            }

            var options = '';
            for(var i = 0; i<this.fieldData.oOrder.length; i++) {
                id = this.fieldData.oOrder[i];
                options += '<li><label><input type="checkbox" value="'+id+'"';

                // Checkboxes for system categories are disabled
                if (this.fieldData.disabled && this.fieldData.disabled[id]) {
                    options += ' disabled="disabled"';
                }
                
                if ((this.fieldValue || []).indexOf(id) !== -1) {
                    options += ' checked="checked"';
                }
                
                options += ' />'+((this.fieldData.options[id] && contactEditor.htmlentities(this.fieldData.options[id])) || $_('&lt;no name&gt;'))+'</label></li>';
            }
            return contactEditor.initCheckboxList('<div class="c-checkbox-menu-container val"><div><ul class="menu-v compact with-icons c-checkbox-menu">'+options+'</ul></div></div>');
        }
    });

    contactEditor.factoryTypes.Name = $.extend({}, contactEditor.baseFieldType, {
        /** Cannot be used inline */
        newInlineFieldElement: null,

        newFieldElement: function(mode) {
            return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
        },
        setValue: function(data) {
            this.fieldValue = data;
        },
        getValue: function(forced) {
            if (this.fieldValue && !forced) {
                return this.fieldValue;
            }

            // Have to build it manually for new contacts
            var val = [];
            if (this.contactType == 'person') {
                if (contactEditor.fieldEditors.firstname) {
                    val.push(contactEditor.fieldEditors.firstname.getValue());
                }
                if (contactEditor.fieldEditors.middlename) {
                    val.push(contactEditor.fieldEditors.middlename.getValue());
                }
                if (contactEditor.fieldEditors.lastname) {
                    val.push(contactEditor.fieldEditors.lastname.getValue());
                }
            } else {
                if (contactEditor.fieldEditors.company) {
                    val.push(contactEditor.fieldEditors.company.getValue());
                }
            }
            return val.join(' ').trim();
        },

        validate: function(skipRequiredCheck) {
            var val = this.getValue(true);
            if (!skipRequiredCheck && this.fieldData.required && !val) {
                // If all name parts are empy then set firstname to be value of the first visible non-empty input:text
                var newfname = $('#contact-info-block input:visible:text[value]:not(.empty)').val();
                if (!newfname) {
                    return $_('At least one of these fields must be filled');
                }
                contactEditor.fieldEditors.firstname.setValue(newfname);
            }
            return null;
        },

        showValidationErrors: function(errors) {
            var el = $('#contact-info-block');
            el.find('div.wa-errors-block').remove();
            if (errors !== null) {
                var err = $('<div class="field wa-errors-block"><div class="value"><em class="errormsg">'+errors+'</em></div></div>');
                if (contactEditor.fieldEditors.lastname) {
                    contactEditor.fieldEditors.lastname.domElement.after(err);
                } else {
                    el.prepend(err);
                }
            }
            var a = ['firstname', 'middlename', 'lastname'];
            for(var i=0; i<a.length; i++) {
                var df = a[i];
                if (contactEditor.fieldEditors[df]) {
                    if (errors !== null) {
                        contactEditor.fieldEditors[df].domElement.find('.val').addClass('external-error');
                    } else {
                        contactEditor.fieldEditors[df].domElement.find('.val').removeClass('external-error');
                    }
                }
            }
        },
                
        setMode: function(mode, replaceEditor) {
            if (typeof replaceEditor == 'undefined') {
                replaceEditor = true;
            }
            if (mode != 'view' && mode != 'edit') {
                throw new Error('Unknown mode: '+mode);
            }

            if (this.currentMode != mode) {
                this.currentMode = mode;
                if (replaceEditor) {
                    var oldDom = this.domElement;
                    this.domElement = this.newFieldElement(mode);
                    if (oldDom !== null) {
                        oldDom.replaceWith(this.domElement);
                    }
                }
            }

            var title = '';
            if (contactEditor.fieldEditors.title) {
                title = contactEditor.fieldEditors.title.getValue()+' ';
            }
            
            if (mode === 'view' && !contactEditor.not_update_name) {

                var contact_fullname = $('#contact-fullname');

                var firstname = '';
                var middlename = '';
                var lastname = '';
                if (contactEditor.fieldEditors.firstname) {
                    var prev_mode = contactEditor.fieldEditors.firstname.currentMode;
                    contactEditor.fieldEditors.firstname.currentMode = mode;
                    firstname = contactEditor.fieldEditors.firstname.getValue();
                    contactEditor.fieldEditors.firstname.currentMode = prev_mode;
                }
                if (contactEditor.fieldEditors.middlename) {
                    var prev_mode = contactEditor.fieldEditors.middlename.currentMode;
                    contactEditor.fieldEditors.middlename.currentMode = mode;
                    middlename = contactEditor.fieldEditors.middlename.getValue();
                    contactEditor.fieldEditors.middlename.currentMode = prev_mode;
                }
                if (contactEditor.fieldEditors.lastname) {
                    var prev_mode = contactEditor.fieldEditors.lastname.currentMode;
                    contactEditor.fieldEditors.lastname.currentMode = mode;
                    lastname = contactEditor.fieldEditors.lastname.getValue();
                    contactEditor.fieldEditors.lastname.currentMode = prev_mode;
                }
                var name = [
                    firstname,
                    middlename,
                    lastname
                ].join(' ').trim();
                if (!name) {
                    name = this.fieldValue || '<'+$_('no name')+'>';
                }

                // Update page header
                var h1 = contact_fullname.find('h1.name').html('<span class="title">' + $.wa.encodeHTML(title) + '</span>' + $.wa.encodeHTML(name));

                if (this.contactType === 'person') {
                    var jobtitle_company = '';
                    var jobtitle = '';
                    var company = '';

                    if (contactEditor.fieldEditors.jobtitle) {
                        var prev_mode = contactEditor.fieldEditors.jobtitle.currentMode;
                        contactEditor.fieldEditors.jobtitle.currentMode = mode;
                        jobtitle = contactEditor.fieldEditors.jobtitle.getValue();
                        contactEditor.fieldEditors.jobtitle.currentMode = prev_mode;
                    }
                    if (contactEditor.fieldEditors.company) {
                        var prev_mode = contactEditor.fieldEditors.company.currentMode;
                        contactEditor.fieldEditors.company.currentMode = mode;
                        company = contactEditor.fieldEditors.company.getValue();
                        contactEditor.fieldEditors.company.currentMode = prev_mode;
                    }
                    if (jobtitle) {
                        jobtitle_company += '<span class="title">' + $.wa.encodeHTML(jobtitle) + '</span> ';
                    }
                    if (jobtitle && company) {
                        jobtitle_company += '<span class="at">' + $_('@') + '</span> ';
                    }
                    if (company) {
                        jobtitle_company += '<span class="company">' + $.wa.encodeHTML(company) + '</span> ';
                    }
                    contact_fullname.find('h1.jobtitle-company').html(jobtitle_company);
                }
                // Update browser title
                if (contactEditor.update_title) {
                    $.wa.controller.setTitle(h1.text());
                }
            }

            if (contactEditor.contact_id && contactEditor.contact_id == contactEditor.current_user_id) {
                // Update user name in top right hand corner
                $('#wa-my-username').text(''+(this.fieldValue ? this.fieldValue : '<'+$_('no name')+'>'));
            }

            return this.domElement;
        }
                
    });

    contactEditor.factoryTypes.NameSubfield = $.extend({}, contactEditor.baseFieldType, {});

    contactEditor.factoryTypes.Multifield = $.extend({}, contactEditor.baseFieldType, {
        subfieldEditors: null,
        subfieldFactory: null,
        emptySubValue: null,

        initializeFactory: function(fieldData) {

            this.fieldData = fieldData;
            if (typeof this.fieldData.ext != 'undefined') {
                this.fieldData.extKeys = [];
                this.fieldData.extValues = [];
                for(var i in this.fieldData.ext) {
                    this.fieldData.extKeys[this.fieldData.extKeys.length] = i;
                    this.fieldData.extValues[this.fieldData.extValues.length] = this.fieldData.ext[i];
                }
            }
        },

        initialize: function() {
            this.subfieldFactory = $.extend({}, contactEditor.factoryTypes[this.fieldData.type]);
            this.subfieldFactory.parentEditor = this;
            this.subfieldFactory.initializeFactory($.extend({}, this.fieldData));
            this.fieldData = $.extend({}, this.fieldData, {'required': this.subfieldFactory.fieldData.required});
            this.subfieldEditors = [this.subfieldFactory.createEditor(this.contactType)];
            if (typeof this.subfieldEditors[0].fieldValue == 'object') {
                this.emptySubValue = $.extend({}, this.subfieldEditors[0].fieldValue);
                if (this.fieldData.ext) {
                    this.emptySubValue.ext = this.fieldData.extKeys[0];
                }
            } else {
                this.emptySubValue = {value: this.subfieldEditors[0].fieldValue};
                if (this.fieldData.ext) {
                    this.emptySubValue.ext = this.fieldData.extKeys[0];
                }
            }
            this.fieldValue = [this.emptySubValue];
        },

        setValue: function(data) {
            // Check if there's at least one value
            if (!data || typeof data[0] == 'undefined') {
                data = [this.emptySubValue];
            }
            this.fieldValue = data;

            // Update data in existing editors
            // (If there's no data from PHP, still need to have at least one editor. Therefore, do-while.)
            var i = 0;
            do {
                // Add an editor if needed
                if (this.subfieldEditors.length <= i) {
                    this.subfieldEditors[i] = this.subfieldFactory.createEditor(this.contactType);
                    if (this.currentMode != 'null') {
                        this.subfieldEditors[i].setMode(this.currentMode).insertAfter(this.subfieldEditors[i-1].parentEditorData.domElement);
                    }
                }
                if (typeof data[i] != 'undefined') {
                    // if data[i] contain only ext and value, then pass value to child;
                    // if there's something else, then pass the whole object.
                    var passObject = false;
                    for(var k in data[i]) {
                        if (k != 'value' && k != 'ext') {
                            passObject = true;
                            break;
                        }
                    }

                    this.subfieldEditors[i].setValue(passObject ? data[i] : (data[i].value ? data[i].value : ''));

                    // save ext
                    if (typeof this.fieldData.ext != 'undefined') {
                        var ext = data[i].ext;
                        if (this.currentMode != 'null' && this.subfieldEditors[i].parentEditorData.domElement) {
                            var el = this.subfieldEditors[i].parentEditorData.domElement.find('input.ext');
                            if (el.size() > 0) {
                                el[0].setExtValue(ext);
                            }
                        }
                    }
                } else {
                    throw new Error('At least one record must exist in data at this time.');
                }
                i++;

            } while(i < data.length);

            // Remove excess editors if needed
            if (data.length < this.subfieldEditors.length) {
                // remove dom elements
                for(i = data.length; i < this.subfieldEditors.length; i++) {
                    if (i === 0) { // Never remove the first
                        continue;
                    }
                    if (this.currentMode != 'null') {
                        this.subfieldEditors[i].parentEditorData.domElement.remove();
                    }
                }

                // remove editors
                var a = data.length > 0 ? data.length : 1; // Never remove the first
                this.subfieldEditors.splice(a, this.subfieldEditors.length - a);
            }

            this.origFieldValue = null;
        },

        getValue: function() {
            if (this.currentMode == 'null') {
                return $.extend({}, this.fieldValue);
            }

            var val = [];
            for(var i = 0; i < this.subfieldEditors.length; i++) {
                var sf = this.subfieldEditors[i];
                val[i] = {
                    'value': sf.getValue()
                };

                // load ext
                if (typeof this.fieldData.ext != 'undefined') {
                    var ext = this.fieldValue[i].ext;
                    var el = sf.parentEditorData.domElement.find('input.ext')[0];
                    if (sf.currentMode == 'edit' && el) {
                        ext = el.getExtValue();
                    }
                    val[i].ext = ext;
                }
            }

            return val;
        },

        isModified: function() {
            for(var i = 0; i < this.subfieldEditors.length; i++) {
                var sf = this.subfieldEditors[i];
                if (sf.isModified()) {
                    return true;
                }
            }
            return false;
        },

        validate: function(skipRequiredCheck) {
            var result = [];

            // for each subfield add a record subfieldId => its validate() into result
            var allEmpty = true;
            for(var i = 0; i < this.subfieldEditors.length; i++) {
                var sf = this.subfieldEditors[i];
                var v = sf.validate(true);
                if (v) {
                    result[i] = v;
                }

                var val = sf.getValue();
                if (val || typeof val != 'string') {
                    allEmpty = false;
                }
            }

            if (!skipRequiredCheck && this.fieldData.required && allEmpty) {
                result[0] = 'This field is required.';
            }

            if (result.length <= 0) {
                return null;
            }
            return result;
        },

        showValidationErrors: function(errors) {
            for(var i = 0; i < this.subfieldEditors.length; i++) {
                var sf = this.subfieldEditors[i];
                if (errors !== null && typeof errors[i] != 'undefined') {
                    sf.showValidationErrors(errors[i]);
                } else {
                    sf.showValidationErrors(null);
                }
            }
        },

        /** Return button to delete subfield. */
        deleteSubfieldButton: function(sf) {
            var that = this;
            var r = $('<a class="delete-subfield hint" href="javascript:void(0)">'+$_('delete')+'</a>').click(function() {
                if (that.subfieldEditors.length <= 1) {
                    return false;
                }

                var i = that.subfieldEditors.indexOf(sf);

                // remove dom element
                if (that.currentMode != 'null') {
                    that.subfieldEditors[i].parentEditorData.domElement.remove();
                }

                // remove editor
                that.subfieldEditors.splice(i, 1);

                // Hide delete button if only one subfield left
                // have to do this because IE<9 lacks :only-child support
                if (that.subfieldEditors.length <= 1) {
                    that.domElement.find('.delete-subfield').hide();
                }

                // (leaves a record in this.fieldValue to be able to restore it if needed)
                return false;
            });

            if (this.subfieldEditors.length <= 1) {
                r.hide();
            }

            return r;
        },

        newSubFieldElement: function(mode, i) {
            i = i-0;
            var sf = this.subfieldEditors[i];
            if(!sf.parentEditorData) {
                sf.parentEditorData = {};
            }
            sf.parentEditorData.parent = this;
            sf.parentEditorData.empty = false;
            var ext;

            // A (composite) field with no inline mode?
            if (typeof sf.newInlineFieldElement != 'function') {
                var nameAddition = '';
                if (mode == 'edit') {
                    //nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
                }
                var wrapper = contactEditor.wrapper('<span class="replace-me-with-value"></span>', i === 0 ? (this.fieldData.name+nameAddition) : '', 'no-bot-margins');
                var rwv = wrapper.find('span.replace-me-with-value');

                // extension
                ext = this.fieldValue[i].ext;
                if (mode == 'edit') {
                    ext = contactEditor.createExtSelect(this.fieldData.ext, ext);
                } else {
                    ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                    ext = $('<strong>'+contactEditor.htmlentities(ext)+'</strong>');
                }
                rwv.before(ext);

                // button to delete this subfield
                if (mode == 'edit') {
                    rwv.before(this.deleteSubfieldButton(sf));
                }
                rwv.remove();

                sf.domElement = this.subfieldEditors[i].newFieldElement(mode);
                var self = this;
                sf.domElement.find('div.name').each(function(i, el) {
                    if (el.innerHTML.substr(0, self.fieldData.name.length) === self.fieldData.name) {
                        el.innerHTML = '';
                    }
                });

                sf.parentEditorData.domElement = $('<div></div>').append(wrapper).append(sf.domElement);

                if (mode == 'edit') {
                    sf.parentEditorData.empty = false;
                } else {
                    sf.parentEditorData.empty = !sf.fieldValue.value && !sf.fieldData.show_empty;
                }

                //this.initInplaceEditor(sf.parentEditorData.domElement, i);
                return sf.parentEditorData.domElement;
            }

            // Inline mode is available
            var value = sf.newInlineFieldElement(mode);
            if (value === null) {
                // Field is empty, return stub.
                sf.parentEditorData.domElement = sf.domElement = $('<div></div>');
                sf.parentEditorData.empty = true;
                return sf.parentEditorData.domElement;
            }

            sf.domElement = value;
            sf.domElement.data('subfield-index', i).attr('data-subfield-index', i);
            var result = $('<div class="value"></div>').append(value);
            var rwe = result.find('.replace-with-ext');
            if (rwe.size() <= 0) {
                result.append('<span><span class="replace-with-ext"></span></span>');
                rwe = result.find('.replace-with-ext');
            }

            // Extension
            if (typeof this.fieldData.ext != 'undefined') {
                ext = this.fieldValue[i].ext;
                if (mode == 'edit') {
                    rwe.before(contactEditor.createExtSelect(this.fieldData.ext, ext));
                } else {
                    ext = this.fieldData.ext[this.fieldValue[i].ext] || ext;
                    if (rwe.parents('.ext').size() > 0) {
                        rwe.before(contactEditor.htmlentities(ext));
                    } else {
                        rwe.before($('<em class="hint"></em>').text(' '+ext));
                    }
                }
            }

            // button to delete this subfield
            if (mode == 'edit') {
                rwe.before(this.deleteSubfieldButton(sf));
            }
            rwe.remove();

            sf.parentEditorData.domElement = result;
            //this.initInplaceEditor(sf.parentEditorData.domElement, i);
            return result;
        },

        newInlineFieldElement: null,

        newFieldElement: function(mode) {
            if(this.fieldData.read_only) {
                mode = 'view';
            } 
           
            var childWrapper = $('<div class="multifield-subfields"></div>');
            var inlineMode = typeof this.subfieldFactory.newInlineFieldElement == 'function';

            var allEmpty = true;
            for(var i = 0; i < this.subfieldEditors.length; i++) {
                var result = this.newSubFieldElement(mode, i);
                childWrapper.append(result);
                allEmpty = allEmpty && this.subfieldEditors[i].parentEditorData.empty;
            }

            // do not show anything if there are no values
            if (allEmpty && !this.fieldData.show_empty) {
                return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
            }

            // Wrap over for all subfields to be in separate div
            var wrapper;
            if (inlineMode) {
                wrapper = $('<div></div>').append(childWrapper);
            } else {
                wrapper = $('<div class="field" data-field-id="'+this.fieldData.id+'"></div>').append(childWrapper);
            }

            // A button to add more fields
            if (mode == 'edit') {
                var adder;
                if (inlineMode) {
                    adder = $('<div class="value multifield-subfields-add-another"><span class="replace-me-with-value"></span></div>');
                } else {
                    adder = contactEditor.wrapper('<span class="replace-me-with-value"></span>');
                }
                var that = this;
                adder.find('.replace-me-with-value').replaceWith(
                    $('<a href="javascript:void(0)" class="small inline-link"><b><i>'+$_('Add another')+'</i></b></a>').click(function (e) {
                        var newLast = that.subfieldFactory.createEditor(this.contactType);
                        var index = that.subfieldEditors.length;

                        val = {
                            value: newLast.getValue(),
                            temp: true
                        };
                        if (typeof that.fieldData.ext != 'undefined') {
                            val.ext = '';
                        }
                        that.fieldValue[index] = val;

                        that.subfieldEditors[index] = newLast;
                        if (that.currentMode != 'null') {
                            newLast.setMode(that.currentMode);
                        }
                        childWrapper.append(that.newSubFieldElement(mode, index));
                        that.domElement.find('.delete-subfield').css('display', 'inline');
                    })
                );
                wrapper.append(adder);
            }

            if (inlineMode) {
                var nameAddition = '';
//                if (mode == 'edit') {
//                    nameAddition = (this.fieldData.required ? '<span class="req-star">*</span>' : '')+':';
//                }
                wrapper = contactEditor.wrapper(wrapper, this.fieldData.name+nameAddition).attr('data-field-id', this.fieldData.id);
            }
            return wrapper;
        },

        setMode: function(mode, replaceEditor) {
            if (typeof replaceEditor == 'undefined') {
                replaceEditor = true;
            }
            if (mode != 'view' && mode != 'edit') {
                throw new Error('Unknown mode: '+mode);
            }

            if (this.currentMode != mode) {
                // When user switches from edit to view, we need to restore
                // deleted editors, if any. So we set initial value here to ensure that.
                if (mode == 'view' && this.currentMode == 'edit' && this.origFieldValue) {
                    this.setValue(this.origFieldValue);
                } else if (this.currentMode == 'view' && !this.origFieldValue) {
                    this.origFieldValue = [];
                    for (var i = 0; i < this.fieldValue.length; i++) {
                        this.origFieldValue.push($.extend({}, this.fieldValue[i]));
                    }
                }

                this.currentMode = mode;
                if (replaceEditor) {
                    var oldDom = this.domElement;
                    this.domElement = this.newFieldElement(mode);
                    if (oldDom !== null) {
                        oldDom.replaceWith(this.domElement);
                    }
                }
            }

            for(var i = 0; i < this.subfieldEditors.length; i++) {
                this.subfieldEditors[i].setMode(mode, false);
            }

            return this.domElement;
        }
    }); // end of Multifield type

    contactEditor.factoryTypes.Composite = $.extend({}, contactEditor.baseFieldType, {
        subfieldEditors: null,

        initializeFactory: function(fieldData, options) {
            this.fieldData = fieldData;
            if (this.fieldData.required) {
                for(var i in this.fieldData.required) {
                    if (this.fieldData.required[i]) {
                        this.fieldData.required = true;
                        break;
                    }
                }
                if (this.fieldData.required !== true) {
                    this.fieldData.required = false;
                }
            }
            this.options = options || {};
        },

        initialize: function() {
            var val = {
                'data': {},
                'value': ''
            };

            this.subfieldEditors = {};
            this.fieldData.subfields = this.fieldData.fields;
            for(var sfid in this.fieldData.subfields) {
                var sf = this.fieldData.subfields[sfid];
                var editor = $.extend({}, contactEditor.factoryTypes[sf.type]);
                var sfData = this.fieldData.fields[sfid];
                if (this.fieldData.required && this.fieldData.required[sfid]) {
                    sfData.required = true;
                }
                var data = $.extend({}, sfData, {id: null, multi: false});
                editor.initializeFactory(data, this.options);
                editor.parentEditor = this;
                editor.parentEditorData = {};
                editor.initialize();
                editor.parentEditorData.sfid = sfid;
                editor.parentEditorData.parent = this;
                this.subfieldEditors[sfid] = editor;
                val.data[sfid] = editor.getValue();
            }

            this.fieldValue = val;
        },

        setValue: function(data) {
            if (!data) {
                return;
            }

            this.fieldValue = data;

            // Save subfields
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                if (typeof data.data == 'undefined') {
                    sf.initialize();
                    sf.setValue(sf.getValue());
                } else if (typeof data.data[sfid] != 'undefined') {
                    sf.setValue(data.data[sfid]);
                } else {
                    sf.setValue(sf.getValue());
                }
            }
        },

        getValue: function() {
            if (this.currentMode == 'null') {
                return $.extend({}, this.fieldValue.data);
            }

            var val = {};

            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                val[sfid] = sf.getValue();
            }

            return val;
        },

        isModified: function() {
            for(var sfid in this.subfieldEditors) {
                if (this.subfieldEditors[sfid].isModified()) {
                    return true;
                }
            }
            return false;
        },

        validate: function(skipRequiredCheck) {
            var result = {};

            // for each subfield add a record subfieldId => its validate() into result
            var errorsFound = false;
            for(var sfid in this.subfieldEditors) {
                var v = this.subfieldEditors[sfid].validate(skipRequiredCheck);
                if (v) {
                    result[sfid] = v;
                    errorsFound = true;
                }
            }

            if (!errorsFound) {
                return null;
            }
            return result;
        },

        showValidationErrors: function(errors) {
            if (this.domElement === null) {
                return;
            }

            // for each subfield call its showValidationErrors with errors[subfieldId]
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                if (errors !== null && typeof errors[sfid] != 'undefined') {
                    sf.showValidationErrors(errors[sfid]);
                } else {
                    sf.showValidationErrors(null);
                }
            }
        },

        /** Cannot be used inline */
        newInlineFieldElement: null,

        newFieldElement: function(mode) {
            if(this.fieldData.read_only) {
                mode = 'view';
            }
            if (mode == 'view') {
                // Do not show anything in view mode if field is empty
                if(!this.fieldValue.value && !this.fieldData.show_empty) {
                    return $('<div style="display: none;" class="field" data-field-id="'+this.fieldData.id+'"></div>');
                }
            }

            var wrapper = $('<div class="composite '+mode+' field" data-field-id="'+this.fieldData.id+'"></div>').append(contactEditor.wrapper('<span style="display:none" class="replace-with-ext"></span>', this.fieldData.name, 'hdr'));
            
            // For each field call its newFieldElement and add to wrapper
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                var element = sf.newFieldElement(mode);
                element.attr('data-field-id', sfid);
                element.data('fieldId', sfid);
                sf.domElement = element;
                wrapper.append(element);
            }

            // In-place editor initialization (when not part of a multifield)
            /*if (mode == 'edit' && this.parent == null) {
                var that = this;
                result.find('span.info-field').click(function() {
                    var buttons = contactEditor.inplaceEditorButtons([that.fieldData.id], function(noValidationErrors) {
                        if (typeof noValidationErrors != 'undefined' && !noValidationErrors) {
                            return;
                        }
                        that.setMode('view');
                        buttons.remove();
                    });
                    result.after(buttons);
                    that.setMode('edit');
                });
            }*/

            return wrapper;
        },

        setMode: function(mode, replaceEditor) {
            if (typeof replaceEditor == 'undefined') {
                replaceEditor = true;
            }
            if (mode != 'view' && mode != 'edit') {
                throw new Error('Unknown mode: '+mode);
            }

            if (this.currentMode != mode) {
                this.currentMode = mode;
                if (replaceEditor) {
                    var oldDom = this.domElement;
                    this.domElement = this.newFieldElement(mode);
                    if (oldDom !== null) {
                        oldDom.replaceWith(this.domElement);
                    }
                }
            }
            for(var sfid in this.subfieldEditors) {
                this.subfieldEditors[sfid].setMode(mode, false);
            }

            return this.domElement;
        }
    }); // end of Composite field type

    contactEditor.factoryTypes.Address = $.extend({}, contactEditor.factoryTypes.Composite, {
        showValidationErrors: function(errors) {
            if (this.domElement === null) {
                return;
            }

            // remove old errors
            this.domElement.find('em.errormsg').remove();
            this.domElement.find('.val').removeClass('error');

            if (!errors) {
                return;
            }

            // Show new errors
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                if (typeof errors[sfid] == 'undefined') {
                    continue;
                }
                var input = sf.domElement.find('.val').addClass('error');
                input.parents('.address-subfield').append($('<em class="errormsg">'+errors[sfid]+'</em>'));
            }
        },

        newInlineFieldElement: function(mode) {
            var result = '';

            if (mode == 'view') {
                // Do not show anything in view mode if field is empty
                if(!this.fieldValue.value) {
                    return null;
                }
                var map_url = '';
                if (typeof this.fieldValue.for_map === 'string') {
                    map_url = this.fieldValue.for_map;
                } else {
                    if (this.fieldValue.for_map.coords) {
                        map_url = this.fieldValue.for_map.coords;
                    } else {
                        map_url = this.fieldValue.for_map.with_street;
                    }
                }
                result = $('<div class="address-field"></div>')
                    //.append('<div class="ext"><strong><span style="display:none" class="replace-with-ext"></span></strong></div>')
                    .append(this.fieldValue.value)
                    .append('<span style="display:none" class="replace-with-ext"></span> ')
                    .append('<a target="_blank" href="//maps.google.ru/maps?q=' + encodeURIComponent(map_url) + '&z=15" class="small map-link">' + $_('map') + '</a>');
                return result;
            }

            //
            // edit mode
            //
            var wrapper = $('<div class="address-field"></div>');
            wrapper.append('<span style="display:none" class="replace-with-ext"></span>');


            // Add fields
            // For each field call its newFieldElement and add to wrapper
            for(var sfid in this.subfieldEditors) {
                var sf = this.subfieldEditors[sfid];
                var element = sf.newInlineFieldElement('edit');
                sf.domElement = element;
                wrapper.append($('<div class="address-subfield"></div>').append(element));
                if (sf.fieldData.type !== 'Hidden') {
                    //$.wa.defaultInputValue(element.find('input.val'), sf.fieldData.name+(sf.fieldData.required ? ' ('+$_('required')+')' : ''), 'empty');
                    element.find('input.val').attr(
                        'placeholder', 
                        sf.fieldData.name+(sf.fieldData.required ? ' ('+$_('required')+')' : '')
                    );
                }
            }
            return wrapper;
        }
    });

    contactEditor.factoryTypes.Birthday = contactEditor.factoryTypes.Date = $.extend({}, contactEditor.baseFieldType, {
        
        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue.value) {
                return null;
            }
            var result = null;
            var data = this.fieldValue.data || {};
            var that = this;
            if (mode == 'edit') {
                var day_html = $('<select class="val" data-part="day"><option data=""></option></select>');
                for (var d = 1; d <= 31; d += 1) {
                    var o = $('<option data="' + d + '" value="' + d + '">' + d + '</option>');
                    if (d == data["day"]) {
                        o.attr('selected', true);
                    }
                    day_html.append(o);
                }
                
                var month_html = $('<select class="val" data-part="month"><option data=""></option></select>');
                var months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];
                for (var m = 0; m < 12; m += 1) {
                    var v = $_(months[m]);
                    var o = $('<option data="' + (m + 1) + '"  value="' + (m + 1) + '">' + v + '</option>');
                    if ((m + 1) == data["month"]) {
                        o.attr('selected', true);
                    }
                    month_html.append(o);
                }
                
                var year_html = $('<input type="text" data-part="year" class="val" style="min-width: 32px; width: 32px;" placeholder="' + $_('year') + '">');
                if (data['year']) {
                    year_html.val(data['year']);
                }
                result = $('<span></span>').
                        append(day_html).
                        append(' ').
                        append(month_html).
                        append(' ').
                        append(year_html);
            } else {
                result = $('<span class="val"></span>').text(this.fieldValue.value);
            }
            return result;
        },
                
        getValue: function() {
            var result = this.fieldValue;
            if (this.currentMode == 'edit' && this.domElement !== null) {
                var input = this.domElement.find('.val');
                if (input.length > 0) {
                    result = {
                        value: {
                            day: null,
                            month: null,
                            year: null
                        }
                    };
                    input.each(function() {
                        var el = $(this);
                        var p = el.data('part');
                        result.value[p] = parseInt(el.val(), 10) || null;
                    });
                }
            }
            return result;
        },
                
        setValue: function(data) {
            this.fieldValue = data;
            if (this.currentMode == 'null' || this.domElement === null) {
                return;
            }
            if (this.currentMode == 'edit') {
                this.domElement.find('.val').each(function() {
                    var el = $(this);
                    var part = el.data('part');
                    el.val(data.data[part] || '');
                });
            } else {
                this.domElement.find('.val').html(this.fieldValue);
            }
        }
                
    });

    contactEditor.factoryTypes.IM = $.extend({}, contactEditor.baseFieldType, {
        /** Accepts both a simple string and {value: previewHTML, data: stringToEdit} */
        setValue: function(data) {
            if (typeof data == 'undefined') {
                data = '';
            }
            if (typeof(data) == 'object') {
                this.fieldValue = data.data;
                this.viewValue = data.value;
            } else {
                this.fieldValue = this.viewValue = data;
            }
            if (this.currentMode == 'null' || !this.domElement) {
                return;
            }

            if (this.currentMode == 'edit') {
                this.domElement.find('input.val').val(this.fieldValue);
            } else {
                this.domElement.find('.val').html(this.viewValue);
            }
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input class="val" type="text"></span>');
                result.find('.val').val(this.fieldValue);
            } else {
                result = $('<span class="val"></span>').html(this.viewValue);
            }
            return result;
        }
    });

    contactEditor.factoryTypes.SocialNetwork = $.extend({}, contactEditor.baseFieldType, {
        /** Accepts both a simple string and {value: previewHTML, data: stringToEdit} */
        setValue: function(data) {
            if (typeof data == 'undefined') {
                data = '';
            }
            if (typeof(data) == 'object') {
                this.fieldValue = data.data;
                this.viewValue = data.value;
            } else {
                this.fieldValue = this.viewValue = data;
            }
            if (this.currentMode == 'null' || !this.domElement) {
                return;
            }

            if (this.currentMode == 'edit') {
                this.domElement.find('input.val').val(this.fieldValue);
            } else {
                this.domElement.find('.val').html(this.viewValue);
            }
        },

        newInlineFieldElement: function(mode) {
            // Do not show anything in view mode if field is empty
            if(mode == 'view' && !this.fieldValue) {
                return null;
            }
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input class="val" type="text"></span>');
                result.find('.val').val(this.fieldValue);
            } else {
                result = $('<span class="val"></span>').html(this.viewValue);
            }
            return result;
        }
    });

    contactEditor.factoryTypes.Url = $.extend({}, contactEditor.factoryTypes.IM, {
        validate: function(skipRequiredCheck) {
            var val = $.trim(this.getValue());

            if (!skipRequiredCheck && this.fieldData.required && !val) {
                return $_('This field is required.');
            }
            if (!val) {
                return null;
            }

            if (!(/^(https?|ftp|gopher|telnet|file|notes|ms-help)/.test(val))) {
                val = 'http://'+val;
                this.setValue(val);
            }

            var l = '[^`!()\\[\\]{};:\'".,<>?«»“”‘’\\s+]'; // letter allowed in url, including IDN
            var p = '[^`!\\[\\]{}\'"<>«»“”‘’\\s]'; // punctuation or letter allowed in url
            var regex = new RegExp('^(https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\\\\\))+'+p+'*$', 'i');
            if (!regex.test(val)) {
                return $_('Incorrect URL format.');
            }

            // More restrictions for common protocols
            if (/^(http|ftp)/.test(val.toLowerCase())) {
                regex = new RegExp('^(https?|ftp):((//)|(\\\\\\\\))+((?:'+l+'+\\.)+'+l+'{2,6})((/|\\\\|#)'+p+'*)?$', 'i');
                if (!regex.test(val)) {
                    return $_('Incorrect URL format.');
                }
            }

            return null;
        }
    });

    contactEditor.factoryTypes.Email = $.extend({}, contactEditor.factoryTypes.Url, {
        validate: function(skipRequiredCheck) {
            var val = $.trim(this.getValue());

            if (!skipRequiredCheck && this.fieldData.required && !val) {
                return $_('This field is required.');
            }
            if (!val) {
                return null;
            }
            var regex = new RegExp('^([^@\\s]+)@[^\\s@]+\\.[^\\s@\\.]{2,}$', 'i');
            if (!regex.test(val)) {
                return $_('Incorrect Email address format.');
            }
            return null;
        }
    });

    contactEditor.factoryTypes.Checkbox = $.extend({}, contactEditor.baseFieldType, {
        /** Load field contents from given data and update DOM. */
        setValue: function(data) {
            this.fieldValue = parseInt(data);
            if (this.currentMode == 'null' || !this.domElement) {
                return;
            }

            if (this.currentMode == 'edit') {
                this.domElement.find('input.val').attr('checked', !!this.fieldValue);
            } else {
                this.domElement.find('.val').text(this.fieldValue ? 'Yes' : 'No');
            }
        },

        newInlineFieldElement: function(mode) {
            var result = null;
            if (mode == 'edit') {
                result = $('<span><input class="val" type="checkbox" value="1" checked="checked"></span>');
                if (!this.fieldValue) {
                    result.find('.val').removeAttr('checked');
                }
            } else {
                result = $('<span class="val"></span>').text(this.fieldValue ? 'Yes' : 'No');
            }
            return result;
        }
    });

    contactEditor.factoryTypes.Number = $.extend({}, contactEditor.baseFieldType, {
        validate: function(skipRequiredCheck) {
            var val = $.trim(this.getValue());
            if (!skipRequiredCheck && this.fieldData.required && !val && val !== 0) {
                return $_('This field is required.');
            }
            if (val && !(/^-?[0-9]+([\.,][0-9]+$)?/.test(val))) {
                return $_('Must be a number.');
            }
            return null;
        }
    });
    
    if (fieldType) {
        return contactEditor.factoryTypes[fieldType];
    } else {
        return contactEditor.factoryTypes;
    }
};

$.wa.fieldTypeFactory = function(fieldType) {
    return $.extend({}, $.wa.fieldTypesFactory(null, fieldType));
};

// EOF
;
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
        initFactories: function(fields) {
            this.fields = fields;
            this.editorFactories = {};
            this.fieldEditors = {};
            for(var fldId in fields) {
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
            for(var f in this.editorFactories) {
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
            for(var f in newData) {
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
            for(var f in this.fieldEditors) {
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
$.wa.contactEditor = $.wa.contactEditorFactory();;
