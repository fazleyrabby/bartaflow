<x-guest-layout>
    <x-slot:title>Workspace Invitation</x-slot:title>

    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
            <svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">You've been invited!</h2>
        <p class="mt-2 text-sm text-gray-500">
            <strong>{{ $invitation->invitedBy->name }}</strong> has invited you to join
            <strong>{{ $invitation->workspace->name }}</strong> as a
            <strong>{{ $invitation->role->label() }}</strong>.
        </p>
        <p class="mt-1 text-xs text-gray-400">Invitation sent to {{ $invitation->email }} · Expires {{ $invitation->expires_at->format('d M Y') }}</p>
    </div>

    <div class="mt-6 space-y-3">
        @auth
            @if (strtolower(auth()->user()->email) === strtolower($invitation->email))
                <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
                    @csrf
                    @if ($errors->any())
                        <div class="mb-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <x-button type="submit" class="w-full">
                        Join {{ $invitation->workspace->name }}
                    </x-button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost" class="w-full">Sign out</x-button>
                </form>
            @else
                <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    You are signed in as <strong>{{ auth()->user()->email }}</strong>. This invitation was sent to
                    <strong>{{ $invitation->email }}</strong>. Please sign in with the correct account to accept.
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" class="w-full">Sign out and switch account</x-button>
                </form>
            @endif
        @else
            <a href="{{ route('login') }}?redirect={{ urlencode(url()->current()) }}" class="block">
                <x-button class="w-full">Sign in to accept</x-button>
            </a>
            <a href="{{ route('register') }}" class="block">
                <x-button variant="secondary" class="w-full">Create account</x-button>
            </a>
        @endauth
    </div>

    <p class="mt-4 text-center text-xs text-gray-400">
        If you did not expect this invitation, you can safely ignore this page.
    </p>
</x-guest-layout>
