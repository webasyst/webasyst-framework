// {* This js including in smarty on all backend layout pages as part of $wa->header() call. *}
(function($) { "use strict";

    var firebase_core_path = {if !empty($firebase_core_path)}{$firebase_core_path|json_encode}{else}null{/if},
        firebase_messaging_path = {if !empty($firebase_messaging_path)}{$firebase_messaging_path|json_encode}{else}null{/if},
        firebase_sender_id = {if !empty($firebase_sender_id)}{$firebase_sender_id|json_encode}{else}null{/if};

    if (!$ || !firebase_core_path || !firebase_sender_id) return;

    var id = '#firebase-core-'+Date.now(),
        $script = $(id);

    if (!$script.length) {
        var script = document.createElement("script");
        script.async = true;

        document.getElementsByTagName("head")[0].appendChild(script);

        $script = $(script)
            .attr("id", id)
            .on("load", function() {
                initFirebase();
            });

        $script.attr("src", firebase_core_path);
    }

    function initFirebase() {
        // Browser supports notifications?
        // In general, this check should be done by the Firebase library, but it does not.
        if (
            'Notification' in window &&
            'serviceWorker' in navigator
        ) {

            // firebase_subscribe.js
            firebase.initializeApp({
                messagingSenderId: firebase_sender_id
            });

            var messaging = firebase.messaging();

            // Handle catch the notification on current page
            messaging.onMessage(function(payload) {
                console.log('Message received', payload);

                // Register fake ServiceWorker for show notification on mobile devices
                navigator.serviceWorker.register(firebase_messaging_path, { scope: '/' });

                Notification.requestPermission(function(permission) {

                    if (permission === 'granted') {

                        navigator.serviceWorker.ready.then(function(registration) {
                            // Copy data object to get parameters in the click handler
                            payload.data.data = JSON.parse(JSON.stringify(payload.data));

                            registration.showNotification(payload.data.title, payload.data);
                        }).catch(function(error) {
                            // Registration failed :(
                            console.log('ServiceWorker registration failed', error);
                        });

                    }
                });
            });

            // Callback fired if Instance ID token is updated.
            messaging.onTokenRefresh(function() {
                messaging.getToken()
                    .then(function(refreshed_subscriber_token) {
                        console.log('Token refreshed');

                        if (refreshed_subscriber_token) {
                            saveSubscriber(refreshed_subscriber_token);
                        }
                    })
                    .catch(function(error) {
                        console.log('Unable to retrieve refreshed token', error);
                    });
            });

            // Requested permission to receive notifications
            messaging.requestPermission()
                .then(function () {
                    // Get device token
                    messaging.getToken()
                        .then(function (subscriber_token) {
                            if (subscriber_token) {
                                saveSubscriber(subscriber_token);
                            }
                        });
                })
                .catch(function (err) {
                    console.log('Failed to get permission to show notifications.', err);
                });
        }
    }

    // Send subscriber token to the server
    function saveSubscriber(subscriber_token) {
        console.log('Sending a token to the server...');
        var href = {$webasyst_app_url|json_encode}+"?module=push&action=addSubscriber",
            data = {
                provider_id: 'firebase',
                data: {
                    sender_id: firebase_sender_id,
                    token: subscriber_token
                }
            };

        $.post(href, data);
    }

}(window.jQuery));