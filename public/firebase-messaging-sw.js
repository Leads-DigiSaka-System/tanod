/* eslint-env serviceworker */
/* global firebase */

importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyBdjJLzOR27vPMVZCHSz_k8QVSyJ12in70',
    authDomain: 'tanod-e40f4.firebaseapp.com',
    projectId: 'tanod-e40f4',
    storageBucket: 'tanod-e40f4.firebasestorage.app',
    messagingSenderId: '513187083786',
    appId: '1:513187083786:web:1ab7b6b60c30d2d23f51f3',
    measurementId: 'G-QB92VN3E8R',
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    const { title, body } = payload.notification ?? {};

    if (title) {
        self.registration.showNotification(title, {
            body: body ?? '',
            icon: '/images/logo.png',
        });
    }
});
