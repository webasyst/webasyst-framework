// Team :: Profile
var Profile = ( function($) {

    Profile = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$tabs = that.$wrapper.find(".t-profile-tabs");
        that.$tabContentPlace = that.$wrapper.find(".t-dynamic-content");
        that.$calendarPlace = that.$wrapper.find(".t-calendar-place");

        // VARS
        that.api_enabled = ( window.history && window.history.pushState );
        that.user = options.user || { id: 0 };
        that.photo_dialog_url = options.photo_dialog_url;
        that.is_own_profile = options.is_own_profile || false;
        that.wa_app_url = options.wa_app_url || '';
        that.backend_url = options.backend_url || '';
        that.wa_url = options.wa_url || '';
        that.wa_version = options.wa_version || '';
        that.webasyst_id_auth_url = options.webasyst_id_auth_url || '';

        // DYNAMIC VARS
        that.is_locked = false;
        that.xhr = false;
        that.dialogs = [];

        // INIT
        that.initClass();
    };

    Profile.prototype.initClass = function() {
        var that = this;
        //
        that.initEditableJobtitle();
        //
        that.bindEvents();
        //
        if ($.team && $.team.sidebar) {
            $.team.sidebar.selectLink(false);
        }

        new ProfileWebasystID({
            is_own_profile: that.is_own_profile,
            user: that.user,
            backend_url: that.backend_url,
            wa_url: that.wa_url,
            wa_version: that.wa_version,
            webasyst_id_auth_url: that.webasyst_id_auth_url
        });
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

        that.$calendarPlace.on("click", ".js-show-outer-calendar-manager", function(event) {
            event.stopPropagation();
            that.showOuterDialogDialog();
        });

        // When photo editor dialog changes something, update the contact photo
        that.$wrapper.on('photo_updated photo_deleted', function(evt, data) {
            that.$wrapper.find('.t-userpic').attr('src', data.url);
        });

        // Open photo editor when user clicks on "Change photo" link
        that.$wrapper.find('.photo-change-link a').click(function() {
            $('#contact-photo-crop-dialog').remove();
            $('<div id="contact-photo-crop-dialog">')
                .appendTo(that.$wrapper)
                .waDialog({
                    'class': 'large',
                    url: that.photo_dialog_url,
                    onLoad: function(d) {
                        /* move buttons where appropriate */
                        var $dialog = $(this);
                        $dialog.find('.dialog-buttons-gradient').append($dialog.find('.dialog-content-indent .buttons'));
                    }
                });
        });

        var $profile_header_links = that.$wrapper.find('.profile-header-links');
        $profile_header_links.on('click', '.edit-link', function() {
            that.switchToTab('info', function($iframe) {
                return typeof $iframe[0].contentWindow.$.wa.contactEditor.switchMode === 'function';
            }).then(function($iframe) {
                $iframe[0].contentWindow.$.wa.contactEditor.switchMode('edit');
            });
        });
        $profile_header_links.on('click', '.delete-link', function() {
            var $icon = $(this).find('i');
            if ($icon.is('.loading')) {
                return;
            }
            $icon.toggleClass('delete loading');
            $.team.confirmContactDelete([that.user.id], {
                onInit: function() {
                    $icon.toggleClass('delete loading');
                },
                onDelete: function() {
                    $.team.content.load($.team.app_url);
                }
            });
        });

        // When data in Contact Info tab is saved, update the block above calendar
        var $profile_tabs_iframes = that.$wrapper.find('.t-profile-tabs-iframes');
        var $contact_info_top = $('#contact-info-top');
        $profile_tabs_iframes.on('contact_saved', function(evt, data) {
            // Name, title, company, job title
            var $wrapper = $contact_info_top.closest('.t-profile-page');
            var $h1 = $wrapper.find('.profile .details h1').first();
            $h1.children('.contact-name:first').text(data.name);
            $h1.children('.title:first').text(data.title);

            var $work = $h1.closest('.details').find('.jobtitle-company');
            $work.children('.company').text(data.company);
            $work.children('.title').text(data.jobtitle);
        });
        $profile_tabs_iframes.on('top_fields_updated', function(evt, top) {
            // common fields like email, phone and im
            var html = '';
            for (var j = 0; j < top.length; j++) {
                var f = top[j];
                var icon = f.id != 'im' ? (f.icon ? '<i class="icon16 ' + f.id + '"></i>' : '') : '';
                html += '<li>' + icon + f.value + '</li>';
            }
            $contact_info_top.html(html);
        });

        // customize groups link after list of groups in header
        $('#header-customize-groups-link').click(function(){
            that.switchToTab('access', function($iframe) {
                return typeof $iframe[0].contentWindow.ProfileAccessTab === 'function';
            }).then(function($iframe) {
                if ($iframe[0].contentWindow.$('#form-customize-groups', $iframe[0].contentWindow.body).is(':not(:visible)')) {
                    $iframe[0].contentWindow.$('#open-customize-groups', $iframe[0].contentWindow.body).click();
                }
            });
        });
    };

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
            $.post(href, data, function(response) {
                new TeamDialog({
                    html: response,
                    onRefresh: load
                });
            }).always( function() {
                that.is_locked = false;
            });
        }
    };

    Profile.prototype.initEditableJobtitle = function() {
        var profile = this,
            $name = profile.$wrapper.find(".js-jobtitle-editable").first();

        if ($name.length) {
            new TeamEditable({
                $wrapper: $name,
                onSave: function( that ) {
                    var text = that.$field.val(),
                        is_empty = ( !text.length );

                    if (that.text !== text) {
                        var href = $.team.app_url + "?module=profile&action=save",
                            data = {
                                id: profile.user.id,
                                data: JSON.stringify({
                                    "jobtitle": text
                                })
                            };

                        that.$field.attr("disabled", true);
                        var $loading = $('<i class="icon16 loading"></i>')
                            .css("margin", "0 0 0 4px")
                            .insertAfter( that.$field );

                        $.post(href, data, function() {
                            that.$field.attr("disabled", false);
                            $loading.remove();

                            that.is_empty = is_empty;
                            that.text = text;
                            that.$wrapper.text( text );
                            that.toggle("hide");

                            if (is_empty) {
                                that.$wrapper.parent().find(".at").hide();
                            }
                        });

                    } else {
                        that.toggle("hide");
                    }
                }
            });
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
        that.$wrapper = that.$dialogWrapper.find(".t-dialog-block");
        that.$form = that.$wrapper.find("form");

        // VARS

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
            that.close();
            //
            var content_uri = $(this).attr("href");
            if (content_uri) {
                $.team.content.load(content_uri);
            }
        });
    };

    OutsideCalendarsDialog.prototype.deleteExternalCalendar = function (id) {
        $.get('?module=calendarExternal&action=DeleteConfirm', {
            id : id
        }, function (html) {
            new TeamDialog({
                html: html,
                onOpen: function ($dialog) {
                    $dialog.bind('afterDelete', function () {
                        $.team.content.reload();
                    });

                }
            });
        });
    };

    OutsideCalendarsDialog.prototype.close = function() {
        var that = this;

        that.$wrapper.trigger("close");
    };

    return OutsideCalendarsDialog;

})(jQuery);
