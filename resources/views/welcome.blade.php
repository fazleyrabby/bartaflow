<x-guest-layout>
    <x-slot:title>BartaFlow</x-slot:title>

    <div class="text-center">
        <h1 class="text-xl font-semibold text-gray-900">Welcome to BartaFlow</h1>
        <p class="mt-2 text-sm text-gray-500">
            Automated WhatsApp notifications for modern businesses.
            Authentication arrives in the next task.
        </p>

        <div class="mt-6 flex justify-center gap-2">
            <x-button :href="route('dashboard')">View dashboard shell</x-button>
            <x-button variant="secondary" :href="route('health')">Health check</x-button>
        </div>
    </div>
</x-guest-layout>
