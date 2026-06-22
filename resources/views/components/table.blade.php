@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'relative overflow-x-auto rounded-lg border border-gray-200 bg-white']) }}>
    <table class="w-full text-sm text-left text-gray-500">
        @if (count($headers))
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    @foreach ($headers as $head)
                        <th scope="col" class="px-6 py-3">
                            {{ $head }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
