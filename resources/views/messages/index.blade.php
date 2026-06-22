<x-app-layout>
    <x-slot:title>Messages</x-slot:title>
    <x-slot:header>Messages</x-slot:header>
    <x-slot:subheader>Recent sends and their delivery status.</x-slot:subheader>

    <x-slot:actions>
        <x-button :href="route('messages.create')">New message</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Status filter --}}
    <form method="GET" class="mb-4 flex items-center gap-3">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All statuses</option>
            @foreach (\App\Enums\MessageStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
        @if (request('status'))
            <a href="{{ route('messages.index') }}" class="text-sm text-emerald-600 hover:underline">Clear</a>
        @endif
    </form>

    @if ($messages->count() > 0)
        <x-table :headers="['Recipient', 'Template', 'Account', 'Status', 'Sent']">
            @foreach ($messages as $message)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="font-medium text-gray-900">{{ $message->recipient_name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $message->recipient_phone }}</div>
                    </td>
                    <td class="px-6 py-3 text-gray-600">{{ $message->template?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $message->account?->label ?? '—' }}</td>
                    <td class="px-6 py-3"><x-badge :status="$message->status" /></td>
                    <td class="px-6 py-3 text-gray-500">{{ $message->sent_at?->diffForHumans() ?? $message->created_at->diffForHumans() }}</td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-6">{{ $messages->links() }}</div>
    @else
        <x-empty-state
            title="No messages yet"
            message="Compose and send a template to your contacts — sends will appear here with live status.">
            <x-slot:actions>
                <x-button :href="route('messages.create')">New message</x-button>
            </x-slot:actions>
        </x-empty-state>
    @endif
</x-app-layout>
