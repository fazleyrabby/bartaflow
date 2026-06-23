<x-app-layout>
    <x-slot:title>Message detail</x-slot:title>
    <x-slot:header>Message detail</x-slot:header>
    <x-slot:subheader>Everything that happened with this message.</x-slot:subheader>

    <x-slot:actions>
        <a href="{{ route('messages.index') }}" class="text-sm text-gray-500 hover:underline">← Back to messages</a>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- ── Main ── --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Message body">
                <p class="whitespace-pre-wrap text-sm text-gray-800">{{ $message->body }}</p>
            </x-card>

            @if (! empty($message->variables_used))
                <x-card title="Variables used">
                    <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($message->variables_used as $key => $value)
                            <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <dt class="font-mono text-xs text-gray-500">{{ is_int($key) ? $value : $key }}</dt>
                                @if (! is_int($key))
                                    <dd class="font-medium text-gray-900">{{ $value }}</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endif

            <x-card title="Timeline">
                <ol class="relative space-y-5 border-l border-gray-200 pl-5">
                    @foreach ($message->timeline() as $step)
                        <li class="relative">
                            <span class="absolute -left-[1.62rem] mt-1 h-3 w-3 rounded-full border-2 border-white {{ $step['done'] ? ($step['label'] === 'Failed' ? 'bg-red-500' : 'bg-emerald-500') : 'bg-gray-300' }}"></span>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium {{ $step['done'] ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $step['at'] ? $step['at']->setTimezone($workspace->timezone)->format('d M Y, g:i A') : '—' }}
                                </span>
                            </div>
                            @if ($step['label'] === 'Failed' && $message->error_message)
                                <p class="mt-1 text-xs text-red-500">{{ $message->error_message }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        {{-- ── Side ── --}}
        <div class="space-y-6">
            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Status</dt>
                        <dd><x-badge :status="$message->status" /></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Recipient</dt>
                        <dd class="text-right font-medium text-gray-900">{{ $message->recipient_name ?? '—' }}<br><span class="text-xs font-normal text-gray-500">{{ $message->recipient_phone }}</span></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Template</dt>
                        <dd class="text-right text-gray-900">{{ $message->template?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Account</dt>
                        <dd class="text-right text-gray-900">{{ $message->account?->label ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Attempts</dt>
                        <dd class="text-gray-900">{{ $message->attempts }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Provider ID</dt>
                        <dd class="break-all text-right font-mono text-xs text-gray-700">{{ $message->provider_message_id ?? '—' }}</dd>
                    </div>
                    @if ($message->scheduled_message_id)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Source</dt>
                            <dd class="text-gray-900">Scheduled</dd>
                        </div>
                    @endif
                </dl>

                @if ($message->status === \App\Enums\MessageStatus::Failed)
                    <x-slot:footer>
                        <form method="POST" action="{{ route('messages.retry', $message) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-700">Retry message</button>
                        </form>
                    </x-slot:footer>
                @endif
            </x-card>

            @if ($message->status === \App\Enums\MessageStatus::Failed && $message->error_code)
                <x-card title="Failure reason">
                    <p class="text-sm text-red-600">{{ $message->error_message ?? 'Unknown error.' }}</p>
                    <p class="mt-1 font-mono text-xs text-gray-400">{{ $message->error_code }}</p>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
