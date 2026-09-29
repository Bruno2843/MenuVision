<div class="grid gap-5 lg:grid-cols-4">
    @foreach ($columns as $column)
        <section>
            <h2 class="mb-3 flex items-center justify-between text-sm font-semibold uppercase tracking-wide text-gray-400">
                {{ $column['estado']->label() }}
                <span class="rounded-full bg-gray-800 px-2 py-0.5 text-xs text-gray-300">
                    {{ $column['orders']->count() }}
                </span>
            </h2>

            <div class="space-y-3">
                @forelse ($column['orders'] as $order)
                    @include('kitchen.components.order-card', ['order' => $order])
                @empty
                    <p class="rounded-xl border border-dashed border-gray-800 px-4 py-8 text-center text-sm text-gray-600">
                        Sin pedidos.
                    </p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>

@if ($delivered->isNotEmpty())
    <section class="mt-8">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">
            Últimos entregados
        </h2>

        <ul class="flex flex-wrap gap-2">
            @foreach ($delivered as $order)
                <li class="rounded-full border border-sky-900 bg-sky-950/40 px-3 py-1 text-xs text-sky-300">
                    Mesa {{ $order->table->numero }}
                    <span class="text-sky-500">#{{ $order->id }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
