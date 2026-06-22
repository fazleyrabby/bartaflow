@props([
    'title' => 'Nothing here yet',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900']) }}>
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
        @isset($icon)
            {{ $icon }}
        @else
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        @endisset
    </div>
    <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    @if ($message)
        <p class="mb-4 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
    @endif
    @isset($actions)
        <div class="flex items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
