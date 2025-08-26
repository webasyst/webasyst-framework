importScripts(
    "https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js"
);

(function (self) {

const firebaseConfig = {
    apiKey: {$api_key|json_encode},
    authDomain: {$project_id|json_encode} + ".firebaseapp.com",
    projectId: {$project_id|json_encode},
    storageBucket: {$project_id|json_encode} + ".firebasestorage.app",
    messagingSenderId: {$sender_id|json_encode},
    appId: {$app_id|json_encode}
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    console.log('Handling background message', payload);
    if (!('notification' in payload) && payload.data?.title && payload.data?.body) {
        self.registration.showNotification(
            payload.data.title, {
                body: payload.data.body,
                image: payload.data.image,
                icon: payload.data.image,
                data: payload.data
            }
        );
    }
});

self.addEventListener('notificationclick', function(event) {
    // Notification clicked.
    console.log('Handling notification click', event.notification);

    const target = event.notification.data?.link || '/';
    event.notification.close();

    // This looks to see if the current is already open and focuses if it is
    event.waitUntil(clients.matchAll({
        type: 'window',
        includeUncontrolled: true
    }).then(function(clientList) {
        // clientList always is empty?!
        for (var i = 0; i < clientList.length; i++) {
            var client = clientList[i];
            if (client.url === target && 'focus' in client) {
                return client.focus();
            }
        }

        return clients.openWindow(target);
    }));
});

})(self);