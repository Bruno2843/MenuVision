@use('App\Enums\OrderStatus')
@use('App\Support\MenuImagen')

@props(['order'])

@php
    $plato = $order->items->first()?->dish;

    // The clock on the card is how long the order has been waiting in the
    // kitchen, read from the only timestamp the order carries.
    $minutos = (int) $order->created_at->diffInMinutes(now());
    $tiempo = $minutos < 60
        ? $minutos.' min'
        : intdiv($minutos, 60).' h '.($minutos % 60).' min';
@endphp

<article
    data-order-state="{{ $order->estado->value }}"
    {{ $attributes->class(['flex flex-col gap-3 rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-2 sm:flex-row']) }}
>
    {{-- Zona izquierda: fotografía del primer plato, con el respaldo que el
         sistema de imágenes ya usa para un plato sin fotografía. --}}
    <div class="h-28 w-full shrink-0 overflow-hidden rounded-lg border-2 border-white/10 bg-cocina-fondo sm:h-[7.5rem] sm:w-[7.5rem]">
        @if ($plato)
            <img
                src="{{ asset(MenuImagen::paraDish($plato)) }}"
                data-imagen-respaldo="{{ asset(MenuImagen::respaldoParaDish($plato)) }}"
                alt=""
                width="240"
                height="220"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover"
            >
        @else
            <div class="flex h-full w-full items-center justify-center text-cocina-amarillo/60">
                @include('partials.icon', ['icon' => 'platos', 'iconClass' => 'h-8 w-8'])
            </div>
        @endif
    </div>

    {{-- Zona central: identificación, datos generales y productos. --}}
    <div class="min-w-0 flex-1">
        <p class="text-lg leading-none font-extrabold text-white sm:text-xl">
            PEDIDO <span class="text-cocina-amarillo">#{{ $order->id }}</span>
        </p>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-cocina-gris">
            <span class="inline-flex items-center gap-1.5">
                @include('partials.icon', ['icon' => 'mesas', 'iconClass' => 'h-3.5 w-3.5 text-cocina-amarillo'])
                Mesa {{ $order->table->numero }}
            </span>

            @if ($order->user)
                <span class="inline-flex items-center gap-1.5">
                    @include('partials.icon', ['icon' => 'usuarios', 'iconClass' => 'h-3.5 w-3.5 text-cocina-amarillo'])
                    {{ $order->user->name }}
                </span>
            @endif

            @if ($order->table->comensales ?? null)
                <span class="inline-flex items-center gap-1.5">
                    @include('partials.icon', ['icon' => 'usuarios', 'iconClass' => 'h-3.5 w-3.5 text-cocina-amarillo'])
                    {{ $order->table->comensales }} comensales
                </span>
            @endif
        </div>

        <div class="my-2 h-px w-full bg-cocina-amarillo/40"></div>

        <p class="text-xs font-bold text-cocina-amarillo">Productos:</p>

        <ul class="mt-1 space-y-1">
            @foreach ($order->items as $item)
                <li class="text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0 text-white">{{ $item->dish->nombre }}</span>
                        <span class="shrink-0 font-bold tabular-nums text-white">{{ $item->cantidad }}&times;</span>
                    </div>

                    @if ($item->observaciones)
                        <p class="pl-3 text-xs text-cocina-gris">{{ $item->observaciones }}</p>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($order->observaciones)
            <p class="mt-2 text-xs text-cocina-gris">
                <span class="font-semibold text-cocina-amarillo">Observaciones:</span>
                {{ $order->observaciones }}
            </p>
        @endif

        @if ($order->motivo_devolucion)
            <p class="mt-2 rounded-lg border-2 border-cocina-amarillo/40 bg-cocina-fondo px-2 py-1 text-xs text-cocina-gris">
                <span class="font-semibold text-cocina-amarillo">Falta:</span>
                {{ $order->motivo_devolucion }}
            </p>
        @endif
    </div>

    {{-- Zona derecha: estado, tiempo, detalle y las acciones de siempre. --}}
    <div class="flex w-full shrink-0 flex-col gap-2 sm:w-36">
        <span class="inline-flex items-center justify-center gap-1.5 rounded-full border-2 border-cocina-amarillo bg-cocina-amarillo px-2.5 py-1 text-[11px] font-bold tracking-wide text-black uppercase">
            @include('partials.icon', ['icon' => 'gorro', 'iconClass' => 'h-3.5 w-3.5'])
            {{ $order->estado->label() }}
        </span>

        <span class="inline-flex items-center gap-1.5 text-xs text-white">
            @include('partials.icon', ['icon' => 'reloj', 'iconClass' => 'h-4 w-4 text-cocina-amarillo'])
            {{ $tiempo }}
        </span>

        <a
            href="{{ route('kitchen.orders.show', $order) }}"
            class="inline-flex items-center justify-between gap-2 rounded-xl border-2 border-cocina-amarillo bg-black px-3 py-2 text-xs font-bold text-cocina-amarillo transition hover:bg-cocina-amarillo hover:text-black"
        >
            <span class="inline-flex items-center gap-2">
                @include('partials.icon', ['icon' => 'gorro', 'iconClass' => 'h-4 w-4'])
                Ver detalles
            </span>
            @include('partials.icon', ['icon' => 'flecha', 'iconClass' => 'h-4 w-4'])
        </a>

        @if ($order->estado->kitchenAction())
            <form method="POST" action="{{ route('kitchen.orders.status', $order) }}" class="mt-auto">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado" value="{{ $order->estado->nextKitchenStatus()->value }}">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-cocina-amarillo px-3 py-2 text-xs font-bold text-black transition hover:brightness-110"
                >
                    {{ $order->estado->kitchenAction() }}
                </button>
            </form>
        @endif

        @if ($order->estado->canBeReturned())
            <details class="text-xs">
                <summary class="cursor-pointer text-cocina-gris transition hover:text-white">
                    ¿Falta algún insumo?
                </summary>

                <form method="POST" action="{{ route('kitchen.orders.status', $order) }}" class="mt-2 space-y-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="estado" value="{{ OrderStatus::SinInsumos->value }}">

                    <label for="motivo-{{ $order->id }}" class="sr-only">Motivo de la devolución</label>
                    <textarea
                        id="motivo-{{ $order->id }}"
                        name="motivo_devolucion"
                        rows="2"
                        maxlength="500"
                        placeholder="Falta la milanesa…"
                        class="w-full rounded-lg border-2 border-cocina-amarillo/50 bg-cocina-fondo px-2 py-1 text-xs text-white placeholder-cocina-gris/70 focus:border-cocina-amarillo focus:outline-none"
                    ></textarea>

                    <button
                        type="submit"
                        class="w-full rounded-lg border-2 border-cocina-amarillo px-2 py-1.5 text-xs font-semibold text-cocina-amarillo transition hover:bg-cocina-amarillo hover:text-black"
                    >
                        Devolver al mozo
                    </button>
                </form>
            </details>
        @endif
    </div>
</article>
