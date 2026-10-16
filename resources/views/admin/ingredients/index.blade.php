@extends('layouts.admin')

@section('title', 'Admin · Ingredientes')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Ingredientes',
        'subtitulo' => 'Los insumos que se pueden asignar a los platos.',
    ])

    @include('admin.partials.flash')

    <div class="mb-4 flex justify-end">
        <a
            href="{{ route('admin.ingredients.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            @include('partials.icon', ['icon' => 'mas', 'iconClass' => 'h-4 w-4'])
            Nuevo ingrediente
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-800">
        <table class="w-full min-w-[30rem] text-left text-sm">
            <thead class="bg-gray-900 text-xs tracking-wide text-gray-500 uppercase">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Ingrediente</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Platos</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($ingredients as $ingredient)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-100">{{ $ingredient->nombre }}</td>
                        <td class="px-4 py-3 text-center text-gray-400">{{ $ingredient->dishes_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a
                                    href="{{ route('admin.ingredients.edit', $ingredient) }}"
                                    class="inline-flex items-center gap-1.5 text-gold-400 hover:text-gold-300"
                                >
                                    @include('partials.icon', ['icon' => 'editar', 'iconClass' => 'h-4 w-4'])
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('admin.ingredients.destroy', $ingredient) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1.5 text-red-400 hover:text-red-300"
                                    >
                                        @include('partials.icon', ['icon' => 'basura', 'iconClass' => 'h-4 w-4'])
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            Todavía no hay ingredientes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
