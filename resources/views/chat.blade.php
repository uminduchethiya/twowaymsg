<x-layouts.app>
    <div
        class="flex h-full"
        x-data="chatApp()"
        x-init="init()"
    >
        {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
        <aside class="w-64 flex-shrink-0 bg-gray-900 border-r border-white/10 flex flex-col">

            {{-- User info --}}
            <div class="p-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-sm text-white flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-white text-sm truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-4 overflow-y-auto">

                {{-- Public Rooms --}}
                <div>
                    <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Rooms</p>
                    <div class="space-y-0.5 mt-1">
                        <template x-for="room in rooms" :key="room.id">
                            <button
                                @click="openRoom(room.id)"
                                :class="activeType === 'room' && activeId === room.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium transition text-left"
                            >
                                <span class="text-base" x-text="room.icon"></span>
                                <span x-text="room.name" class="truncate"></span>
                                <span
                                    x-show="(unread['room_' + room.id] ?? 0) > 0"
                                    x-text="unread['room_' + room.id]"
                                    class="ml-auto bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Direct Messages --}}
                <div>
                    <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Direct Messages</p>
                    <div class="space-y-0.5 mt-1">
                        <template x-if="users.length === 0">
                            <p class="px-3 py-2 text-xs text-gray-600">No other users yet.</p>
                        </template>
                        <template x-for="u in users" :key="u.id">
                            <button
                                @click="openDM(u)"
                                :class="activeType === 'dm' && activeId === u.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium transition text-left"
                            >
                                <div
                                    :class="activeType === 'dm' && activeId === u.id ? 'bg-white/20' : 'bg-gray-700'"
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                    x-text="u.name.charAt(0).toUpperCase()"
                                ></div>
                                <span x-text="u.name" class="truncate"></span>
                                <span
                                    x-show="(unread['dm_' + u.id] ?? 0) > 0"
                                    x-text="unread['dm_' + u.id]"
                                    class="ml-auto bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>
            </nav>

            <div class="p-3 border-t border-white/10">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Chat area ───────────────────────────────────────────────────── --}}
        <main class="flex-1 flex flex-col min-w-0">

            {{-- Header --}}
            <header class="flex items-center gap-3 px-6 py-4 border-b border-white/10 bg-gray-900/50 backdrop-blur flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <h2 class="font-bold text-white truncate">
                        <span x-show="activeType === 'room'" x-text="'# ' + activeId"></span>
                        <span x-show="activeType === 'dm'" class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span x-text="dmPartner ? dmPartner.name : ''"></span>
                        </span>
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <span x-show="activeType === 'dm'" class="text-indigo-400">Private conversation</span>
                        <span x-show="activeType === 'room'">
                            <span x-show="connected" class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                Live
                            </span>
                            <span x-show="!connected" class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                                Connecting…
                            </span>
                        </span>
                    </p>
                </div>
            </header>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4" x-ref="messageList">

                <template x-if="loading">
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                    </div>
                </template>

                <template x-if="!loading && messages.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 text-gray-600">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-sm" x-show="activeType === 'room'">No messages yet. Say hello!</p>
                        <p class="text-sm" x-show="activeType === 'dm'">
                            Start a private conversation with <span class="text-indigo-400" x-text="dmPartner ? dmPartner.name : ''"></span>
                        </p>
                    </div>
                </template>

                <template x-for="(msg, index) in messages" :key="msg.id">
                    <div
                        :class="msg.user.id === {{ auth()->id() }} ? 'flex-row-reverse' : 'flex-row'"
                        class="flex items-end gap-2.5"
                    >
                        <div
                            :class="msg.user.id === {{ auth()->id() }} ? 'bg-indigo-600' : 'bg-gray-700'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0 mb-0.5"
                            x-text="msg.user.name.charAt(0).toUpperCase()"
                        ></div>

                        <div
                            :class="msg.user.id === {{ auth()->id() }} ? 'items-end' : 'items-start'"
                            class="flex flex-col max-w-[70%]"
                        >
                            <div
                                :class="msg.user.id === {{ auth()->id() }} ? 'flex-row-reverse' : 'flex-row'"
                                class="flex items-baseline gap-2 mb-1"
                            >
                                <span class="text-xs font-semibold text-gray-300" x-text="msg.user.name"></span>
                                <span class="text-xs text-gray-600" x-text="formatTime(msg.created_at)"></span>
                            </div>

                            <div
                                :class="msg.user.id === {{ auth()->id() }}
                                    ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm'
                                    : 'bg-gray-800 text-gray-100 rounded-2xl rounded-bl-sm'"
                                class="px-4 py-2.5 text-sm leading-relaxed break-words"
                                x-text="msg.body"
                            ></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Input --}}
            <div class="px-6 py-4 border-t border-white/10 bg-gray-900/50 backdrop-blur flex-shrink-0">
                <form @submit.prevent="sendMessage" class="flex items-end gap-3">
                    <div class="flex-1">
                        <textarea
                            x-model="newMessage"
                            @keydown.enter.exact.prevent="sendMessage"
                            @keydown.enter.shift.exact="newMessage += '\n'"
                            x-ref="input"
                            rows="1"
                            :disabled="sending"
                            :placeholder="activeType === 'dm'
                                ? 'Message ' + (dmPartner ? dmPartner.name : '') + '… (private)'
                                : 'Message #' + activeId + '… (Enter to send)'"
                            class="w-full resize-none rounded-2xl bg-gray-800 border border-gray-700 px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition max-h-32 overflow-y-auto"
                            style="field-sizing: content"
                        ></textarea>
                    </div>
                    <button
                        type="submit"
                        :disabled="!newMessage.trim() || sending"
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg x-show="!sending" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                        <svg x-show="sending" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        window.__AUTH_USER_ID__   = {{ auth()->id() }};
        window.__AUTH_USER_NAME__ = @json(auth()->user()->name);
    </script>
</x-layouts.app>
