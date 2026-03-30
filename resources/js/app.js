import './bootstrap';
import '../css/app.css';
import 'flowbite';
import { onForegroundMessage } from './firebase';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => title ? `${title} - TANOD` : 'TANOD',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);

        // Listen for push notifications while the app is in the foreground.
        onForegroundMessage();

        // Subscribe to private WebSocket channel for real-time events.
        const user = props.initialPage?.props?.auth?.user;
        if (user && window.Echo) {
            const channel = window.Echo.private(`notifications.${user.id}`);

            const showNotification = (title, body) => {
                if (Notification.permission === 'granted') {
                    new Notification(title, { body, icon: '/images/logo.png' });
                }
                // Notify the NotificationBell component to refresh
                window.dispatchEvent(new CustomEvent('tanod:notification'));
            };

            channel
                .listen('AlertCreated', (e) => {
                    showNotification(
                        e.alert?.title ?? 'New Alert',
                        e.alert?.message ?? '',
                    );
                })
                .listen('TicketCreated', (e) => {
                    showNotification(
                        'New Ticket',
                        e.ticket?.subject ?? 'A new ticket has been submitted.',
                    );
                })
                .listen('TicketStatusUpdated', (e) => {
                    showNotification(
                        'Ticket Updated',
                        `Ticket "${e.ticket?.subject}" is now ${e.ticket?.status}.`,
                    );
                })
                .listen('BookingCreated', (e) => {
                    showNotification(
                        'New Booking Request',
                        e.booking?.purpose ?? 'A new booking request has been submitted.',
                    );
                })
                .listen('BookingStatusUpdated', (e) => {
                    showNotification(
                        'Booking Updated',
                        `Your booking is now ${e.booking?.status}.`,
                    );
                })
                .listen('DistributionCreated', (e) => {
                    showNotification(
                        'New Distribution',
                        'A tractor has been distributed.',
                    );
                });
        }
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
    },
});