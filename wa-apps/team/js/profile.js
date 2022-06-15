// Team :: Profile
var Profile = ( function($) {

    Profile = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$tabs = that.$wrapper.find(".t-profile-tabs");
        that.$tabContentPlace = that.$wrapper.find(".t-dynamic-content");
        that.$calendarPlace = that.$wrapper.find(".t-calendar-place");
        that.$profile_header_links = that.$wrapper.find('.t-profile-actions-btn');
        that.$profile_sidebar = that.$wrapper.find('.t-profile-sidebar');

        // VARS
        that.api_enabled = ( window.history && window.history.pushState );
        that.user = options.user || { id: 0 };
        that.photo_dialog_url = options.photo_dialog_url;
        that.is_own_profile = options.is_own_profile || false;
        that.wa_app_url = options.wa_app_url || '';
        that.wa_backend_url = options.wa_backend_url || '';
        that.wa_url = options.wa_url || '';
        that.wa_version = options.wa_version || '';
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';

        // DYNAMIC VARS
        that.is_locked = false;
        that.xhr = false;
        that.dialogs = [];
        that.$calendar_wrapper = null;
        that.sidebar_drawer = null;
        that.sidebarDialog = {};

        // INIT
        that.initClass();
        that.initEditor(options.editor || {});
    };

    Profile.prototype.initEditor = function (editor_data) {
        if ($.isEmptyObject(editor_data) || $.isEmptyObject(editor_data.options) || $.isEmptyObject(editor_data.data)) {
            return;
        }

        const that = this;

        $.storage = new $.store();

        // init editor options
        $.each(editor_data.options || {}, function (key, value) {
            $.wa.contactEditor[key] = value;
        })

        if (that.is_own_profile){
            $.wa.contactEditor.wa_app_url = that.wa_backend_url;
        }else{
            $.wa.contactEditor.wa_app_url = that.wa_app_url;
        }

        $.wa.contactEditor.wa_backend_url = that.wa_backend_url;

        $.wa.contactEditor.initFactories(editor_data.data.contactFields, editor_data.data.contactFieldsOrder);
        $.wa.contactEditor.resetFieldEditors();
        $.wa.contactEditor.initFieldEditors(editor_data.data.fieldValues);

        // initially set to 'view' mode
        $.wa.contactEditor.initContactInfoBlock('view');

        if (that.is_own_profile || !editor_data.data.fieldValues.timezone){
            // If user timezone setting is 'Auto', use JS to set timezone.
            $.wa.determineTimezone(that.wa_url);
        }

        // Edit contact data
        const dialog_template = `<div class="dialog t-edit-profile">
            <div class="dialog-background"></div>
            <div class="dialog-body" style="width: 800px;">
            <h3 class="dialog-header">${ $_('Contact info') }</h3>
                <div class="dialog-content fields"></div>
                <div class="dialog-footer"></div>
            </div>
        </div>`;

        that.$profile_header_links.on('click', '.edit-link', function() {
            const $user_info = $('.js-user-info')
            let $contact_info_block;

            if (that.contactsDialog) {
                that.contactsDialog.show();
                return;
            }

            that.contactsDialog = $.waDialog({
                html: dialog_template,
                onOpen($dialog, dialog){
                    $.wa.contactEditor.dialogInstance(dialog);
                    $.wa.contactEditor.switchMode('edit');
                    $contact_info_block = $('#contact-info-block');
                    dialog.$content.append($contact_info_block);
                    dialog.resize();
                    $($.wa.contactEditor).on('contact_saved', function() {
                        dialog.hide();
                    });
                },
                onClose(dialog){
                    dialog.hide();
                    return false;
                }
            })
        });

    };

    Profile.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        if ($.team && $.team.sidebar) {
            $.team.sidebar.selectLink(false);
        }

        new ProfileWebasystID({
            is_own_profile: that.is_own_profile,
            user: that.user,
            backend_url: that.wa_backend_url,
            wa_url: that.wa_url,
            wa_version: that.wa_version,
            webasyst_id_auth_url: that.webasyst_id_auth_url
        });

        $(document).on('wa_before_load', () => {
            that.sidebar_drawer = null;
            that.showSidebarDrawer(true);
        });

        that.showSidebarDrawer(true);
    };

    Profile.prototype.bindEvents = function() {
        var that = this;

        that.$tabs.on("click", ".t-tab a", function() {
            that.changeTab( $(this) );
            return false;
        });

        that.$calendarPlace.on("click", ".js-calendar-toggle", function(event) {
            event.preventDefault();
            that.calendarToggle( $(this) );
        });

        that.$wrapper.find('.js-sidebar-calendar').on("click", ".js-show-outer-calendar-manager", function(event) {
            event.stopPropagation();
            that.showOuterDialogDialog();
        });

        $(document).on("click", ".t-profile-drawer .js-show-outer-calendar-manager", function(event) {
            event.stopPropagation();
            that.showOuterDialogDialog();
        });

        // When photo editor dialog changes something, update the contact photo
        that.$wrapper.on('photo_updated photo_deleted', function(evt, data) {
            that.$wrapper.find('.t-userpic').attr('src', data.url);
        });

        // Open photo editor when user clicks on "Change photo" link
        that.$wrapper.find('.js-change-photo').on('click', function() {
            let $wrapper = $('#contact-photo-crop-dialog');

            if (!$wrapper.length) {
                $wrapper = $('<div class="dialog" id="contact-photo-crop-dialog"/>');
                $("body").append($wrapper);
            }

            $wrapper.load(that.photo_dialog_url, function () {
                $.waDialog({
                    $wrapper
                })
            })
        });

        that.$profile_header_links.on('click', '.access-link', function() {
            const href = "?module=profile&action=sidebarDialog";
            let is_params_error = false;

            const options = $(this)[0].dataset;
            options.userId = that.user.id

            if (that.accessDialog) {
                that.accessDialog.show();
                return;
            }

            const html = `
                <div class="dialog t-sidebar-profile-dialog">
                    <div class="dialog-background"></div>
                    <div class="dialog-body flexbox vertical" ${options.dialogWidth ? ' style="width:' + options.dialogWidth?.replace(/(<([^>]+)>)/gi, "")  +'"': "" }>
                        <h3 class="dialog-header">${options.dialogHeader?.replace(/(<([^>]+)>)/gi, '') || ''}</h3>
                        <div class="dialog-content wide"></div>
                        <div class="dialog-footer custom-mt-auto">
                            <button type="button" class="button light-gray js-close-dialog">${is_params_error ? 'Ok' : $_('Close')}</button>
                        </div>
                    </div>
                </div>
            `;

            that.accessDialog = $.waDialog({
                html,
                onOpen($dialog, dialog) {
                    dialog.$content.empty().append('<div class="align-center"><span class="spinner custom-p-16"></span></div>');

                    $.post(href, options, function (content) {
                        dialog.$content.empty().html(content);
                        that.$wrapper.trigger('dialog_opened', dialog);
                    });
                },
                onClose(dialog) {
                    dialog.hide();
                    $.team.content.reload();
                    return false;
                }
            });
        });

        that.$profile_header_links.on('click', '.delete-link', function() {
            const $link = $(this)
            $(document).on('wa_confirm_contact_delete_dialog', function() {
                $link.find('[data-icon="trash-alt"]').removeClass('hidden');
                $link.find('[data-icon="spinner"]').addClass('hidden');
            })
            $link.find('svg').toggleClass('hidden')
            $.team.confirmContactDelete([that.user.id]);
        });

        $('.js-edit-groups').on('click', function(){
            that.showSidebarDialog($('.access-link').data());
        });

        that.$wrapper.find('.js-profile-user-slider').one('click touchstart', function() {
            $(this).animate({
                height: '375px'
            },function () {
                $(this).removeClass('cursor-pointer');
            })
        });

        that.$wrapper.find('.js-toggle-user-info').on("click", function(event) {
            event.preventDefault();
            $(this).find('svg').toggleClass('fa-caret-down fa-caret-up')
            that.$wrapper.find('.js-user-info').toggleClass('hidden')
        });

        that.$profile_sidebar.on("click", '.js-sidebar-profile-dialog', function(event) {
            event.preventDefault();
            let section_data = this.dataset
            if (section_data.sectionId === undefined) {
                section_data = this.closest('[data-section]').querySelector('.js-sidebar-profile-dialog').dataset;
            }
            // send all data-* attributes to controller
            that.showSidebarDialog(section_data);
        });

        // use ONE to avoid double dialog opening. because content reload when dialog closed
        $(document).one("click", '.t-profile-drawer .js-sidebar-profile-dialog', function(event) {
            event.preventDefault();
            let section_data = this.dataset
            if (section_data.sectionId === undefined) {
                section_data = this.closest('[data-section]').querySelector('.js-sidebar-profile-dialog').dataset;
            }
            // send all data-* attributes to controller
            that.showSidebarDialog(section_data);
        });

        that.$wrapper.find(".js-show-drawer").on("click", function (event) {
            event.preventDefault();
            that.showSidebarDrawer();
        });
    };

    /**
     * @deprecated
     * @param tab_id
     * @param testCallback
     * @returns {any}
     */
    Profile.prototype.switchToTab = function(tab_id, testCallback) {

        var $iframes_wrapper = this.$wrapper.find('.t-profile-tabs-iframes');
        var $tab_a = this.$wrapper.find('.t-tab a[data-tab-id="'+tab_id+'"]');
        var deferred = $.Deferred();

        $tab_a.on('tab_content_updated', tryCallback);
        var interval = setInterval(tryCallback, 100);
        if ($tab_a.closest('.t-tab').hasClass('is-selected')) {
            tryCallback();
        } else {
            $tab_a.click();
        }

        // Animate scroll to tabs
        if ($tab_a.length) {
            $('html, body').animate({
                scrollTop: $tab_a.offset().top
            }, 500);
        }

        return deferred.promise();

        function tryCallback() {
            var $iframe = $iframes_wrapper.children().filter(function() {
                return tab_id == $(this).data('tab-id');
            }).first();
            try {
                if (!$iframe[0].contentWindow || !testCallback($iframe)) {
                    return;
                }
                setTimeout(function() {
                    deferred.resolve($iframe);
                }, 0);
                $tab_a.off('tab_content_updated', tryCallback);
                if (interval) {
                    clearInterval(interval);
                }
                interval = null;
            } catch (e) {
            }
        }
    };

    /**
     * @deprecated
     * @param $link
     */
    Profile.prototype.changeTab = function( $link ) {

        if (this.api_enabled) {

            var tab_id = $link.data('tab-id');
            var profile_uri = window.location.href.match(/^.*\/(id|u)\/[^\/]+/);
            if (!profile_uri || !tab_id) {
                return;
            }

            var uri = profile_uri[0] + '/' + tab_id + '/';
            history.replaceState({
                reload: true,
                content_uri: uri
            }, "", uri);
        }

    };

    Profile.prototype.calendarToggle = function( $toggle ) {
        var that = this,
            short_class = "is-short",
            $text = $toggle.find(".t-calendar-toggle .text"),
            is_active = that.$calendarPlace.hasClass(short_class);

        if ( is_active ) {
            $text.text( $toggle.data("hide-text") );
            that.$calendarPlace.removeClass(short_class);
        } else {
            $text.text( $toggle.data("show-text") );
            that.$calendarPlace.addClass(short_class);
        }
    };

    Profile.prototype.showOuterDialogDialog = function() {
        var that = this,
            href = "?module=schedule&action=settings",
            data = {};

        if (that.user.id > 0) {
            href += '&id=' + that.user.id;
        }

        if (!that.is_locked) {
            that.is_locked = true;

            load();
        }

        function load() {
            $.post(href, data, function(html) {
                $.waDialog({
                    html
                });
            }).always( function() {
                that.is_locked = false;
            });
        }
    };

    Profile.prototype.showSidebarDialog = function (options) {
        const that = this,
            href = "?module=profile&action=sidebarDialog",
            $profile_sidebar_body = $('.js-profile-sidebar-body');
        let is_params_error = false;

        options.userId = that.user.id;

        if (that.sidebarDialog[options.sectionId]) {
            that.sidebarDialog[options.sectionId].show();
            return;
        }

        if(!options.sectionId || !options.userId) {
            is_params_error = true;
        }

        const html = `
            <div class="dialog t-sidebar-profile-dialog">
                <div class="dialog-background"></div>
                <div class="dialog-body flexbox vertical" ${options.dialogWidth ? ' style="width:' + options.dialogWidth?.replace(/(<([^>]+)>)/gi, "")  +'"': "" }>
                    <h3 class="dialog-header">${options.dialogHeader?.replace(/(<([^>]+)>)/gi, '') || ''}</h3>
                    <div class="dialog-content wide"></div>
                    <div class="dialog-footer custom-mt-auto">
                        <button type="button" class="button light-gray js-close-dialog">${is_params_error ? 'Ok' : $_('Close')}</button>
                    </div>
                </div>
            </div>
        `;

        that.sidebarDialog[options.sectionId] = $.waDialog({
            html,
            onOpen($dialog, dialog) {
                dialog.$content.empty().append('<div class="align-center"><span class="spinner custom-p-16"></span></div>');

                if (options.sectionId === 'calendar') {
                    if(!that.$calendar_wrapper) {
                        that.$calendar_wrapper = $profile_sidebar_body.find('.js-calendar-html > .t-calendar-wrapper').detach();
                    }
                    dialog.$content.empty().append(that.$calendar_wrapper)
                    dialog.resize();
                    return;
                }

                if (options.url === '') {
                    const content = $('.js-tab-content-' + options.sectionId).html();
                    dialog.$content.empty().html(content);
                    dialog.resize();
                    that.$wrapper.trigger('dialog_opened', dialog);
                    return;
                }

                $.post(href, options, function(content) {
                    dialog.$content.empty().html(content);
                    that.$wrapper.trigger('dialog_opened', dialog);
                    const $section_iframe = $dialog.find(`.t-profile-section-iframe`);
                    if($section_iframe.length) {
                        $section_iframe.data('dialog', dialog);
                    }
                });
            },
            onClose(dialog) {
                dialog.hide();
                if (options.sectionId === 'calendar') {
                    $.team.content.reload();
                }
                return false;
            }
        });
    };

    Profile.prototype.showSidebarDrawer = function (is_init = false) {
        const that = this;
        if (!that.sidebar_drawer) {
            that.sidebar_drawer = $.waDrawer({
                $wrapper: $('.js-profile-sidebar-drawer'),
                lock_body_scroll: !is_init,
                onClose() {
                    this.hide()
                    return false;
                }
            });
            if (is_init) {
                setTimeout(() => {
                    that.sidebar_drawer.close();
                }, 100)
            }
        }else{
            let wrapper_style = that.sidebar_drawer.$wrapper[0].style;
            wrapper_style.removeProperty('z-index')
            wrapper_style.removeProperty('opacity')
            that.sidebar_drawer.show();
        }
    };

    return Profile;

})(jQuery);

// Team :: Activity :: Lazy Loading
var ActivityLazyLoading = ( function($) {

    ActivityLazyLoading = function(options) {
        var that = this;

        // VARS
        that.list_name = options["names"]["list"];
        that.items_name = options["names"]["items"];
        that.pagind_name = options["names"]["paging"];
        that.user_id = options["user_id"];

        // DOM
        that.$wrapper = ( options["$wrapper"] || false );
        that.$list = that.$wrapper.find(that.list_name);
        that.$window = $(window);

        // Handler
        that.onLoad = ( options["onLoad"] || function() {} );

        // DYNAMIC VARS
        that.$paging = that.$wrapper.find(that.pagind_name);
        that.xhr = false;
        that.is_locked = false;

        // INIT
        that.addWatcher();
    };

    ActivityLazyLoading.prototype.addWatcher = function() {
        var that = this,
            window_parent = window.parent;

        that.$window.on("scroll", onScroll);
        if (window_parent && window.frameElement) {
            $(window_parent).on("scroll", onScroll);
        }

        function onScroll() {
            var is_paging_exist = window && ( $.contains(document, that.$paging[0]) );
            if (is_paging_exist && window_parent && window.frameElement) {
                is_paging_exist = $.contains(window_parent.document, window.frameElement);
            }

            if (is_paging_exist) {
                try {
                    that.onScroll();
                } catch (e) {
                    is_paging_exist = false;
                }
            }
            if (!is_paging_exist) {
                that.$window.off("scroll", onScroll);
                $(window_parent).off("scroll", onScroll);
            }
        }
    };

    ActivityLazyLoading.prototype.onScroll = function() {
        var that = this,
            $window = that.$window,
            scroll_top = $window.scrollTop(),
            display_height = $window.height(),
            paging_top = that.$paging.offset().top;

        if (window.parent && window.frameElement) {
            var $parent_window = $(window.parent);
            display_height = $parent_window.height();
            scroll_top += $parent_window.scrollTop();
            paging_top += $(window.frameElement).offset().top;
        }

        // If we see paging, stop watcher and run load
        if (scroll_top + display_height >= paging_top) {

            if (!that.is_locked) {
                that.is_locked = true;
                that.loadNextPage();
            }
        }
    };

    ActivityLazyLoading.prototype.loadNextPage = function() {
        var that = this,
            href = $.team.app_url + "?module=profile&action=activity",
            data = {
                max_id: that.$paging.data("max-id"),
                id: that.user_id,
                timestamp: that.$list.find(that.items_name).last().data("timestamp")
            };

        if (that.xhr) {
            that.xhr.abort();
            that.xhr = false;
        }

        that.xhr = $.get(href, data, function(response) {
            var $wrapper = $(response),
                $newItems = $wrapper.find(that.list_name + " " + that.items_name),
                $newPaging = $wrapper.find(that.pagind_name);

            that.$list.append($newItems);
            that.$paging.after($newPaging);
            that.$paging.remove();
            that.$paging = $newPaging;
            that.is_locked = false;
            //
            that.onLoad();
        });
    };

    return ActivityLazyLoading;

})(jQuery);

var OutsideCalendarsDialog = ( function($) {

    OutsideCalendarsDialog = function(options) {
        var that = this;

        // DOM
        that.$dialogWrapper = options["$wrapper"];
        that.$wrapper = that.$dialogWrapper.find(".dialog-body");
        that.$form = that.$wrapper;

        // VARS
        that.dialog = that.$dialogWrapper.data('dialog');

        // DYNAMIC VARS
        that.is_locked = false;
        that.xhr = false;

        // INIT
        that.initClass();
    };

    OutsideCalendarsDialog.prototype.initClass = function() {
        var that = this;

        that.bindEvents();
    };

    OutsideCalendarsDialog.prototype.bindEvents = function() {
        var that = this;

        that.$wrapper.on("click", ".t-external-calendar-unmount", function () {
            that.deleteExternalCalendar( $(this).data('id') );
        });

        that.$wrapper.find(".js-add-external-calendar").on("click", function(event) {
            event.preventDefault();
            //
            that.dialog.close();
            //
            var content_uri = $(this).attr("href");
            if (content_uri) {
                $.team.content.load(content_uri);
            }
        });
    };

    OutsideCalendarsDialog.prototype.deleteExternalCalendar = function (id) {
        var that = this;
        $.get('?module=calendarExternal&action=DeleteConfirm', { id }, function (html) {
            $.waDialog({
                html,
                onOpen($dialog, dialog) {
                    $dialog.on('afterDelete', () => {
                        $.team.content.reload();
                        that.dialog.close();
                        dialog.close();
                    });
                }
            });
        });
    };

    return OutsideCalendarsDialog;

})(jQuery);
