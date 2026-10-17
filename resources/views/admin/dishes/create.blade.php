@extends('layouts.admin')

@section('title', 'Admin · Nuevo plato')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Nuevo plato',
        'volver' => route('admin.dishes.index'),
        'volverTexto' => 'Volver a los platos',
    ])

    @include('admin.partials.flash')

    <form
        method="POST"
        action="{{ route('admin.dishes.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="max-w-3xl rounded-xl border-2 border-gray-800 bg-gray-900 p-6">
            @include('admin.dishes.form')

            <div class="mt-6 flex justify-end gap-3">
                <a
                    href="{{ route('admin.dishes.index') }}"
                    class="rounded-lg border-2 border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                >
                    Crear plato
                </button>
            </div>
        </div>
    </form>
@endsection
