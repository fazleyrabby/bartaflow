@props([
    'label' => '',
    'value' => '0',
    'href' => null,
    'accent' => 'gray',
])

@php
    $accents = [
        'gray'    => 'text-gray-900',
        'green'   => 'text-emerald-600',
        'blue'    => 'text-blue-600',
        'red'     => 'text-red-600',
        'amber'   => 'text-amber-600',
    ];
    $valueClass = $accents[$accent] ?? $accents['gray'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm {{ $href ? 'transition hover:border-gray-300 hover:shadow' : '' }}">
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold {{ $valueClass }}">{{ $value }}</p>
    @if (! empty(trim($slot)))
        <div class="mt-2">{{ $slot }}</div>
    @endif
</{{ $tag }}>
