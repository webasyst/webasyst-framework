// Import the functions you need from the SDKs you need
{if !empty($api_key) && !empty($project_id) && !empty($app_id) && !empty($sender_id)}
import {
    initializeApp
} from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import {
    getMessaging,
    onMessage,
    isSupported,
    getToken,
} from "https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging.js";
/*
import {
    isSupported as isSwSupported
} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging-sw.js";
*/
(function($) {

(async function (window) {

    const loc = {
        resetPermissionText: {_ws("Please clear notifications permissions for your domain in the browser settings.")|json_encode},
        notSupportedText: {_ws("Your browser does not support web push notifications.")|json_encode},
        testSuccessText: {$testSuccessText|json_encode}
    };

    if (!isSupported()) {
        // Notification is not supported by the browser
        $(window).trigger('wa_push_error', [loc.notSupportedText]);
        return;
    }

    if (window.location.protocol === 'http:') {
        $.wa_push.showHttpPermissionAlert();
        return;
    }

    // Your web app's Firebase configuration
    const firebaseConfig = {
        apiKey: {$api_key|json_encode},
        authDomain: {$project_id|json_encode} + ".firebaseapp.com",
        projectId: {$project_id|json_encode},
        storageBucket: {$project_id|json_encode} + ".firebasestorage.app",
        messagingSenderId: {$sender_id|json_encode},
        appId: {$app_id|json_encode}
    };

    const registerServiceWorker = async () => {
        try {
            const swOptions = {
                type: "classic",
                scope: "/",
            };

            const sw = await window.navigator.serviceWorker.register("{$messaging_sw_url}?ts={$update_ts}", swOptions);

            return sw
                .update()
                .then((registration) => {
                    return registration;
                })
                .catch((error) =>
                    console.error("Can not update service worker", error)
                );
        } catch (error) {
            // Oops. Registration was unsucessfull
            console.error("Can not register service worker", error);
        }
    };

    const requestPermission = async (messaging) => {
        try {
          const permission = await window.Notification.requestPermission();

          if (permission === "granted") {
            $('.js-push-request').append('<i class="fas fa-spinner wa-animation-spin speed-1000 custom-ml-8"></i>');
            /*
            const serviceWorkerRegistration = await registerServiceWorker();

            return getToken(messaging, {
              serviceWorkerRegistration,
              vapidKey: {$vapid_key|json_encode},
            })
              .then((token) => {
                // Generated a new FCM token for the client
                // You can send it to server, e.g. fetch('your.server/subscribe', { token });
                // And store it for further usages (Server, LocalStorage, IndexedDB, ...)
                // For example:
                window.localStorage.setItem("fcm_token", token);
                console.log(token);
                saveSubscriber(token);
              })
              .catch((err) => {
                console.error("Unable to get FCM Token", err);
              }); */
          } else {
            console.log("Unable to grant permission", permission);
            $('.js-push-request').attr('disabled', false);
          }
        } catch (error) {
          console.error("Unable to request permission", error);
          $('.js-push-request').attr('disabled', false);
        }
    };

    const subscriberCheck = async (func = () => {}) => {
        if (window.Notification.permission !== "granted") {
            func(false);
            return;
        }

        const subscriber_token = await getSubscriberToken();
        if (subscriber_token) {
            $.wa_push.checkSubscriber(prepareData(subscriber_token), () => {
                func(true);
            });
        } else {
            func(false);
        }
    };

    const prepareData = (subscriber_token) => {
        return {
            provider_id: 'firebase',
            data: {
                sender_id: {$sender_id|json_encode},
                token: subscriber_token
            }
        };
    };

    const getSubscriberToken = async () => {
        return navigator.locks.request("token_inprocess", async (lock) => {
            if (window.Notification.permission !== "granted") {
                return null;
            }
            if (window.localStorage.getItem("fcm_token")) {
                return window.localStorage.getItem("fcm_token");
            }

            const serviceWorkerRegistration = await registerServiceWorker();
            try {
                const subscriber_token = await getToken(messaging, {
                    serviceWorkerRegistration,
                    vapidKey: {$vapid_key|json_encode},
                });
                if (!subscriber_token) {
                    $(window).trigger('wa_push_error', [{_ws('An error occurred while getting the Firebase messaging subscriber token. Please check your Firebase settings.')|json_encode}]);
                    return null;
                }
                $.wa_push.saveSubscriber(prepareData(subscriber_token));
                window.localStorage.setItem("fcm_token", subscriber_token);
                return subscriber_token;
            } catch (err) {
                $(window).trigger('wa_push_error', ["<p>" + {_ws('An error occurred while getting the Firebase messaging subscriber token. Please check your Firebase settings.')|json_encode} + "</p><p class='hint'>" + err + "</p>"]);
            }
            return null;
        }).then((subscriber_token) => subscriber_token);
    }

    const checkIfTokenIsNotGeneratedBefore = () =>
        !window.localStorage.getItem("fcm_token");

    const app = initializeApp(firebaseConfig, "a" + {$update_ts|json_encode});

    const messaging = getMessaging(app);

    const serviceWorkerRegistration = await registerServiceWorker();

/*
    if (checkIfTokenIsNotGeneratedBefore() && window.Notification.permission !== "denied") {
        await requestPermission(messaging);
    }
*/

    onMessage(messaging, (payload) => {
        // Show notification & handle other stuff
        console.log(payload);

        if (payload.data?.test) {
            $.wa_push.clearTimeout();
            $.wa_push.is_test_push_recieved = true;
            $.wa_push.showPush(loc.testSuccessText + '<br><span class="small"><strong>' + payload.data.title + '</strong><br>' + payload.data.body + '</span>');
        } else {
            $.wa_push.showPush('<span class="small"><strong>' + payload.data.title + '</strong><br>' + payload.data.body + (payload.data?.link ? '<br><a class="button light-gray" href="' + payload.data.link + '">{_ws('Open')} <i class="fas fa-angle-double-right"></i></a>' : '') + '</span>');
        }

        navigator.locks.request("show_firebase_message", async (lock) => {
            const last_message_id = localStorage.getItem('last_firebase_message_id');
            if (payload.messageId != last_message_id) {
                localStorage.setItem('last_firebase_message_id', payload.messageId);
                serviceWorkerRegistration.showNotification(
                    payload.data.title,
                    {
                        body: payload.data.body,
                        image: payload.data.image,
                        icon: payload.data.image,
                        data: payload.data
                    }
                );
            }
        });
    });

    navigator.permissions?.query({
        name: 'notifications'
    })?.then(function(notificationPerm) {
        notificationPerm.onchange = async () => {
            console.log("User decided to change his seettings. New permission: " + notificationPerm.state);
            if (notificationPerm.state == "granted") {
                const token = await getSubscriberToken();
            } else {
                if (window.localStorage.getItem("fcm_token")) {
                    const token = window.localStorage.getItem("fcm_token");
                    $.wa_push.deleteSubscriber(prepareData(token));
                    window.localStorage.removeItem("fcm_token");
                }
                $(window).trigger('wa_push_status_changed', [false]);
            }
        };
    });

    $.wa_push = $.extend($.wa_push || {}, {
        check: (func) => {
            subscriberCheck(func);
        },

        requestAllow: async () => {
            $('.js-push-request').attr('disabled', true);
            await requestPermission(messaging);
        },

        sendTest: async (func = () => {}) => {
            if (window.Notification.permission !== "granted") {
                func(false);
                return;
            }

            const subscriber_token = await getSubscriberToken();
            if (subscriber_token) {
                $.wa_push.testSubscriber(prepareData(subscriber_token), function (res) {
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

    $(window).trigger('wa_push_loaded');

    await subscriberCheck((permission) => {
        if (!permission) {
            $.wa_push.askAllow();
        }
    });

})(window);

}(window.jQuery));
{/if}
