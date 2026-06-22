@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
    'hint' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:ring-emerald-500 dark:focus:border-emerald-500 '.($errors->has($name) ? 'border-red-500 bg-red-50 dark:border-red-500 dark:bg-red-900/20' : '')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
