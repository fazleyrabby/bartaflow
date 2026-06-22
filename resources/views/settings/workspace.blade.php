<x-app-layout>
    <x-slot:title>Workspace Settings</x-slot:title>
    <x-slot:header>Workspace Settings</x-slot:header>
    <x-slot:subheader>Manage your workspace name, timezone, and business identity.</x-slot:subheader>

    <div class="space-y-6">

        {{-- ── General Settings ── --}}
        <x-card title="General">
            <form method="POST" action="{{ route('settings.workspace.update') }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input
                        name="name"
                        label="Workspace name"
                        :value="$workspace->name"
                        required
                    />

                    <div class="space-y-1">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <select
                            id="timezone"
                            name="timezone"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 {{ $errors->has('timezone') ? 'border-red-400' : '' }}"
                        >
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $workspace->timezone) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <x-form.input
                            name="business_name"
                            label="Business name"
                            :value="$workspace->businessName()"
                            placeholder="{{ $workspace->name }}"
                        />
                        <p class="mt-1 text-xs text-gray-500">Used as a variable in message templates.</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-button type="submit">Save changes</x-button>
                    @if (session('status'))
                        <p class="text-sm text-emerald-600">{{ session('status') }}</p>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- ── Workspace Info ── --}}
        <x-card title="Workspace Info">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Slug</dt>
                    <dd class="font-mono text-gray-900">{{ $workspace->slug }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd><x-badge :status="$workspace->status" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-gray-900">{{ $workspace->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- ── Ownership Transfer ── --}}
        @can('transferOwnership', $workspace)
        <x-card title="Transfer Ownership">
            <p class="mb-4 text-sm text-gray-600">
                Transfer ownership to another member of this workspace. You will be downgraded to Admin.
            </p>

            @php
                $otherMembers = $workspace->users()
                    ->wherePivot('status', 'active')
                    ->where('users.id', '!=', auth()->id())
                    ->select(['users.id', 'users.name', 'users.email'])
                    ->get();
            @endphp

            @if ($otherMembers->isEmpty())
                <p class="text-sm text-gray-400 italic">No other members to transfer to.</p>
            @else
                <div x-data="{ open: false, name: '' }" class="space-y-3">
                    <x-button type="button" variant="secondary" @click="open = true">Transfer ownership…</x-button>

                    <div x-show="open" x-transition style="display:none" class="rounded-lg border border-amber-200 bg-amber-50 p-4 space-y-3">
                        <p class="text-sm font-medium text-amber-800">Select the new owner:</p>
                        <form method="POST" action="{{ route('settings.workspace.transfer') }}">
                            @csrf
                            <x-form.select name="user_id" label="New owner">
                                @foreach ($otherMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                                @endforeach
                            </x-form.select>
                            <div class="mt-3 flex gap-2">
                                <x-button type="submit" variant="danger">Transfer</x-button>
                                <x-button type="button" variant="ghost" @click="open = false">Cancel</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </x-card>
        @endcan

        {{-- ── Danger Zone ── --}}
        @can('delete', $workspace)
        <x-card>
            <div class="rounded-lg border border-red-200 p-4">
                <h3 class="text-sm font-semibold text-red-700">Delete workspace</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Permanently delete <strong>{{ $workspace->name }}</strong>. This action cannot be undone.
                    All data will be removed after the retention period.
                </p>

                <div x-data="{ open: false, confirmed: '' }" class="mt-4">
                    <x-button type="button" variant="danger" @click="open = true">Delete workspace</x-button>

                    <div x-show="open" x-transition style="display:none" class="mt-4 space-y-3 rounded-lg border border-red-200 bg-red-50 p-4">
                        <p class="text-sm text-red-700">
                            Type <strong>{{ $workspace->name }}</strong> to confirm deletion:
                        </p>
                        <form method="POST" action="{{ route('settings.workspace.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <input
                                type="text"
                                name="confirm_name"
                                x-model="confirmed"
                                placeholder="{{ $workspace->name }}"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                            >
                            <div class="mt-3 flex gap-2">
                                <x-button
                                    type="submit"
                                    variant="danger"
                                    x-bind:disabled="confirmed !== '{{ $workspace->name }}'"
                                >
                                    Delete permanently
                                </x-button>
                                <x-button type="button" variant="ghost" @click="open = false; confirmed = ''">Cancel</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </x-card>
        @endcan

    </div>
</x-app-layout>
