@extends('layouts.app')

@section('title', 'Menú · Mesa ' . $table->numero)

@section('content')
    <div class="mb-4 flex items-baseline gap-2">
        <span class="text-sm text-gray-400">Estás en la</span>
        <h1 class="text-xl font-bold text-gold-400">Mesa {{ $table->numero }}</h1>
    </div>

    @if ($categories->isNotEmpty())
        <nav
            data-category-nav
            aria-label="Categorías del menú"
            class="sticky top-0 z-10 -mx-4 mb-5 border-b border-gray-800 bg-gray-950/95 px-4 py-3 backdrop-blur"
        >
            <ul class="flex snap-x gap-2 overflow-x-auto pb-1">
                @foreach ($categories as $category)
                    <li class="shrink-0 snap-start">
                        <a
                            href="#categoria-{{ $category->id }}"
                            data-category-link
                            @class([
                                'block rounded-full border px-4 py-2 text-sm font-medium transition',
                                'border-gold-400 bg-gold-500/15 text-gold-200' => $loop->first,
                                'border-gray-800 text-gray-400 hover:border-gold-600/60 hover:text-gold-300' => ! $loop->first,
                            ])
                        >
                            {{ $category->nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @forelse ($categories as $category)
        <section id="categoria-{{ $category->id }}" class="mb-8 scroll-mt-20">
            <div class="mb-3 flex items-baseline justify-between gap-3 border-b border-gray-800/80 pb-2">
                <h2 class="border-l-4 border-gold-500 pl-3 text-xl font-semibold text-gray-50">
                    {{ $category->nombre }}
                </h2>

                <span class="shrink-0 text-xs text-gray-500">
                    {{ $category->dishes->count() }}
                    {{ $category->dishes->count() === 1 ? 'plato' : 'platos' }}
                </span>
            </div>

            @if ($category->descripcion)
                <p class="mb-3 pl-4 text-sm text-gray-500">{{ $category->descripcion }}</p>
            @endif

            <div class="grid gap-2.5">
                @foreach ($category->dishes as $dish)
                    <article class="rounded-xl border border-gray-800 bg-gray-900/60 p-4">
                        <div class="flex items-baseline justify-between gap-4">
                            <h3 class="font-semibold text-gray-100">{{ $dish->nombre }}</h3>
                            <span class="shrink-0 font-semibold tabular-nums text-gold-400">
                                ${{ number_format($dish->precio, 2) }}
                            </span>
                        </div>

                        @if ($dish->descripcion)
                            <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-gray-400">
                                {{ $dish->descripcion }}
                            </p>
                        @endif

                        @if ($dish->ingredients->isNotEmpty())
                            <ul class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($dish->ingredients as $ingredient)
                                    <li class="rounded-md bg-gray-800/70 px-1.5 py-0.5 text-xs text-gray-400">
                                        {{ $ingredient->nombre }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <p class="rounded-xl border border-gray-800 bg-gray-900 p-6 text-center text-gray-400">El menú aún no está disponible.</p>
    @endforelse
@endsection
