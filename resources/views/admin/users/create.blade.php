@extends('layouts.admin')

@section('title', 'Admin · Nuevo usuario')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Nuevo usuario',
        'volver' => route('admin.users.index'),
        'volverTexto' => 'Volver a los usuarios',
    ])

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="max-w-2xl rounded-xl border border-gray-800 bg-gray-900 p-6">
            @include('admin.users.form')

            <div class="mt-6 flex justify-end gap-3">
                <a
                    href="{{ route('admin.users.index') }}"
                    class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                >
                    Crear usuario
                </button>
            </div>
        </div>
    </form>
@endsection
