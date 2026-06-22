<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>
    <x-slot:header>Dashboard</x-slot:header>
    <x-slot:subheader>Foundation shell — KPIs and onboarding arrive in task 010.</x-slot:subheader>

    <x-slot:actions>
        <x-button variant="secondary" :href="route('health')">Health</x-button>
        <x-button>New message</x-button>
    </x-slot:actions>

    {{-- KPI placeholders to prove the grid + components render --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (['Sent today' => '0', 'Scheduled' => '0', 'Failed (24h)' => '0', 'Contacts' => '0', 'Templates' => '0'] as $label => $value)
            <x-card>
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</p>
            </x-card>
        @endforeach

        <x-card>
            <p class="text-sm text-gray-500">WhatsApp account</p>
            <p class="mt-2"><x-badge :status="\App\Enums\AccountStatus::Pending" /></p>
        </x-card>
    </div>

    <div class="mt-6">
        <x-empty-state
            title="No activity yet"
            message="Once authentication and messaging ship, your recent sends and onboarding checklist appear here."
        >
            <x-slot:actions>
                <x-button>Get started</x-button>
            </x-slot:actions>
        </x-empty-state>
    </div>
</x-app-layout>
