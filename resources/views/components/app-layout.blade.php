<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased">
    <div class="flex h-screen overflow-hidden">
        <x-nav-sidebar />

        <div class="flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
                <div class="ml-auto flex items-center gap-3">
                    {{-- Workspace switcher --}}
                    @isset($userWorkspaces)
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.outside="open = false"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200"
                        >
                            <span class="max-w-[160px] truncate">{{ $workspaceName ?? 'My Workspace' }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 z-10 mt-2 w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
                            <ul class="p-2 space-y-1">
                            @foreach ($userWorkspaces as $ws)
                                <li>
                                    <form method="POST" action="{{ route('workspaces.switch') }}">
                                        @csrf
                                        <input type="hidden" name="workspace_id" value="{{ $ws->id }}">
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-left hover:bg-gray-100 {{ ($currentWorkspaceId ?? null) === $ws->id ? 'bg-emerald-50 text-emerald-700' : 'text-gray-700' }}">
                                            @if (($currentWorkspaceId ?? null) === $ws->id)
                                                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @else
                                                <span class="h-4 w-4"></span>
                                            @endif
                                            <span class="truncate">{{ $ws->name }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                            </ul>
                        </div>
                    </div>
                    @endisset

                    {{-- Profile menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.outside="open = false"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 hover:bg-emerald-200"
                            aria-label="Account menu"
                        >
                            {{ strtoupper(substr($userName ?? 'U', 0, 1)) }}
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 z-10 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg">
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-900 truncate">{{ $userName ?? '' }}</p>
                            </div>
                            <ul class="py-1 text-sm text-gray-700">
                                <li>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.workspace') }}" class="block px-4 py-2 hover:bg-gray-100">Workspace settings</a>
                                </li>
                            </ul>
                            <div class="border-t border-gray-100"></div>
                            <ul class="py-1 text-sm">
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100">
                                            Sign out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Suspended workspace banner --}}
            @if (isset($workspace) && $workspace->isSuspended())
                <div class="flex items-center p-4 text-sm text-yellow-800 bg-yellow-50 border-b border-yellow-200">
                    <svg class="shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>This workspace is suspended. Contact support for assistance.</span>
                </div>
            @endif

            <main class="p-4 sm:p-6 lg:p-8">
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
