@extends('layouts.admin')

@section('title', 'Admin · Panel')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Panel</h1>
        <p class="text-sm text-gray-400">Resumen del menú, la operación y el equipo.</p>
    </div>

    @include('admin.partials.flash')

    {{-- The four headline numbers. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border-2 border-gray-800 bg-gray-900 p-5">
            <p class="text-sm text-gray-400">Ingresos de pedidos entregados</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-gold-400">
                ${{ number_format($ingresos, 2) }}
            </p>
        </div>

        <div class="rounded-xl border-2 border-gray-800 bg-gray-900 p-5">
            <p class="text-sm text-gray-400">Pedidos</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $pedidos['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $pedidos['hoy'] }} hoy · {{ $pedidos['abiertos'] }} en curso
            </p>
        </div>

        <div class="rounded-xl border-2 border-gray-800 bg-gray-900 p-5">
            <p class="text-sm text-gray-400">Mesas</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $mesas['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $mesas['ocupadas'] }} ocupadas ahora</p>
        </div>

        <div class="rounded-xl border-2 border-gray-800 bg-gray-900 p-5">
            <p class="text-sm text-gray-400">Platos</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $menu['platos'] }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $menu['disponibles'] }} disponibles · {{ $menu['agotados'] }} agotados
            </p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Orders, by status. --}}
        <section class="lg:col-span-1">
            <h2 class="mb-3 text-lg font-semibold text-white">Pedidos por estado</h2>

            <ul class="overflow-hidden rounded-xl border-2 border-gray-800">
                @foreach ($pedidos['porEstado'] as $fila)
                    <li class="flex items-center justify-between gap-3 border-b-2 border-gray-800 bg-gray-900 px-4 py-2.5 last:border-b-0">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                            $fila['estado']->badgeClasses(),
                        ])>
                            {{ $fila['estado']->label() }}
                        </span>
                        <span class="tabular-nums text-gray-300">{{ $fila['total'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- The dishes that were actually served most. --}}
        <section class="lg:col-span-1">
            <h2 class="mb-3 text-lg font-semibold text-white">Platos más pedidos</h2>

            <ul class="overflow-hidden rounded-xl border-2 border-gray-800">
                @forelse ($pedidos['topPlatos'] as $fila)
                    <li class="flex items-center justify-between gap-3 border-b-2 border-gray-800 bg-gray-900 px-4 py-2.5 last:border-b-0">
                        <span class="truncate text-sm text-gray-200">{{ $fila['dish']->nombre }}</span>
                        <span class="shrink-0 tabular-nums text-gray-400">{{ $fila['cantidad'] }}</span>
                    </li>
                @empty
                    <li class="bg-gray-900 px-4 py-6 text-center text-sm text-gray-500">
                        Todavía no hay pedidos entregados.
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Shortcuts into the sections that need attention. --}}
        <section class="lg:col-span-1">
            <h2 class="mb-3 text-lg font-semibold text-white">Para completar</h2>

            <div class="space-y-3">
                <a
                    href="{{ route('admin.dishes.index') }}"
                    class="flex items-center justify-between gap-3 rounded-xl border-2 border-gray-800 bg-gray-900 px-4 py-3 transition hover:border-gold-600/60"
                >
                    <span class="text-sm text-gray-300">Platos sin foto</span>
                    <span class="tabular-nums text-gray-400">{{ $menu['sinFoto'] }}</span>
                </a>

                <a
                    href="{{ route('admin.dishes.index') }}"
                    class="flex items-center justify-between gap-3 rounded-xl border-2 border-gray-800 bg-gray-900 px-4 py-3 transition hover:border-gold-600/60"
                >
                    <span class="text-sm text-gray-300">Platos sin modelo 3D</span>
                    <span class="tabular-nums text-gray-400">{{ $menu['sinModelo'] }}</span>
                </a>

                <a
                    href="{{ route('admin.tables.index') }}"
                    class="flex items-center justify-between gap-3 rounded-xl border-2 border-gray-800 bg-gray-900 px-4 py-3 transition hover:border-gold-600/60"
                >
                    <span class="text-sm text-gray-300">Mesas y códigos QR</span>
                    <span class="tabular-nums text-gray-400">{{ $mesas['total'] }}</span>
                </a>

                <a
                    href="{{ route('admin.users.index') }}"
                    class="flex items-center justify-between gap-3 rounded-xl border-2 border-gray-800 bg-gray-900 px-4 py-3 transition hover:border-gold-600/60"
                >
                    <span class="text-sm text-gray-300">
                        Usuarios activos
                        <span class="text-gray-500">({{ $personal['administradores'] }} admin)</span>
                    </span>
                    <span class="tabular-nums text-gray-400">{{ $personal['activos'] }}</span>
                </a>
            </div>
        </section>
    </div>

    {{-- The most recent orders, with a link into the read-only list. --}}
    <section class="mt-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-white">Últimos pedidos</h2>

            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gold-400 hover:text-gold-300">
                Ver todos
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border-2 border-gray-800">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-gray-900 text-xs tracking-wide text-gray-500 uppercase">
                    <tr>
                        <th scope="col" class="px-4 py-2 font-medium">Pedido</th>
                        <th scope="col" class="px-4 py-2 font-medium">Mesa</th>
                        <th scope="col" class="px-4 py-2 font-medium">Estado</th>
                        <th scope="col" class="px-4 py-2 text-right font-medium">Total</th>
                        <th scope="col" class="px-4 py-2 text-right font-medium">Cuando</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-gray-800">
                    @forelse ($ultimosPedidos as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-gold-400 hover:text-gold-300">
                                    #{{ $order->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ $order->table?->numero ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    $order->estado->badgeClasses(),
                                ])>
                                    {{ $order->estado->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-300">
                                ${{ number_format($order->total(), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-gray-500">
                                {{ $order->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Todavía no hay pedidos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
