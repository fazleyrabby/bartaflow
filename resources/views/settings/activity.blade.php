<x-app-layout>
    <x-slot:title>Activity log</x-slot:title>
    <x-slot:header>Activity log</x-slot:header>
    <x-slot:subheader>A queryable audit trail of privileged actions in this workspace.</x-slot:subheader>

    {{-- ── Filters ────────────────────────────────────────────────────────── --}}
    <form method="GET" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <select name="action" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>

        <select name="user_id" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All members</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((int) request('user_id') === $member->id)>{{ $member->name }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" aria-label="From date">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" aria-label="To date">

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
            @if (request()->hasAny(['action', 'user_id', 'date_from', 'date_to']))
                <a href="{{ route('settings.activity') }}" class="text-sm text-emerald-600 hover:underline">Clear</a>
            @endif
        </div>
    </form>

    @if ($logs->count() > 0)
        <x-table :headers="['Time', 'Actor', 'Action', 'Description', 'IP']">
            @foreach ($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap px-6 py-3 text-gray-500">
                        {{ $log->created_at?->setTimezone($workspace->timezone)->format('d M Y, g:i A') ?? '—' }}
                    </td>
                    <td class="px-6 py-3">
                        <div class="font-medium text-gray-900">{{ $log->user?->name ?? 'System' }}</div>
                        <div class="text-xs text-gray-500">{{ $log->user?->email }}</div>
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 font-mono text-xs text-gray-700">{{ $log->action }}</span>
                    </td>
                    <td class="px-6 py-3 text-gray-600">{{ $log->description ?? '—' }}</td>
                    <td class="px-6 py-3 font-mono text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-6">{{ $logs->links() }}</div>
    @else
        <x-empty-state
            title="No activity recorded"
            message="Privileged actions — invites, role changes, sends, account changes — will appear here as they happen." />
    @endif
</x-app-layout>
