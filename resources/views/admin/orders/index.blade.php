@extends('layouts.admin')

@section('title', 'Admin · Pedidos')

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Pedidos',
        'subtitulo' => 'Solo lectura: el flujo lo mueven el mozo y la cocina.',
    ])

    @include('admin.partials.flash')

    <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="estado" class="mb-1 block text-xs font-medium text-gray-400">Estado</label>
            <select
                id="estado"
                name="estado"
                class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
                <option value="">Todos</option>
                @foreach ($estados as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($estadoSeleccionado === $valor)>
                        {{ $etiqueta }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="mesa" class="mb-1 block text-xs font-medium text-gray-400">Mesa</label>
            <select
                id="mesa"
                name="mesa"
                class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
                <option value="">Todas</option>
                @foreach ($tables as $table)
                    <option value="{{ $table->id }}" @selected($mesaSeleccionada === $table->id)>
                        {{ $table->numero }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="desde" class="mb-1 block text-xs font-medium text-gray-400">Desde</label>
            <input
                id="desde"
                type="date"
                name="desde"
                value="{{ $desde }}"
                class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
        </div>

        <button
            type="submit"
            class="rounded-lg border border-gold-600 px-4 py-2 text-sm font-semibold text-gold-400 transition hover:bg-gold-500/10"
        >
            Filtrar
        </button>

        @if ($estadoSeleccionado || $mesaSeleccionada || $desde)
            <a href="{{ route('admin.orders.index') }}" class="py-2 text-sm text-gray-400 hover:text-gold-400">
                Limpiar
            </a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-800">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-gray-900 text-xs tracking-wide text-gray-500 uppercase">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Pedido</th>
                    <th scope="col" class="px-4 py-2 font-medium">Mesa</th>
                    <th scope="col" class="px-4 py-2 font-medium">Estado</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Platos</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Total</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Cuando</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse ($orders as $order)
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
                        <td class="px-4 py-3 text-center text-gray-400">
                            {{ $order->items->sum('cantidad') }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-200">
                            ${{ number_format($order->total(), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron pedidos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection
