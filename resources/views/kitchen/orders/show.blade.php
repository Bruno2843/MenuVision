@extends('layouts.staff')

@section('title', 'Cocina · Pedido #' . $order->id)

@section('content')
    <div class="mb-4">
        <a href="{{ route('kitchen.dashboard') }}" class="text-sm text-cocina-gris transition hover:text-cocina-amarillo">
            ← Volver a los pedidos
        </a>

        <h1 class="mt-2 text-2xl font-bold text-white">
            Pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
        </h1>
        <div class="mt-1.5 h-0.5 w-16 bg-cocina-amarillo"></div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border-2 border-cocina-verde bg-cocina-panel px-4 py-2.5 text-sm font-medium text-cocina-verde">
            {{ session('status') }}
        </div>
    @endif

    <div class="max-w-3xl">
        @include('kitchen.components.order-card', ['order' => $order])
    </div>

    <div class="mt-4 max-w-2xl overflow-hidden rounded-xl border-2 border-cocina-amarillo">
        <table class="w-full text-left text-sm">
            <thead class="bg-cocina-panel text-xs tracking-wide text-cocina-amarillo uppercase">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Cantidad</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-cocina-amarillo/20">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-white">{{ $item->dish->nombre }}</td>
                        <td class="px-4 py-3 text-center text-cocina-gris">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-cocina-amarillo">
                            ${{ number_format($item->cantidad * (float) $item->precio_unitario, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-cocina-panel">
                <tr>
                    <th scope="row" colspan="2" class="px-4 py-3 text-right font-medium text-cocina-gris">Total</th>
                    <td class="px-4 py-3 text-right text-lg font-bold text-cocina-amarillo">
                        ${{ number_format($order->total(), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
