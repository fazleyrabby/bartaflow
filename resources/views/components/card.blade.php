@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-gray-800']) }}>
    @if ($title || $subtitle || isset($header))
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            @endisset
        </div>
    @endif

    <div class="px-6 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-200 px-6 py-3 dark:border-gray-800">
            {{ $footer }}
        </div>
    @endisset
</div>
