@props([
    'variant' => 'primary', // primary | secondary | danger | ghost
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg text-sm px-5 py-2.5 text-center focus:ring-4 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50';
    $styles = [
        'primary' => 'text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-emerald-300',
        'secondary' => 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200',
        'danger' => 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-300',
        'ghost' => 'text-gray-500 hover:bg-gray-100 focus:ring-gray-200',
    ];
    $classes = $base.' '.($styles[$variant] ?? $styles['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
