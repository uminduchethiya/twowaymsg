import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const scheme   = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const isTLS    = scheme === 'https';
const wsPort   = import.meta.env.VITE_REVERB_PORT   ?? (isTLS ? 443 : 8080);

window.Echo = new Echo({
    broadcaster:        'reverb',
    key:                import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:             import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1',
    wsPort:             isTLS ? 443  : wsPort,
    wssPort:            isTLS ? 443  : wsPort,
    forceTLS:           isTLS,
    enabledTransports:  ['ws', 'wss'],
    disableStats:       true,
});
