@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="mx-auto mt-10 max-w-md">
        <div class="rounded-xl border-2 border-gray-800 bg-gray-900 p-8">
            <h1 class="text-2xl font-bold text-white">Acceso del personal</h1>
            <p class="mt-1 text-sm text-gray-400">
                Área interna de MenuVision. Solo personal autorizado.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border-2 border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-300">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-300">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:ring-offset-2 focus:ring-offset-gray-950"
                >
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>
@endsection