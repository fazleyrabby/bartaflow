<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Prevent dark mode flash before Alpine initialises --}}
    <script>if(localStorage.getItem('bartaflow_dark')==='true')document.documentElement.classList.add('dark')</script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <x-nav-sidebar />

        {{-- Mobile backdrop --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
            style="display:none"
        ></div>

        {{-- Main --}}
        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    {{-- Hamburger (mobile only) --}}
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
                        aria-label="Toggle sidebar"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Dark / Light toggle — self-contained Alpine component --}}
                    <button
                        x-data="{
                            dark: localStorage.getItem('bartaflow_dark') === 'true',
                            toggle() {
                                this.dark = !this.dark;
                                this.dark
                                    ? document.documentElement.classList.add('dark')
                                    : document.documentElement.classList.remove('dark');
                                localStorage.setItem('bartaflow_dark', this.dark);
                            }
                        }"
                        @click="toggle()"
                        :title="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    {{-- Workspace switcher --}}
                    @isset($userWorkspaces)
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.outside="open = false"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700"
                        >
                            <span class="max-w-[140px] truncate">{{ $workspaceName ?? 'My Workspace' }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 z-10 mt-2 w-56 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <ul class="p-1.5 space-y-0.5">
                            @foreach ($userWorkspaces as $ws)
                                <li>
                                    <form method="POST" action="{{ route('workspaces.switch') }}">
                                        @csrf
                                        <input type="hidden" name="workspace_id" value="{{ $ws->id }}">
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 {{ ($currentWorkspaceId ?? null) === $ws->id ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-300' }}">
                                            @if (($currentWorkspaceId ?? null) === $ws->id)
                                                <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
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
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60"
                            aria-label="Account menu"
                        >
                            {{ strtoupper(substr($userName ?? 'U', 0, 1)) }}
                        </button>
                        <div x-show="open" x-transition style="display:none" class="absolute right-0 z-10 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $userName ?? '' }}</p>
                            </div>
                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-300">
                                <li>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Profile</a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.workspace') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Workspace settings</a>
                                </li>
                            </ul>
                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                            <ul class="py-1 text-sm">
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100 dark:text-red-400 dark:hover:bg-gray-700">
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
                <div class="flex items-center border-b border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                    <svg class="me-3 inline h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>This workspace is suspended. Contact support for assistance.</span>
                </div>
            @endif

            <main class="w-full grow p-4 sm:p-6 lg:p-8">
                @isset($header)
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $header }}</h1>
                            @isset($subheader)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subheader }}</p>
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
