@use('App\Enums\OrderStatus')
@use('App\Models\Order')

<section class="mb-8">
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-cocina-gris">Mesas</h2>

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

            <div class="rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-4">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-white">Mesa {{ $table->numero }}</span>
                    <span class="inline-flex items-center rounded-full border-2 border-current px-3 py-1 text-xs font-medium {{ $table->estado->badgeClasses() }}">
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
                        <p class="mt-1 text-xs text-cocina-gris">+{{ $otros }} pedido{{ $otros === 1 ? '' : 's' }} en curso</p>
                    @endif
                @else
                    <p class="mt-2 text-xs text-cocina-gris">Sin pedidos abiertos</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-cocina-gris">Todavía no hay mesas cargadas.</p>
        @endforelse
    </div>
</section>

<section>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-cocina-gris">Todos los pedidos</h2>

    @if ($orders->isEmpty())
        <p class="rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-8 text-center text-cocina-gris">
            Todavía no se registró ningún pedido.
        </p>
    @else
        <ul class="space-y-2">
            @foreach ($orders as $order)
                <li>
                    <a
                        href="{{ route('waiter.orders.show', $order) }}"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-4 transition hover:brightness-110"
                    >
                        <div>
                            <span class="font-semibold text-white">Mesa {{ $order->table->numero }}</span>
                            <span class="ml-2 text-sm text-cocina-gris">#{{ $order->id }}</span>
                        </div>

                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-cocina-gris">{{ $order->items->sum('cantidad') }} platos</span>
                            <span class="font-bold text-cocina-amarillo">${{ number_format($order->total(), 2) }}</span>
                            <span class="inline-flex items-center rounded-full border-2 border-current px-3 py-1 text-xs font-medium {{ $order->estado->badgeClasses() }}">
                                {{ $order->estado->label() }}
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
