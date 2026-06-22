<x-guest-layout>
    <x-slot:title>Sign in</x-slot:title>

    <h2 class="mb-8 text-center text-xl font-semibold text-gray-900">Sign in to BartaFlow</h2>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-base text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false, email: '', password: '' }" @submit="loading = true">
        @csrf

        <div class="space-y-6">
            <x-form.input
                name="email"
                label="Email address"
                type="email"
                :value="old('email')"
                placeholder="you@example.com"
                autocomplete="email"
                x-model="email"
                required
            />

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-base font-medium text-gray-700">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm text-emerald-600 hover:underline">Forgot password?</a>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    x-model="password"
                    required
                    class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 {{ $errors->has('password') ? 'border-red-400' : '' }}"
                >
                @error('password')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="remember" class="size-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-base text-gray-600">Remember me</span>
            </label>
        </div>

        <x-button type="submit" class="mt-6 w-full" :disabled="$errors->any()" x-bind:disabled="loading">
            <span x-show="!loading">Sign in</span>
            <span x-show="loading" x-cloak>Signing in…</span>
        </x-button>

        <button
            type="button"
            class="mt-3 flex w-full justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-5 py-3 text-base font-medium text-emerald-700 hover:bg-emerald-100"
            @click="
                email = 'admin@email.com';
                password = 'password';
                $nextTick(() => $root.querySelector('[type=submit]').click())
            "
        >
            Demo Login
        </button>
    </form>

    <p class="mt-6 text-center text-base text-gray-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-medium text-emerald-600 hover:underline">Sign up free</a>
    </p>
</x-guest-layout>
