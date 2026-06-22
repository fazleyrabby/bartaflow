<x-app-layout>
    <x-slot:title>WhatsApp Accounts</x-slot:title>
    <x-slot:header>WhatsApp Accounts</x-slot:header>
    <x-slot:subheader>Connect and manage WhatsApp Business accounts for this workspace.</x-slot:subheader>

    <div class="max-w-3xl space-y-6">

        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700 border border-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{-- ── Account List ── --}}
        @if ($accounts->isEmpty())
            <x-card>
                <div class="py-10 text-center">
                    <div class="mb-3 text-4xl">📱</div>
                    <h3 class="text-base font-semibold text-gray-900">No WhatsApp accounts connected</h3>
                    <p class="mt-1 text-sm text-gray-500">Connect a WhatsApp Business account to start sending messages.</p>
                    @can('create', [\App\Models\WhatsAppAccount::class, $workspace->id])
                        <div class="mt-4">
                            <a href="{{ route('settings.whatsapp.create') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Connect account
                            </a>
                        </div>
                    @endcan
                </div>
            </x-card>
        @else
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">{{ $accounts->count() }} account(s) connected.</p>
                @can('create', [\App\Models\WhatsAppAccount::class, $workspace->id])
                    <a href="{{ route('settings.whatsapp.create') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        Connect account
                    </a>
                @endcan
            </div>

            <div class="space-y-4">
                @foreach ($accounts as $account)
                    @php
                        /** @var \App\Models\WhatsAppAccount $account */
                        /** @var \App\Enums\AccountStatus $status */
                        $status = $account->status;
                    @endphp
                    <x-card>
                        <div class="flex items-start justify-between gap-4" x-data="{ testOpen: false, testTo: '', testResult: null, testLoading: false }">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate font-medium text-gray-900">{{ $account->label }}</h3>
                                    @if ($account->is_default)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Default</span>
                                    @endif
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        bg-{{ $status->color() }}-100 text-{{ $status->color() }}-700">
                                        {{ $status->label() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $account->phone_number }}</p>
                                @if ($account->status_reason)
                                    <p class="mt-1 text-xs text-red-600">{{ $account->status_reason }}</p>
                                @endif
                                @if ($account->last_checked_at)
                                    <p class="mt-1 text-xs text-gray-400">Last checked: {{ $account->last_checked_at->diffForHumans() }}</p>
                                @endif
                            </div>

                            @can('update', $account)
                                <div class="flex shrink-0 items-center gap-2">
                                    @if (! $account->is_default && $account->isConnected())
                                        <form method="POST" action="{{ route('settings.whatsapp.default', $account) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 underline">
                                                Set default
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Test message --}}
                                    @if ($account->isConnected())
                                        <button @click="testOpen = ! testOpen" type="button"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                            Send test
                                        </button>
                                    @endif

                                    <a href="{{ route('settings.whatsapp.edit', $account) }}"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('settings.whatsapp.disconnect', $account) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Disconnect {{ addslashes($account->label) }}?')"
                                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Disconnect
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </div>

                        {{-- Test message form --}}
                        @can('sendTest', $account)
                            @if ($account->isConnected())
                            <div x-show="testOpen" x-cloak class="mt-4 border-t pt-4">
                                <div class="flex gap-2">
                                    <input
                                        type="tel"
                                        x-model="testTo"
                                        placeholder="+8801700000000"
                                        class="block flex-1 rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    />
                                    <button
                                        type="button"
                                        :disabled="testLoading || ! testTo"
                                        @click="
                                            testLoading = true;
                                            testResult = null;
                                            fetch('{{ route('settings.whatsapp.test', $account) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                },
                                                body: JSON.stringify({ to: testTo }),
                                            })
                                            .then(r => r.json())
                                            .then(data => { testResult = data; testLoading = false; })
                                            .catch(() => { testResult = { error: 'Request failed.' }; testLoading = false; });
                                        "
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                                    >
                                        <span x-show="! testLoading">Send</span>
                                        <span x-show="testLoading">Sending…</span>
                                    </button>
                                </div>
                                <div x-show="testResult !== null" class="mt-2">
                                    <p x-show="testResult?.message" class="text-sm text-emerald-600" x-text="testResult?.message"></p>
                                    <p x-show="testResult?.error" class="text-sm text-red-600" x-text="testResult?.error"></p>
                                </div>
                            </div>
                            @endif
                        @endcan
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
