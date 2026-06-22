<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased">
    <div class="flex flex-col items-center justify-center px-6 mx-auto md:h-screen">
        <a href="{{ route('home') }}" class="flex items-center mb-6 text-2xl font-semibold text-emerald-600">BartaFlow</a>

        <main class="w-full bg-white rounded-lg shadow md:mt-0 sm:max-w-md xl:p-0">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                {{ $slot }}
            </div>
        </main>

        <p class="mt-6 text-sm text-gray-500">Automated WhatsApp Notifications for Modern Businesses</p>
    </div>

    <x-toast />
</body>
</html>
