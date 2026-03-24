import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('chatApp', () => ({
    // ── Rooms ──────────────────────────────────────────────────────────────
    rooms: [
        { id: 'general',       name: 'General',       icon: '💬' },
        { id: 'random',        name: 'Random',        icon: '🎲' },
        { id: 'tech',          name: 'Tech',          icon: '💻' },
        { id: 'announcements', name: 'Announcements', icon: '📢' },
    ],

    // ── State ──────────────────────────────────────────────────────────────
    activeType: 'room',   // 'room' | 'dm'
    activeId: 'general',  // room slug OR user id (number)
    dmPartner: null,      // { id, name } of the person we're DMing

    messages: [],
    users: [],            // other users for DM list
    newMessage: '',
    loading: false,
    sending: false,
    connected: false,
    unread: {},           // keys: 'room_general', 'dm_5', etc.

    _roomChannel: null,
    _dmChannels: {},      // keyed by partner id

    // ── Boot ───────────────────────────────────────────────────────────────
    async init() {
        await Promise.all([this.loadUsers(), this.loadMessages()]);
        this.subscribeRoom(this.activeId);

        const conn = window.Echo.connector.pusher.connection;
        this.connected = conn.state === 'connected';
        conn.bind('connected',    () => { this.connected = true; });
        conn.bind('disconnected', () => { this.connected = false; });
        conn.bind('unavailable',  () => { this.connected = false; });
        conn.bind('failed',       () => { this.connected = false; });
    },

    // ── Data loaders ───────────────────────────────────────────────────────
    async loadUsers() {
        try {
            const res = await window.axios.get('/users');
            this.users = res.data;
            // Subscribe to every DM private channel up-front so we get notifications
            this.users.forEach(u => this.subscribeDM(u));
        } catch (e) {
            console.error('Failed to load users', e);
        }
    },

    async loadMessages() {
        this.loading = true;
        this.messages = [];
        try {
            let res;
            if (this.activeType === 'room') {
                res = await window.axios.get('/messages', { params: { room: this.activeId } });
            } else {
                res = await window.axios.get('/dm', { params: { with: this.activeId } });
            }
            this.messages = res.data;
            this.$nextTick(() => this.scrollToBottom());
        } catch (e) {
            console.error('Failed to load messages', e);
        } finally {
            this.loading = false;
        }
    },

    // ── Navigation ─────────────────────────────────────────────────────────
    async openRoom(roomId) {
        if (this.activeType === 'room' && this.activeId === roomId) return;
        this.activeType = 'room';
        this.activeId   = roomId;
        this.dmPartner  = null;
        this.unread['room_' + roomId] = 0;
        this.subscribeRoom(roomId);
        await this.loadMessages();
        this.$nextTick(() => this.$refs.input.focus());
    },

    async openDM(user) {
        if (this.activeType === 'dm' && this.activeId === user.id) return;
        this.activeType = 'dm';
        this.activeId   = user.id;
        this.dmPartner  = user;
        this.unread['dm_' + user.id] = 0;
        await this.loadMessages();
        this.$nextTick(() => this.$refs.input.focus());
    },

    // ── WebSocket subscriptions ────────────────────────────────────────────
    subscribeRoom(roomId) {
        if (this._roomChannel) {
            window.Echo.leave('chat.' + this._roomChannel);
        }
        this._roomChannel = roomId;

        window.Echo.channel('chat.' + roomId)
            .listen('.message.sent', (data) => {
                if (this.activeType === 'room' && this.activeId === data.room) {
                    this._upsertMessage(data);
                } else {
                    this._bump('room_' + data.room);
                }
            });
    },

    subscribeDM(user) {
        if (this._dmChannels[user.id]) return; // already subscribed

        const myId      = window.__AUTH_USER_ID__;
        const partnerId = user.id;
        const key       = Math.min(myId, partnerId) + '.' + Math.max(myId, partnerId);

        this._dmChannels[user.id] = window.Echo.private('dm.' + key)
            .listen('.dm.sent', (data) => {
                const fromMe = data.user.id === myId;
                if (this.activeType === 'dm' && this.activeId === (fromMe ? data.receiver_id : data.user.id)) {
                    this._upsertMessage(data);
                } else if (!fromMe) {
                    this._bump('dm_' + data.user.id);
                }
            });
    },

    // ── Send ───────────────────────────────────────────────────────────────
    async sendMessage() {
        const body = this.newMessage.trim();
        if (!body || this.sending) return;

        this.sending = true;

        const optimistic = {
            id:          'tmp-' + Date.now(),
            body,
            created_at:  new Date().toISOString(),
            user:        { id: window.__AUTH_USER_ID__, name: window.__AUTH_USER_NAME__ },
            receiver_id: this.activeType === 'dm' ? this.activeId : null,
        };
        this.messages.push(optimistic);
        this.newMessage = '';
        this.$nextTick(() => this.scrollToBottom());

        try {
            let res;
            if (this.activeType === 'room') {
                res = await window.axios.post('/messages', { body, room: this.activeId });
            } else {
                res = await window.axios.post('/dm', { body, receiver_id: this.activeId });
            }
            // Replace optimistic with real message from server
            const idx = this.messages.findIndex(m => m.id === optimistic.id);
            if (idx !== -1) this.messages.splice(idx, 1, res.data);
        } catch (e) {
            const idx = this.messages.findIndex(m => m.id === optimistic.id);
            if (idx !== -1) this.messages.splice(idx, 1);
            this.newMessage = body;
            console.error('Failed to send message', e);
        } finally {
            this.sending = false;
            this.$nextTick(() => this.$refs.input.focus());
        }
    },

    // ── Helpers ────────────────────────────────────────────────────────────
    _upsertMessage(data) {
        // Replace matching optimistic bubble to avoid duplicates
        const optIdx = this.messages.findIndex(
            m => String(m.id).startsWith('tmp-')
              && m.user.id === data.user.id
              && m.body    === data.body
        );
        if (optIdx !== -1) {
            this.messages.splice(optIdx, 1, data);
        } else {
            this.messages.push(data);
            this.$nextTick(() => this.scrollToBottom());
        }
    },

    _bump(key) {
        this.unread[key] = (this.unread[key] ?? 0) + 1;
    },

    scrollToBottom() {
        const el = this.$refs.messageList;
        if (el) el.scrollTop = el.scrollHeight;
    },

    formatTime(iso) {
        return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
}));

Alpine.start();
