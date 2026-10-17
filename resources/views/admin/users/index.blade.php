@extends('layouts.admin')

@section('title', 'Admin · Usuarios')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Usuarios</h1>
            <p class="text-sm text-gray-400">
                Las cuentas no se borran: se desactivan, para que los pedidos que took cada persona
                sigan siendo trazables.
            </p>
        </div>

        <a
            href="{{ route('admin.users.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            @include('partials.icon', ['icon' => 'mas', 'iconClass' => 'h-4 w-4'])
            Nuevo usuario
        </a>
    </div>

    @include('admin.partials.flash')

    <div class="overflow-x-auto rounded-xl border-2 border-gray-800">
        <table class="w-full min-w-[44rem] text-left text-sm">
            <thead class="bg-gray-900 text-xs tracking-wide text-gray-500 uppercase">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Nombre</th>
                    <th scope="col" class="px-4 py-2 font-medium">Correo</th>
                    <th scope="col" class="px-4 py-2 font-medium">Rol</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Estado</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-gray-800">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-100">
                            {{ $user->name }}
                            @if ($user->is(auth()->user()))
                                <span class="ml-1 text-xs font-normal text-gray-500">(vos)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $user->role->label() }}</td>
                        <td class="px-4 py-3 text-center">
                            <span @class([
                                'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-green-950 text-green-300' => $user->estaActivo(),
                                'bg-gray-800 text-gray-400' => ! $user->estaActivo(),
                            ])>
                                {{ $user->estaActivo() ? 'Activo' : 'Desactivado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a
                                href="{{ route('admin.users.edit', $user) }}"
                                class="inline-flex items-center gap-1.5 text-gold-400 hover:text-gold-300"
                            >
                                @include('partials.icon', ['icon' => 'editar', 'iconClass' => 'h-4 w-4'])
                                Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
