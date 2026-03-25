import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Read config injected by the blade layout via window.__REVERB_CONFIG__
// so values are always correct at runtime regardless of build-time env vars.
const cfg    = window.__REVERB_CONFIG__ ?? {};
const key    = cfg.key    ?? '';
const host   = cfg.host   ?? '127.0.0.1';
const port   = cfg.port   ?? 443;
const scheme = cfg.scheme ?? 'https';
const isTLS  = scheme === 'https';

window.Echo = new Echo({
    broadcaster:       'reverb',
    key,
    wsHost:            host,
    wsPort:            isTLS ? 443 : port,
    wssPort:           isTLS ? 443 : port,
    forceTLS:          isTLS,
    enabledTransports: ['ws', 'wss'],
    disableStats:      true,
});
