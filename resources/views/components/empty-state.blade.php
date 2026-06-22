@props([
    'title' => 'Nothing here yet',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center']) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
        @isset($icon)
            {{ $icon }}
        @else
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        @endisset
    </div>
    <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-gray-500">{{ $message }}</p>
    @endif
    @isset($actions)
        <div class="mt-4 flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
