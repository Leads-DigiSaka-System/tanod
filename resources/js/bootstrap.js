import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_WEBSOCKET_APP_KEY,
    cluster: 'mt1',
    wsHost: import.meta.env.VITE_WEBSOCKET_HOST,
    wsPort: import.meta.env.VITE_WEBSOCKET_PORT,
    wssPort: import.meta.env.VITE_WEBSOCKET_PORT,
    forceTLS: true,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/api/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
                .then(response => callback(false, response.data))
                .catch(error => callback(true, error));
            },
        };
    },
});
