@extends('layouts.staff')

@section('title', 'Admin · Nuevo plato')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.dishes.index') }}" class="text-sm text-gray-400 hover:text-gold-400">
            ← Volver a los platos
        </a>
        <h1 class="mt-2 text-2xl font-bold text-white">Nuevo plato</h1>
    </div>

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.dishes.store') }}">
        @csrf

        <div class="max-w-2xl rounded-xl border border-gray-800 bg-gray-900 p-6">
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
                    Crear plato
                </button>
            </div>
        </div>
    </form>
@endsection
