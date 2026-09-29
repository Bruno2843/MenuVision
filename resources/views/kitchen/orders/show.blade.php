@extends('layouts.staff')

@section('title', 'Cocina · Pedido #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('kitchen.dashboard') }}" class="text-sm text-gray-400 hover:text-gold-400">
            ← Volver a la cocina
        </a>

        <h1 class="mt-2 text-2xl font-bold text-white">
            Pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
        </h1>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-900 bg-green-950 px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="max-w-2xl">
        @include('kitchen.components.order-card', ['order' => $order])
    </div>

    <div class="mt-6 max-w-2xl overflow-hidden rounded-xl border border-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                    <th scope="col" class="px-4 py-2 text-center font-medium">Cantidad</th>
                    <th scope="col" class="px-4 py-2 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-100">{{ $item->dish->nombre }}</td>
                        <td class="px-4 py-3 text-center text-gray-300">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gold-400">
                            ${{ number_format($item->cantidad * (float) $item->precio_unitario, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-900">
                <tr>
                    <th scope="row" colspan="2" class="px-4 py-3 text-right font-medium text-gray-300">Total</th>
                    <td class="px-4 py-3 text-right text-lg font-bold text-gold-400">
                        ${{ number_format($order->total(), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
