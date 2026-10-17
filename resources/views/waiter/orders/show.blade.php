@use('App\Enums\OrderStatus')

@extends('layouts.staff')

@section('title', 'Mozo · Pedido #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('waiter.dashboard') }}" class="text-sm text-cocina-gris hover:text-cocina-amarillo">
            ← Volver a los pedidos
        </a>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    Pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
                </h1>
                    <p class="text-sm text-cocina-gris">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                        @if ($order->user)
                            · Mozo: {{ $order->user->name }}
                        @endif
                    </p>
            </div>

            <span class="inline-flex items-center rounded-full border-2 border-current px-3 py-1 text-xs font-medium {{ $order->estado->badgeClasses() }}">
                {{ $order->estado->label() }}
            </span>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border-2 border-cocina-verde/50 bg-cocina-panel px-4 py-3 text-sm text-cocina-verde">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border-2 border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 rounded-xl border-2 border-cocina-amarillo/30 bg-cocina-panel p-4 sm:p-5">
        @include('waiter.partials.order-progress', ['estado' => $order->estado])
    </div>

    @if ($order->motivo_devolucion)
        <div class="mb-6 rounded-xl border-2 border-orange-900 bg-orange-950/40 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-400">
                La cocina no pudo prepararlo
            </p>
            <p class="mt-1 text-sm text-orange-100">{{ $order->motivo_devolucion }}</p>
        </div>
    @endif

    <div class="mb-6 overflow-hidden rounded-xl border-2 border-cocina-amarillo/30 bg-cocina-panel">
        <table class="w-full text-left text-sm">
            <thead class="bg-cocina-fondo text-xs uppercase tracking-wide text-cocina-gris">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Cantidad</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-cocina-amarillo/15">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-white">
                            {{ $item->dish->nombre }}
                            @if ($item->observaciones)
                                <p class="mt-0.5 text-xs text-cocina-gris">{{ $item->observaciones }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-cocina-gris">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-right text-cocina-gris">
                            ${{ number_format($item->precio_unitario, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-cocina-amarillo">
                            ${{ number_format($item->cantidad * (float) $item->precio_unitario, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-cocina-fondo">
                <tr>
                    <th scope="row" colspan="3" class="px-4 py-3 text-right font-medium text-cocina-gris">Total</th>
                    <td class="px-4 py-3 text-right text-lg font-bold text-cocina-amarillo">
                        ${{ number_format($order->total(), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($order->observaciones)
        <div class="mb-6 rounded-xl border-2 border-cocina-amarillo/30 bg-cocina-panel p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-cocina-gris">Observaciones</p>
            <p class="mt-1 text-sm text-cocina-gris">{{ $order->observaciones }}</p>
        </div>
    @endif

    @php
        $transiciones = $order->estado->waiterTransitions();
        $devueltoPorCocina = $order->estado === OrderStatus::SinInsumos;
    @endphp

    @if ($devueltoPorCocina || $transiciones !== [])
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($devueltoPorCocina)
                <p class="text-sm text-cocina-gris">
                    Cambiá lo que va a comer la mesa, reenvialo a cocina o rechazalo.
                </p>
            @endif

            <div class="ml-auto flex flex-wrap justify-end gap-3">
                {{-- A handback can only go back to the kitchen through the
                     resend form, where the dishes can still be changed. --}}
                @if ($devueltoPorCocina)
                    <a
                        href="{{ route('waiter.orders.resend', $order) }}"
                        class="rounded-lg bg-cocina-amarillo px-4 py-2 text-sm font-semibold text-black transition hover:brightness-110"
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
                            class="rounded-lg border-2 border-red-900 px-4 py-2 text-sm font-semibold text-red-300 transition hover:bg-red-950"
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
                            class="rounded-lg bg-cocina-amarillo px-4 py-2 text-sm font-semibold text-black transition hover:brightness-110"
                        >
                            Confirmar pedido
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection
