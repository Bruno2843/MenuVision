@extends('layouts.staff')

@section('title', 'Mozo → Cargar pedido')

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-lg border-2 border-red-600/50 bg-cocina-panel px-4 py-3 text-sm text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-lg border-2 border-cocina-verde/50 bg-cocina-panel px-4 py-3 text-sm text-cocina-verde">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border-2 border-red-600/50 bg-cocina-panel px-4 py-3 text-sm text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('waiter.orders.store') }}">
            @csrf

            <!-- Información de la mesa -->
            <div class="mb-6 rounded-lg border-2 border-cocina-amarillo bg-cocina-panel p-4">
                <div class="flex flex-wrap items-center gap-4">
                    @include('partials.icon', ['icon' => 'mesas', 'iconClass' => 'h-5 w-5 text-cocina-amarillo'])
                    @if ($codigoQr && $table)
                        <input type="hidden" name="codigo_qr" value="{{ $codigoQr }}">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-base font-semibold text-white">Mesa {{ $table->numero }}</span>
                            <span class="text-sm text-cocina-gris">Pedido #{{ '--' }}</span>
                            <span class="hidden h-6 w-px bg-cocina-amarillo/30 sm:inline"></span>
                            @include('partials.icon', ['icon' => 'pedidos', 'iconClass' => 'h-4 w-4 text-cocina-amarillo'])
                            <span class="text-sm text-cocina-gris">{{ now()->format('H:i') }} - {{ now()->format('d/m/Y') }}</span>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center gap-3">
                            <select
                                name="table_id"
                                class="rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo px-3 py-2 text-sm text-white focus:border-cocina-amarillo focus:outline-none focus:ring-1 focus:ring-cocina-amarillo"
                            >
                                <option value="">Elegir mesa…</option>
                                @foreach ($tables as $table)
                                    <option value="{{ $table->id }}" @selected(old('table_id') == $table->id)>
                                        Mesa {{ $table->numero }} — {{ $table->estado->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="hidden h-6 w-px bg-cocina-amarillo/30 sm:inline"></span>
                            <span class="text-sm text-cocina-gris">{{ now()->format('H:i') }} - {{ now()->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Título de la sección -->
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">Platos del pedido</h2>
                    <div class="mt-2 h-0.5 w-16 bg-cocina-amarillo"></div>
                </div>
                <button
                    type="button"
                    data-order-add
                    class="inline-flex items-center gap-2 rounded-lg border-2 border-cocina-amarillo/60 px-3 py-2 text-sm font-medium text-cocina-amarillo transition hover:bg-cocina-amarillo/10"
                >
                    @include('partials.icon', ['icon' => 'mas', 'iconClass' => 'h-4 w-4'])
                    Agregar plato
                </button>
            </div>

            <!-- Listado de platos -->
            <div id="order-items" data-order-items class="space-y-3">
                <div id="empty-order" data-order-empty class="rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-8 text-center text-cocina-gris">
                    Aún no hay platos agregados al pedido.
                </div>
            </div>

            <!-- Catálogo que abre el botón "Agregar plato". -->
            <div
                data-order-picker
                hidden
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 sm:items-center sm:p-4"
            >
                <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-t-2xl border-2 border-cocina-amarillo bg-cocina-panel p-4 sm:rounded-2xl">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-white">Agregar plato</h2>
                        <button type="button" data-order-picker-close class="rounded-lg p-1 text-cocina-gris transition hover:text-white">
                            @include('partials.icon', ['icon' => 'cerrar', 'iconClass' => 'h-5 w-5'])
                        </button>
                    </div>

                    <div class="space-y-4">
                        @forelse ($categories as $category)
                            <div>
                                <h3 class="mb-1 border-l-2 border-cocina-amarillo pl-2 text-sm font-semibold text-white">
                                    {{ $category->nombre }}
                                </h3>
                                <div class="space-y-1.5">
                                    @foreach ($category->dishes as $dish)
                                        <button
                                            type="button"
                                            data-order-dish
                                            data-dish-id="{{ $dish->id }}"
                                            data-dish-name="{{ $dish->nombre }}"
                                            data-dish-price="{{ number_format((float) $dish->precio, 2) }}"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg border-2 border-cocina-amarillo/25 bg-cocina-fondo px-3 py-2 text-left transition hover:border-cocina-amarillo/60"
                                        >
                                            <span class="text-sm text-white">{{ $dish->nombre }}</span>
                                            <span class="shrink-0 text-sm font-semibold text-cocina-amarillo">
                                                ${{ number_format((float) $dish->precio, 2) }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl border-2 border-cocina-amarillo/25 bg-cocina-fondo p-6 text-center text-sm text-cocina-gris">
                                No hay platos disponibles para pedir.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Fila que el navegador clona por cada plato agregado. -->
            <template data-order-row-template>
                <div data-order-row class="rounded-xl border-2 border-cocina-amarillo bg-cocina-panel p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-white" data-order-row-name></p>
                            <p class="mt-0.5 text-xs text-cocina-amarillo" data-order-row-price></p>
                        </div>
                        <button
                            type="button"
                            data-order-row-remove
                            class="rounded-lg p-1 text-cocina-gris transition hover:text-red-400"
                        >
                            @include('partials.icon', ['icon' => 'basura', 'iconClass' => 'h-4 w-4'])
                        </button>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-1 rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo p-1">
                            <button
                                type="button"
                                data-order-row-decrement
                                class="flex h-7 w-7 items-center justify-center rounded-md text-base font-bold text-cocina-gris transition hover:bg-cocina-amarillo/10 hover:text-white"
                            >
                                −
                            </button>
                            <input
                                type="number"
                                data-order-row-qty
                                min="1"
                                max="99"
                                value="1"
                                aria-label="Cantidad"
                                class="w-10 border-0 bg-transparent text-center text-sm font-semibold text-white focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            >
                            <button
                                type="button"
                                data-order-row-increment
                                class="flex h-7 w-7 items-center justify-center rounded-md text-base font-bold text-cocina-gris transition hover:bg-cocina-amarillo/10 hover:text-white"
                            >
                                +
                            </button>
                        </div>

                        <input
                            type="text"
                            data-order-row-obs
                            maxlength="500"
                            placeholder="Observaciones (sin sal, sin cebolla…)"
                            class="min-w-0 flex-1 rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo px-3 py-1.5 text-sm text-white placeholder-cocina-gris/70 focus:border-cocina-amarillo focus:outline-none focus:ring-1 focus:ring-cocina-amarillo"
                        >
                    </div>
                </div>
            </template>

            <script type="application/json" data-order-old-items>@json(old('items', []))</script>

            <!-- Opciones del pedido -->
            <div class="mt-6">
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-white">Opciones del pedido</h2>
                    <div class="mt-2 h-0.5 w-16 bg-cocina-amarillo"></div>
                </div>
                <div class="rounded-lg border-2 border-cocina-amarillo bg-cocina-panel divide-y-2 divide-cocina-amarillo/15">
                    <button
                        type="button"
                        onclick="document.getElementById('obs-global').focus()"
                        class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-cocina-fondo"
                    >
                        <div class="flex items-center gap-3">
                            @include('partials.icon', ['icon' => 'editar', 'iconClass' => 'h-5 w-5 text-cocina-amarillo'])
                            <span class="text-sm text-white">Agregar observaciones</span>
                        </div>
                        <span class="text-cocina-amarillo">›</span>
                    </button>
                    <button
                        type="button"
                        onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                        class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-cocina-fondo"
                    >
                        <div class="flex items-center gap-3">
                            @include('partials.icon', ['icon' => 'editar', 'iconClass' => 'h-5 w-5 text-cocina-amarillo'])
                            <span class="text-sm text-white">Modificar platos</span>
                        </div>
                        <span class="text-cocina-amarillo">›</span>
                    </button>
                    <button
                        type="button"
                        onclick="if(confirm('¿Está seguro que desea eliminar este pedido?')) { window.location.href = '{{ route('waiter.dashboard') }}'; }"
                        class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-cocina-fondo"
                    >
                        <div class="flex items-center gap-3">
                            @include('partials.icon', ['icon' => 'basura', 'iconClass' => 'h-5 w-5 text-cocina-amarillo'])
                            <span class="text-sm text-white">Eliminar pedido</span>
                        </div>
                        <span class="text-cocina-amarillo">›</span>
                    </button>
                </div>
                <div class="mt-4">
                    <label for="obs-global" class="mb-1 block text-sm font-medium text-cocina-gris">Observaciones del pedido</label>
                    <textarea
                        id="obs-global"
                        name="observaciones"
                        rows="2"
                        maxlength="1000"
                        placeholder="Observaciones para cocina o para el pedido..."
                        class="w-full rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo px-3 py-2 text-sm text-white placeholder-cocina-gris/70 focus:border-cocina-amarillo focus:outline-none focus:ring-1 focus:ring-cocina-amarillo"
                    >{{ old('observaciones') }}</textarea>
                </div>
            </div>

            <!-- Botón principal -->
            <div class="mt-6 sticky bottom-4">
                <button
                    type="submit"
                    class="flex w-full flex-col items-center justify-center gap-1 rounded-lg bg-cocina-amarillo px-4 py-4 text-black transition hover:brightness-110 active:scale-[0.995]"
                >
                    <div class="flex items-center gap-2">
                        @include('partials.icon', ['icon' => 'salir', 'iconClass' => 'h-5 w-5 -rotate-90'])
                        <span class="text-base font-bold">Enviar pedido</span>
                    </div>
                    <span class="text-xs font-medium">Notificar al mozo / Cocina</span>
                </button>
            </div>
        </form>
    </div>
@endsection
