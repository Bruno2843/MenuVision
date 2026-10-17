@extends('layouts.admin')

@section('title', 'Admin · Editar mesa')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Editar mesa',
        'subtitulo' => 'Mesa '.$table->numero,
        'volver' => route('admin.tables.index'),
        'volverTexto' => 'Volver a las mesas',
    ])

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.tables.update', $table) }}">
        @csrf
        @method('PUT')

        <div class="max-w-xl rounded-xl border-2 border-gray-800 bg-gray-900 p-6">
            @include('admin.tables.form')

            <p class="mt-4 text-xs text-gray-500">
                Su estado ({{ $table->estado->label() }}) lo define el último pedido, no se edita a mano.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <a
                    href="{{ route('admin.tables.index') }}"
                    class="rounded-lg border-2 border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
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
@endsection
