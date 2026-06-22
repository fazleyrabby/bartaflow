@props(['headers' => []])

{{--
    Responsive table shell. On <md screens callers should render stacked cards;
    this component covers the desktop table per docs/frontend.md §7.
--}}
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white']) }}>
    <table class="min-w-full divide-y divide-gray-200">
        @if (count($headers))
            <thead class="bg-gray-50">
                <tr>
                    @foreach ($headers as $head)
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $head }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
