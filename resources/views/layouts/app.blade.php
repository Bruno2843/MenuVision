<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'MenuVision'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">MenuVision</a>
            <span class="text-sm text-gray-500">Menú digital</span>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
