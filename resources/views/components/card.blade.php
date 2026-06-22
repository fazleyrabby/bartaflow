@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || $subtitle || isset($header))
        <div class="border-b border-gray-100 px-5 py-4">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            @endisset
        </div>
    @endif

    <div class="px-5 py-4">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
