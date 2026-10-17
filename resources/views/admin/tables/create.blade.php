@extends('layouts.admin')

@section('title', 'Admin · Nueva mesa')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Nueva mesa',
        'volver' => route('admin.tables.index'),
        'volverTexto' => 'Volver a las mesas',
    ])

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.tables.store') }}">
        @csrf

        <div class="max-w-xl rounded-xl border-2 border-gray-800 bg-gray-900 p-6">
            @include('admin.tables.form')

            <p class="mt-4 text-xs text-gray-500">
                Al crearla se genera automáticamente su código QR.
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
                    Crear mesa
                </button>
            </div>
        </div>
    </form>
@endsection
