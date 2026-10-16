@extends('layouts.admin')

@section('title', 'Admin · Categorías')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Categorías</h1>
            <p class="text-sm text-gray-400">El orden define cómo aparecen en el menú.</p>
        </div>

        <a
            href="{{ route('admin.categories.create') }}"
            class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            Nueva categoría
        </a>
    </div>

    @include('admin.partials.flash')

    <div class="overflow-hidden rounded-xl border border-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Orden</th>
                    <th scope="col" class="px-4 py-2 font-medium">Categoría</th>
                    <th scope="col" class="px-4 py-2 font-medium">Descripción</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Platos</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 text-gray-500">{{ $category->orden }}</td>
                        <td class="px-4 py-3 font-medium text-gray-100">{{ $category->nombre }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $category->descripcion ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-400">{{ $category->dishes_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    href="{{ route('admin.categories.edit', $category) }}"
                                    class="text-gold-400 hover:text-gold-300"
                                >
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Todavía no hay categorías.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
