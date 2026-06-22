<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="mb-6 text-2xl font-bold text-emerald-600">BartaFlow</a>

        <main class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-10 shadow-sm">
            {{ $slot }}
        </main>

        <p class="mt-6 text-sm text-gray-500">Automated WhatsApp Notifications for Modern Businesses</p>
    </div>

    <x-toast />
</body>
</html>
