@extends('layouts.app')

@section('title', 'Menú · Mesa ' . $table->numero)

@section('content')
    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Estás en la</p>
        <h1 class="text-2xl font-bold">Mesa {{ $table->numero }}</h1>
    </div>

    @forelse ($categories as $category)
        <section class="mb-8">
            <h2 class="mb-3 border-l-4 border-emerald-600 pl-3 text-xl font-semibold">{{ $category->nombre }}</h2>

            <div class="grid gap-4">
                @forelse ($category->dishes as $dish)
                    <article class="rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold">{{ $dish->nombre }}</h3>
                                @if ($dish->descripcion)
                                    <p class="mt-1 text-sm text-gray-500">{{ $dish->descripcion }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 font-bold text-emerald-700">
                                ${{ number_format($dish->precio, 2) }}
                            </span>
                        </div>

                        @if ($dish->ingredients->isNotEmpty())
                            <p class="mt-2 text-xs text-gray-400">
                                {{ $dish->ingredients->pluck('nombre')->implode(', ') }}
                            </p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-gray-500">No hay platos en esta categoría.</p>
                @endforelse
            </div>
        </section>
    @empty
        <p class="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm">El menú aún no está disponible.</p>
    @endforelse
@endsection