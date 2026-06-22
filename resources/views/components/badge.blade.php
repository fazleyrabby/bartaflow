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
        'gray'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'blue'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        'green'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
        'red'     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
        'yellow'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
    ];
    $classes = $palette[$color] ?? $palette['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
