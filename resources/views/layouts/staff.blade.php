<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MenuVision · Personal')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans text-gray-900 antialiased">
    <header class="bg-gray-900 text-gray-100">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <span class="text-lg font-bold">MenuVision</span>
            @auth
                <span class="text-sm text-gray-400">{{ auth()->user()->name }}</span>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @yield('content')
    </main>
</body>
</html>