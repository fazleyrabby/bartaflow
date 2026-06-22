<x-app-layout>
    <x-slot:title>Scheduling</x-slot:title>
    <x-slot:header>Scheduling</x-slot:header>
    <x-slot:subheader>Schedule templated messages to send automatically at a future time.</x-slot:subheader>

    <x-slot:actions>
        <x-button :href="route('scheduling.create')">Schedule message</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 flex items-center gap-3">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ScheduleStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
        @if (request('status'))
            <a href="{{ route('scheduling.index') }}" class="text-sm text-emerald-600 hover:underline">Clear</a>
        @endif
    </form>

    @if ($schedules->count() > 0)
        <x-table :headers="['Name', 'Template', 'Account', 'Runs at', 'Status', '']">
            @foreach ($schedules as $schedule)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $schedule->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $schedule->template?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $schedule->account?->label ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">
                        {{ $schedule->run_at->setTimezone($schedule->timezone)->format('d M Y, g:i A') }}
                        <span class="text-xs text-gray-400">{{ $schedule->timezone }}</span>
                    </td>
                    <td class="px-6 py-3"><x-badge :status="$schedule->status" /></td>
                    <td class="px-6 py-3 text-right">
                        @if ($schedule->status === \App\Enums\ScheduleStatus::Pending)
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('scheduling.edit', $schedule) }}" class="text-sm font-medium text-emerald-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('scheduling.cancel', $schedule) }}"
                                      onsubmit="return confirm('Cancel this scheduled message?')">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700">Cancel</button>
                                </form>
                            </div>
                        @elseif ($schedule->last_error)
                            <span class="text-xs text-red-500" title="{{ $schedule->last_error }}">Failed</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-6">{{ $schedules->links() }}</div>
    @else
        <x-empty-state
            title="No scheduled messages"
            message="Plan ahead — schedule a template to send to your contacts at the perfect time.">
            <x-slot:actions>
                <x-button :href="route('scheduling.create')">Schedule message</x-button>
            </x-slot:actions>
        </x-empty-state>
    @endif
</x-app-layout>
