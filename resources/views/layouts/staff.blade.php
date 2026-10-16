<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MenuVision · Personal')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans text-gray-100 antialiased">
    <header class="border-b border-gold-600/40 bg-black text-gray-100">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
            <div class="flex items-center gap-6">
                <a href="{{ route(auth()->user()->role->dashboardRoute()) }}" class="text-lg font-bold text-gold-400">
                    MenuVision
                </a>

                <nav class="hidden items-center gap-1 sm:flex">
                    @foreach ($navigation as [$route, $label, $icon])
                        <a
                            href="{{ route($route) }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                'bg-gold-500/10 text-gold-300' => request()->routeIs($route) || request()->routeIs($route.'.*'),
                                'text-gray-400 hover:bg-gray-900 hover:text-gray-200' => ! request()->routeIs($route) && ! request()->routeIs($route.'.*'),
                            ])
                        >
                            @include('partials.icon', ['icon' => $icon, 'iconClass' => 'h-4 w-4'])
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>

            @auth
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-gray-400 sm:inline">{{ auth()->user()->name }}</span>
                    <span class="rounded-full border border-gold-600/50 px-2 py-0.5 text-xs font-medium text-gold-300">
                        {{ auth()->user()->role->label() }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 text-sm text-gray-300 hover:text-gold-400">
                            @include('partials.icon', ['icon' => 'salir', 'iconClass' => 'h-4 w-4'])
                            <span class="hidden sm:inline">Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
