@extends('layouts.staff')

@section('title', 'Mozo · Carta')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Carta</h1>
        <p class="text-sm text-cocina-gris">
            Menú agrupado por categoría. Los platos agotados aparecen tachados.
        </p>
    </div>

    <nav aria-label="Categorías de la carta" class="mb-6">
        <ul class="flex gap-2 overflow-x-auto pb-1">
            @foreach ($categories as $category)
                <li class="shrink-0">
                    <a
                        href="#categoria-{{ $category->id }}"
                        class="inline-flex items-center rounded-full border-2 border-cocina-amarillo px-3 py-1 text-xs font-medium text-cocina-amarillo transition hover:bg-cocina-amarillo/10"
                    >
                        {{ $category->nombre }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @forelse ($categories as $category)
        <section id="categoria-{{ $category->id }}" class="mb-8 scroll-mt-20">
            <h2 class="mb-1 border-l-2 border-cocina-amarillo pl-3 text-xl font-semibold text-white">
                {{ $category->nombre }}
            </h2>

            @if ($category->descripcion)
                <p class="mb-3 pl-4 text-sm text-cocina-gris">{{ $category->descripcion }}</p>
            @endif

            <div class="overflow-hidden rounded-xl border-2 border-cocina-amarillo bg-cocina-panel">
                <table class="w-full text-left text-sm">
                    <thead class="bg-cocina-fondo text-xs uppercase tracking-wide text-cocina-gris">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                            <th scope="col" class="px-4 py-2 font-medium">Ingredientes</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-cocina-amarillo/15">
                        @foreach ($category->dishes as $dish)
                            <tr @class(['bg-cocina-fondo/60' => ! $dish->disponible])>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'font-medium text-white',
                                        'text-cocina-gris line-through' => ! $dish->disponible,
                                    ])>
                                        {{ $dish->nombre }}
                                    </span>

                                    @if (! $dish->disponible)
                                        <span class="ml-2 inline-flex items-center rounded-full border-2 border-red-500/50 bg-red-950 px-3 py-1 text-xs text-red-300">
                                            Agotado
                                        </span>
                                    @endif

                                    @if ($dish->descripcion)
                                        <p class="mt-0.5 text-xs text-cocina-gris">{{ $dish->descripcion }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-cocina-gris">
                                    {{ $dish->ingredients->isNotEmpty()
                                        ? $dish->ingredients->pluck('nombre')->implode(', ')
                                        : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-cocina-amarillo">
                                    ${{ number_format($dish->precio, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-8 text-center text-cocina-gris">
            Todavía no hay platos cargados.
        </p>
    @endforelse
@endsection
