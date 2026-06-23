<x-app-layout>
    <x-slot:title>Messages</x-slot:title>
    <x-slot:header>Messages</x-slot:header>
    <x-slot:subheader>A complete history of every message — status, details, and retry.</x-slot:subheader>

    <x-slot:actions>
        <x-button :href="route('messages.create')">New message</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- ── Filter bar ─────────────────────────────────────────────────────── --}}
    <form method="GET" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <select name="status" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All statuses</option>
            @foreach (\App\Enums\MessageStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>

        <select name="account_id" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All accounts</option>
            @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}" @selected((int) request('account_id') === $acc->id)>{{ $acc->label }}</option>
            @endforeach
        </select>

        <select name="template_id" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All templates</option>
            @foreach ($templates as $tpl)
                <option value="{{ $tpl->id }}" @selected((int) request('template_id') === $tpl->id)>{{ $tpl->name }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" aria-label="From date">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" aria-label="To date">

        <div class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or phone…" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-6">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Apply filters</button>
            @if (request()->hasAny(['status', 'account_id', 'template_id', 'date_from', 'date_to', 'search']))
                <a href="{{ route('messages.index') }}" class="text-sm text-emerald-600 hover:underline">Clear</a>
            @endif
        </div>
    </form>

    @if ($messages->count() > 0)
        <form method="POST" action="{{ route('messages.retry-bulk') }}" x-data="{ selected: [] }">
            @csrf

            {{-- Bulk action bar --}}
            <div x-show="selected.length > 0" x-cloak class="mb-3 flex items-center justify-between rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800">
                <span><span x-text="selected.length"></span> failed message(s) selected</span>
                <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 font-medium text-white hover:bg-amber-700">Retry selected</button>
            </div>

            <x-table :headers="['', 'Recipient', 'Template', 'Account', 'Status', 'Sent', '']">
                @foreach ($messages as $message)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if ($message->status === \App\Enums\MessageStatus::Failed)
                                <input type="checkbox" name="message_ids[]" value="{{ $message->id }}" x-model.number="selected" class="rounded text-emerald-600 focus:ring-emerald-500">
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <a href="{{ route('messages.show', $message) }}" class="font-medium text-gray-900 hover:text-emerald-600">{{ $message->recipient_name ?? '—' }}</a>
                            <div class="text-xs text-gray-500">{{ $message->recipient_phone }}</div>
                            @if ($message->status === \App\Enums\MessageStatus::Failed && $message->error_message)
                                <div class="text-xs text-red-500">{{ \Illuminate\Support\Str::limit($message->error_message, 60) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $message->template?->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $message->account?->label ?? '—' }}</td>
                        <td class="px-6 py-3"><x-badge :status="$message->status" /></td>
                        <td class="px-6 py-3 text-gray-500">{{ $message->sent_at?->diffForHumans() ?? $message->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('messages.show', $message) }}" class="text-sm font-medium text-emerald-600 hover:underline">View</a>
                                @if ($message->status === \App\Enums\MessageStatus::Failed)
                                    <button type="submit" form="retry-{{ $message->id }}" class="text-sm font-medium text-amber-600 hover:underline">Retry</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </form>

        {{-- Per-row retry forms (outside the bulk form to avoid nesting) --}}
        @foreach ($messages as $message)
            @if ($message->status === \App\Enums\MessageStatus::Failed)
                <form id="retry-{{ $message->id }}" method="POST" action="{{ route('messages.retry', $message) }}" class="hidden">@csrf</form>
            @endif
        @endforeach

        <div class="mt-6">{{ $messages->links() }}</div>
    @else
        <x-empty-state
            title="No messages found"
            message="No messages match your filters yet — try clearing them or compose a new send.">
            <x-slot:actions>
                <x-button :href="route('messages.create')">New message</x-button>
            </x-slot:actions>
        </x-empty-state>
    @endif
</x-app-layout>
