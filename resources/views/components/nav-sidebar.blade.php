@php
    $navItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ],
        ['label' => 'Contacts', 'route' => 'contacts.index', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
        ['label' => 'Templates', 'route' => null, 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => 'Messages', 'route' => null, 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['label' => 'Scheduling', 'route' => null, 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['label' => 'Logs', 'route' => null, 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ];

    $settingsItems = [
        ['label' => 'Workspace', 'route' => 'settings.workspace', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => 'Team', 'route' => 'settings.team', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['label' => 'WhatsApp', 'route' => 'settings.whatsapp', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2v-2a7 7 0 00-14 0v2a2 2 0 002 2zM12 3a4 4 0 110 8 4 4 0 010-8z'],
    ];
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 flex flex-col bg-gray-900 overflow-hidden transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
>
    {{-- Logo --}}
    <div class="flex h-16 items-center px-5 border-b border-gray-700/60">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-xl font-bold text-white">
            <svg class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            BartaFlow
        </a>
    </div>

    {{-- Nav --}}
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {{-- Main nav --}}
        <p class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-gray-500">Main</p>
        @foreach ($navItems as $item)
            @php
                $href   = $item['route'] ? route($item['route']) : '#';
                $active = $item['route'] && request()->routeIs($item['route'].'*');
            @endphp
            <a
                href="{{ $href }}"
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-all duration-150 group',
                    'bg-white text-gray-900 shadow-sm'                     => $active,
                    'text-gray-400 hover:bg-gray-800 hover:text-white'     => ! $active,
                    'cursor-default opacity-40 pointer-events-none'        => ! $item['route'],
                ])
            >
                <svg @class(['h-5 w-5 shrink-0 transition-colors', 'text-gray-900' => $active, 'text-gray-500 group-hover:text-white' => ! $active]) fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Settings nav --}}
        <p class="px-3 pb-1 pt-4 text-[11px] font-bold uppercase tracking-wider text-gray-500">Settings</p>
        @foreach ($settingsItems as $item)
            @php
                $href   = $item['route'] ? route($item['route']) : '#';
                $active = $item['route'] && request()->routeIs($item['route'].'*');
            @endphp
            <a
                href="{{ $href }}"
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-all duration-150 group',
                    'bg-white text-gray-900 shadow-sm'                     => $active,
                    'text-gray-400 hover:bg-gray-800 hover:text-white'     => ! $active,
                    'cursor-default opacity-40 pointer-events-none'        => ! $item['route'],
                ])
            >
                <svg @class(['h-5 w-5 shrink-0 transition-colors', 'text-gray-900' => $active, 'text-gray-500 group-hover:text-white' => ! $active]) fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    {{-- User card --}}
    <div class="border-t border-gray-700/60 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-sm font-bold text-emerald-400">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? '' }}</p>
                <p class="truncate text-xs text-gray-500">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" title="Sign out" class="rounded p-1 text-gray-500 hover:bg-gray-800 hover:text-red-400 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
