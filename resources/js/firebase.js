import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

const firebaseConfig = {
    apiKey: 'AIzaSyBdjJLzOR27vPMVZCHSz_k8QVSyJ12in70',
    authDomain: 'tanod-e40f4.firebaseapp.com',
    projectId: 'tanod-e40f4',
    storageBucket: 'tanod-e40f4.firebasestorage.app',
    messagingSenderId: '513187083786',
    appId: '1:513187083786:web:1ab7b6b60c30d2d23f51f3',
    measurementId: 'G-QB92VN3E8R',
};

const app = initializeApp(firebaseConfig);
let messagingPromise;

async function resolveMessaging() {
    if (!messagingPromise) {
        messagingPromise = (async () => {
            if (typeof window === 'undefined' || typeof navigator === 'undefined') {
                return null;
            }

            if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                return null;
            }

            if (!await isSupported()) {
                return null;
            }

            return getMessaging(app);
        })().catch((error) => {
            console.warn('Firebase messaging is unavailable in this browser.', error);

            return null;
        });
    }

    return messagingPromise;
}

/**
 * Request notification permission and retrieve FCM token.
 * Returns the token string or null if denied.
 */
export async function requestFcmToken(vapidKey) {
    try {
        const messaging = await resolveMessaging();

        if (!messaging || !('Notification' in window)) {
            return null;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.warn('Notification permission denied');
            return null;
        }

        const token = await getToken(messaging, { vapidKey });
        return token;
    } catch (error) {
        console.error('Failed to get FCM token:', error);
        return null;
    }
}

/**
 * Listen for foreground push messages and show a browser notification.
 */
export function onForegroundMessage(callback) {
    void resolveMessaging().then((messaging) => {
        if (!messaging) {
            return;
        }

        onMessage(messaging, (payload) => {
            const { title, body } = payload.notification ?? {};

            if (title && 'Notification' in window && Notification.permission === 'granted') {
                new Notification(title, {
                    body: body ?? '',
                    icon: '/images/logo.png',
                });
            }

            if (callback) {
                callback(payload);
            }
        });
    });
}
