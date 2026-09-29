@use('App\Enums\OrderStatus')
@use('App\Models\Order')

<section class="mb-8">
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Mesas</h2>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($tables as $table)
            @php
                // `whereIn` compares loosely, and a backed enum never loosely
                // equals its string value, so the statuses are compared by
                // their backing value instead.
                $abiertos = $table->orders->filter(
                    fn (Order $order): bool => in_array($order->estado->value, OrderStatus::open(), true)
                );
                // A handback or a still unconfirmed order is the one the
                // waiter actually has to act on, so it wins over the mere
                // presence of a newer order that the kitchen is handling.
                $requiereAccion = [OrderStatus::SinInsumos, OrderStatus::Pendiente];
                $pedido = $abiertos->first(
                    fn (Order $order): bool => in_array($order->estado, $requiereAccion, true)
                ) ?? $abiertos->first();
                $otros = $abiertos->count() - ($pedido ? 1 : 0);
            @endphp

            <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-100">Mesa {{ $table->numero }}</span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $table->estado->badgeClasses() }}">
                        {{ $table->estado->label() }}
                    </span>
                </div>

                @if ($pedido)
                    <a
                        href="{{ route('waiter.orders.show', $pedido) }}"
                        class="mt-2 block text-xs {{ $pedido->estado->badgeClasses() }} rounded-sm px-1.5 py-0.5 hover:opacity-80"
                    >
                        Pedido #{{ $pedido->id }} · {{ $pedido->estado->label() }}
                    </a>

                    @if ($otros > 0)
                        <p class="mt-1 text-xs text-gray-500">+{{ $otros }} pedido{{ $otros === 1 ? '' : 's' }} en curso</p>
                    @endif
                @else
                    <p class="mt-2 text-xs text-gray-500">Sin pedidos abiertos</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400">Todavía no hay mesas cargadas.</p>
        @endforelse
    </div>
</section>

<section>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Todos los pedidos</h2>

    @if ($orders->isEmpty())
        <p class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-8 text-center text-gray-500">
            Todavía no se registró ningún pedido.
        </p>
    @else
        <ul class="space-y-2">
            @foreach ($orders as $order)
                <li>
                    <a
                        href="{{ route('waiter.orders.show', $order) }}"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-800 bg-gray-900 p-4 transition hover:border-gold-600/60"
                    >
                        <div>
                            <span class="font-semibold text-gray-100">Mesa {{ $order->table->numero }}</span>
                            <span class="ml-2 text-sm text-gray-500">#{{ $order->id }}</span>
                        </div>

                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-400">{{ $order->items->sum('cantidad') }} platos</span>
                            <span class="font-bold text-gold-400">${{ number_format($order->total(), 2) }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $order->estado->badgeClasses() }}">
                                {{ $order->estado->label() }}
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
