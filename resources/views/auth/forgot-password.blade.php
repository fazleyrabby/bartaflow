<x-guest-layout>
    <x-slot:title>Reset your password</x-slot:title>

    <h2 class="mb-2 text-center text-lg font-semibold text-gray-900">Forgot your password?</h2>
    <p class="mb-6 text-center text-sm text-gray-500">
        Enter your email and we'll send a reset link if an account exists.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <x-form.input
            name="email"
            label="Email address"
            type="email"
            :value="old('email')"
            placeholder="you@example.com"
            autocomplete="email"
            required
        />

        <x-button type="submit" class="mt-6 w-full">Send reset link</x-button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:underline">Back to sign in</a>
    </p>
</x-guest-layout>
