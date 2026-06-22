@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => 1,
])

<label class="flex cursor-pointer items-center gap-3" x-data="{ on: {{ old($name, $checked) ? 'true' : 'false' }} }">
    <input type="hidden" name="{{ $name }}" :value="on ? '{{ $value }}' : '0'">
    <button
        type="button"
        @click="on = !on"
        :class="on ? 'bg-emerald-600' : 'bg-gray-300'"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
        role="switch"
        :aria-checked="on"
    >
        <span :class="on ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition"></span>
    </button>
    @if ($label)
        <span class="text-sm text-gray-700">{{ $label }}</span>
    @endif
</label>

@error($name)
    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
@enderror
