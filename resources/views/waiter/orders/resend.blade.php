@extends('layouts.staff')

@section('title', 'Mozo · Reenviar pedido #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('waiter.orders.show', $order) }}" class="text-sm text-cocina-gris transition hover:text-cocina-amarillo">
            ← Volver al pedido
        </a>

        <h1 class="mt-2 text-2xl font-bold text-white">
            Reenviar pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
        </h1>
        <p class="text-sm text-cocina-gris">
            Cambiá lo que va a comer la mesa o, si sigue igual, avisale a cocina que lo prepare de igual manera.
        </p>
    </div>

    @if ($order->motivo_devolucion)
        <div class="mb-6 rounded-xl border-2 border-orange-900 bg-orange-950/40 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-400">
                La cocina no pudo prepararlo
            </p>
            <p class="mt-1 text-sm text-orange-100">{{ $order->motivo_devolucion }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border-2 border-red-600/50 bg-cocina-panel px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('waiter.orders.resend.update', $order) }}">
        @csrf
        @method('PATCH')

        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <div>
                <span class="mb-1 block text-sm font-medium text-cocina-gris">El pedido cambió?</span>

                <label
                    for="preparar_igual"
                    class="flex items-start gap-3 rounded-xl border-2 border-cocina-amarillo/30 bg-cocina-panel p-4"
                >
                    <input
                        type="checkbox"
                        id="preparar_igual"
                        name="preparar_igual"
                        value="1"
                        @checked(old('preparar_igual'))
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-cocina-amarillo/40 bg-cocina-fondo text-cocina-amarillo focus:ring-cocina-amarillo"
                    >
                    <span>
                        <span class="block text-sm font-semibold text-white">No, es el mismo pedido</span>
                        <span class="block text-xs text-cocina-gris">
                            Se le avisa a cocina que lo prepare de igual manera.
                        </span>
                    </span>
                </label>
            </div>

            <div>
                <label for="observaciones" class="mb-1 block text-sm font-medium text-cocina-gris">
                    Observaciones para cocina
                </label>
                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="2"
                    maxlength="1000"
                    class="w-full rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo px-3 py-2 text-sm text-white placeholder-cocina-gris/70 focus:border-cocina-amarillo focus:outline-none focus:ring-1 focus:ring-cocina-amarillo"
                >{{ old('observaciones', $order->observaciones) }}</textarea>
            </div>
        </div>

        <section class="mb-6">
            <h2 class="mb-2 border-l-2 border-cocina-amarillo pl-3 text-lg font-semibold text-white">
                Platos del pedido
            </h2>
            <p class="mb-3 text-sm text-cocina-gris">
                Poné 0 en los que la mesa ya no quiere.
            </p>

            @if ($lineas->isEmpty())
                <p class="rounded-xl border-2 border-cocina-amarillo/25 bg-cocina-panel p-8 text-center text-cocina-gris">
                    El pedido no tiene platos.
                </p>
            @else
                @include('waiter.partials.dish-picker', ['lineas' => $lineas])
            @endif
        </section>

        @forelse ($categorias as $categoria)
            <section class="mb-6">
                <h2 class="mb-2 border-l-2 border-cocina-amarillo pl-3 text-lg font-semibold text-white">
                    {{ $categoria['nombre'] }}
                </h2>

                @include('waiter.partials.dish-picker', ['lineas' => $categoria['lineas']])
            </section>
        @empty
            <p class="rounded-xl border-2 border-cocina-amarillo/25 bg-cocina-panel p-8 text-center text-cocina-gris">
                No hay platos disponibles para agregar.
            </p>
        @endforelse

        <div class="flex flex-wrap justify-end gap-3">
            <a
                href="{{ route('waiter.orders.show', $order) }}"
                class="rounded-lg border-2 border-cocina-amarillo/40 px-4 py-2 text-sm font-medium text-cocina-gris transition hover:bg-cocina-fondo"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="rounded-lg bg-cocina-amarillo px-4 py-2 text-sm font-semibold text-black transition hover:brightness-110"
            >
                Reenviar a cocina
            </button>
        </div>
    </form>
@endsection
