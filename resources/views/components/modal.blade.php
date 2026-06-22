@props([
    'name' => 'modal',
    'title' => null,
    'maxWidth' => 'lg', // sm | md | lg | xl
])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-2xl'];
    $width = $widths[$maxWidth] ?? $widths['lg'];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') { open = true }"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') { open = false }"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    style="display:none"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
>
    <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50"></div>

    <div
        x-show="open"
        x-transition
        x-trap.inert.noscroll="open"
        class="relative w-full {{ $width }} rounded-lg bg-white shadow-lg"
        role="dialog"
        aria-modal="true"
    >
        @if ($title)
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <button @click="open = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-3">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
