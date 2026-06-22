@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

<div class="space-y-1">
    @if ($label)
        <label for="{{ $name }}" class="block text-base font-medium text-gray-700">{{ $label }}</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 '.($errors->has($name) ? 'border-red-400' : '')]) }}
    >

    @if ($hint && ! $errors->has($name))
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
