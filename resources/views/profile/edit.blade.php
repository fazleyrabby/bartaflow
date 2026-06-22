<x-app-layout>
    <x-slot:title>Profile</x-slot:title>
    <x-slot:header>Profile</x-slot:header>
    <x-slot:subheader>Manage your personal information and password.</x-slot:subheader>

    <div class="max-w-2xl space-y-6">

        {{-- ── Profile Information ── --}}
        <x-card title="Personal information">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <x-form.input
                        name="name"
                        label="Full name"
                        :value="$user->name"
                        required
                    />

                    <div class="space-y-1">
                        <x-form.input
                            name="email"
                            label="Email address"
                            type="email"
                            :value="$user->email"
                            required
                        />
                        @if (! $user->hasVerifiedEmail())
                            <p class="flex items-center gap-1 text-xs text-amber-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z"/></svg>
                                Unverified — check your email or
                                <a href="{{ route('verification.notice') }}" class="underline">resend link</a>.
                            </p>
                        @endif
                    </div>

                    <x-form.input
                        name="phone"
                        label="Phone (optional)"
                        type="tel"
                        :value="$user->phone"
                        placeholder="+8801XXXXXXXXX"
                    />
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-button type="submit">Save changes</x-button>
                    @if (session('status') === 'Profile updated successfully.')
                        <span class="text-sm text-emerald-600">Saved!</span>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- ── Change Password ── --}}
        <x-card title="Change password">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PATCH')

                <div class="space-y-4" x-data="passwordStrength()">
                    <x-form.input
                        name="current_password"
                        label="Current password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />

                    <div class="space-y-1">
                        <x-form.input
                            name="password"
                            label="New password"
                            type="password"
                            x-model="password"
                            @input="score()"
                            autocomplete="new-password"
                            placeholder="Min 8 characters"
                            required
                        />
                        <div x-show="password.length > 0" class="mt-1 space-y-1" x-cloak>
                            <div class="flex gap-1">
                                <template x-for="n in 4">
                                    <div class="h-1.5 flex-1 rounded-full transition-colors"
                                        :class="n <= strength ? strengthColor() : 'bg-gray-200'"></div>
                                </template>
                            </div>
                            <p class="text-xs" :class="strengthTextColor()" x-text="strengthLabel()"></p>
                        </div>
                    </div>

                    <x-form.input
                        name="password_confirmation"
                        label="Confirm new password"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-button type="submit">Update password</x-button>
                    @if (session('status') === 'Password updated successfully.')
                        <span class="text-sm text-emerald-600">Updated!</span>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- ── 2FA placeholder ── --}}
        <x-card title="Two-factor authentication">
            <p class="text-sm text-gray-500">Two-factor authentication will be available in a future update.</p>
        </x-card>

    </div>
</x-app-layout>

<script>
function passwordStrength() {
    return {
        password: '',
        strength: 0,
        score() {
            let s = 0;
            if (this.password.length >= 8) s++;
            if (/[A-Z]/.test(this.password)) s++;
            if (/[0-9]/.test(this.password)) s++;
            if (/[^A-Za-z0-9]/.test(this.password)) s++;
            this.strength = s;
        },
        strengthLabel() { return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength] ?? ''; },
        strengthColor() { return [null,'bg-red-400','bg-amber-400','bg-blue-400','bg-emerald-500'][this.strength]; },
        strengthTextColor() { return [null,'text-red-600','text-amber-600','text-blue-600','text-emerald-600'][this.strength]; },
    };
}
</script>
