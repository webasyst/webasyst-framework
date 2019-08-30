// {* This js including in smarty on all backend layout pages as part of $wa->header() call. *}
(function($) { "use strict";

    if (!$) return;

    var ONESIGNAL_LOAD_TIMEOUT = 15000;

    var loc = {
        httpPermissionRequest: {
            enable: true,
            modalTitle: {_w("Thanks for subscribing")|json_encode},
            modalMessage: {_w("You're now subscribed to notifications. You can unsubscribe at any time.")|json_encode},
            modalButtonText: {_w("Finish Subscribing!")|json_encode}
        }
    };

    var urls = {
        webasyst_app_url: {$webasyst_app_url|json_encode},
        actions: {$actions_url|json_encode},
        manifest: "manifest.json",
        worker: "OneSignalSDKWorker.djs",
        worker_updater: "OneSignalSDKUpdaterWorker.djs"
    };

    var options = {$options|json_encode};

    // Make sure we're not loaded twice
    var $manifest_link = $('head link[rel="manifest"][href="'+urls["actions"]+urls["manifest"]+'"]');
    if ($manifest_link.length && $manifest_link.data('os-init')) {
        console.log('System: Attempted to init OneSignal twice!');
        return;
    }

    // Manifest may or may not be present in HTML source upon page load.
    // It must be present upon loading to *subscribe* for web-push,
    // but for receiving it is not required. Following hack seems to be enough.
    if (!$manifest_link.length) {
        $manifest_link = $('<link rel="manifest" href="'+urls["actions"]+urls["manifest"]+'">').appendTo('head');
    }
    $manifest_link.data('os-init', 1);

    // Initialize OneSignal SDK
    function initOneSignal() {

        // Make sure it's not loaded yet.
        var initialize_onesignal = !window.OneSignal || !window.OneSignal.push;

        // Prepare initialization callbacks
        if (initialize_onesignal) {
            if (DEBUG()) console.log('System: Initializing Onsesignal');

            window.OneSignal = [];
            OneSignal.push(function() {
                // Scope defines which URLs service worker will be able to affect (i.e. send messages to JS, intercept XHRs).
                // This should be '/' and have to match 'Service-Worker-Allowed'.
                // !!! TODO: use backend URL here instead?..
                OneSignal.SERVICE_WORKER_PARAM = { scope: '/' };

                // We use custom extension here because 'js'
                // is cached by nginx and/or intercepted at .htaccess level.
                OneSignal.SERVICE_WORKER_PATH = urls["worker"];
                OneSignal.SERVICE_WORKER_UPDATER_PATH = urls["worker_updater"];

                OneSignal.LOGGING = !!DEBUG();
            });

            OneSignal.push(["init", {
                appId: options.api_app_id,
                autoRegister: false,
                path: urls["actions"],
                subdomainName: options["api_subdomain_name"],
                httpPermissionRequest: loc.httpPermissionRequest,
                //promptOptions:
                welcomeNotification: { disable: true },
                persistNotification: false,
                notificationClickHandlerMatch: 'exact',
                notificationClickHandlerAction: 'navigate',
                //allowLocalhostAsSecureOrigin: true,
                notifyButton: {
                    enable: false
                }
            }]);
        }

        // Callback when OneSignal is ready
        var deferred = $.Deferred();
        OneSignal.push(["getNotificationPermission", function(permission) {
            if (typeof permission == 'string') {
                deferred.resolveWith(OneSignal, [permission, options]);
            } else {
                permission.then(function(permission) {
                    deferred.resolveWith(OneSignal, [permission, options]);
                });
            }
        }]);

        // Load OneSignal SDK if not loaded yet
        var ONESIGNAL_SDK_URL = 'https://cdn.onesignal.com/sdks/OneSignalSDK.js';
        if (initialize_onesignal && $('script[src="'+ONESIGNAL_SDK_URL+'"]').length <= 0) {
            $.ajax({
                global: false,
                dataType: 'script',
                url: ONESIGNAL_SDK_URL,
                timeout: ONESIGNAL_LOAD_TIMEOUT,
                error: function() {
                    //console.log('onesignal load error', arguments);
                    deferred.reject('onesignal_load_error');
                },
                cache: true
            });
        }

        setTimeout(function() {
            if (deferred.state() == 'pending') {
                deferred.reject('onesignal_timeout');
            }
        }, ONESIGNAL_LOAD_TIMEOUT + 3000);

        return deferred.promise();
    }

    var promise = initOneSignal();

    promise.then(function(current_permission) {
        // Don't bother if user explicitly denied the permission
        if (current_permission === 'denied') {
            if (DEBUG()) console.log('User previously denied permission for push-notifications.');
            return;
        }

        // Do not ask forpermission more than once per full page reload
        if (promise.subscribeForPush_called) {
            if (DEBUG()) console.log('Do not ask forpermission more than once per full page reload');
            return;
        }

        // Ask him politely if he neither granted nor denied permission
        if (current_permission == 'default') {
            if (DEBUG()) console.log('Asking for permission to send push notifications about calls');
            promise.subscribeForPush_called = true;
            OneSignal.registerForPushNotifications();
            return;
        }

        if (current_permission == 'granted') {
            OneSignal.isPushNotificationsEnabled().then(function(is_enabled) {
                if (DEBUG()) console.log('is_enabled', is_enabled);
                // User has granted permission in browser,
                // but OneSignal does not know about it for some reason
                if (!is_enabled) {
                    if (DEBUG()) console.log('Push permission is granted, but OneSignal does not know that yet.');
                    promise.subscribeForPush_called = true;
                    return OneSignal.registerForPushNotifications();
                }
            }).then(null, function() {
                console.log('isPushNotificationsEnabled() or registerForPushNotifications() error', arguments);
            });
        }
    });

    promise.then(function(current_permission) {
        if (DEBUG()) console.log('System: Current onesignal permission:', current_permission);

        // Remember current user's OneSignal credentials on server to know where to notify to
        initSubscriberSave(current_permission);

        OneSignal.on('notificationPermissionChange', function(permissionChange) {
            var new_permission = permissionChange.to;
            initSubscriberSave(new_permission);
        });

        function initSubscriberSave(permission) {
            if (permission == 'granted') {

                setTimeout(function () {
                    OneSignal.getUserId().then(function (user_id) {
                        if (!user_id) {
                            if (DEBUG()) console.log('System: current onesignal user_id:', user_id);
                            return;
                        }

                        if (DEBUG()) console.log('System: Saving onesignal user_id to server:', user_id);

                        saveSubscriber(user_id);
                    }, function (reason) {
                        console.log(reason);
                    });
                }, 2000);

            }
        }

        function saveSubscriber(user_id) {
            var href = urls["webasyst_app_url"] + "?module=push&action=addSubscriber",
                data = {
                    provider_id: 'onesignal',
                    data: {
                        api_app_id: options["api_app_id"],
                        api_user_id: user_id
                    }
                };

            $.post(href, data);
        }

    });

    function DEBUG() {
        return window.ONESIGNAL_DEBUG || document.cookie.indexOf('ONESIGNAL_DEBUG=') >= 0;
    }

}(window.jQuery));