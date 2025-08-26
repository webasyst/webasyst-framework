// {* This js including in smarty on all backend layout pages as part of $wa->header() call. *}
(function($) { "use strict";

    if (!$) return;


    const loc = {
        resetPermissionText: {_ws("Please clear notifications permissions for your domain in the browser settings.")|json_encode},
        notSupportedText: {_ws("Your browser does not support web push notifications.")|json_encode},
        testSuccessText: {_ws("Test notification received:")|json_encode}
    };


    const urls = {
        actions: {$actions_url|json_encode},
        manifest: "manifest.json",
        worker: "OneSignalSDKWorker.djs",
        worker_updater: "OneSignalSDKUpdaterWorker.djs"
    };

    const options = {$options|json_encode};

    // Make sure we're not loaded twice
    let $manifest_link = $('head link[rel="manifest"][href="'+urls.actions+urls.manifest+'"]');
    if ($manifest_link.length && $manifest_link.data('os-init')) {
        console.log('System: Attempted to init OneSignal twice!');
        return;
    }

    // Manifest may or may not be present in HTML source upon page load.
    // It must be present upon loading to *subscribe* for web-push,
    // but for receiving it is not required. Following hack seems to be enough.
    if (!$manifest_link.length) {
        $manifest_link = $('<link rel="manifest" href="'+urls.actions+urls.manifest+'">').appendTo('head');
    }
    $manifest_link.data('os-init', 1);

    // Load OneSignal SDK if not loaded yet
    const ONESIGNAL_LOAD_TIMEOUT = 15000;
    const ONESIGNAL_SDK_URL = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
    if ($('script[src="'+ONESIGNAL_SDK_URL+'"]').length <= 0) {
        $.ajax({
            global: false,
            dataType: 'script',
            url: ONESIGNAL_SDK_URL,
            timeout: ONESIGNAL_LOAD_TIMEOUT,
            error: function() {
                console.log('OneSignal load error', arguments);
                $(window).trigger('wa_push_error', ['OneSignal load error']);
            },
            cache: true
        });
    }

    window.OneSignalDeferred = window.OneSignalDeferred || [];
    OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.init({
            appId: options.api_app_id,
            path: urls.actions,
            welcomeNotification: { disable: true },
            persistNotification: false,
            notificationClickHandlerMatch: 'exact',
            notificationClickHandlerAction: 'navigate',
            notifyButton: {
                enable: false,
            },
            // Scope defines which URLs service worker will be able to affect (i.e. send messages to JS, intercept XHRs).
            // This should be '/' and have to match 'Service-Worker-Allowed'.
            // !!! TODO: use backend URL here instead?..
            serviceWorkerParam: { scope: "/" },
            serviceWorkerPath: urls.worker,
        });
    });
/*
    OneSignalDeferred.push(function(OneSignal) {
        if (DEBUG()) OneSignal.Debug.setLogLevel("debug");

        OneSignal.User.PushSubscription.addEventListener("change", (event) => {
            if (event.current.token) {
                if (DEBUG()) console.log('The push subscription has received a token!');
                try {
                    OneSignal.logout();
                    OneSignal.login(options.external_id);
                } catch (ex) {
                    if (DEBUG()) console.log('Error on OneSignal login', ex);
                }
            }
        });
    });
*/

    OneSignalDeferred.push(async function() {
        await OneSignal.Notifications.addEventListener("permissionPromptDisplay", () => {
            $.wa_push.clearTimeout();
        });
    });

    OneSignalDeferred.push(async function() {
        await OneSignal.Notifications.addEventListener("foregroundWillDisplay", (event) => {
            console.log(event.notification);
            if (event.notification.additionalData?.test) {
                $.wa_push.clearTimeout();
                $.wa_push.is_test_push_recieved = true;
                $.wa_push.showPush(loc.testSuccessText + '<br><span class="small"><strong>' + event.notification.title + '</strong><br>' + event.notification.body + '</span>');
            }
        });
    });

    OneSignalDeferred.push(async function() {
        await OneSignal.Notifications.addEventListener("permissionChange", (permission) => {
            //OneSignal.User.PushSubscription.optOut();
            localStorage.removeItem('onesignal-check-mute');
            if (permission) {
                if (!OneSignal.User.PushSubscription.optedIn) {
                    OneSignal.User.PushSubscription.optIn();
                }
                if (OneSignal.User.PushSubscription.id) {
                    $.wa_push.saveSubscriber(prepareData(OneSignal.User.PushSubscription.id), () => {});
                    if (DEBUG()) console.log('Push permission is granted.');
                } else {
                    if (DEBUG()) console.log("Subscription does not saved: cannot get subscription ID");
                }
            } else {
                if (OneSignal.User.PushSubscription.id) {
                    $.wa_push.deleteSubscriber(prepareData(OneSignal.User.PushSubscription.id));
                }
                if (DEBUG()) console.log("Permission rejected");
                $(window).trigger('wa_push_status_changed', [false]);
            }
        });
    });

    OneSignalDeferred.push(function() {
        setTimeout(async function () {
            await subscriberCheck((permission) => {
                if (!permission) {
                    $.wa_push.askAllow();
                }
            });
        }, 2000);
    });

    /*
    OneSignalDeferred.push(function() {
        OneSignal.User.addEventListener('change', function (event) {
            console.log('change', { event });
        });
    });
    */

    function prepareData(subscription_id) {
        return {
            provider_id: "onesignal",
            data: {
                api_app_id: options.api_app_id,
                api_user_id: subscription_id
            }
        };
    }

    function DEBUG() {
        return window.ONESIGNAL_DEBUG || document.cookie.indexOf('ONESIGNAL_DEBUG=') >= 0;
    }

    async function subscriberCheck(func = () => {}) {
        const isSupported = await OneSignal.Notifications.isPushSupported();
        if (!isSupported) {
            func(false);
            $(window).trigger('wa_push_error', [loc.notSupportedText]);
            return;
        }
        const permission = await OneSignal.Notifications.permission;
        if (!permission) {
            func(false);
        } else {
            const opted_in = await OneSignal.User.PushSubscription.optedIn;
            if (!opted_in) {
                OneSignal.User.PushSubscription.optIn();
            }
            const subscriber_id = await OneSignal.User.PushSubscription.id;
            if (subscriber_id) {
                $.wa_push.checkSubscriber(prepareData(subscriber_id), () => {
                    func(true);
                });
            } else {
                func(false);
            }
        }
    }

    $.wa_push = $.extend($.wa_push || {}, {
        check: async (func) => {
            if (typeof OneSignal !== 'undefined') {
                await subscriberCheck(func);
            } else {
                setTimeout(async () => {
                    if (typeof OneSignal !== 'undefined') {
                        await subscriberCheck(func);
                    }
                }, 2000);
            }
        },

        requestAllow: () => {
            OneSignal.Notifications.requestPermission();
        },

        sendTest: async (func = () => {}) => {
            const subscriber_id = await OneSignal.User.PushSubscription.id;
            if (subscriber_id) {
                $.wa_push.testSubscriber(prepareData(subscriber_id), function (res) {
                    if (res.status === "ok") {
                        func(true, null);
                    } else {
                        $(window).trigger('wa_push_error', [res.errors]);
                        func(false, res.errors);
                    }
                });
            } else {
                $(window).trigger('wa_push_error', [loc.resetPermissionText]);
                func(false, loc.resetPermissionText);
            }
        }
    });

    OneSignalDeferred.push(function() {
        $(window).trigger('wa_push_loaded');
    });

}(window.jQuery));
