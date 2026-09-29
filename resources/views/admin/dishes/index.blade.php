@extends('layouts.staff')

@section('title', 'Admin · Platos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Platos</h1>
            <p class="text-sm text-gray-400">El orden se aplica dentro de cada categoría.</p>
        </div>

        <a
            href="{{ route('admin.dishes.create') }}"
            class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            Nuevo plato
        </a>
    </div>

    @include('admin.partials.flash')

    <form method="GET" action="{{ route('admin.dishes.index') }}" class="mb-4 flex flex-wrap gap-3">
        <select
            name="categoria"
            class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($categoriaSeleccionada === $category->id)>
                    {{ $category->nombre }}
                </option>
            @endforeach
        </select>

        <input
            type="search"
            name="buscar"
            value="{{ $busqueda }}"
            placeholder="Buscar por nombre"
            class="w-56 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >

        <button
            type="submit"
            class="rounded-lg border border-gold-600 px-4 py-2 text-sm font-semibold text-gold-400 transition hover:bg-gold-500/10"
        >
            Filtrar
        </button>

        @if ($categoriaSeleccionada || $busqueda)
            <a
                href="{{ route('admin.dishes.index') }}"
                class="self-center text-sm text-gray-400 hover:text-gold-400"
            >
                Limpiar
            </a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                    <th scope="col" class="px-4 py-2 font-medium">Categoría</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Orden</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Estado</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($dishes as $dish)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-100">{{ $dish->nombre }}</span>
                            @if ($dish->descripcion)
                                <p class="mt-0.5 text-xs text-gray-500">{{ $dish->descripcion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $dish->category->nombre }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $dish->orden }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gold-400">
                            ${{ number_format($dish->precio, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-950 text-green-300' => $dish->disponible,
                                'bg-red-950 text-red-300' => ! $dish->disponible,
                            ])>
                                {{ $dish->disponible ? 'Disponible' : 'Agotado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    href="{{ route('admin.dishes.edit', $dish) }}"
                                    class="text-gold-400 hover:text-gold-300"
                                >
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('admin.dishes.destroy', $dish) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron platos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
