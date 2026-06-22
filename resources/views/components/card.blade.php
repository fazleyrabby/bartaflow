@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-lg shadow-sm']) }}>
    @if ($title || $subtitle || isset($header))
        <div class="border-b border-gray-200 px-6 py-4">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            @endisset
        </div>
    @endif

    <div class="px-6 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-200 px-6 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
