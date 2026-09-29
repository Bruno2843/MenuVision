@extends('layouts.staff')

@section('title', 'Mozo · Carta')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Carta</h1>
        <p class="text-sm text-gray-400">
            Menú agrupado por categoría. Los platos agotados aparecen tachados.
        </p>
    </div>

    <nav aria-label="Categorías de la carta" class="mb-6">
        <ul class="flex gap-2 overflow-x-auto pb-1">
            @foreach ($categories as $category)
                <li class="shrink-0">
                    <a
                        href="#categoria-{{ $category->id }}"
                        class="block rounded-full border border-gold-600/60 px-4 py-2 text-sm font-medium text-gold-300 transition hover:bg-gold-500/10"
                    >
                        {{ $category->nombre }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @forelse ($categories as $category)
        <section id="categoria-{{ $category->id }}" class="mb-8 scroll-mt-20">
            <h2 class="mb-1 border-l-4 border-gold-500 pl-3 text-xl font-semibold text-gray-50">
                {{ $category->nombre }}
            </h2>

            @if ($category->descripcion)
                <p class="mb-3 pl-4 text-sm text-gray-500">{{ $category->descripcion }}</p>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                            <th scope="col" class="px-4 py-2 font-medium">Ingredientes</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach ($category->dishes as $dish)
                            <tr @class(['bg-gray-900/40' => ! $dish->disponible])>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'font-medium text-gray-100',
                                        'text-gray-500 line-through' => ! $dish->disponible,
                                    ])>
                                        {{ $dish->nombre }}
                                    </span>

                                    @if (! $dish->disponible)
                                        <span class="ml-2 rounded-full bg-red-950 px-2 py-0.5 text-xs text-red-300">
                                            Agotado
                                        </span>
                                    @endif

                                    @if ($dish->descripcion)
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $dish->descripcion }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $dish->ingredients->isNotEmpty()
                                        ? $dish->ingredients->pluck('nombre')->implode(', ')
                                        : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gold-400">
                                    ${{ number_format($dish->precio, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-8 text-center text-gray-500">
            Todavía no hay platos cargados.
        </p>
    @endforelse
@endsection
