<x-guest-layout>
    <x-slot:title>Set new password</x-slot:title>

    <h2 class="mb-6 text-center text-lg font-semibold text-gray-900">Set a new password</h2>

    <form method="POST" action="{{ route('password.update') }}" x-data="passwordStrength()">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="space-y-4">
            <x-form.input
                name="email"
                label="Email address"
                type="email"
                :value="old('email', request('email'))"
                placeholder="you@example.com"
                autocomplete="email"
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
                placeholder="Repeat password"
                required
            />
        </div>

        <x-button type="submit" class="mt-6 w-full">Reset password</x-button>
    </form>
</x-guest-layout>

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
