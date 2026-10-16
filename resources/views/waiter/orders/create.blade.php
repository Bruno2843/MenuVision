@extends('layouts.staff')

@section('title', 'Mozo · Tomar pedido')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Tomar pedido</h1>
        <p class="text-sm text-gray-400">
            @if ($codigoQr)
                Mesa {{ $table->numero }} · Mozo: {{ auth()->user()->name }}. Elegí las cantidades y las
                observaciones de cada plato.
            @else
                Elegí la mesa y las cantidades de cada plato.
            @endif
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('waiter.orders.store') }}">
        @csrf

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            @if ($codigoQr)
                {{--
                    Reached through the scanner: the table was resolved from the
                    stored QR code on the server, so it is shown as a fact and not
                    offered as a choice. The code travels with the form so the
                    table is resolved again when the order is saved.
                --}}
                <input type="hidden" name="codigo_qr" value="{{ $codigoQr }}">

                <div>
                    <span class="mb-1 block text-sm font-medium text-gray-300">Mesa</span>
                    <p
                        data-waiter-scan-table
                        class="rounded-lg border border-gold-600/50 bg-gray-800 px-3 py-2 text-sm font-semibold text-gold-400"
                    >
                        Mesa {{ $table->numero }} — {{ $table->estado->label() }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        Detectada con el QR. Mozo: {{ auth()->user()->name }}.
                    </p>
                </div>
            @else
                <div>
                    <label for="table_id" class="mb-1 block text-sm font-medium text-gray-300">Mesa</label>
                    <select
                        id="table_id"
                        name="table_id"
                        required
                        class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                    >
                        <option value="">Elegí una mesa</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected(old('table_id') == $table->id)>
                                Mesa {{ $table->numero }} — {{ $table->estado->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="observaciones" class="mb-1 block text-sm font-medium text-gray-300">
                    Observaciones (opcional)
                </label>
                <input
                    id="observaciones"
                    type="text"
                    name="observaciones"
                    value="{{ old('observaciones') }}"
                    placeholder="Sin alérgenos, preferencias de la mesa, etc."
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                >
            </div>
        </div>

        @forelse ($categories as $category)
            <section class="mb-6">
                <h2 class="mb-2 border-l-4 border-gold-500 pl-3 text-lg font-semibold text-gray-50">
                    {{ $category->nombre }}
                </h2>

                <div class="overflow-x-auto rounded-xl border border-gray-800">
                    <table class="w-full min-w-[36rem] text-left text-sm">
                        <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                                <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                                <th scope="col" class="px-4 py-2 text-right font-medium">Cantidad</th>
                                <th scope="col" class="px-4 py-2 font-medium">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach ($category->dishes as $dish)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-100">{{ $dish->nombre }}</span>
                                        @if ($dish->descripcion)
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $dish->descripcion }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gold-400">
                                        ${{ number_format($dish->precio, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input
                                            type="number"
                                            name="items[{{ $dish->id }}][cantidad]"
                                            min="0"
                                            max="99"
                                            step="1"
                                            value="{{ old("items.{$dish->id}.cantidad", 0) }}"
                                            aria-label="Cantidad de {{ $dish->nombre }}"
                                            class="w-20 rounded-lg border border-gray-700 bg-gray-800 px-2 py-1 text-right text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="sr-only" for="obs-{{ $dish->id }}">
                                            Observaciones de {{ $dish->nombre }}
                                        </label>
                                        <input
                                            id="obs-{{ $dish->id }}"
                                            type="text"
                                            name="items[{{ $dish->id }}][observaciones]"
                                            value="{{ old("items.{$dish->id}.observaciones") }}"
                                            placeholder="Sin sal, sin cebolla…"
                                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-2 py-1 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-8 text-center text-gray-500">
                No hay platos disponibles para pedir.
            </p>
        @endforelse

        <div class="flex flex-wrap justify-end gap-3">
            <a
                href="{{ route('waiter.dashboard') }}"
                class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
            >
                Guardar pedido
            </button>
        </div>
    </form>
@endsection
