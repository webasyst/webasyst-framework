(function ($) {

    $.wa_push = $.extend($.wa_push || {}, {

        loc: {},

        bell_ids: {
            request_permissions: 'push-request-permissions',
            permissions_success: 'push-permissions-success',
            notification: 'push-notification',
            error: 'push-error',
            timeout: 'push-timeout'
        },

        force_no_mute: false,

        timeout_id: undefined,

        is_test_push_recieved: false,

        init: function (options = {}) {
            var source = {
                    id: 'wa-push-init',
                    uri: backend_url + "webasyst/?module=push&action=initJs"
                },
                $script = $("#" + source.id);

            if (!$script.length) {
                var script = document.createElement("script");
                document.getElementsByTagName("head")[0].appendChild(script);

                $script = $(script).attr("id", source.id);

                $script
                    .on("load", function() {
                        $(window).trigger('wa_push_ready');
                        if (typeof options.func === 'function') {
                            options.func();
                        }
                    })
                    .on("error", function() {
                        $(window).trigger('wa_push_init_error');
                    });

                $script.attr("src", source.uri);
            } else {
                $(window).trigger('wa_push_loaded');
                if (typeof options.func === 'function') {
                    options.func();
                }
            }
            if (options.force_no_mute) {
                this.force_no_mute = options.force_no_mute;
            }
        },

        askAllow: function() {
            if (typeof this.requestAllow !== 'function') {
                // Push adapter is not supported
                return;
            }
            if (!window.WaBellAnnouncement || typeof WaBellAnnouncement.adhocShow !== 'function') {
                // UI 1.3 is not supported
                this.requestAllow();
                return;
            }

            if (localStorage.getItem('push-mute') && !this.force_no_mute) {
                return;
            }

            if (window.location.protocol === 'http:') {
                this.showHttpPermissionAlert();
                return;
            }

            if (window.Notification.permission === "denied") {
                this.showDeniedPermissionAlert();
                return;
            }

            const that = this;
            const $bell_announcement = $("<p />").html(this.loc.requestMessage + ' <a href="javascript:void(0);" class="js-submit">' + this.loc.buttonText + '</a>' + (this.force_no_mute ? '' : '<br><a href="javascript:void(0);" class="button light-gray outlined custom-mt-8 js-mute">' + this.loc.muteText + '</a>'));
            $bell_announcement.find('.js-submit').on("click", function() {
                that.requestAllow();
                WaBellAnnouncement.adhocHide(that.bell_ids.request_permissions);
                if (!["default", "prompt"].includes(window.Notification.permission)) {
                    that.timeout_id = setTimeout(() => {
                        WaBellAnnouncement.adhocHide(that.bell_ids.timeout); // prevent multiple notifications
                        WaBellAnnouncement.adhocShow($("<p />").html(that.loc.requestTimeoutMessage), that.bell_ids.timeout);
                        that.timeout_id = undefined;
                    }, 1000);
                }
            });

            $bell_announcement.find('.js-mute').on("click", function() {
                that.mute();
            });

            WaBellAnnouncement.adhocHide(that.bell_ids.error);
            WaBellAnnouncement.adhocHide(that.bell_ids.request_permissions); // prevent multiple notifications
            WaBellAnnouncement.adhocShow($bell_announcement, that.bell_ids.request_permissions);
        },


        showDeniedPermissionAlert: function() {
            $(window).trigger('wa_push_error', [this.loc.deniedPermission]);
        },

        showHttpPermissionAlert: function() {
            $(window).trigger('wa_push_error', [this.loc.httpNotSupported]);
        },

        mute: function() {
            if (window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocHide === 'function') {
                WaBellAnnouncement.adhocHide(this.bell_ids.request_permissions);
                WaBellAnnouncement.adhocHide(this.bell_ids.error);
            }
            localStorage.setItem('push-mute', '1');
        },

        clearTimeout: function() {
            clearTimeout(this.timeout_id);
            if (window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocHide === 'function') {
                WaBellAnnouncement.adhocHide(this.bell_ids.timeout);
            }
        },

        showSuccess: function() {
            if (!window.WaBellAnnouncement || typeof WaBellAnnouncement.adhocShow !== 'function') {
                // UI 1.3 is not supported
                return;
            }
            if (typeof this.sendTest !== 'function') {
                // Push adapter is not supported
                return;
            }

            const $bell_announcement = $("<p />").html(this.loc.thanxMessage + ' <a href="javascript:void(0);" class="js-submit">' + this.loc.testButtonText + '</a>');
            const that = this;
            $bell_announcement.find('.js-submit').on("click", function() {
                that.sendTest();
            });

            WaBellAnnouncement.adhocHide(this.bell_ids.error);
            WaBellAnnouncement.adhocHide(this.bell_ids.request_permissions);
            WaBellAnnouncement.adhocHide(this.bell_ids.permissions_success); // prevent multiple notifications
            WaBellAnnouncement.adhocShow($bell_announcement, this.bell_ids.permissions_success);
            $(window).trigger('wa_push_status_changed', [true]);
        },

        saveSubscriber: function(data, func = () => {}) {
            const that = this;
            const href = backend_url + "webasyst/?module=push&action=addSubscriber";

            $.post(href, data, function(res) {
                if (res.status === "ok") {
                    func();
                    that.showSuccess();
                } else {
                    console.log("Cannot save subscriber: " + res.errors);
                    $(window).trigger('wa_push_error', [res.errors]);
                }
            });
        },

        deleteSubscriber: function(data) {
            const href = backend_url + "webasyst/?module=push&action=deleteSubscriber";
            $.post(href, data);
        },

        testSubscriber: function(data, func) {
            if (window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocHide === 'function') {
                WaBellAnnouncement.adhocHide(this.bell_ids.error);
            }
            const that = this;
            that.is_test_push_recieved = false;
            const href = backend_url + "webasyst/?module=push&action=testSubscriber";
            $.post(href, data, (res) => {
                if (res.status === "ok") {
                    if (!that.is_test_push_recieved && window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocHide === 'function') {
                        that.timeout_id = setTimeout(() => {
                            WaBellAnnouncement.adhocHide(that.bell_ids.timeout); // prevent multiple notifications
                            WaBellAnnouncement.adhocShow($("<p />").html(that.loc.testTimeoutMessage), that.bell_ids.timeout);
                            that.timeout_id = undefined;
                        }, 1000);
                    }
                } else {
                    if (window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocHide === 'function') {
                        WaBellAnnouncement.adhocHide(that.bell_ids.error); // prevent multiple notifications
                        WaBellAnnouncement.adhocShow($("<p />").addClass("state-error").html(res.errors), that.bell_ids.error);
                    }
                }
                func(res);
            });
        },

        showPush: function(text) {
            if (window.WaBellAnnouncement && typeof WaBellAnnouncement.adhocShow === 'function') {
                WaBellAnnouncement.adhocShow($("<p />").html(text), this.bell_ids.notification);
            }
        },

        checkSubscriber: function(data, func = () => {}) {
            if (sessionStorage.getItem("push-subscriber-check-ok")) {
                func();
                return;
            }
            const that = this;
            const href = backend_url + "webasyst/?module=push&action=checkSubscriber";

            $.post(href, data, function (res) {
                if (res.status === "ok") {
                    func();
                    sessionStorage.setItem("push-subscriber-check-ok", "1");
                } else {
                    that.saveSubscriber(data, func);
                }
            });
        }
    });

    $(window).on('wa_push_error', function(e, error) {
        if (!window.WaBellAnnouncement || typeof WaBellAnnouncement.adhocShow !== 'function') {
            return;
        }
        if (localStorage.getItem('push-mute') && !$.wa_push.force_no_mute) {
            return;
        }
        if (!$.wa_push.force_no_mute) {
            error += '<br><a href="javascript:void(0);" class="gray js-mute">' + $.wa_push.loc.muteText + '</a>';
        }
        $bell_announcement = $("<p />").addClass("state-error").html(error);
        $bell_announcement.find('.js-mute').on("click", function() {
            $.wa_push.mute();
        });
        WaBellAnnouncement.adhocHide($.wa_push.bell_ids.error); // prevent multiple notifications
        WaBellAnnouncement.adhocShow($bell_announcement, $.wa_push.bell_ids.error);
    });

    $(window).on('wa_push_status_changed', function(e) {
        localStorage.removeItem('push-mute');
    });
})($);
