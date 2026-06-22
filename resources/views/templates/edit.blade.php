<x-app-layout>
    <x-slot:title>Edit Template</x-slot:title>
    <x-slot:header>Edit Template</x-slot:header>
    <x-slot:subheader>Update “{{ $template->name }}” and preview your changes live.</x-slot:subheader>

    @include('templates._form')
</x-app-layout>
