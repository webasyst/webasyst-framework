//
// Scripts for Access page in backend
//
var AccessPage = ( function($) {

    var Slider = ( function($) {

        Slider = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$accessWrapper = that.$wrapper.find(".t-access-wrapper");
            that.$accessSlider = that.$wrapper.find(".t-access-slider");
            that.$headerApps = that.$wrapper.find(".t-header-apps");
            that.$headerList = that.$headerApps.find(".t-apps-list");
            that.$apps = that.$headerList.find(".t-app-item");

            // VARS
            that.item_count = that.$apps.length;

            // DYNAMIC VARS
            that.type_class = false;
            that.left = 0;
            that.items_left = 0;
            that.access_wrapper_w = false;
            that.access_slider_w = false;
            that.item_w = false;

            // INIT
            that.initClass();
        };

        Slider.prototype.initClass = function() {
            var that = this;
            //
            that.detectSliderWidth();
            //
            that.showArrows();

            $(window).on("resize", onResize);

            that.$wrapper.on("click", ".t-action", function () {
                var $link = $(this);
                if ($link.hasClass("left")) {
                    that.moveSlider( false );
                }
                if ($link.hasClass("right")) {
                    that.moveSlider( true );
                }
            });

            function onResize() {
                var is_exist = $.contains(document, that.$wrapper[0]);
                if (is_exist) {
                    that.reset();
                } else {
                    $(window).off("resize", onResize);
                }
            }
        };

        Slider.prototype.detectSliderWidth = function() {
            var that = this;

            that.access_wrapper_w = that.$accessWrapper.outerWidth();
            that.access_slider_w = that.$accessSlider.outerWidth();
            that.item_w = that.$apps.first().outerWidth();
        };

        Slider.prototype.showArrows = function() {
            var that = this;

            if (that.left >= 0) {
                if (that.access_wrapper_w < that.access_slider_w) {
                    setType("type-1");
                } else {
                    setType();
                }
            } else {
                if (that.access_wrapper_w < (that.access_slider_w - Math.abs(that.left) ) ) {
                    setType("type-2");
                } else {
                    setType("type-3");
                }
            }

            function setType( type_class ) {
                if (that.type_class) {
                    that.$accessWrapper.removeClass(that.type_class);
                    that.$headerApps.removeClass(that.type_class);
                }

                if (type_class) {
                    that.$accessWrapper.addClass(type_class);
                    that.$headerApps.addClass(type_class);
                    that.type_class = type_class;
                }
            }
        };

        Slider.prototype.setLeft = function( left ) {
            var that = this;

            that.$headerList.css({
                left: left
            });

            that.$accessSlider.css({
                left: left
            });

            that.left = left;
        };

        Slider.prototype.moveSlider = function( right ) {
            var that = this,
                step = 1,
                items_left = that.items_left,
                new_items_left, new_left;

            if (right) {
                new_items_left = items_left + step;
            } else {
                new_items_left = items_left - step;
                if (new_items_left < 0) {
                    new_items_left = 0;
                }
            }

            new_left = new_items_left * that.item_w;

            if ( new_left > -(that.access_wrapper_w - that.access_slider_w) ) {
                new_left = -(that.access_wrapper_w - that.access_slider_w)
            }

            that.items_left = new_items_left;
            that.setLeft(-new_left);
            that.showArrows();
        };

        Slider.prototype.reset = function() {
            var that = this;

            that.items_left = 0;
            that.setLeft(0);
            that.detectSliderWidth();
            that.showArrows();
        };

        return Slider;

    })($);

    var ElasticHeader = ( function($) {

        ElasticHeader = function(options) {
            var that = this;

            // DOM
            that.$wrapper = options["$wrapper"];
            that.$header = that.$wrapper.find(".t-elastic-header");

            // VARS
            that.wrapper_offset = that.$wrapper.offset();
            that.header_w = that.$header.outerWidth();
            that.header_h = that.$header.outerHeight();
            that.fixed_class = "is-fixed";

            // DYNAMIC VARS
            that.is_fixed = false;

            // INIT
            that.initClass();
        };

        ElasticHeader.prototype.initClass = function() {
            var that = this,
                $window = $(window);

            $window
                .on("scroll", onScroll)
                .on("resize", onResize);

            function onScroll() {
                var is_exist = $.contains(document, that.$header[0]);
                if (is_exist) {
                    that.onScroll( $window.scrollTop() );
                } else {
                    $window.off("scroll", onScroll);
                }
            }

            function onResize() {
                var is_exist = $.contains(document, that.$header[0]);
                if (is_exist) {
                    that.onResize();
                } else {
                    $window.off("resize", onResize);
                }
            }
        };

        ElasticHeader.prototype.onScroll = function( scroll_top ) {
            var that = this;

            var set_fixed = ( scroll_top > that.wrapper_offset.top );
            if (set_fixed) {

                that.$header
                    .addClass(that.fixed_class)
                    .css({
                        top: 0,
                        left: that.wrapper_offset.left,
                        width: that.header_w
                    });

                that.is_fixed = true;

            } else {

                that.$header
                    .removeClass(that.fixed_class)
                    .removeAttr("style");

                that.is_fixed = false;
            }
        };

        ElasticHeader.prototype.onResize = function() {
            var that = this;

            that.header_w = that.$wrapper.outerWidth();

            if (that.is_fixed) {
                that.$header.width(that.header_w);
            }
        };

        return ElasticHeader;

    })(jQuery);

    //

    AccessPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS

        // DYNAMIC VARS
        that.slider = false;
        that.dialogs = [];

        // INIT
        that.initClass();
    };

    AccessPage.prototype.initClass = function() {
        var that = this;
        //
        that.initAccessSlider();
        //
        that.initElasticHeader();
        //
        that.initHover();
        //
        that.bindEvents();
    };

    AccessPage.prototype.bindEvents = function() {
        var that = this;

        that.$wrapper.on("click", ".t-access-status", function(event) {
            event.preventDefault();
            var $access = $(this);
            if ($access.hasClass("is-admin") && !that.dialogs.length) {
                $.team.content.load($access.data("uri"));
            } else {
                showAccessDialog($access, $access.data("app-id"), $access.data("user-id"));
            }
        });

        // When user changes rights of user, update the table
        var h;
        $(document).on('team_access_level_changed', h = function(evt, data) {
            if (!$.contains(document.body, that.$wrapper[0])) {
                $(document).off('team_access_level_changed', h);
                return;
            }
            var $access_status = that.$wrapper.find('table.t-access-table tr[data-user-id="'+data.contact_id+'"] .t-access-status[data-app-id="'+data.app_id+'"]');
            $access_status.removeClass('type-no type-limited type-full type-'+data.prev_level).addClass('type-'+data.new_level);
        });
    };

    AccessPage.prototype.initAccessSlider = function() {
        var that = this;

        if (!that.slider) {
            that.slider = new Slider({
                $wrapper: that.$wrapper
            });
        }
    };

    AccessPage.prototype.initElasticHeader = function() {
        var that = this;

        new ElasticHeader({
            $wrapper: that.$wrapper
        });
    };

    AccessPage.prototype.closeDialogs = function() {
        var that = this,
            result = false;

        // Prev Dialog
        if (that.dialogs.length) {

            $.each(that.dialogs, function(index, dialog) {
                if ( $.contains(document, dialog.$wrapper[0]) ) {
                    dialog.close();
                    result = true;
                }
            });

            // or $.each()splice
            that.dialogs = [];
        }

        return result;
    };

    AccessPage.prototype.initHover = function() {
        var that = this,
            $activeUser = false,
            $activeApp = false,
            hover_class = "highlighted";

        that.$wrapper.on("mouseenter", ".t-access-status", function() {
            render( $(this) );
        });

        that.$wrapper.on("mouseleave", ".t-access-status", clear);

        function render( $link ) {
            var user_id = $link.data("user-id"),
                app_id = $link.data("app-id");

            clear();

            if (user_id && app_id) {
                var $user = $("#t-user-" + user_id),
                    $app = $("#t-app-" + app_id);

                if ($user.length) {
                    $user.addClass(hover_class);
                    $activeUser = $user;
                }
                if ($app.length) {
                    $app.addClass(hover_class);
                    $activeApp = $app;
                }
            }
        }

        function clear() {
            if ($activeUser) {
                $activeUser.removeClass(hover_class);
                $activeUser = false;
            }
            if ($activeApp) {
                $activeApp.removeClass(hover_class);
                $activeApp = false;
            }
        }
    };

    return AccessPage;

})(jQuery);

( function($) {

/**
 * Dialog to set up access rights of a single user for a single app.
 * Used on access page, group access page, and access tab in profile.
 */
window.AccessDialog = ( function($) {

    function AccessDialog(options) {
        var that = this;

        // DOM
        that.$dialogWrapper = options["$wrapper"];
        that.$wrapper = that.$dialogWrapper.find(".t-dialog-block");
        that.$limitedContent = that.$wrapper.find(".t-limited-access-form");

        //
        that.active_class = "is-active";
        that.disabled_class = "is-disabled";

        // VARS
        that.wa_app_url = options["wa_app_url"];
        that.app_id = options["app_id"];
        that.contact_id = options["contact_id"];
        that.teamDialog = ( that.$dialogWrapper.data("teamDialog") || false );
        that.noticeToggle = getNoticeToggle( that.$wrapper );

        // DYNAMIC VARS
        that.$activeTab = that.$wrapper.find(".t-access-item." + that.active_class);
        that.active_access_id = that.$activeTab.data("access-id");
        that.is_locked = false;

        // INIT
        that.initClass();
    }

    AccessDialog.prototype.initClass = function() {
        var that = this;

        that.bindEvents();
    };

    AccessDialog.prototype.bindEvents = function() {
        var that = this;

        // Do stuff when user clicks on no access/limited/full access buttons
        that.$wrapper.on("click", ".t-access-item", function() {
            var $item = $(this),
                is_active = $item.hasClass(that.active_class),
                is_disabled = $item.hasClass(that.disabled_class);

            if ( !(that.is_locked || is_active || is_disabled) ) {
                that.changeTab( $item );

                if (that.teamDialog) {
                    that.teamDialog.resize();
                }
            } else if (is_disabled) {
                alert($item.data('reason-disabled'));
            }
        });

        // Submit
        that.$wrapper.on("click","input[type=\"submit\"]", function(event) {
            event.stopPropagation();
            that.save();
        });
    };

    AccessDialog.prototype.changeTab = function( $link ) {
        var that = this,
            access_id = $link.data("access-id"),
            $limitedContent = that.$limitedContent;

        // unmark old selected item
        if (that.$activeTab.length) {
            that.$activeTab.removeClass(that.active_class);
        }

        // mark this link
        $link.addClass(that.active_class);
        that.$activeTab = $link;

        that.$wrapper.find(".t-hint").hide();
        if (access_id == "no") {
            that.$wrapper.find(".t-hint.js-access-no").show();

        } else if (access_id == "full") {
            that.$wrapper.find(".t-hint.js-access-full").show();
        }

        // limited
        if (that.$limitedContent.length) {
            if (access_id == 'limited') {
                // "Limited" status is not saved right away. User can either press "Save" or "cancel"
                $limitedContent.show();
            } else {
                $limitedContent.hide();
            }
        }
    };

    AccessDialog.prototype.save = function() {
        var that = this,
            access_id = that.$activeTab.data("access-id"),
            access_code = getAccessCode( access_id );

        that.is_locked = true;
        that.noticeToggle.loading();

        var promise = setAppRight(access_code);

        if (access_id === "limited") {
            promise = promise.then( function() {
                var $form = that.$limitedContent.find("form");
                return $.post($form.attr('action'), $form.serialize(), 'json');
            });
        }

        promise.then( function() {
            triggerAccessLevelChangedEvent(that.active_access_id, access_id);
            that.active_access_id = access_id;
            that.noticeToggle.success();
            that.is_locked = false;
            that.teamDialog.close();
        });

        function triggerAccessLevelChangedEvent(prev_saved_access_id, new_access_id) {
            $(document).trigger("team_access_level_changed", {
                app_id: that.app_id,
                contact_id: that.contact_id,
                prev_level: prev_saved_access_id,
                new_level: new_access_id
            });
        }

        function setAppRight(value) {
            var href = that.wa_app_url + "?module=accessSave&action=rights&id=" +  that.contact_id;
            return $.post(href, {
                app_id: that.app_id,
                name: "backend",
                value: value
            }, "json");
        }

        function getAccessCode( access_id ) {
            var result = false,
                accessCodeTable = {
                    "no": 0,
                    "limited": 1,
                    "full": 2
                };

            if (accessCodeTable.hasOwnProperty(access_id)) {
                result = accessCodeTable[access_id];
            }

            return result;
        }
    };

    return AccessDialog;

    function getNoticeToggle( $wrapper ) {
        var $loading = $wrapper.find(".t-loading"),
            $success = $wrapper.find(".t-success"),
            visible_class = "is-visible",
            notice, timer;

        return notice = {
            loading: function() {
                $success.removeClass(visible_class);
                $loading.addClass(visible_class);
            },
            success: function() {
                $success.addClass(visible_class);
                $loading.removeClass(visible_class);
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(function() {
                    if ($.contains(document, $success[0])) {
                        notice.hide();
                    }
                }, 2000);
            },
            hide: function() {
                if (timer) {
                    clearTimeout(timer);
                }
                $loading.removeClass(visible_class);
                $success.removeClass(visible_class);
            }
        };
    }

})($);

//
// Scripts for access tab in contact profiles.
//
window.ProfileAccessTab = function(o) { "use strict";
    var login = o.login,
        password = o.password, // true/false
        contact_id = o.contact_id,
        wa_app_url = o.wa_app_url,
        url_change_api_token = o.url_change_api_token,
        loc = o.loc;

    initGroupsChecklist();
    initFormCreateUser();
    initFormChangeLogin();
    initFormChangePassword();
    initApiTokensEditor();

    if (!o.is_own_profile) {
        initToggleBan(o.wa_url, o.wa_framework_version);
    }

    if (o.is_own_profile) {
        initWebasystIDAuth();
    }

    initWebasystIDUnbindAuth();
    initWebasystIDHelpLink();

    initSelectorGlobalAccess(o.is_own_profile, o.contact_no_access, o.contact_groups_no_access);
    new UserAccessTable({
        $wrapper: $('#c-access-rights-wrapper'),
        contact_id: contact_id,
        is_frame: true
    });
    return;

    function initWebasystIDHelpLink() {
        $('.js-webasyst-id-help-link').on('click', function (e) {
            e.preventDefault();
            window.top.$('body').trigger('wa_waid_help_link');
        });
    }

    function initWebasystIDAuth() {
        $('.js-webasyst-id-auth').on('click', function (e) {
            e.preventDefault();
            window.top.$('body').trigger('wa_webasyst_id_auth');
        });
    }
    
    function initWebasystIDUnbindAuth() {
        $('.js-webasyst-id-unbind-auth').on('click', function (e) {
            e.preventDefault();
            window.top.$('.js-webasyst-id-unbind-auth').trigger('wa_waid_unbind_auth', {id: contact_id});
        });
    }

    function initApiTokensEditor() {
        var $wrapper = $('#tc-api-tokens-filed'),
            $list_table = $wrapper.find('.js-api-tokens-list'),
            is_locked = false;

        $wrapper.on('click', '.js-remove-api-token', function (e) {
            e.preventDefault();
            var $token_item = $(this).parents('.js-token-item'),
                $icon = $token_item.find('.icon16'),
                token_id = $token_item.data('token'),
                data = {action: 'remove', token_id: token_id};

            if (!is_locked && token_id && confirm(loc['remove_ask'])) {
                is_locked = true;

                $icon.removeClass('no').addClass('loading');

                $.post(url_change_api_token, data, function(res) {
                    if (res.status && res.status === 'ok') {
                        // Remove tr from tokens list
                        $token_item.remove();
                        // Remove the entire list if it is empty
                        if ($list_table.find('.js-token-item').length === 0) {
                            $wrapper.remove();
                        }
                    } else {
                        is_locked = false;
                        $icon.removeClass('loading').addClass('no');
                    }
                }).always( function() {
                    is_locked = false;
                    $icon.removeClass('loading').addClass('no');
                });
            }
        });
    }

    function initGroupsChecklist() {//{{{
        var $form = $('#form-customize-groups');
        if (!$form.length) {
            return;
        }

        initCheckboxList($('#form-customize-groups .c-checkbox-menu'));
        $('#open-customize-groups').click(function() {
            $('#form-customize-groups').toggle();
        });
        $('#cancel-customize-groups').click(function() {
            var form = $('#form-customize-groups').hide();
            form.find('.loading').hide();
            form.find('.errormsg').remove();
            return false;
        });
        $form.submit(function() {
            var form = $(this);
            form.find('.errormsg').remove();
            form.find('.loading').show();
            $.post(form.attr('action'), form.serialize(), function(response) {
                if (response.status == 'ok') {
                    try {
                        window.parent.$.team.sidebar.reload();
                    } catch (e) {
                    }
                    if (window.hasOwnProperty("profileTab")) {
                        window.profileTab.reload();
                    }
                } else if (response.status == 'fail') {
                    form.find('.c-checkbox-menu-container').after($('<em class="errormsg">'+response.errors.join('<br />')+'</em>'));
                }
            }, 'json');
            return false;
        });

        function initCheckboxList($ul) {
            $ul.find('input[type="checkbox"]')
                .click(updateStatus)
                .each(updateStatus);
            return $ul;

            function updateStatus(i, cb) {
                var self = $(cb || this);
                if (self.prop('checked')) {
                    self.parent().addClass('highlighted');
                } else {
                    self.parent().removeClass('highlighted');
                }
            }
        }
    }//}}}

    function initFormCreateUser() {//{{{
        $("#c-credentials-form").submit(function () {
            var form = $(this);
            form.find('input.error').removeClass('error');
            form.find('.errormsg').remove();
            var login_input = form.find('.c-login-input');
            var new_login = $.trim(login_input.val());
            if (!new_login) {
                login_input.addClass('error').after('<em class="errormsg">'+loc["Login is required"]+'</em>');
                return false;
            }

            var data = form.serializeArray();
            var $select = $('#c-access-rights-toggle');
            if ($select.val() === '1') {
                data = data.concat([
                    {
                        name: 'set_rights',
                        value: '1'
                    },
                    {
                        name: 'app_id',
                        value: 'webasyst'
                    },
                    {
                        name: 'name',
                        value: 'backend'
                    },
                    {
                        name: 'value',
                        value: 1
                    }
                ]);
            }

            $.post(form.attr('action'), data, function (r) {
                if (r.status === 'ok') {
                    form.hide();
                    login = new_login;
                    $('#c-login-block').show()
                        .find('.c-login-input').val(login).end()
                        .find('.c-login').text(login);
                    try {
                        window.parent.$.team.sidebar.reload();
                    } catch (e) {
                    }
                    try {
                        window.profileTab.reload();
                    } catch (e) {
                    }
                } else if (r.status === 'fail') {
                    form.find('input[type="submit"]').parent().prepend($('<em class="errormsg" style="margin-bottom:10px">'+r.errors.join('<br>')+'</em>'));
                }
            }, 'json');
            return false;
        }).find('.cancel').click(function() {
            $('#c-credentials-block').hide();
            return false;
        });
    }//}}}

    function initFormChangeLogin() {//{{{

        var $form = $("#c-login-form");
        var $login_input = $form.find('.c-login-input');

        $form.submit(function () {
            $form.find('input.error').removeClass('error');
            $form.find('.errormsg').remove();
            var new_login = $.trim($login_input.val());
            if (login === new_login) {
                return false;
            }
            if (!new_login) {
                $login_input.addClass('error').after('<em class="errormsg">'+loc["Login is required."]+'</em>');
                return false;
            }

            $form.find('.loading').show();
            $.post($form.attr('action'), $form.serialize(), function (r) {
                $form.find('.loading').hide();
                if (r.status === 'ok') {
                    $('#c-login-block').show();
                    $login_input.val(new_login);
                    $form.find('.c-login').text(new_login);
                    $form.find('.c-one-tab').show();
                    $form.find('.c-two-tab').hide();
                    if (!login) {
                        try {
                            window.profileTab.reload();
                        } catch (e) {
                        }
                    }
                    login = new_login;
                } else if (r.status === 'fail') {
                    $form.find('input[type="submit"]').parent().prepend($('<em class="errormsg" style="margin-bottom:10px">'+r.errors.join("\n<br>\n")+'</em>'));
                }
            }, 'json');
            return false;
        });

        $form.find('.c-tab-toggle').click(function() {
            $form.find('.c-one-tab,.c-two-tab').toggle();
            if ($login_input.is(':visible')) {
                $login_input.focus();
            }
            return false;
        });
    }//}}}

    function initFormChangePassword() {//{{{

        var $form = $('#c-password-form');
        var $password_input = $form.find('.c-password-input');
        var $confirm_password_input = $form.find('.c-confirm-password-input');

        $form.submit(function() {
            $form.find('input.error').removeClass('error');
            $form.find('.errormsg').remove();

            // do passwords match?
            if ($password_input.val() !== $confirm_password_input.val()) {
                $password_input.addClass('error');
                $confirm_password_input.after().after('<em class="errormsg">'+loc["Passwords do not match."]+'</em>');
                return false;
            }

            $form.find('.loading').show();
            $.post($form.attr('action'), $form.serialize(), function(response) {
                $form.find('.loading').hide();
                if (response.status === 'ok') {
                    password = true;
                    $password_input.val('');
                    $confirm_password_input.val('');
                    $form.find('.c-one-tab').show();
                    $form.find('.c-two-tab').hide();
                    $('#c-password-block').show();
                } else if (response.status === 'fail') {
                     $confirm_password_input.after('<em class="errormsg">'+response.errors.join('<br />')+'</em>');
                }
            }, 'json');

            return false;
        });

        // Show inputs when user clicks 'change password' link
        // and hide inputs when user clicks cancel.
        $form.find('.c-tab-toggle').click(function() {
            $form.find('.c-one-tab,.c-two-tab').toggle();
            var $input = $form.find('.c-password-input');
            if ($input.is(':visible')) {
                $input.focus();
            }
            return false;
        });
    }//}}}

    function initToggleBan(wa_url, wa_framework_version) {//{{{

        if (!$.fn.iButton) {
            $.ajax({
                cache: true,
                dataType: "script",
                url: wa_url + 'wa-content/js/jquery-plugins/ibutton/jquery.ibutton.min.js?' + wa_framework_version,
                success: function() {
                    initToggleBan(wa_url, wa_framework_version);
                }
            });
            return;
        }

        var $fields = $('.basic-user-fields'),
            $block_form = $('.js-block-user-reason-form');

        $block_form.on('click', '.js-block-user-cancel', function () {
            $block_form.hide();
        });

        $block_form.on('submit', function (e) {
            e.preventDefault();

            if (!confirm($link_block.data('alert'))) {
                return;
            }

            var $textarea = $block_form.find('.js-block-user-reason'),
                text = $.trim($textarea.val());

            $('.c-shown-on-enabled').hide();
            var $loading = $link_unblock.parent().find('.loading').show();
            $.post(wa_app_url+'?module=accessSave&action=ban&id='+contact_id, {
                magic_word: 'please',
                text: text
            }, function(r) {
                $loading.hide();
                $block_form.hide();
                if (r.status === 'ok') {
                    $fields.addClass('gray');
                    $('.c-shown-on-disabled').show();
                    $('#tc-user-access-disabled').show().html(r.data.access_disable_msg);
                }
            }, 'json');
        });

        // Link to block contact
        var $link_block = $('#c-access-link-block').click(function() {
            $block_form.show();
        });

        // Link to unblock contact
        var $link_unblock = $('#c-access-link-unblock').click(function() {
            if (!confirm($link_unblock.data('alert'))) {
                return false;
            }

            $('.c-shown-on-disabled').hide();
            var $loading = $link_unblock.parent().find('.loading').show();
            $.post(wa_app_url+'?module=accessSave&action=unban&id='+contact_id, {
                magic_word: 'please'
            }, function() {
                $loading.hide();
                $fields.removeClass('gray');
                $('.c-shown-on-enabled').show();
                $('#tc-user-access-disabled').hide().html('');
            });
        });

    }//}}}

    function initSelectorGlobalAccess(is_own_profile, contact_no_access, contact_groups_no_access) {//{{{
        var $select = $('#c-access-rights-toggle');
        var $confirm_wrapper = $('#access-rights-toggle-confirm');
        var last_select_value = $select.val();

        if (contact_no_access) {
            $('.c-shown-on-access').hide();
        } else {
            $('.c-shown-on-access').show();
        }

        initForm();

        $confirm_wrapper.on('click', '.cancel', function() {
            $select.val(last_select_value);
            $confirm_wrapper.hide();
        });

        $confirm_wrapper.on('click', '.button', function() {
            $confirm_wrapper.hide();
            updateFormAndSave();
        });

        $select.change(function() {
            if (!login) {
                initForm(true);
                return;
            }

            $('#c-access-rights-hint-warning').hide();
            $('#c-access-rights-hint-customize').hide();
            var new_select_value = $select.val();
            if (new_select_value === undefined) {
                new_select_value = '1';
            }

            if (new_select_value === last_select_value) {
                $confirm_wrapper.hide();
            } else {
                $confirm_wrapper.show();
            }
        });

        function updateFormAndSave() {
            if (initForm(true)) {
                saveUserAccess();
            }
        }

        /**
         * @param {boolean|undefined} is_update - is form need to update after some state changed. On first init is_update must be FALSE (default)
         * @return {boolean}
         */
        function initForm(is_update) {
            $('#c-access-rights-hint-warning').hide();
            $('#c-access-rights-hint-customize').hide();

            var new_select_value = $select.val();
            if (new_select_value === undefined) {
                new_select_value = '1';
            }

            switch(new_select_value) {
                case 'remove':
                    $('#c-credentials-block').hide();
                    $('#c-login-block').hide();
                    $('#c-password-block').hide();
                    if (contact_groups_no_access) {
                        $('#c-access-rights-by-app').hide();
                        $('.c-shown-on-access').hide();
                        break;
                    }
                    $select.val(last_select_value || '0');
                    $('#c-access-rights-hint-warning').show();
                    return false;
                case '0':
                    if (!login && !password) {
                        $('#c-credentials-block').show()
                            .find('.c-login-input').focus().end()
                            .find('.cancel').one('click.access', function() {
                                $select.val(last_select_value);
                                updateFormAndSave();
                            });
                        return false;
                    } else {
                        if (login) {
                            var $apps_access_rights = $('#c-access-rights-by-app');

                            $apps_access_rights.show();
                            if (is_update) {
                                $apps_access_rights.find('.t-access-status').removeClass('type-no type-limited type-full').addClass('type-no');
                            }

                            $('.c-shown-on-access').show();
                            $('#c-login-block').show();
                            $('#c-password-block').show();
                            break;
                        } else {
                            $('#c-login-block').show()
                                .find('.cancel').one('click.access', function() {
                                    $select.val(last_select_value);
                                    updateFormAndSave();
                                }).end()
                                .find('.c-tab-toggle:first').click();
                            $('#c-password-block').show();
                            return false;
                        }
                    }
                case '1':
                    if (!login && !password) {
                        $('#c-credentials-block').show()
                            .find('.c-login-input').focus().end()
                            .find('.cancel').one('click.access', function() {
                                $select.val(last_select_value);
                                updateFormAndSave();
                            });
                        return false;
                    } else {
                        if (login) {
                            $('#c-access-rights-by-app').hide();
                            $('.c-shown-on-access').show();
                            $('#c-login-block').show();
                            $('#c-password-block').show();
                            break;
                        } else {
                            $('#c-login-block').show()
                                .find('.cancel').one('click.access', function() {
                                    $select.val(last_select_value);
                                    updateFormAndSave();
                                }).end()
                                .find('.c-tab-toggle:first').click();
                            $('#c-password-block').show();
                            return false;
                        }
                    }
                default:
                    return false;
            }

            if (is_own_profile) {
                $('#c-login-block').show();
                $('#c-password-block').show();
            }

            last_select_value = new_select_value;
            return true;
        }

        function saveUserAccess() {
            var new_select_value = $select.val();
            if (new_select_value === undefined) {
                new_select_value = '1';
            }

            (function() {
                switch(new_select_value) {
                    case '0':
                        // Limited access user
                        return makeIsUser1().then(function() {
                            return setAppRight('webasyst', 'backend', 0);
                        });
                    case '1':
                        // make superadmin
                        // also sets is_user=1 if it was 0
                        return setAppRight('webasyst', 'backend', 1);
                    case 'remove':
                        // revoke all access
                        return $.post(wa_app_url+'?module=accessSave&action=revoke', { id: contact_id }, 'json');
                }
            }()).then(function() {
                if (contact_groups_no_access && new_select_value == '0') {
                    $('#c-access-rights-hint-customize').show();
                } else {
                    $('#c-access-rights-hint-warning').hide();
                }
            });

            function makeIsUser1() {
                return $.post(wa_app_url+'?module=accessSave&action=makeuser', { id: contact_id }, 'json');
            }

            function setAppRight(app_id, name, value) {
                return $.post(wa_app_url+'?module=accessSave&action=rights&id='+contact_id, {
                    app_id: app_id,
                    name: name,
                    value: value
                }, 'json');
            }
        }
    }//}}}
};

/**
 * Table to set up per-app access rights for a single user or group.
 * Used on access profile page, as well as in group access page.
 */
window.UserAccessTable = function(o) { "use strict";
    var $wrapper = o.$wrapper,
        contact_id = o.contact_id, // may be negative if group
        is_frame = ( o.is_frame || false ); // need for dialog for detecting window scroll top

    // Open access dialog when user clicks on app status block
    $wrapper.on('click', '.t-access-status', function(event) {
        event.preventDefault();
        var $access = $(this);
        showAccessDialog($access, $access.data('app-id'), contact_id, true, is_frame);
    });

    // Update app status block when access rights change
    var h;
    $(document).on('team_access_level_changed', h = function(evt, data) {
        if (!$.contains(document.body, $wrapper[0])) {
            $(document).off('team_access_level_changed', h);
            return;
        }
        $wrapper.find('.t-access-status[data-app-id="'+data.app_id+'"]')
            .removeClass('type-no type-limited type-full type-'+data.prev_level)
            .addClass('type-'+data.new_level);
    });
};

/**
 * Dialog to set up access rights for a single user/group and single app.
 * Used on access page, group access page, and profile access tab.
 * @param $access    jQuery object to position center of the dialog above.
 * @param app_id     string
 * @param contact_id int    negative for group, positive for user
 * @param is_attach boolean set position near access column
 * @param is_frame boolean need for dialog for detecting window scroll top
 */
window.showAccessDialog = function($access, app_id, contact_id, is_attach, is_frame) {//{{{

    // Close all dialogs if exist
    $access.trigger('close');

    $.post($.team.app_url + "?module=access&action=dialog", {
        user_id: contact_id,
        app_id: app_id
    }, function(response) {
        var options = {
            html: response
        };

        if (is_frame && is_attach) {
            options.setPosition = function(area) {
                var $window = $(window),
                    window_w = $window.width(),
                    top = $access.offset().top;

                return {
                    top: top,
                    left: parseInt( (window_w - area.width)/2 )
                };
            }
        }

        new TeamDialog(options);
    });

};//}}}

})(jQuery);
