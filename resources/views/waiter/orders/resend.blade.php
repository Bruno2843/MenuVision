@extends('layouts.staff')

@section('title', 'Mozo · Reenviar pedido #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('waiter.orders.show', $order) }}" class="text-sm text-gray-400 hover:text-gold-400">
            ← Volver al pedido
        </a>

        <h1 class="mt-2 text-2xl font-bold text-white">
            Reenviar pedido #{{ $order->id }} · Mesa {{ $order->table->numero }}
        </h1>
        <p class="text-sm text-gray-400">
            Cambiá lo que va a comer la mesa o, si sigue igual, avisale a cocina que lo prepare de igual manera.
        </p>
    </div>

    @if ($order->motivo_devolucion)
        <div class="mb-6 rounded-xl border border-orange-900 bg-orange-950/40 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-400">
                La cocina no pudo prepararlo
            </p>
            <p class="mt-1 text-sm text-orange-100">{{ $order->motivo_devolucion }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
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
                <span class="mb-1 block text-sm font-medium text-gray-300">El pedido cambió?</span>

                <label
                    for="preparar_igual"
                    class="flex items-start gap-3 rounded-xl border border-gray-800 bg-gray-900 p-4"
                >
                    <input
                        type="checkbox"
                        id="preparar_igual"
                        name="preparar_igual"
                        value="1"
                        @checked(old('preparar_igual'))
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-700 bg-gray-800 text-gold-500 focus:ring-gold-500"
                    >
                    <span>
                        <span class="block text-sm font-semibold text-gray-100">No, es el mismo pedido</span>
                        <span class="block text-xs text-gray-400">
                            Se le avisa a cocina que lo prepare de igual manera.
                        </span>
                    </span>
                </label>
            </div>

            <div>
                <label for="observaciones" class="mb-1 block text-sm font-medium text-gray-300">
                    Observaciones para cocina
                </label>
                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="2"
                    maxlength="1000"
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                >{{ old('observaciones', $order->observaciones) }}</textarea>
            </div>
        </div>

        <section class="mb-6">
            <h2 class="mb-2 border-l-4 border-gold-500 pl-3 text-lg font-semibold text-gray-50">
                Platos del pedido
            </h2>
            <p class="mb-3 text-sm text-gray-500">
                Poné 0 en los que la mesa ya no quiere.
            </p>

            @if ($lineas->isEmpty())
                <p class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-6 text-center text-sm text-gray-500">
                    Este pedido no tiene platos.
                </p>
            @else
                @include('waiter.partials.dish-picker', ['lineas' => $lineas])
            @endif
        </section>

        @forelse ($categorias as $categoria)
            <section class="mb-6">
                <h2 class="mb-2 border-l-4 border-gray-700 pl-3 text-lg font-semibold text-gray-50">
                    {{ $categoria['nombre'] }}
                </h2>

                @include('waiter.partials.dish-picker', ['lineas' => $categoria['lineas']])
            </section>
        @empty
            <p class="mb-6 rounded-xl border border-dashed border-gray-700 bg-gray-900 p-6 text-center text-sm text-gray-500">
                No hay otros platos disponibles para agregar.
            </p>
        @endforelse

        <div class="flex flex-wrap justify-end gap-3">
            <a
                href="{{ route('waiter.orders.show', $order) }}"
                class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
            >
                Reenviar a cocina
            </button>
        </div>
    </form>
@endsection
