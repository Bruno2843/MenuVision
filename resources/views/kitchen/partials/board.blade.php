@use('App\Enums\OrderStatus')

@php
    // Only the statuses the kitchen has always owned are offered as filters.
    // Pending and delivered orders are not part of this board, so no filter is
    // invented for them: the tabs mirror what the kitchen can actually see.
    $estados = [
        OrderStatus::Confirmado,
        OrderStatus::EnPreparacion,
        OrderStatus::Listo,
        OrderStatus::SinInsumos,
    ];

    $filtros = collect([['clave' => 'todos', 'label' => 'Todos', 'total' => $orders->count()]])
        ->concat(array_map(fn (OrderStatus $estado): array => [
            'clave' => $estado->value,
            'label' => $estado->label(),
            'total' => $orders->filter(fn ($order): bool => $order->estado === $estado)->count(),
        ], $estados));
@endphp

<nav aria-label="Filtros de pedidos" data-kitchen-filters class="flex flex-wrap gap-2">
    @foreach ($filtros as $filtro)
        <button
            type="button"
            data-kitchen-filter="{{ $filtro['clave'] }}"
            aria-pressed="{{ $filtro['clave'] === 'todos' ? 'true' : 'false' }}"
            class="inline-flex flex-1 flex-col items-center rounded-full border-2 border-cocina-amarillo bg-cocina-panel px-4 py-1 text-cocina-amarillo transition hover:bg-cocina-amarillo/10 aria-pressed:bg-cocina-amarillo aria-pressed:text-black sm:min-w-[7rem]"
        >
            <span class="text-sm leading-tight font-bold">{{ $filtro['label'] }}</span>
            <span class="text-[11px] leading-tight tabular-nums opacity-80">{{ $filtro['total'] }}</span>
        </button>
    @endforeach
</nav>

<div class="mt-3 space-y-2">
    @foreach ($orders as $order)
        @include('kitchen.components.order-card', ['order' => $order])
    @endforeach
</div>

<p
    data-kitchen-empty
    @class([
        'hidden' => $orders->isNotEmpty(),
        'mt-3 rounded-xl border-2 border-cocina-amarillo bg-cocina-panel px-4 py-6 text-center text-sm text-cocina-gris',
    ])
>
    No hay pedidos para mostrar en este filtro.
</p>

@if ($delivered->isNotEmpty())
    <section class="mt-4">
        <h2 class="mb-2 text-xs font-bold tracking-wide text-cocina-amarillo uppercase">
            Últimos entregados
        </h2>

        <ul class="flex flex-wrap gap-2">
            @foreach ($delivered as $order)
                <li class="inline-flex items-center gap-1.5 rounded-full border-2 border-cocina-verde/60 bg-cocina-panel px-3 py-1 text-xs text-cocina-verde">
                    @include('partials.icon', ['icon' => 'mesas', 'iconClass' => 'h-3.5 w-3.5'])
                    Mesa {{ $order->table->numero }}
                    <span class="text-cocina-verde/60">#{{ $order->id }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
