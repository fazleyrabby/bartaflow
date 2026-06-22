@props([
    'name',
    'label' => null,
    'options' => [], // ['value' => 'Label'] or list of ['value'=>, 'label'=>]
    'selected' => null,
    'placeholder' => null,
    'hint' => null,
])

<div class="space-y-1">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 '.($errors->has($name) ? 'border-red-400' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $key => $option)
            @php
                [$value, $text] = is_array($option)
                    ? [$option['value'] ?? $key, $option['label'] ?? $option['value'] ?? $key]
                    : [$key, $option];
            @endphp
            <option value="{{ $value }}" @selected(old($name, $selected) == $value)>{{ $text }}</option>
        @endforeach

        {{ $slot }}
    </select>

    @if ($hint && ! $errors->has($name))
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
