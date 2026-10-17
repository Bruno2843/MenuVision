@extends('layouts.admin')

@section('title', 'Admin · Editar ingrediente')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Editar ingrediente',
        'volver' => route('admin.ingredients.index'),
        'volverTexto' => 'Volver a los ingredientes',
    ])

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.ingredients.update', $ingredient) }}">
        @csrf
        @method('PUT')

        <div class="max-w-xl rounded-xl border-2 border-gray-800 bg-gray-900 p-6">
            @include('admin.ingredients.form')

            <div class="mt-6 flex justify-end gap-3">
                <a
                    href="{{ route('admin.ingredients.index') }}"
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
