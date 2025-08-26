import {
    initializeApp
} from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import {
    getMessaging,
    isSupported,
    getToken,
} from "https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging.js";

(function($) { "use strict";
    window.waSettingsPush.validators.push(async (form) => {
        if (form["push_adapter"].value !== 'firebase') {
            return;
        }

        const api_key = form["push_settings[firebase][api_key]"].value;
        const project_id = form["push_settings[firebase][project_id]"].value;
        const app_id = form["push_settings[firebase][app_id]"].value;
        const sender_id = form["push_settings[firebase][sender_id]"].value;
        const vapid_key = form["push_settings[firebase][vapid_key]"].value;
        const json_key = form["push_settings[firebase][json_key]"].value;

        if (!api_key && !project_id && !app_id && !sender_id && !vapid_key && !json_key) {
            return;
        }

        if (!api_key || !project_id || !app_id || !sender_id || !vapid_key || !json_key) {
            return {_ws('Please fill in all fields.')|json_encode};
        }

        const is_supported = await isSupported();
        if (!is_supported) {
            console.log('Browser does not support notifications');
            return;
        }

        if (window.location.protocol === 'http:') {
            console.log('Validation is not available: https required.');
            return;
        }

        if (window.Notification.permission === "denied") {
            console.log('Validation is not available: notifications are blocked by user.');
            return;
        }

        const registerServiceWorker = async () => {
            try {
                const swOptions = {
                    type: "classic",
                    scope: "/",
                };

                const sw = await window.navigator.serviceWorker.register("{$messaging_sw_url}", swOptions);

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

        const firebaseConfig = {
            apiKey: api_key,
            authDomain: project_id + ".firebaseapp.com",
            projectId: project_id,
            storageBucket: project_id + ".firebasestorage.app",
            messagingSenderId: sender_id,
            appId: app_id
        };

        try {
            const app = initializeApp(firebaseConfig, "a" + Date.now());
            const messaging = getMessaging(app);
            const serviceWorkerRegistration = await registerServiceWorker();
            const token = await getToken(messaging, {
                serviceWorkerRegistration,
                vapidKey: vapid_key
            });
            if (!token) {
                return {_ws('An error occurred while getting the Firebase messaging subscriber token. Please check your Firebase settings.')|json_encode};
            }
        } catch (err) {
            console.log('An error occurred while retrieving token. ', err);
            // ...
            return {_ws('An error occurred while getting the Firebase messaging subscriber token. Please check your Firebase settings.')|json_encode};
        }
    });
}(window.jQuery));
