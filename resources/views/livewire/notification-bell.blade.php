<div class="relative" wire:poll.5s>
    <!-- Bell Button -->
    <button 
        type="button" 
        wire:click="toggleDropdown" 
        class="relative p-2 text-slate-600 hover:text-slate-900 focus:outline-none transition rounded-xl hover:bg-slate-100"
        title="Notifications"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if($unreadCount > 0)
            <!-- Traditional Red Dot Badge with Pulsing Ring -->
            <span class="absolute top-1 right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-600 ring-2 ring-white"></span>
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    @if($isOpen)
        <div 
            class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white border border-slate-200 shadow-2xl z-50 overflow-hidden divide-y divide-slate-100"
            wire:click.outside="closeDropdown"
        >
            <!-- Header -->
            <div class="px-4 py-3 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-sm text-slate-800">Notifications</span>
                    @if($unreadCount > 0)
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-600 border border-rose-200">
                            {{ $unreadCount }} new
                        </span>
                    @endif
                </div>

                @if($unreadCount > 0)
                    <button 
                        type="button" 
                        wire:click="markAllAsRead" 
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition"
                    >
                        Mark all read
                    </button>
                @endif
            </div>

            <!-- List -->
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                    @endphp
                    <div class="p-3.5 hover:bg-slate-50 transition group relative flex items-start space-x-3 {{ $isUnread ? 'bg-indigo-50/40' : '' }}">
                        <!-- Icon -->
                        <div class="shrink-0 mt-0.5">
                            @if(($data['icon'] ?? '') === 'sparkles')
                                <div class="w-8 h-8 rounded-xl bg-purple-100 border border-purple-200 flex items-center justify-center text-purple-600 font-bold text-sm">
                                    ✨
                                </div>
                            @elseif(($data['icon'] ?? '') === 'document')
                                <div class="w-8 h-8 rounded-xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-600 font-bold text-sm">
                                    📄
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-xl bg-indigo-100 border border-indigo-200 flex items-center justify-center text-indigo-600 font-bold text-sm">
                                    📥
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <a 
                                    href="{{ $data['action_url'] ?? '#' }}" 
                                    wire:click="markAsRead('{{ $notification->id }}')" 
                                    class="text-xs font-bold text-slate-800 hover:text-indigo-600 truncate transition"
                                >
                                    {{ $data['title'] ?? 'Notification' }}
                                </a>
                                <span class="text-[10px] font-medium text-slate-400 shrink-0 ml-2">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5 leading-snug line-clamp-2">
                                {{ $data['message'] ?? '' }}
                            </p>
                        </div>

                        <!-- Delete button -->
                        <button 
                            type="button" 
                            wire:click="deleteNotification('{{ $notification->id }}')" 
                            class="text-slate-400 hover:text-slate-600 transition opacity-0 group-hover:opacity-100 p-1 text-sm font-bold"
                            title="Remove"
                        >
                            &times;
                        </button>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400 text-xs font-medium">
                        No notifications yet!
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
