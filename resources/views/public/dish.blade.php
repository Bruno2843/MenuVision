@extends('layouts.app')

@section('title', $dish->nombre.' · '.config('app.name', 'MenuVision'))

@section('content')
    {{--
        Dish detail. Still read only: the page describes the dish and sends the
        client back to their mozo, who is the one who registers the order.
    --}}
    <nav aria-label="Volver" class="pt-6">
        <a
            href="{{ route('menu.show', $code) }}"
            class="inline-flex h-[48px] items-center gap-2 rounded-full border-2 border-amarillo-500 px-5 text-base font-semibold text-amarillo-500 transition hover:bg-amarillo-500 hover:text-noche-950 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amarillo-500"
        >
            <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13.5 8h-10"/>
                <path d="M7.5 4 3.5 8l4 4"/>
            </svg>
            Volver al menú
        </a>
    </nav>

    <article class="py-8">
        <div class="grid items-start gap-8 lg:grid-cols-2 lg:gap-12">
            {{-- The main visual. When the dish has a 3D model, that model is the
                 hero: `<model-viewer>` renders it and rotates on desktop, and
                 the same element is the one that starts the AR session, so the
                 file is downloaded once and never twice. Without a model the
                 photograph keeps the column. --}}
            <div class="relative">
                @if ($modelo3dUrl)
                    <model-viewer
                        data-ar-visor
                        src="{{ $modelo3dUrl }}"
                        poster="{{ asset(\App\Support\MenuImagen::paraDish($dish)) }}"
                        alt="Modelo 3D de {{ $dish->nombre }}"
                        loading="eager"
                        ar
                        ar-modes="webxr scene-viewer quick-look fallback"
                        ar-placement="floor"
                        ar-scale="auto"
                        camera-controls
                        auto-rotate
                        auto-rotate-delay="1500"
                        shadow-intensity="0.9"
                        exposure="1.1"
                        interaction-prompt="none"
                        class="block aspect-[16/10] w-full rounded-xl bg-noche-900 [--poster-color:transparent]"
                    ></model-viewer>
                @else
                    <img
                        src="{{ asset(\App\Support\MenuImagen::paraDish($dish)) }}"
                        data-imagen-respaldo="{{ asset(\App\Support\MenuImagen::respaldoParaDish($dish)) }}"
                        alt=""
                        width="1200"
                        height="675"
                        fetchpriority="high"
                        decoding="async"
                        class="aspect-[16/10] w-full rounded-xl object-cover"
                    >
                @endif
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false" class="pointer-events-none absolute -inset-2.5 h-[calc(100%+20px)] w-[calc(100%+20px)] text-amarillo-500">
                    <rect x="2" y="2" width="96" height="96" rx="9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <g transform="rotate(1.1 50 50)">
                        <rect x="4.6" y="4.4" width="96" height="96" rx="10" fill="none" stroke="currentColor" stroke-width="0.9" stroke-linejoin="round" opacity="0.55"/>
                    </g>
                </svg>
            </div>

            <div>
                <p class="inline-flex items-center gap-2 rounded-full border-2 border-amarillo-500 px-4 py-1.5 text-xs font-bold tracking-[0.14em] text-amarillo-500 uppercase">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <use href="#icono-{{ \App\Support\MenuConcepto::para($dish->category?->nombre) }}"></use>
                    </svg>
                    {{ $dish->category?->nombre }}
                </p>

                <h1 class="mt-4 text-4xl leading-tight font-extrabold tracking-tight sm:text-5xl">
                    {{ $dish->nombre }}
                </h1>

                <p class="mt-4 text-4xl font-extrabold tracking-tight tabular-nums text-amarillo-500">
                    ${{ number_format($dish->precio, 2) }}
                </p>

                <div class="mt-6 h-1 w-24 bg-amarillo-500" aria-hidden="true"></div>

                @if ($dish->descripcion)
                    <p class="mt-6 text-lg leading-relaxed text-humo-100">
                        {{ $dish->descripcion }}
                    </p>
                @endif

                {{-- Augmented reality. The button only exists for a dish whose
                     own model is on disk, so it never promises an experience it
                     cannot deliver. It sits right under the price, above the
                     ingredients, and well away from "Volver al menú".
                     `disabled` until the model finishes loading, because AR
                     needs the file in memory to place it. --}}
                <div class="mt-7">
                    @if ($modelo3dUrl)
                        <button
                            type="button"
                            data-ar-boton
                            disabled
                            class="flex h-[64px] w-full items-center justify-center gap-3 rounded-2xl bg-amarillo-500 text-xl font-extrabold tracking-tight text-noche-950 transition hover:bg-amarillo-400 disabled:cursor-progress disabled:bg-amarillo-600 disabled:text-noche-950/70 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amarillo-400"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-7 w-7 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <use href="#icono-ar"></use>
                            </svg>
                            Ver en mi espacio
                        </button>

                        {{-- The one live region for every AR state, so a screen
                             reader hears loading, failure and success without
                             the page ever being replaced by an alert. --}}
                        <p
                            data-ar-estado
                            role="status"
                            aria-live="polite"
                            class="mt-3 min-h-6 text-center text-sm font-medium text-humo-300"
                        >Cargando modelo 3D…</p>
                    @else
                        <p class="rounded-2xl border-2 border-amarillo-500/50 bg-noche-900 px-5 py-4 text-center text-base font-medium text-humo-100">
                            Este plato todavía no tiene un modelo 3D disponible.
                        </p>
                    @endif
                </div>

                @if ($dish->ingredients->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="text-xs font-bold tracking-[0.18em] text-amarillo-500 uppercase">Ingredientes</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($dish->ingredients as $ingredient)
                                <li class="rounded-full border-2 border-amarillo-500/60 bg-noche-900 px-3.5 py-1.5 text-[0.95rem] text-white">
                                    {{ $ingredient->nombre }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-10 rounded-2xl border-2 border-amarillo-500/60 bg-noche-900 p-5">
                    <p class="text-xl font-bold text-amarillo-500">¿Te deleita?</p>
                    <p class="mt-1.5 text-base leading-relaxed text-humo-100">
                        Contale a tu mozo que querés este plato y lo anota en la comanda.
                    </p>
                </div>
            </div>
        </div>
    </article>
@endsection