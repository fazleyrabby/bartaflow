@props([
    'status' => null,
    'color' => 'gray',
])

@php
    if ($status !== null && is_object($status) && method_exists($status, 'color')) {
        $color = $status->color();
        $label = method_exists($status, 'label') ? $status->label() : (string) $status->value;
    } else {
        $label = trim($slot) !== '' ? $slot : ($status ?? '');
    }

    $palette = [
        'gray'    => 'bg-gray-100 text-gray-700',
        'blue'    => 'bg-blue-100 text-blue-700',
        'green'   => 'bg-emerald-100 text-emerald-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'red'     => 'bg-red-100 text-red-700',
        'yellow'  => 'bg-amber-100 text-amber-700',
    ];
    $classes = $palette[$color] ?? $palette['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
