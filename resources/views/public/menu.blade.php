@extends('layouts.app')

@section('title', config('app.name', 'MenuVision'))

@if (isset($mesa))
    @section('mesa-badge')
        <div class="ml-auto shrink-0 flex items-center gap-2 rounded-xl border-2 border-amarillo-500 bg-amarillo-500 px-3 py-2 text-noche-950">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-5 w-5 shrink-0">
                <use href="#icono-mesa"></use>
            </svg>
            <span class="text-sm font-extrabold tracking-wide whitespace-nowrap">
                Mesa {{ str_pad($mesa, 2, '0', STR_PAD_LEFT) }}
            </span>
        </div>
    @endsection
@endif

@section('content')
    @php
        $todos = $categories->flatMap(fn ($category) => $category->dishes);
    @endphp

    {{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
    <section class="relative mt-4 overflow-hidden rounded-3xl border-2 border-amarillo-500/40 bg-noche-950">

        {{-- decorative sparkle --}}
        <svg viewBox="0 0 36 36" aria-hidden="true" focusable="false"
             class="pointer-events-none absolute top-4 right-3 h-8 w-8 text-amarillo-500 opacity-80 lg:right-5 lg:top-5">
            <use href="#deco-brillo"></use>
        </svg>

        {{-- decorative branch bottom-left --}}
        <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false"
             class="pointer-events-none absolute -bottom-2 -left-4 h-24 w-24 text-amarillo-500 opacity-60 sm:-left-5">
            <use href="#deco-rama"></use>
        </svg>

        <div class="grid items-stretch lg:grid-cols-2">
            {{-- Left: text --}}
            <div class="relative flex flex-col justify-center px-6 py-8 sm:px-8 lg:min-h-[340px] lg:py-10">

                <p class="flex items-center gap-3 text-[0.65rem] font-bold tracking-[0.24em] text-amarillo-500 uppercase">
                    <span class="h-px w-7 bg-amarillo-500" aria-hidden="true"></span>
                    Bienvenido
                </p>

                <h1 class="mt-4 text-4xl leading-[0.96] font-extrabold tracking-tight sm:text-5xl xl:text-6xl">
                    Descubrí<br>nuestro <em class="not-italic text-amarillo-500">menú</em>
                </h1>

                <p class="mt-4 max-w-xs text-base leading-relaxed text-humo-100 sm:text-lg">
                    Platos frescos, sabores únicos<br>
                    y una experiencia diferente.
                </p>

                {{-- hand-drawn underline --}}
                <svg viewBox="0 0 100 32" aria-hidden="true" focusable="false"
                     class="mt-5 h-6 w-36 text-amarillo-500">
                    <use href="#deco-trazo"></use>
                </svg>
            </div>

            {{-- Right: hero photo --}}
            <div class="relative flex items-center justify-center px-4 pb-8 lg:px-4 lg:py-8">
                <div class="relative w-full max-w-[560px]">
                    <img
                        src="{{ asset(\App\Support\MenuImagen::hero()) }}"
                        alt=""
                        width="1600"
                        height="900"
                        fetchpriority="high"
                        decoding="async"
                        class="aspect-[16/10] w-full rounded-2xl object-cover"
                    >
                    {{-- double yellow frame --}}
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false"
                         class="pointer-events-none absolute -inset-2.5 h-[calc(100%+20px)] w-[calc(100%+20px)] text-amarillo-500">
                        <rect x="2" y="2" width="96" height="96" rx="9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <g transform="rotate(1.2 50 50)">
                            <rect x="4.5" y="4" width="96" height="96" rx="10" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round" opacity="0.5"/>
                        </g>
                    </svg>
                    <svg viewBox="0 0 48 16" aria-hidden="true" focusable="false"
                         class="pointer-events-none absolute -bottom-1 left-5 h-3.5 w-20 text-amarillo-500">
                        <use href="#deco-puntos"></use>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        {{-- ── Category nav ─────────────────────────────────────────────────── --}}
        <nav data-category-nav aria-label="Categorías del menú" class="mt-7">
            <ul class="flex snap-x gap-x-3 overflow-x-auto pb-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

                {{-- "Todas" --}}
                <li class="shrink-0 snap-start">
                    <button
                        type="button"
                        data-category-all
                        aria-pressed="true"
                        class="flex h-[90px] w-[84px] flex-col items-center justify-center gap-2 rounded-2xl border-2 border-amarillo-500 bg-amarillo-500 text-noche-950 transition hover:bg-amarillo-400 sm:h-[96px] sm:w-[90px]"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-7 w-7">
                            <use href="#icono-todas"></use>
                        </svg>
                        <span class="text-xs leading-tight font-bold">Todas</span>
                    </button>
                </li>

                @foreach ($categories as $category)
                    <li class="shrink-0 snap-start">
                        <button
                            type="button"
                            data-category-link
                            data-categoria="{{ $category->id }}"
                            aria-pressed="false"
                            class="flex h-[90px] w-[84px] flex-col items-center justify-center gap-2 rounded-2xl border-2 border-amarillo-500 bg-noche-950 text-white transition hover:bg-amarillo-500 hover:text-noche-950 hover:*:text-noche-950 aria-pressed:bg-amarillo-500 aria-pressed:text-noche-950 aria-pressed:*:text-noche-950 sm:h-[96px] sm:w-[90px] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amarillo-500"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-7 w-7 text-amarillo-500 transition">
                                <use href="#icono-{{ \App\Support\MenuConcepto::para($category->nombre) }}"></use>
                            </svg>
                            <span class="text-xs leading-tight font-semibold text-center px-1">{{ $category->nombre }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- ── Featured dishes ──────────────────────────────────────────────── --}}
        <section class="mt-10">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Platos destacados</h2>

                <a
                    href="#platos"
                    data-reset-filtro
                    class="group flex shrink-0 items-center gap-1.5 text-base font-bold text-amarillo-500 transition hover:text-amarillo-400 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amarillo-500"
                >
                    Ver todo
                    <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"
                         class="h-4 w-4 transition group-hover:translate-x-0.5"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 8h10"/>
                        <path d="M9 4l4 4-4 4"/>
                    </svg>
                </a>
            </div>

            <p data-platos-count class="mt-1 text-sm font-medium text-humo-300">
                {{ $todos->count() }} {{ $todos->count() === 1 ? 'plato' : 'platos' }} disponibles
            </p>

            <div id="platos" class="mt-6 grid gap-5 sm:gap-6 md:grid-cols-2">
                @foreach ($todos as $dish)
                    @include('public.partials.dish-card', [
                        'dish' => $dish,
                        'code' => $code,
                    ])
                @endforeach
            </div>

            <p data-filtro-vacio class="mt-8 hidden rounded-2xl border-2 border-amarillo-500/40 bg-noche-900 px-6 py-10 text-center text-humo-300">
                De momento no hay platos en esta sección.
            </p>
        </section>

    @else
        <div class="mt-10 rounded-2xl border-2 border-amarillo-500/40 bg-noche-900 px-6 py-16 text-center">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="mx-auto h-12 w-12 text-amarillo-500" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <use href="#icono-plato"></use>
            </svg>
            <h2 class="mt-4 text-2xl font-extrabold">El menú aún no está disponible</h2>
            <p class="mt-2 text-humo-300">Estamos actualizando la carta. Preguntale a tu mozo por los platos del día.</p>
        </div>
    @endif
@endsection
