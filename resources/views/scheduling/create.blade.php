<x-app-layout>
    <x-slot:title>Schedule Message</x-slot:title>
    <x-slot:header>Schedule Message</x-slot:header>
    <x-slot:subheader>Pick a template, recipients, and a future time to send.</x-slot:subheader>

    @if ($accounts->isEmpty())
        <x-empty-state title="Connect a WhatsApp account first" message="You need a connected account before scheduling messages.">
            <x-slot:actions><x-button :href="route('settings.whatsapp')">WhatsApp settings</x-button></x-slot:actions>
        </x-empty-state>
    @elseif ($templates->isEmpty())
        <x-empty-state title="Create a template first" message="Scheduled messages are sent from reusable templates.">
            <x-slot:actions><x-button :href="route('templates.create')">New template</x-button></x-slot:actions>
        </x-empty-state>
    @else
        @include('scheduling._form')
    @endif
</x-app-layout>
