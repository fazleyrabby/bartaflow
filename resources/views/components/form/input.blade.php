@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900">{{ $label }}</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 '.($errors->has($name) ? 'border-red-500 bg-red-50' : '')]) }}
    >

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
