@use('App\Enums\OrderStatus')

@props(['order'])

<article {{ $attributes->class(['rounded-xl border border-gray-800 bg-gray-900']) }}>
    <header class="flex items-start justify-between gap-3 border-b border-gray-800 px-4 py-3">
        <div>
            <a
                href="{{ route('kitchen.orders.show', $order) }}"
                class="text-lg font-bold text-white hover:text-gold-400"
            >
                Mesa {{ $order->table->numero }}
            </a>

            <p class="text-xs text-gray-500">
                #{{ $order->id }} · {{ $order->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->estado->badgeClasses() }}">
            {{ $order->estado->label() }}
        </span>
    </header>

    <ul class="divide-y divide-gray-800">
        @foreach ($order->items as $item)
            <li class="px-4 py-2">
                <p class="text-sm text-gray-100">
                    <span class="mr-2 font-bold text-gold-400">{{ $item->cantidad }}&times;</span>
                    {{ $item->dish->nombre }}
                </p>

                @if ($item->observaciones)
                    <p class="mt-0.5 pl-6 text-xs text-gray-400">{{ $item->observaciones }}</p>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($order->observaciones)
        <p class="border-t border-gray-800 px-4 py-2 text-xs text-gray-400">
            <span class="font-semibold uppercase tracking-wide text-gray-500">Observaciones:</span>
            {{ $order->observaciones }}
        </p>
    @endif

    @if ($order->motivo_devolucion)
        <p class="border-t border-gray-800 bg-orange-950/30 px-4 py-2 text-xs text-orange-300">
            <span class="font-semibold uppercase tracking-wide text-orange-400">Falta:</span>
            {{ $order->motivo_devolucion }}
        </p>
    @endif

    @if ($order->estado->kitchenAction() || $order->estado->canBeReturned())
        <footer class="space-y-2 border-t border-gray-800 p-3">
            @if ($order->estado->kitchenAction())
                <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="estado" value="{{ $order->estado->nextKitchenStatus()->value }}">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                    >
                        {{ $order->estado->kitchenAction() }}
                    </button>
                </form>
            @endif

            @if ($order->estado->canBeReturned())
                {{-- A disclosure keeps the handback out of the way until the
                     kitchen actually needs it, with no JavaScript involved. --}}
                <details class="text-center" @if ($errors->has('motivo_devolucion')) open @endif>
                    <summary class="cursor-pointer text-xs font-medium text-orange-300 hover:text-orange-200">
                        ¿Falta algún insumo?
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('kitchen.orders.status', $order) }}"
                        class="mt-2 text-left"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ OrderStatus::SinInsumos->value }}">

                        <label for="motivo-{{ $order->id }}" class="sr-only">Motivo de la devolución</label>
                        <textarea
                            id="motivo-{{ $order->id }}"
                            name="motivo_devolucion"
                            rows="2"
                            maxlength="255"
                            required
                            placeholder="Falta la milanesa, por ejemplo"
                            class="w-full rounded-lg border border-gray-700 bg-gray-950 px-2 py-1.5 text-xs text-gray-100 placeholder:text-gray-600 focus:border-orange-600 focus:outline-none"
                        ></textarea>

                        @error('motivo_devolucion')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="mt-2 w-full rounded-lg border border-orange-800 px-3 py-1.5 text-xs font-semibold text-orange-300 transition hover:bg-orange-950"
                        >
                            Devolver al mozo
                        </button>
                    </form>
                </details>
            @endif
        </footer>
    @endif
</article>
