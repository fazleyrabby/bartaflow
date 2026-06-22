<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        <x-nav-sidebar />

        {{-- Mobile backdrop --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/40 lg:hidden"
            style="display:none"
        ></div>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4">
                <button @click="sidebarOpen = true" class="rounded p-2 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <span class="font-semibold text-emerald-600 lg:hidden">BartaFlow</span>

                <div class="ml-auto flex items-center gap-2">
                    {{-- Workspace switcher --}}
                    @isset($userWorkspaces)
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.outside="open = false"
                            class="flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm hover:bg-gray-50"
                        >
                            <span class="max-w-[160px] truncate">{{ $workspaceName ?? 'My Workspace' }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 mt-1 w-56 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                            @foreach ($userWorkspaces as $ws)
                                <form method="POST" action="{{ route('workspaces.switch') }}">
                                    @csrf
                                    <input type="hidden" name="workspace_id" value="{{ $ws->id }}">
                                    <button type="submit" class="flex w-full items-center gap-2 rounded px-3 py-2 text-sm text-left hover:bg-gray-50 {{ ($currentWorkspaceId ?? null) === $ws->id ? 'font-medium text-emerald-700' : 'text-gray-700' }}">
                                        @if (($currentWorkspaceId ?? null) === $ws->id)
                                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <span class="h-3.5 w-3.5"></span>
                                        @endif
                                        <span class="truncate">{{ $ws->name }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                    @endisset

                    {{-- Profile menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.outside="open = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-medium text-emerald-700"
                            aria-label="Account menu"
                        >
                            {{ strtoupper(substr($userName ?? 'U', 0, 1)) }}
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 mt-1 w-48 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                            <p class="truncate px-3 py-2 text-xs text-gray-400">{{ $userName ?? '' }}</p>
                            <div class="my-1 border-t border-gray-100"></div>
                            <a href="{{ route('profile.edit') }}" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                            <a href="{{ route('settings.workspace') }}" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Workspace settings</a>
                            <div class="my-1 border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Suspended workspace banner --}}
            @if (isset($workspace) && $workspace->isSuspended())
                <div class="bg-amber-50 border-b border-amber-200 px-4 py-2 text-sm text-amber-700">
                    This workspace is suspended. Contact support for assistance.
                </div>
            @endif

            <main class="flex-1 p-4 sm:p-6">
                @isset($header)
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">{{ $header }}</h1>
                            @isset($subheader)
                                <p class="text-sm text-gray-500">{{ $subheader }}</p>
                            @endisset
                        </div>
                        @isset($actions)
                            <div class="flex items-center gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                @endisset

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
</body>
</html>
