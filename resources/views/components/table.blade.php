@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'relative overflow-x-auto rounded-xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-800']) }}>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        @if (count($headers))
            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 dark:bg-gray-800/50 dark:text-gray-400 dark:border-gray-800">
                <tr>
                    @foreach ($headers as $head)
                        <th scope="col" class="px-6 py-3 font-medium">
                            {{ $head }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            {{ $slot }}
        </tbody>
    </table>
</div>
