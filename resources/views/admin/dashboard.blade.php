@use('Illuminate\Support\Str')

@extends('layouts.staff')

@section('title', 'Administración')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Administración</h1>
        <p class="text-sm text-gray-400">Gestión del menú, mesas, códigos QR y usuarios.</p>
    </div>

    @include('admin.partials.flash')

    <div class="grid gap-4 sm:grid-cols-2">
        <a
            href="{{ route('admin.categories.index') }}"
            class="rounded-xl border border-gray-800 bg-gray-900 p-6 transition hover:border-gold-600/60"
        >
            <h2 class="text-lg font-semibold text-white">Categorías</h2>
            <p class="mt-1 text-sm text-gray-400">
                {{ $categoriesCount }} {{ Str::plural('categoría', $categoriesCount) }}
                · organizá el orden del menú.
            </p>
        </a>

        <a
            href="{{ route('admin.dishes.index') }}"
            class="rounded-xl border border-gray-800 bg-gray-900 p-6 transition hover:border-gold-600/60"
        >
            <h2 class="text-lg font-semibold text-white">Platos</h2>
            <p class="mt-1 text-sm text-gray-400">
                {{ $dishesCount }} {{ Str::plural('plato', $dishesCount) }}
                · alta, edición y disponibilidad.
            </p>
        </a>

        <div class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-6 opacity-60">
            <h2 class="text-lg font-semibold text-white">Mesas y QR</h2>
            <p class="mt-1 text-sm text-gray-400">Próximamente.</p>
        </div>

        <div class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-6 opacity-60">
            <h2 class="text-lg font-semibold text-white">Usuarios</h2>
            <p class="mt-1 text-sm text-gray-400">Próximamente.</p>
        </div>
    </div>
@endsection
