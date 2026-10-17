@extends('layouts.admin')

@section('title', 'Admin · Pedido #'.$order->id)

@section('content')
    @include('admin.partials.page-header', [
        'titulo' => 'Pedido #'.$order->id,
        'subtitulo' => 'Mesa '.($order->table?->numero ?? '—').' · '.$order->created_at->format('d/m/Y H:i'),
        'volver' => route('admin.orders.index'),
        'volverTexto' => 'Volver a los pedidos',
    ])

    @include('admin.partials.flash')

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <h2 class="mb-3 text-lg font-semibold text-white">Platos</h2>

            <div class="overflow-x-auto rounded-xl border-2 border-gray-800">
                <table class="w-full min-w-[30rem] text-left text-sm">
                    <thead class="bg-gray-900 text-xs tracking-wide text-gray-500 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                            <th scope="col" class="px-4 py-2 text-center font-medium">Cantidad</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Unitario</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-gray-800">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3 text-gray-100">
                                    {{ $item->dish?->nombre ?? 'Plato eliminado' }}

                                    @if ($item->observaciones)
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $item->observaciones }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums text-gray-400">
                                    {{ $item->cantidad }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-400">
                                    ${{ number_format($item->precio_unitario, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-200">
                                    ${{ number_format($item->cantidad * (float) $item->precio_unitario, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-900">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-medium text-gray-300">
                                Total
                            </td>
                            <td class="px-4 py-3 text-right text-lg font-bold tabular-nums text-gold-400">
                                ${{ number_format($order->total(), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="mt-2 text-xs text-gray-500">
                Los precios son los que quedaron congelados cuando se tomó el pedido.
            </p>
        </section>

        <section>
            <h2 class="mb-3 text-lg font-semibold text-white">Detalle</h2>

            <dl class="space-y-3 rounded-xl border-2 border-gray-800 bg-gray-900 p-5 text-sm">
                <div>
                    <dt class="text-xs tracking-wide text-gray-500 uppercase">Estado</dt>
                    <dd class="mt-1">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                            $order->estado->badgeClasses(),
                        ])>
                            {{ $order->estado->label() }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-gray-500 uppercase">Mesa</dt>
                    <dd class="mt-1 text-gray-200">{{ $order->table?->numero ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs tracking-wide text-gray-500 uppercase">Observaciones</dt>
                    <dd class="mt-1 text-gray-300">{{ $order->observaciones ?: '—' }}</dd>
                </div>

                @if ($order->motivo_devolucion)
                    <div>
                        <dt class="text-xs tracking-wide text-gray-500 uppercase">Motivo de devolución</dt>
                        <dd class="mt-1 text-orange-300">{{ $order->motivo_devolucion }}</dd>
                    </div>
                @endif
            </dl>

            <p class="mt-4 text-xs text-gray-500">
                Para cambiar el estado de un pedido, usá el panel del mozo o el de cocina.
            </p>
        </section>
    </div>
@endsection
