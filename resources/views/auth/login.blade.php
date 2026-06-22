<x-guest-layout>
    <x-slot:title>Sign in</x-slot:title>

    <h2 class="text-xl font-semibold text-gray-900">Sign in to BartaFlow</h2>

    @if (session('status'))
        <div class="flex items-center p-4 text-sm text-green-800 rounded-lg bg-green-50">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false, email: '', password: '' }" @submit="loading = true">
        @csrf

        <div class="space-y-4">
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

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-sm font-medium text-gray-900">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-600 hover:underline">Forgot password?</a>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    x-model="password"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 {{ $errors->has('password') ? 'border-red-500 bg-red-50' : '' }}"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500">
                <span class="text-sm font-medium text-gray-900">Remember me</span>
            </label>
        </div>

        <x-button type="submit" class="mt-6 w-full" :disabled="$errors->any()" x-bind:disabled="loading">
            <span x-show="!loading">Sign in</span>
            <span x-show="loading" x-cloak>Signing in…</span>
        </x-button>

        <button
            type="button"
            class="mt-3 w-full text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            @click="
                email = 'admin@email.com';
                password = 'password';
                $nextTick(() => $root.querySelector('[type=submit]').click())
            "
        >
            Demo Login
        </button>
    </form>

    <p class="text-sm font-light text-gray-500">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-medium text-emerald-600 hover:underline">Sign up free</a>
    </p>
</x-guest-layout>
