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
            this.wa_app_url = options.wa_app_url || '';

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
                    offset: $(this).data('offset') || 0
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
                hash = hash.replace(/\\\//g, 'ESCAPED_SLASH');
                hash = hash.split('/');
                for (var i = 0; i < hash.length; i += 1) {
                    hash[i] = hash[i].replace(/ESCAPED_SLASH/g, '/');
                }
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
            var hash = filters;
            $.wa.controller.setBlock('contacts-list', null, ['search-actions']);

            var escaped_hash = hash || '';
            if (escaped_hash.indexOf('/') >= 0) {
                escaped_hash = escaped_hash.replace('/', '\\/');
            }

            this.loadGrid(p, '/contacts/search/' + (escaped_hash ? escaped_hash + '/' : ''), null, {
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
            this.checkAdminRights(function() {
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
            });
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
                this.load( "#c-core .c-core-content", '?module=contacts&action=mergeSelectMaster', { ids: ids }, null, function() {
                    $(window).unbind('wa_after_merge_contacts').one('wa_after_merge_contacts', function(e, response) {
                        if (response && response.status === 'ok') {
                            // Come back to previous view
                            $(window).one('wa_after_load', function () {
                                $.wa.controller.showMessage(response.data.message);
                            });
                            $.wa.setHash('#/contact/'+response.data.master.id);
                        }
                    });
                    $(window).unbind('wa_cancel_merge_contacts').one('wa_cancel_merge_contacts', function(e) {
                        var hashes = $.wa.controller.hashes;
                        if (hashes[1]) {
                            $.wa.setHash(hashes[1]);
                        } else {
                            $.wa.setHash('');
                        }
                    });
                });
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
            s = s.replace(/\//g, '\\/').replace(/&/, '\\&');
            if (s.indexOf('@') != -1) {
                q = "email*=" + s; //encodeURIComponent(s);
            } else {
                q = "name*=" + s; //encodeURIComponent(s);
            }
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
                url: (this.wa_app_url || '') + '?module=contacts&action=links',
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

        checkAdminRights: function(fn, context) {
            $.get('?module=contacts&action=user&a=is_admin', function() {
                fn.call(context === undefined ? $.wa.controller : context);
            });
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
                    groups_html += $.wa.controller.renderGroups(options.groups || $.wa.controller.groups);
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
                    $.wa.controller.showMenus(menus);
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
            if (h1.find('.loading').show().size() > 0) {
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
                var sb = $("#c-sidebar");
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
