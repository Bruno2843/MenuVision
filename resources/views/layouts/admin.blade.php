<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MenuVision · Administración')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 font-sans text-gray-100 antialiased">
    {{--
        On a phone the sidebar is a drawer that slides over the content. The
        overlay and the drawer share the `hidden` class, toggled by
        `resources/js/admin-sidebar.js`, so the panel is fully usable on a
        screen too small for a permanent sidebar.
    --}}
    <div
        id="admin-sidebar-overlay"
        data-sidebar-overlay
        class="fixed inset-0 z-30 hidden bg-black/70 lg:hidden"
        aria-hidden="true"
    ></div>

    <aside
        id="admin-sidebar"
        data-sidebar
        aria-label="Navegación de administración"
        class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-gray-800 bg-gray-900 transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex items-center justify-between gap-2 border-b border-gray-800 px-5 py-4">
            <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-gold-400">
                MenuVision
            </a>

            <button
                type="button"
                data-sidebar-cerrar
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-800 hover:text-gray-100 lg:hidden"
            >
                @include('partials.icon', ['icon' => 'cerrar', 'iconClass' => 'h-5 w-5'])
                <span class="sr-only">Cerrar el menú</span>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach ($navigation as [$route, $label, $icon])
                @php($activo = request()->routeIs($route) || request()->routeIs($route.'.*'))

                <a
                    href="{{ route($route) }}"
                    @if ($activo) aria-current="page" @endif
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-gold-500/10 text-gold-300' => $activo,
                        'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => ! $activo,
                    ])
                >
                    @include('partials.icon', ['icon' => $icon, 'iconClass' => 'h-5 w-5 shrink-0'])
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-gray-800 px-5 py-4">
            <p class="truncate text-sm font-medium text-gray-200">{{ auth()->user()->name }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ auth()->user()->email }}</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gold-400">
                    @include('partials.icon', ['icon' => 'salir', 'iconClass' => 'h-4 w-4'])
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-gray-800 bg-gray-950/95 backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <button
                    type="button"
                    data-sidebar-abrir
                    class="rounded-lg p-2 text-gray-300 hover:bg-gray-800"
                >
                    @include('partials.icon', ['icon' => 'menu', 'iconClass' => 'h-5 w-5'])
                    <span class="sr-only">Abrir el menú</span>
                </button>

                <span class="text-base font-bold text-gold-400">MenuVision</span>

                <span class="rounded-full border border-gold-600/50 px-2 py-0.5 text-xs font-medium text-gold-300">
                    {{ auth()->user()->role->label() }}
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
            @yield('content')
        </main>
    </div>
</body>
</html>
