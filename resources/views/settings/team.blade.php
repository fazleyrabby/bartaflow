<x-app-layout>
    <x-slot:title>Team</x-slot:title>
    <x-slot:header>Team</x-slot:header>
    <x-slot:subheader>Manage workspace members and invitations.</x-slot:subheader>

    <x-slot:actions>
        @can('invite', [\App\Models\WorkspaceUser::class, $workspace])
        <x-button
            type="button"
            x-data
            @click="$dispatch('open-modal', 'invite-member')"
        >
            Invite member
        </x-button>
        @endcan
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="space-y-6">

        {{-- ── Members table ── --}}
        <x-card title="Members">
            @if ($members->isEmpty())
                <x-empty-state title="No members" message="Invite your team to collaborate." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="pb-2 pr-4">Member</th>
                                <th class="pb-2 pr-4">Role</th>
                                <th class="pb-2 pr-4">Joined</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($members as $membership)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-medium text-gray-900">{{ $membership->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $membership->user->email }}</div>
                                    </td>
                                    <td class="py-3 pr-4">
                                        @if ($membership->role === \App\Enums\Role::Owner)
                                            <x-badge :status="$membership->role" />
                                        @elsecan('updateRole', [$workspace, $membership])
                                            <form method="POST" action="{{ route('settings.team.role', $membership) }}">
                                                @csrf
                                                @method('PATCH')
                                                <select
                                                    name="role"
                                                    onchange="this.form.submit()"
                                                    class="rounded border-gray-300 py-1 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                >
                                                    @foreach ($assignableRoles as $role)
                                                        <option value="{{ $role->value }}" @selected($membership->role === $role)>{{ $role->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <x-badge :status="$membership->role" />
                                        @endcan
                                    </td>
                                    <td class="py-3 pr-4 text-gray-500">
                                        {{ $membership->joined_at?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if ($membership->user_id !== auth()->id())
                                            @can('remove', [$workspace, $membership])
                                                <form method="POST" action="{{ route('settings.team.remove', $membership) }}" onsubmit="return confirm('Remove {{ $membership->user->name }} from this workspace?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button type="submit" variant="ghost" class="text-red-600 hover:text-red-700">Remove</x-button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        {{-- ── Pending Invitations ── --}}
        @can('invite', [\App\Models\WorkspaceUser::class, $workspace])
        <x-card title="Pending Invitations">
            @if ($invitations->isEmpty())
                <p class="text-sm text-gray-400 italic">No pending invitations.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="pb-2 pr-4">Email</th>
                                <th class="pb-2 pr-4">Role</th>
                                <th class="pb-2 pr-4">Invited by</th>
                                <th class="pb-2 pr-4">Expires</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($invitations as $invitation)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $invitation->email }}</td>
                                    <td class="py-3 pr-4"><x-badge :status="$invitation->role" /></td>
                                    <td class="py-3 pr-4 text-gray-500">{{ $invitation->invitedBy->name }}</td>
                                    <td class="py-3 pr-4 text-gray-500">{{ $invitation->expires_at->format('d M Y') }}</td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2 justify-end">
                                            <form method="POST" action="{{ route('settings.invitations.resend', $invitation) }}">
                                                @csrf
                                                <x-button type="submit" variant="ghost" class="text-xs">Resend</x-button>
                                            </form>
                                            <form method="POST" action="{{ route('settings.invitations.revoke', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="ghost" class="text-xs text-red-600">Revoke</x-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
        @endcan

    </div>

    {{-- ── Invite Member Modal ── --}}
    @can('invite', [\App\Models\WorkspaceUser::class, $workspace])
    <x-modal name="invite-member" title="Invite a team member">
        <form method="POST" action="{{ route('settings.team.invite') }}" class="space-y-4">
            @csrf
            <x-form.input
                name="email"
                label="Email address"
                type="email"
                :value="old('email')"
                placeholder="colleague@example.com"
                required
            />
            <x-form.select name="role" label="Role">
                @foreach ($assignableRoles as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </x-form.select>
            @error('email')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <div class="flex gap-3 pt-2">
                <x-button type="submit">Send invitation</x-button>
                <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'invite-member')">Cancel</x-button>
            </div>
        </form>
    </x-modal>
    @endcan

</x-app-layout>
