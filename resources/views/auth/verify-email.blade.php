<x-guest-layout>
    <x-slot:title>Verify your email</x-slot:title>

    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
            <svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">Check your email</h2>
        <p class="mt-2 text-sm text-gray-500">
            We've sent a verification link to your email address.
            Click the link to verify and start using BartaFlow.
        </p>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6" x-data="{ sent: false }" @submit.prevent="sent = true; $el.submit()">
        @csrf
        <x-button type="submit" variant="secondary" class="w-full" x-bind:disabled="sent">
            <span x-show="!sent">Resend verification email</span>
            <span x-show="sent" x-cloak>Sent! Check your inbox.</span>
        </x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-button type="submit" variant="ghost" class="w-full">Sign out</x-button>
    </form>
</x-guest-layout>
