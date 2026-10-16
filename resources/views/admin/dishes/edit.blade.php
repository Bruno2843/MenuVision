@extends('layouts.admin')

@section('title', 'Admin · Editar plato')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Editar plato',
        'subtitulo' => $dish->nombre,
        'volver' => route('admin.dishes.index'),
        'volverTexto' => 'Volver a los platos',
    ])

    @include('admin.partials.flash')

    <form
        method="POST"
        action="{{ route('admin.dishes.update', $dish) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <div class="max-w-3xl rounded-xl border border-gray-800 bg-gray-900 p-6">
            @include('admin.dishes.form')

            <div class="mt-6 flex justify-end gap-3">
                <a
                    href="{{ route('admin.dishes.index') }}"
                    class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                >
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>

    {{--
        Removing the model is destructive and immediate, so it is its own form
        below the edit form instead of another button inside it: an HTML form
        cannot be nested, and this keeps the delete from ever being submitted by
        accident together with a rename.
    --}}
    @if ($dish->model3D)
        <div class="mt-6 max-w-3xl rounded-xl border border-red-900/60 bg-gray-900 p-6">
            <h2 class="text-sm font-semibold text-white">Quitar el modelo 3D</h2>
            <p class="mt-1 text-sm text-gray-400">
                Borra el archivo y desactiva la vista 3D y el botón de realidad aumentada
                de este plato. El plato no se elimina.
            </p>

            <form
                method="POST"
                action="{{ route('admin.dishes.model.destroy', $dish) }}"
                class="mt-4"
                onsubmit="return confirm('¿Eliminar el modelo 3D de {{ $dish->nombre }}?');"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-800 px-4 py-2 text-sm font-medium text-red-300 transition hover:bg-red-950"
                >
                    @include('partials.icon', ['icon' => 'basura', 'iconClass' => 'h-4 w-4'])
                    Eliminar modelo
                </button>
            </form>
        </div>
    @endif
@endsection
