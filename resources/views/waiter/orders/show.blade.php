@use('App\Enums\OrderStatus')

@extends('layouts.staff')

@section('title', 'Mozo · Pedido #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('waiter.dashboard') }}" class="text-sm text-gray-400 hover:text-gold-400">
            ← Volver a los pedidos
        </a>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    Pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
                </h1>
                <p class="text-sm text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $order->estado->badgeClasses() }}">
                {{ $order->estado->label() }}
            </span>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-900 bg-green-950 px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-800 bg-gray-900 p-4 sm:p-5">
        @include('waiter.partials.order-progress', ['estado' => $order->estado])
    </div>

    @if ($order->motivo_devolucion)
        <div class="mb-6 rounded-xl border border-orange-900 bg-orange-950/40 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-400">
                La cocina no pudo prepararlo
            </p>
            <p class="mt-1 text-sm text-orange-100">{{ $order->motivo_devolucion }}</p>
        </div>
    @endif

    <div class="mb-6 overflow-hidden rounded-xl border border-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Cantidad</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-100">{{ $item->dish->nombre }}</td>
                        <td class="px-4 py-3 text-center text-gray-300">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-right text-gray-400">
                            ${{ number_format($item->precio_unitario, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gold-400">
                            ${{ number_format($item->cantidad * (float) $item->precio_unitario, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-900">
                <tr>
                    <th scope="row" colspan="3" class="px-4 py-3 text-right font-medium text-gray-300">Total</th>
                    <td class="px-4 py-3 text-right text-lg font-bold text-gold-400">
                        ${{ number_format($order->total(), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($order->observaciones)
        <div class="mb-6 rounded-xl border border-gray-800 bg-gray-900 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones</p>
            <p class="mt-1 text-sm text-gray-300">{{ $order->observaciones }}</p>
        </div>
    @endif

    @php
        $transiciones = $order->estado->waiterTransitions();
        $devueltoPorCocina = $order->estado === OrderStatus::SinInsumos;
    @endphp

    @if ($devueltoPorCocina || $transiciones !== [])
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($devueltoPorCocina)
                <p class="text-sm text-gray-400">
                    Cambiá lo que va a comer la mesa, reenvialo a cocina o rechazalo.
                </p>
            @endif

            <div class="ml-auto flex flex-wrap justify-end gap-3">
                {{-- A handback can only go back to the kitchen through the
                     resend form, where the dishes can still be changed. --}}
                @if ($devueltoPorCocina)
                    <a
                        href="{{ route('waiter.orders.resend', $order) }}"
                        class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                    >
                        Reenviar a cocina
                    </a>
                @endif

                @if (in_array(OrderStatus::Rechazado->value, $transiciones, true))
                    <form method="POST" action="{{ route('waiter.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ OrderStatus::Rechazado->value }}">
                        <button
                            type="submit"
                            class="rounded-lg border border-red-900 px-4 py-2 text-sm font-semibold text-red-300 transition hover:bg-red-950"
                        >
                            Rechazar
                        </button>
                    </form>
                @endif

                @if (in_array(OrderStatus::Confirmado->value, $transiciones, true))
                    <form method="POST" action="{{ route('waiter.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ OrderStatus::Confirmado->value }}">
                        <button
                            type="submit"
                            class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                        >
                            Confirmar pedido
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection
