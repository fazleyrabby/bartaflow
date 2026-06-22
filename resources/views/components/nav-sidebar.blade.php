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
    class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-transform duration-200 -translate-x-full lg:static lg:transform-none"
    :class="sidebarOpen ? 'translate-x-0' : ''"
>
    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
        <a href="{{ route('dashboard') }}" class="text-xl font-semibold text-emerald-600">BartaFlow</a>
        <button @click="sidebarOpen = false" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Close menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="overflow-y-auto py-4 px-3">
        <ul class="space-y-1">
            @foreach ($navItems as $item)
                @php
                    $href   = $item['route'] ? route($item['route']) : '#';
                    $active = $item['route'] && request()->routeIs($item['route'].'*');
                @endphp
                <li>
                    <a
                        href="{{ $href }}"
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium',
                            'bg-emerald-50 text-emerald-700' => $active,
                            'text-gray-700 hover:bg-gray-100'  => ! $active,
                            'cursor-default opacity-50'        => ! $item['route'],
                        ])
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <ul class="pt-4 mt-4 space-y-1 border-t border-gray-200">
            <li class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Settings</li>
            @foreach ($settingsItems as $item)
                @php
                    $href   = $item['route'] ? route($item['route']) : '#';
                    $active = $item['route'] && request()->routeIs($item['route'].'*');
                @endphp
                <li>
                    <a
                        href="{{ $href }}"
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium',
                            'bg-emerald-50 text-emerald-700' => $active,
                            'text-gray-700 hover:bg-gray-100'  => ! $active,
                            'cursor-default opacity-50'        => ! $item['route'],
                        ])
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
