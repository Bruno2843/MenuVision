<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#050505">

    <title>@yield('title', config('app.name', 'MenuVision'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-noche-950 font-marca text-white antialiased">
    <a
        href="#contenido"
        class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-amarillo-500 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-noche-950"
    >
        Ir al contenido
    </a>

    <header class="border-b-2 border-amarillo-500/30 bg-noche-950">
        <div class="mx-auto flex w-[94%] max-w-[1800px] items-center gap-4 px-4 py-4 sm:gap-5 sm:px-6 sm:py-5">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-11 w-11 shrink-0 text-amarillo-500 sm:h-13 sm:w-13">
                <use href="#icono-gorro"></use>
            </svg>

            <div class="min-w-0 flex-1">
                <p class="text-2xl leading-none font-extrabold tracking-tight sm:text-[2rem]">
                    Menu<span class="text-amarillo-500">Vision</span>
                </p>
                <p class="mt-1.5 text-xs font-medium text-humo-100 sm:text-sm">
                    Buen sabor, en cada detalle
                </p>
            </div>

            @hasSection('mesa-badge')
                @yield('mesa-badge')
            @endif
        </div>
    </header>

    <main id="contenido" class="mx-auto w-[94%] max-w-[1800px] px-4 pb-20 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t-2 border-amarillo-500/25 bg-noche-950">
        <div class="mx-auto flex w-[94%] max-w-[1800px] flex-col items-center gap-2 px-4 py-9 text-center sm:px-6">
            <p class="text-lg font-extrabold tracking-tight">
                Menu<span class="text-amarillo-500">Vision</span>
            </p>
            <p class="text-sm text-humo-300">Buen sabor, en cada detalle</p>
        </div>
    </footer>

    @include('public.partials.decorations')
    @include('public.partials.icons')
</body>
</html>